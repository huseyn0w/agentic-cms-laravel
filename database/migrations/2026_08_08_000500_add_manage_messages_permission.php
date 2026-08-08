<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the manage_messages permission (contact-form inbox) and backfill
 * existing roles: a full-access role (Administrator) keeps access, others get 0.
 * Idempotent. Mirrors the manage_newsletter / manage_updates backfills.
 */
class AddManageMessagesPermission extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_messages']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_messages', $perms)) {
                continue;
            }

            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_messages'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_messages']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_messages')->delete();
        }
    }
}
