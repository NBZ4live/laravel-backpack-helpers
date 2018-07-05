<?php

namespace Nbz4live\LaravelBackpackHelpers;

use Illuminate\Database\Eloquent\Collection;

class PermissionHelper
{
    public static function createRoles($roleNames): ?array
    {
        return self::createEntities(self::roleModel(), $roleNames);
    }

    public static function deleteRoles($roleNames): void
    {
        self::deleteEntities(self::roleModel(), $roleNames);
    }

    public static function createPermissions($permissionNames): ?array
    {
        return self::createEntities(self::permissionModel(), $permissionNames);
    }

    public static function deletePermissions($permissionNames): void
    {
        self::deleteEntities(self::permissionModel(), $permissionNames);
    }

    public static function assignPermissionsToRoles($permissionNames, $roleNames)
    {
        /** @var Collection $roles */
        $roles = self::roleModel()::whereIn('name', self::getValuesArray($roleNames))->get();

        foreach ($roles as $role) {
            $role->givePermissionTo(self::getValuesArray($permissionNames));
        }
    }

    public static function renameRoles(array $namesMap): ?Collection
    {
        return self::renameEntities(self::roleModel(), $namesMap);
    }

    public static function renamePermissions(array $namesMap): ?Collection
    {
        return self::renameEntities(self::permissionModel(), $namesMap);
    }

    public static function roleModel()
    {
        return \config('permission.models.role');
    }

    public static function permissionModel()
    {
        return \config('permission.models.permission');
    }

    protected static function createEntities($model, $names): ?array
    {
        if (empty($names)) {
            return null;
        }

        $entities = [];

        foreach (self::getValuesArray($names) as $name) {
            $entities[] = $model::firstOrCreate(['name' => $name]);
        }

        return $entities;
    }

    protected static function deleteEntities($model, $names)
    {
        if (empty($names)) {
            return null;
        }

        $model::whereIn('name', self::getValuesArray($names))->delete();
    }

    protected static function renameEntities($model, $namesMap): ?Collection
    {
        if (empty($namesMap)) {
            return null;
        }

        /** @var Collection $entities */
        $entities = $model::whereIn('name', \array_keys($namesMap))->get();

        return $entities->map(function ($item, $key) use ($namesMap) {
            $item->name = $namesMap[$item->name] ?? $item->name;
            $item->save();

            return $item;
        });
    }

    protected static function getValuesArray($values): array
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        return $values;
    }
}
