<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    private Role $roleModel;
    private Permission $permissionModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    public function index(): void
    {
        $roles = $this->roleModel->getAllWithUserCount();
        $groupedPermissions = $this->permissionModel->getGroupedByModule();

        // Tableau des permissions par rôle
        $rolePermissions = [];
        foreach ($roles as $r) {
            $rolePermissions[$r['id']] = $this->roleModel->getPermissionIds((int)$r['id']);
        }

        $this->render('roles.index', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $role = $this->roleModel->findById($id);

        if (!$role) {
            Session::setFlash('danger', 'Rôle introuvable.');
            $this->redirect('/roles');
        }

        $groupedPermissions = $this->permissionModel->getGroupedByModule();
        $activePermissionIds = $this->roleModel->getPermissionIds($id);

        $this->render('roles.edit', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'activePermissionIds' => $activePermissionIds,
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $role = $this->roleModel->findById($id);

        if (!$role) {
            Session::setFlash('danger', 'Rôle introuvable.');
            $this->redirect('/roles');
        }

        // L'admin conserve toujours tout
        if ($role['code'] === 'admin') {
            Session::setFlash('warning', "Le rôle Administrateur Principal conserve obligatoirement l'intégralité des privilèges.");
            $this->redirect('/roles');
        }

        $selectedPermissions = $this->request->post('permissions') ?? [];
        $permissionIds = is_array($selectedPermissions) ? array_map('intval', $selectedPermissions) : [];

        $this->roleModel->syncPermissions($id, $permissionIds);

        Logger::log(Auth::id(), null, 'role.permissions_maj', 'roles', $id, null, ['permissions' => $permissionIds], $this->request);

        Session::setFlash('success', "Permissions du rôle « {$role['libelle']} » mises à jour.");
        $this->redirect('/roles');
    }
}
