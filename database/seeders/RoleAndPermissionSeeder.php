<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $assignments = $this->assignments();

        foreach ($assignments as $role => $perms) {
            $role = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }

    private function permissions(): array
    {
        $modules = [
            'dashboard' => ['view'],
            'properties' => ['view', 'create', 'update', 'delete'],
            'rooms' => ['view', 'create', 'update', 'delete'],
            'amenities' => ['view', 'create', 'update', 'delete'],
            'guests' => ['view', 'create', 'update', 'delete'],
            'reservations' => ['view', 'create', 'update', 'cancel'],
            'calendar' => ['view', 'manage'],
            'pricing' => ['view', 'create', 'update', 'delete'],
            'channels' => ['view', 'sync', 'configure'],
            'payments' => ['view', 'create', 'refund'],
            'communications' => ['view', 'send', 'manage_templates'],
            'chatbot' => ['view', 'manage'],
            'reports' => ['view', 'export'],
            'users' => ['view', 'create', 'update', 'delete', 'manage_roles'],
            'settings' => ['view', 'update'],
            'audit_logs' => ['view'],
            'food-drink' => ['view', 'create', 'update', 'delete'],
            'places' => ['view', 'create', 'update', 'delete'],
            'addons' => ['view', 'create', 'update', 'delete'],
        ];

        $permissions = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    private function assignments(): array
    {
        $all = $this->permissions();

        return [
            'Super Admin' => $all,
            'Property Manager' => [
                'dashboard.view',
                'properties.view', 'properties.create', 'properties.update',
                'rooms.view', 'rooms.create', 'rooms.update',
                'amenities.view', 'amenities.create', 'amenities.update',
                'food-drink.view', 'food-drink.create', 'food-drink.update', 'food-drink.delete',
                'places.view', 'places.create', 'places.update', 'places.delete',
                'addons.view', 'addons.create', 'addons.update', 'addons.delete',
                'guests.view', 'guests.create', 'guests.update',
                'reservations.view', 'reservations.create', 'reservations.update', 'reservations.cancel',
                'calendar.view', 'calendar.manage',
                'channels.view', 'channels.sync',
                'communications.view', 'communications.send',
            ],
            'Revenue Manager' => [
                'dashboard.view',
                'pricing.view', 'pricing.create', 'pricing.update', 'pricing.delete',
                'reservations.view',
                'calendar.view',
                'reports.view', 'reports.export',
                'channels.view',
                'payments.view',
            ],
            'Guest Manager' => [
                'dashboard.view',
                'guests.view', 'guests.create', 'guests.update',
                'reservations.view', 'reservations.create', 'reservations.update',
                'calendar.view',
                'communications.view', 'communications.send',
                'chatbot.view', 'chatbot.manage',
            ],
            'Finance Manager' => [
                'dashboard.view',
                'payments.view', 'payments.create', 'payments.refund',
                'reservations.view',
                'reports.view', 'reports.export',
            ],
            'Support Staff' => [
                'dashboard.view',
                'guests.view',
                'reservations.view',
                'calendar.view',
                'communications.view',
                'chatbot.view',
            ],
        ];
    }
}
