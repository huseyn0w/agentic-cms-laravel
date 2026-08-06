<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the dedicated manage_media permission and backfill existing roles.
 *
 * The /media routes move from manage_general_settings to manage_media, so any
 * live install's full-access role (Administrator) MUST gain the flag or it
 * would lose media access. A role holding every existing permission is treated
 * as full-access and gets manage_media = 1; every other role defaults to 0.
 * Idempotent: skips roles that already carry the key.
 */
class AddManageMediaPermission extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_media']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_media', $perms)) {
                continue;
            }

            // Full-access role = every current flag enabled.
            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_media'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_media']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_media')->delete();
        }
    }
}
