<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CityNewsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name = 'Redakteur'): User
    {
        return User::query()->create(['name' => $name, 'email' => uniqid('user', true).'@example.test', 'password' => 'a-long-test-password']);
    }

    private function organization(User $user, string $role = 'owner'): Organization
    {
        $org = Organization::query()->create(['name' => 'Verein', 'slug' => uniqid('verein-'), 'type' => 'club']);
        $org->users()->attach($user->id, ['role' => $role]);

        return $org;
    }

    /** @param array<string, mixed> $attributes */
    private function newsPost(Organization $org, User $user, array $attributes = []): Post
    {
        $post = new Post;
        $post->forceFill(array_merge(['organization_id' => $org->id, 'user_id' => $user->id, 'text' => 'Eine Nachricht aus der Stadt.', 'type' => 'news', 'published_at' => now()->subMinute()], $attributes));
        $post->save();

        return $post;
    }

    public function test_public_feed_and_api_exclude_drafts_future_expired_and_hidden_posts(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $visible = $this->newsPost($org, $user);
        $this->newsPost($org, $user, ['published_at' => null, 'text' => 'Entwurf geheim']);
        $this->newsPost($org, $user, ['published_at' => now()->addHour(), 'text' => 'Geplant geheim']);
        $this->newsPost($org, $user, ['expires_at' => now()->subSecond(), 'text' => 'Abgelaufen geheim']);
        $hidden = $this->newsPost($org, $user, ['text' => 'Moderiert geheim']);
        $hidden->hidden_at = now();
        $hidden->save();
        $this->get('/')->assertOk()->assertSee($visible->text)->assertDontSee('geheim');
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
        $this->getJson('/api/v1/posts/'.$hidden->id)->assertNotFound();
        $this->get('/feed.xml')->assertOk()->assertDontSee('geheim');
    }

    public function test_time_window_boundaries_and_scheduling_need_no_worker(): void
    {
        $this->freezeTime();
        $user = $this->user();
        $org = $this->organization($user);
        $this->newsPost($org, $user, ['published_at' => now()]);
        $this->newsPost($org, $user, ['expires_at' => now()]);
        $this->newsPost($org, $user, ['published_at' => now()->addHour()]);
        $this->getJson('/api/v1/posts')->assertJsonCount(1, 'data');
        $this->travel(61)->minutes();
        $this->getJson('/api/v1/posts')->assertJsonCount(2, 'data');
    }

    public function test_foreign_organization_cannot_be_read_or_written_by_member(): void
    {
        $member = $this->user();
        $this->organization($member);
        $other = $this->user();
        $org = $this->organization($other);
        $post = $this->newsPost($org, $other);
        $this->actingAs($member)->get(route('posts.index', [$org]))->assertForbidden();
        $this->post(route('posts.store', [$org]), ['type' => 'news', 'text' => 'Fremder Beitrag'])->assertForbidden();
        $this->put(route('posts.update', [$org, $post]), ['type' => 'news', 'text' => 'Fremde Änderung'])->assertForbidden();
        $this->delete(route('posts.destroy', [$org, $post]))->assertForbidden();
    }

    public function test_cross_tenant_post_route_is_not_found_even_for_member_of_both(): void
    {
        $user = $this->user();
        $a = $this->organization($user);
        $b = $this->organization($user);
        $post = $this->newsPost($b, $user);
        $this->actingAs($user)->get(route('posts.edit', [$a, $post]))->assertNotFound();
        $this->put(route('posts.update', [$a, $post]), ['type' => 'news', 'text' => 'Änderung'])->assertNotFound();
        $this->delete(route('posts.destroy', [$a, $post]))->assertNotFound();
    }

    public function test_editor_can_edit_own_posts_but_cannot_manage_team_pin_or_edit_others(): void
    {
        $owner = $this->user();
        $org = $this->organization($owner);
        $editor = $this->user();
        $org->users()->attach($editor->id, ['role' => 'editor']);
        $own = $this->newsPost($org, $editor);
        $other = $this->newsPost($org, $owner);
        $this->actingAs($editor)->put(route('posts.update', [$org, $own]), ['type' => 'news', 'text' => 'Neue Nachricht'])->assertRedirect();
        $this->put(route('posts.update', [$org, $other]), ['type' => 'news', 'text' => 'Fremde Nachricht'])->assertForbidden();
        $this->post(route('posts.store', [$org]), ['type' => 'news', 'text' => 'Pin Versuch', 'pinned' => true])->assertForbidden();
        $this->post(route('members.store', [$org]), ['email' => $owner->email, 'role' => 'admin'])->assertForbidden();
        $this->put(route('organizations.update', [$org]), ['name' => 'Übernahme'])->assertForbidden();
    }

    public function test_editor_preserves_pin_set_by_admin_when_editing_own_post(): void
    {
        $editor = $this->user();
        $org = $this->organization($editor, 'editor');
        $post = $this->newsPost($org, $editor, ['pinned' => true]);
        $this->actingAs($editor)->put(route('posts.update', [$org, $post]), ['type' => 'news', 'text' => 'Korrektur'])->assertRedirect();
        self::assertTrue($post->fresh()?->pinned);
    }

    public function test_owner_cannot_remove_or_demote_self(): void
    {
        $owner = $this->user();
        $org = $this->organization($owner);
        $this->actingAs($owner)->delete(route('members.destroy', [$org, $owner]))->assertStatus(422);
        $this->post(route('members.store', [$org]), ['email' => $owner->email, 'role' => 'editor'])->assertStatus(422);
        self::assertSame('owner', $org->roleFor($owner)?->value);
    }

    public function test_owner_can_add_existing_user_and_admin_can_edit_all_posts(): void
    {
        $owner = $this->user();
        $org = $this->organization($owner);
        $admin = $this->user();
        $this->actingAs($owner)->post(route('members.store', [$org]), ['email' => $admin->email, 'role' => 'admin'])->assertRedirect();
        $post = $this->newsPost($org, $owner);
        $this->actingAs($admin)->put(route('posts.update', [$org, $post]), ['type' => 'news', 'text' => 'Admin Nachricht', 'pinned' => true])->assertRedirect();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'text' => 'Admin Nachricht', 'pinned' => true]);
    }

    public function test_token_ability_and_membership_are_both_required(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $other = $this->organization($this->user());
        $token = $user->createToken('client', ['posts:write'])->plainTextToken;
        $url = '/api/v1/organizations/'.$org->slug.'/posts';
        $this->postJson($url, ['type' => 'news', 'text' => 'API Beitrag'])->assertUnauthorized();
        $this->withToken($token)->postJson($url, ['type' => 'news', 'text' => 'API Beitrag', 'published_at' => now()->toIso8601String()])->assertCreated();
        $this->withToken($token)->postJson('/api/v1/organizations/'.$other->slug.'/posts', ['type' => 'news', 'text' => 'Fremde API'])->assertForbidden();
    }

    public function test_token_without_write_ability_is_rejected(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $url = '/api/v1/organizations/'.$org->slug.'/posts';
        $readToken = $user->createToken('read', [])->plainTextToken;
        $this->withToken($readToken)->postJson($url, ['type' => 'news', 'text' => 'Ohne Scope'])->assertForbidden();
    }

    public function test_removed_member_loses_api_write_access_immediately(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $token = $user->createToken('client', ['posts:write'])->plainTextToken;
        $org->users()->detach($user->id);
        $this->withToken($token)->postJson('/api/v1/organizations/'.$org->slug.'/posts', ['type' => 'news', 'text' => 'Abgelehnt'])->assertForbidden();
    }

    public function test_expired_and_revoked_tokens_are_rejected(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $expired = $user->createToken('old', ['posts:write'], now()->subMinute())->plainTextToken;
        $token = $user->createToken('revoked', ['posts:write']);
        $plainText = $token->plainTextToken;
        $token->accessToken->delete();
        $url = '/api/v1/organizations/'.$org->slug.'/posts';
        $this->withToken($expired)->postJson($url, ['type' => 'news', 'text' => 'Abgelehnt'])->assertUnauthorized();
        $this->withToken($plainText)->postJson($url, ['type' => 'news', 'text' => 'Abgelehnt'])->assertUnauthorized();
    }

    public function test_event_validation_and_http_link_validation(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $this->actingAs($user)->postJson(route('posts.store', [$org]), ['type' => 'event', 'text' => 'Ein Event', 'link' => 'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors(['title', 'starts_at', 'location', 'link']);
        $this->postJson(route('posts.store', [$org]), ['type' => 'news', 'text' => str_repeat('a', 1001)])->assertUnprocessable();
    }

    public function test_local_dates_are_converted_to_utc_and_api_preserves_offsets(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $this->actingAs($user)->post(route('posts.store', [$org]), ['type' => 'event', 'text' => 'Termin', 'title' => 'Musik', 'location' => 'Bahnhof', 'starts_at' => '2026-10-10T19:00', 'published_at' => '2026-10-01T12:00'])->assertRedirect();
        $post = Post::query()->firstOrFail();
        self::assertSame('2026-10-10 17:00:00', $post->starts_at?->utc()->toDateTimeString());
    }

    public function test_post_image_upload_and_delete(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $org = $this->organization($user);
        $png = hex2bin('89504e470d0a1a0a0000000d4948445200000001000000010804000000b51c0c020000000b4944415478da63fcff1f0003030200ef9ae1df0000000049454e44ae426082');
        self::assertIsString($png);
        $this->actingAs($user)->post(route('posts.store', [$org]), ['type' => 'news', 'text' => 'Mit Bild', 'image' => UploadedFile::fake()->createWithContent('foto.png', $png)])->assertRedirect();
        $post = Post::query()->firstOrFail();
        $image = $post->image;
        self::assertNotNull($image);
        Storage::disk('public')->assertExists($image);
        $this->delete(route('posts.destroy', [$org, $post]))->assertRedirect();
        Storage::disk('public')->assertMissing($image);
    }

    public function test_moderation_is_restricted_and_hidden_post_stays_hidden_after_edit(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $post = $this->newsPost($org, $user);
        $this->actingAs($user)->get('/moderation')->assertForbidden();
        $this->put(route('moderation.verify', [$org]))->assertForbidden();
        $admin = $this->user();
        $admin->is_platform_admin = true;
        $admin->save();
        $this->actingAs($admin)->put(route('moderation.hide', [$post]))->assertRedirect();
        $this->actingAs($user)->put(route('posts.update', [$org, $post]), ['type' => 'news', 'text' => 'Korrigierte Nachricht', 'hidden_at' => null])->assertRedirect();
        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
        $this->actingAs($admin)->put(route('moderation.hide', [$post]))->assertRedirect();
        $this->getJson('/api/v1/posts')->assertJsonCount(1, 'data');
    }

    public function test_public_reports_require_visible_post_and_can_be_resolved(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $post = $this->newsPost($org, $user);
        $this->post(route('reports.store', [$post]), ['reason' => 'Dieser Beitrag ist problematisch.'])->assertRedirect();
        $this->assertDatabaseCount('reports', 1);
        $draft = $this->newsPost($org, $user, ['published_at' => null]);
        $this->post(route('reports.store', [$draft]), ['reason' => 'Unsichtbare Nachricht melden'])->assertNotFound();
    }

    public function test_search_category_filter_and_pinned_order(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $cat = Category::query()->create(['name' => 'Sport', 'slug' => 'sport']);
        $pinned = $this->newsPost($org, $user, ['pinned' => true, 'text' => 'Heimspiel Fußball', 'published_at' => now()->subDays(2)]);
        $pinned->categories()->attach($cat->id);
        $this->newsPost($org, $user, ['text' => 'Bäckerei']);
        $this->getJson('/api/v1/posts')->assertJsonPath('data.0.id', $pinned->id);
        $this->getJson('/api/v1/posts?q=Heimspiel&category=sport')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/posts?category=unbekannt')->assertJsonCount(0, 'data');
    }

    public function test_registration_creates_user_and_organization_with_owner_membership(): void
    {
        $this->post('/registrieren', ['name' => 'Neue Person', 'email' => 'neu@example.test', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password', 'is_platform_admin' => true])->assertRedirect(route('dashboard'));
        $this->post(route('organizations.store'), ['name' => 'Neuer Verein', 'slug' => 'neuer-verein', 'type' => 'club', 'verified' => true])->assertRedirect();
        $user = User::query()->where('email', 'neu@example.test')->firstOrFail();
        $org = Organization::query()->firstOrFail();
        self::assertFalse($user->is_platform_admin);
        self::assertFalse($org->verified);
        self::assertSame('owner', $org->roleFor($user)?->value);
    }

    public function test_rss_is_valid_xml_and_escapes_user_text(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $this->newsPost($org, $user, ['text' => '<script>alert("x")</script> & Stadt']);
        $response = $this->get('/feed.xml')->assertOk();
        $content = $response->baseResponse->getContent();
        self::assertIsString($content);
        self::assertNotFalse(simplexml_load_string($content));
        $this->get('/')->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_editorial_forms_render_for_members(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $post = $this->newsPost($org, $user);
        $this->actingAs($user)->get('/redaktion')->assertOk();
        $this->get(route('organizations.edit', [$org]))->assertOk();
        $this->get(route('posts.create', [$org]))->assertOk();
        $this->get(route('posts.edit', [$org, $post]))->assertOk();
        $this->get('/veranstaltungen')->assertOk();
    }

    public function test_api_event_offsets_are_stored_as_utc(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $token = $user->createToken('event-client', ['posts:write'])->plainTextToken;
        $this->withToken($token)->postJson('/api/v1/organizations/'.$org->slug.'/posts', [
            'type' => 'event', 'text' => 'Ein Termin', 'title' => 'Musik', 'location' => 'Bahnhof',
            'published_at' => '2026-10-01T12:00:00+02:00', 'starts_at' => '2026-10-10T19:00:00+02:00',
        ])->assertCreated()->assertJsonPath('data.event.starts_at', '2026-10-10T17:00:00+00:00');
        self::assertSame('2026-10-10 17:00:00', Post::query()->firstOrFail()->starts_at?->utc()->toDateTimeString());
    }

    public function test_password_reset_changes_password_and_rejects_bad_token(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $this->post(route('password.update'), ['email' => $user->email, 'token' => $token, 'password' => 'another-long-password', 'password_confirmation' => 'another-long-password'])->assertRedirect(route('login'));
        self::assertTrue(Hash::check('another-long-password', $user->refresh()->password));
        $this->postJson(route('password.update'), ['email' => $user->email, 'token' => 'invalid', 'password' => 'a-third-long-password', 'password_confirmation' => 'a-third-long-password'])->assertUnprocessable();
    }

    public function test_moderator_can_verify_organization_and_resolve_report(): void
    {
        $user = $this->user();
        $org = $this->organization($user);
        $post = $this->newsPost($org, $user);
        $report = Report::query()->create(['post_id' => $post->id, 'reason' => 'Bitte diesen Beitrag prüfen.']);
        $user->is_platform_admin = true;
        $user->save();
        $this->actingAs($user)->put(route('moderation.verify', [$org]))->assertRedirect();
        self::assertTrue($org->fresh()?->verified);
        $this->put(route('moderation.resolve', [$report]))->assertRedirect();
        self::assertNotNull($report->fresh()?->resolved_at);
        $this->get(route('moderation'))->assertOk()->assertDontSee($report->reason);
    }
}
