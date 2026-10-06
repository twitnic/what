<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class PromoteAdmin extends Command
{
    protected $signature = 'stadtnews:admin {email} {--revoke}';

    protected $description = 'Plattformmoderator anhand seiner E-Mail setzen oder entfernen';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->error('Kein Konto mit dieser E-Mail vorhanden.');

            return self::FAILURE;
        }
        $user->is_platform_admin = ! $this->option('revoke');
        $user->save();
        $this->info('Moderatorrechte aktualisiert.');

        return self::SUCCESS;
    }
}
