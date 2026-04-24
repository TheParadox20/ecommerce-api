<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncLegacyRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:legacy-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy user.role strings to Spatie model_has_roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Initializing identity synchronization...");

        $roleMap = [
            'super_admin' => 'super_admin',
            'superadmin'  => 'super_admin',
            'admin'       => 'admin',
            'distributor' => 'distributor',
            'influencer'  => 'influencer',
            'buyer'       => 'buyer',
            'user'        => 'buyer',
        ];

        // Ensure roles exist in Spatie
        foreach (array_unique(array_values($roleMap)) as $roleName) {
            Role::findOrCreate($roleName);
        }

        $users = User::all();
        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        foreach ($users as $user) {
            $legacyRole = $user->role;
            $spatieRole = $roleMap[$legacyRole] ?? 'buyer';

            if ($spatieRole) {
                $user->syncRoles([$spatieRole]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Identity synchronization complete. Spatie permissions are now active across all accounts.");
        
        return 0;
    }
}
