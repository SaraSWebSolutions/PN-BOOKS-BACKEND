<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────
        // ✅ CREATE PERMISSIONS
        // ─────────────────────────────────────

        $permissions = [
            // Users
            'view users', 'create users', 'edit users', 'delete users',

            // Roles
            'view roles', 'create roles', 'edit roles', 'delete roles',

            // Contacts
            'view contacts', 'create contacts', 'edit contacts', 'delete contacts',

            // Suppliers
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',

            // Products
            'view products', 'create products', 'edit products', 'delete products',

            // Orders
            'view orders', 'create orders', 'edit orders', 'delete orders',

            // Reports
            'view reports',

            // Dashboard
            'view dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─────────────────────────────────────
        // ✅ CREATE ROLES & ASSIGN PERMISSIONS
        // ─────────────────────────────────────

        // 1. Admin - all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // 2. Owner - all permissions except delete users/roles
        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->syncPermissions([
            'view dashboard',
            'view users', 'create users', 'edit users',
            'view roles',
            'view contacts', 'create contacts', 'edit contacts', 'delete contacts',
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',
            'view products', 'create products', 'edit products', 'delete products',
            'view orders', 'create orders', 'edit orders', 'delete orders',
            'view reports',
        ]);

        // 3. Manager - manage contacts, suppliers, products, orders
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view dashboard',
            'view contacts', 'create contacts', 'edit contacts',
            'view suppliers', 'create suppliers', 'edit suppliers',
            'view products', 'create products', 'edit products',
            'view orders', 'create orders', 'edit orders',
            'view reports',
        ]);

        // 4. Cashier - only orders and view
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions([
            'view dashboard',
            'view contacts',
            'view products',
            'view orders', 'create orders',
        ]);

        // ─────────────────────────────────────
        // ✅ CREATE USERS & ASSIGN ROLES
        // ─────────────────────────────────────

        // Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sarasjewellery.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );
        $adminUser->assignRole('admin');

        // Owner User
        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@sarasjewellery.com'],
            [
                'name'     => 'Owner',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );
        $ownerUser->assignRole('owner');

        // Manager User
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@sarasjewellery.com'],
            [
                'name'     => 'Manager',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );
        $managerUser->assignRole('manager');

        // Cashier User
        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@sarasjewellery.com'],
            [
                'name'     => 'Cashier',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );
        $cashierUser->assignRole('cashier');

        $this->command->info('✅ Roles, Permissions & Users seeded successfully!');
    }
}