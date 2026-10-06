<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo-Daten sind nur lokal erlaubt.');
        }
        $this->call(DatabaseSeeder::class);
        $user = User::query()->firstOrCreate(['email' => 'demo@stadtnews.test'], ['name' => 'Demo Redaktion', 'password' => Str::password(24)]);
        $user->is_platform_admin = true;
        $user->save();
        $password = Str::password(24);
        $user->password = $password;
        $user->save();
        $this->command->info('Demo-Login: demo@stadtnews.test / '.$password);
        $examples = [
            ['Rathaus Musterstadt', 'rathaus', 'city', 'city', 'Ein neuer Platz für Begegnungen', 'Unser Marktplatz wird grüner. Am Freitag stellen wir die Pläne vor – kommt vorbei und bringt eure Ideen mit.', 'stadt'],
            ['SV Musterstadt', 'sv-musterstadt', 'club', 'sport', 'Gemeinsam am Spielfeldrand', 'Heimspiel am Samstag! Anstoß um 15 Uhr. Für Kaffee, Kuchen und gute Stimmung ist gesorgt.', 'sport'],
            ['Kulturwerk', 'kulturwerk', 'club', 'event', 'Musik im alten Bahnhof', 'Ein Abend mit lokalen Bands, gutem Essen und offenen Türen. Wir freuen uns auf euch!', 'kultur'],
            ['Bäckerei Müller', 'baeckerei-mueller', 'business', 'offer', 'Der Herbst schmeckt nach Kürbis', 'Unsere Kürbisbrötchen sind wieder da. Frisch aus dem Ofen, jeden Morgen ab 7 Uhr.', 'handel'],
            ['Freiwillige Feuerwehr', 'feuerwehr', 'club', 'news', 'Ein Blick hinter die roten Tore', 'Wie funktioniert eigentlich ein Löschfahrzeug? Beim Tag der offenen Tür zeigen wir es euch. Familien sind herzlich willkommen.', 'vereine'],
        ];
        foreach ($examples as $index => [$name, $slug, $orgType, $type, $title, $text, $category]) {
            $organization = Organization::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'type' => $orgType, 'description' => 'Aktuelle Nachrichten und Termine aus Musterstadt.']);
            $organization->verified = true;
            $organization->save();
            $organization->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
            $post = Post::query()->firstOrNew(['organization_id' => $organization->id, 'title' => $title], ['user_id' => $user->id, 'type' => $type, 'text' => $text, 'published_at' => now()->subMinutes($index * 37 + 12), 'pinned' => $index === 0, 'starts_at' => $type === 'event' ? now()->addDays(3)->setTime(19, 0) : null, 'ends_at' => $type === 'event' ? now()->addDays(3)->setTime(23, 0) : null, 'location' => $type === 'event' ? 'Alter Bahnhof, Musterstadt' : null]);
            if (! $post->exists) {
                $post->organization()->associate($organization);
                $post->user()->associate($user);
                $post->save();
            }
            $post->categories()->sync([Category::query()->where('slug', $category)->firstOrFail()->id]);
        }
    }
}
