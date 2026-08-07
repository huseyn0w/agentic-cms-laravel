<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the manage_newsletter permission and backfill existing roles, so a
 * live install's full-access role (Administrator) keeps access once the admin
 * newsletter routes go behind manage_newsletter. A role holding every existing
 * flag is treated as full-access and gets manage_newsletter = 1; others get 0.
 * Idempotent: skips roles that already carry the key.
 */
class AddManageNewsletterPermission extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_newsletter']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_newsletter', $perms)) {
                continue;
            }

            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_newsletter'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_newsletter']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_newsletter')->delete();
        }
    }
}
