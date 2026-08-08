<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the manage_content permission (gates the generic CRUD for plugin
 * content types) and backfill existing roles: a full-access role keeps access,
 * others get 0. Idempotent. Mirrors the manage_updates / manage_messages
 * backfills.
 */
class AddManageContentPermission extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_content']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_content', $perms)) {
                continue;
            }

            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_content'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_content']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_content')->delete();
        }
    }
}
