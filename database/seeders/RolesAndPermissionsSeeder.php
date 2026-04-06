<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'projects.view_any',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.archive',
            'projects.change_state',
            'projects.assign_responsibilities',
            'projects.view_finance',
            'projects.view_documentation',
            'readiness.update',
            'execution.update',
            'documentation.update',
            'documentation.upload',
            'finance.view',
            'finance.create',
            'finance.update',
            'finance.approve',
            'attachments.view',
            'attachments.upload',
            'users.view',
            'users.manage',
            'roles.manage',
            'reports.view',
            'reports.export',
            'alerts.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $projectManager = Role::findOrCreate('project_manager', 'web');
        $readinessOfficer = Role::findOrCreate('readiness_officer', 'web');
        $executionOfficer = Role::findOrCreate('execution_officer', 'web');
        $documentationOfficer = Role::findOrCreate('documentation_officer', 'web');
        $financeOfficer = Role::findOrCreate('finance_officer', 'web');
        $viewer = Role::findOrCreate('viewer', 'web');

        $superAdmin->syncPermissions(Permission::all());

        $projectManager->syncPermissions([
            'projects.view_any',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.change_state',
            'projects.assign_responsibilities',
            'projects.view_finance',
            'projects.view_documentation',
            'reports.view',
            'alerts.view',
        ]);

        $readinessOfficer->syncPermissions([
            'projects.view_any',
            'projects.view',
            'readiness.update',
            'projects.change_state',
            'alerts.view',
        ]);

        $executionOfficer->syncPermissions([
            'projects.view_any',
            'projects.view',
            'execution.update',
            'projects.change_state',
            'alerts.view',
        ]);

        $documentationOfficer->syncPermissions([
            'projects.view_any',
            'projects.view',
            'documentation.update',
            'documentation.upload',
            'attachments.view',
            'attachments.upload',
            'projects.change_state',
            'alerts.view',
        ]);

        $financeOfficer->syncPermissions([
            'projects.view_any',
            'projects.view',
            'finance.view',
            'finance.create',
            'finance.update',
            'finance.approve',
            'attachments.view',
            'attachments.upload',
            'alerts.view',
            'reports.view',
        ]);

        $viewer->syncPermissions([
            'projects.view_any',
            'projects.view',
            'finance.view',
            'attachments.view',
            'alerts.view',
            'reports.view',
        ]);
    }
}
