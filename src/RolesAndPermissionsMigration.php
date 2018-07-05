<?php

namespace Nbz4live\LaravelBackpackHelpers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

abstract class RolesAndPermissionsMigration extends Migration
{
    protected $createRoleNames = [];
    protected $createPermissionNames = [];
    protected $autoassignRolePermissions = [];
    protected $renameRoleNamesMap = [];
    protected $renamePermissionNamesMap = [];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        PermissionHelper::createRoles($this->createRoleNames);

        PermissionHelper::createPermissions($this->createPermissionNames);

        if (!empty($this->autoassignRolePermissions) && !\is_array($this->autoassignRolePermissions)) {
            dump('fdsdfsd');
            PermissionHelper::assignPermissionsToRoles($this->createPermissionNames, $this->createRoleNames);
        }

        if (!empty($this->autoassignRolePermissions) && \is_array($this->autoassignRolePermissions)) {
            foreach ($this->autoassignRolePermissions as $role => $permissions) {
                PermissionHelper::assignPermissionsToRoles($permissions, $role);
            }
        }

        PermissionHelper::renameRoles($this->renameRoleNamesMap);
        PermissionHelper::renamePermissions($this->renamePermissionNamesMap);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        PermissionHelper::renameRoles(\array_flip($this->renameRoleNamesMap));
        PermissionHelper::renamePermissions(\array_flip($this->renamePermissionNamesMap));
        PermissionHelper::deletePermissions($this->createPermissionNames);
        PermissionHelper::deleteRoles($this->createRoleNames);
    }
}
