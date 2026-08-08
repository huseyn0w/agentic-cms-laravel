<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the manage_updates permission and backfill existing roles, so a
 * live install's full-access role (Administrator) can run core updates once the
 * admin update routes go behind manage_updates. A role holding every existing
 * flag is treated as full-access and gets manage_updates = 1; others get 0.
 * Idempotent: skips roles that already carry the key. Mirrors the
 * manage_newsletter backfill.
 */
class AddManageUpdatesPermission extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_updates']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_updates', $perms)) {
                continue;
            }

            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_updates'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_updates']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_updates')->delete();
        }
    }
}
