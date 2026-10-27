<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;

class UserController extends Controller
{
    private User $userModel;
    private Role $roleModel;
    private Etablissement $etablissementModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->etablissementModel = new Etablissement();
    }

    public function index(): void
    {
        $search = trim((string)$this->request->query('q', ''));
        $selectedEtab = $this->request->query('etab');
        $filterEtabId = ($selectedEtab !== null && $selectedEtab !== '') ? (int)$selectedEtab : null;

        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = null;
        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds();
            if ($filterEtabId !== null && !in_array($filterEtabId, $allowedEtabIds, true)) {
                $filterEtabId = null;
            }
        }

        $users = $this->userModel->getAllWithRoles($filterEtabId, $search, $allowedEtabIds);
        $etablissements = $accessibleEtabs;

        $this->render('users.index', [
            'users' => $users,
            'search' => $search,
            'selectedEtabId' => $filterEtabId,
            'etablissements' => $etablissements,
        ]);
    }

    public function create(): void
    {
        $roles = $this->roleModel->findAll([], 'id ASC');
        $etablissements = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        $activeEtabId = Auth::getActiveEtablissementId();

        if (!Auth::hasRole('admin')) {
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
            }
        }

        $this->render('users.create', [
            'roles' => $roles,
            'etablissements' => $etablissements,
            'activeEtabId' => $activeEtabId,
        ]);
    }

    public function store(): void
    {
        $data = $this->request->post();

        $rules = [
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'email' => 'required|email|max:190|unique:users,email',
            'role_id' => 'required',
            'mot_de_passe' => 'required|min:10',
        ];

        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            $roles = $this->roleModel->findAll([], 'id ASC');
            $etablissements = Auth::getAccessibleEtablissements();
            $activeEtabId = Auth::getActiveEtablissementId();
            $this->render('users.create', [
                'roles' => $roles,
                'etablissements' => $etablissements,
                'activeEtabId' => $activeEtabId,
                'errors' => $errors,
                'old' => $data
            ]);
            return;
        }

        $plainPassword = (string)$data['mot_de_passe'];
        $passwordHash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        $selectedEtabs = $this->request->post('etablissements') ?? [];

        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds();
            if (empty($selectedEtabs) && count($allowedEtabIds) === 1) {
                $selectedEtabs = $allowedEtabIds;
            } else {
                $selectedEtabs = array_values(array_intersect(array_map('intval', (array)$selectedEtabs), $allowedEtabIds));
            }
        }

        $userId = $this->userModel->create([
            'role_id' => (int)$data['role_id'],
            'nom' => strtoupper(trim((string)$data['nom'])),
            'prenom' => ucfirst(trim((string)$data['prenom'])),
            'email' => strtolower(trim((string)$data['email'])),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'mot_de_passe' => $passwordHash,
            'statut' => (string)($data['statut'] ?? 'actif'),
            'doit_changer_mdp' => 1, // Obligatoire à la première connexion
        ]);

        if (is_array($selectedEtabs)) {
            $this->userModel->syncEtablissements($userId, $selectedEtabs);
        }

        // Récupérer le libellé du rôle
        $role = $this->roleModel->findById((int)$data['role_id']);
        $roleLibelle = $role['libelle'] ?? 'Utilisateur';

        // Récupérer le nom du premier établissement affecté
        $etabNom = null;
        if (!empty($selectedEtabs)) {
            $firstEtabId = (int)$selectedEtabs[0];
            $etabObj = $this->etablissementModel->findById($firstEtabId);
            if ($etabObj) {
                $etabNom = $etabObj['nom'];
            }
        }

        // Choix des canaux de notification
        $channels = [];
        if (!empty($data['notify_email'])) {
            $channels[] = 'email';
        }
        if (!empty($data['notify_whatsapp'])) {
            $channels[] = 'whatsapp';
        }
        if (!empty($data['notify_sms'])) {
            $channels[] = 'sms';
        }

        // Envoi / Préparation des identifiants (Email, WhatsApp, SMS)
        $notifResults = NotificationService::sendUserCredentials([
            'id' => $userId,
            'nom' => strtoupper(trim((string)$data['nom'])),
            'prenom' => ucfirst(trim((string)$data['prenom'])),
            'email' => strtolower(trim((string)$data['email'])),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'role_libelle' => $roleLibelle,
        ], $plainPassword, $channels, [
            'etablissement_nom' => $etabNom,
        ]);

        // Sauvegarde des résultats en session pour affichage immédiat dans la modale
        Session::set('user_credentials_created', $notifResults);

        Logger::log(Auth::id(), null, 'utilisateur.creer', 'users', $userId, null, [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'channels' => $channels,
            'notifications' => [
                'email' => $notifResults['email']['status'] ?? 'none',
                'whatsapp' => $notifResults['whatsapp']['status'] ?? 'none',
                'sms' => $notifResults['sms']['status'] ?? 'none',
            ],
        ], $this->request);

        Session::setFlash('success', "Utilisateur '{$data['prenom']} {$data['nom']}' créé avec succès.");
        $this->redirect('/users');
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        $roles = $this->roleModel->findAll([], 'id ASC');
        $etablissements = Auth::getAccessibleEtablissements();
        $userEtabs = $this->userModel->getEtablissements($id);
        $assignedEtabIds = array_column($userEtabs, 'id');
        $userRole = $this->roleModel->findById((int)$user['role_id']);
        $isAdminPrincipal = ((int)$user['id'] === 1 || ($userRole && $userRole['code'] === 'admin'));

        $this->render('users.edit', [
            'user' => $user,
            'roles' => $roles,
            'etablissements' => $etablissements,
            'assignedEtabIds' => $assignedEtabIds,
            'isAdminPrincipal' => $isAdminPrincipal,
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        $userRole = $this->roleModel->findById((int)$user['role_id']);
        $isAdminPrincipal = ((int)$user['id'] === 1 || ($userRole && $userRole['code'] === 'admin'));

        $data = $this->request->post();

        $rules = [
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'email' => "required|email|max:190|unique:users,email,$id",
            'role_id' => 'required',
            'statut' => 'required|in:actif,inactif,verrouille',
        ];

        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            $roles = $this->roleModel->findAll([], 'id ASC');
            $etablissements = Auth::getAccessibleEtablissements();
            $userEtabs = $this->userModel->getEtablissements($id);
            $this->render('users.edit', [
                'user' => array_merge($user, $data),
                'roles' => $roles,
                'etablissements' => $etablissements,
                'assignedEtabIds' => array_column($userEtabs, 'id'),
                'isAdminPrincipal' => $isAdminPrincipal,
                'errors' => $errors
            ]);
            return;
        }

        $updateData = [
            'role_id' => $isAdminPrincipal ? (int)$user['role_id'] : (int)$data['role_id'],
            'nom' => strtoupper(trim((string)$data['nom'])),
            'prenom' => ucfirst(trim((string)$data['prenom'])),
            'email' => strtolower(trim((string)$data['email'])),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'statut' => $isAdminPrincipal ? 'actif' : (string)$data['statut'],
        ];

        // Déverrouillage si remis à actif
        if ($updateData['statut'] === 'actif') {
            $updateData['tentatives_echec'] = 0;
            $updateData['verrouille_jusqua'] = null;
        }

        // Si nouveau mot de passe renseigné
        if (!empty($data['nouveau_mot_de_passe'])) {
            if (strlen((string)$data['nouveau_mot_de_passe']) < 10) {
                Session::setFlash('danger', 'Le mot de passe doit comporter au moins 10 caractères.');
                $this->redirect("/users/$id/modifier");
            }
            $updateData['mot_de_passe'] = password_hash((string)$data['nouveau_mot_de_passe'], PASSWORD_ARGON2ID);
            $updateData['doit_changer_mdp'] = 1;
        }

        $this->userModel->update($id, $updateData);

        $selectedEtabs = $this->request->post('etablissements') ?? [];
        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds();
            $existingEtabs = $this->userModel->getEtablissements($id);
            $existingEtabIds = array_map('intval', array_column($existingEtabs, 'id'));
            $unmanagedEtabIds = array_diff($existingEtabIds, $allowedEtabIds);

            $managedSelected = array_intersect(array_map('intval', (array)$selectedEtabs), $allowedEtabIds);
            if (empty($managedSelected) && count($allowedEtabIds) === 1) {
                $managedSelected = $allowedEtabIds;
            }
            $selectedEtabs = array_values(array_unique(array_merge($unmanagedEtabIds, $managedSelected)));
        }

        if (is_array($selectedEtabs)) {
            $this->userModel->syncEtablissements($id, $selectedEtabs);
        }

        Logger::log(Auth::id(), null, 'utilisateur.modifier', 'users', $id, $user, $updateData, $this->request);

        Session::setFlash('success', 'Utilisateur mis à jour avec succès.');
        $this->redirect('/users');
    }

    public function toggleStatus(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        // Empêcher la désactivation de l'administrateur principal
        $userRole = $this->roleModel->findById((int)$user['role_id']);
        if ((int)$id === 1 || ($userRole && $userRole['code'] === 'admin') || $id === Auth::id()) {
            Session::setFlash('warning', "Le compte de l'administrateur principal ne peut pas être désactivé.");
            $this->redirect('/users');
        }

        $newStatut = $user['statut'] === 'actif' ? 'inactif' : 'actif';
        $update = [
            'statut' => $newStatut,
            'tentatives_echec' => 0,
            'verrouille_jusqua' => null
        ];

        $this->userModel->update($id, $update);
        Logger::log(Auth::id(), null, 'utilisateur.statut', 'users', $id, $user, $update, $this->request);

        Session::setFlash('success', "Statut de l'utilisateur changé en : $newStatut");
        $this->redirect('/users');
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        // Empêcher la suppression de l'administrateur principal
        $userRole = $this->roleModel->findById((int)$user['role_id']);
        if ((int)$id === 1 || ($userRole && $userRole['code'] === 'admin') || $id === Auth::id()) {
            Session::setFlash('warning', "Le compte de l'administrateur principal ne peut pas être supprimé.");
            $this->redirect('/users');
        }

        $this->userModel->delete($id);
        Logger::log(Auth::id(), null, 'utilisateur.supprimer', 'users', $id, null, null, $this->request);

        Session::setFlash('success', 'Utilisateur supprimé.');
        $this->redirect('/users');
    }
}
