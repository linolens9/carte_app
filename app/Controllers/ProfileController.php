<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Logger;
use PDO;

/**
 * Contrôleur de gestion du profil de l'utilisateur connecté
 */
class ProfileController extends Controller
{
    /**
     * Affichage du profil utilisateur
     */
    public function index(array $params = [], array $errors = [], array $old = []): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $sessionUser = Auth::user();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT u.*, r.libelle AS role_libelle, r.code AS role_code
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $sessionUser['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::setFlash('danger', "Utilisateur introuvable.");
            $this->redirect('/dashboard');
            return;
        }

        $etablissements = Auth::getAccessibleEtablissements();

        $this->render('profile.index', [
            'user' => $user,
            'etablissements' => $etablissements,
            'errors' => $errors,
            'old' => !empty($old) ? $old : $user,
            'title' => 'Mon Profil'
        ]);
    }

    /**
     * Mise à jour des informations du profil (nom, prénom, e-mail, mot de passe)
     */
    public function update(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $sessionUser = Auth::user();
        $userId = (int)$sessionUser['id'];
        $db = Database::getInstance();

        // Données postées
        $nom = strtoupper(trim((string)$this->request->post('nom', '')));
        $prenom = ucfirst(trim((string)$this->request->post('prenom', '')));
        $email = strtolower(trim((string)$this->request->post('email', '')));
        $telephone = trim((string)$this->request->post('telephone', ''));
        
        $currentPassword = (string)$this->request->post('current_password', '');
        $newPassword = (string)$this->request->post('new_password', '');
        $confirmPassword = (string)$this->request->post('confirm_password', '');

        $errors = [];
        $old = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone
        ];

        // Validations informations personnelles
        if (empty($nom)) {
            $errors['nom'][] = "Le nom est obligatoire.";
        } elseif (mb_strlen($nom) > 100) {
            $errors['nom'][] = "Le nom ne doit pas dépasser 100 caractères.";
        }

        if (empty($prenom)) {
            $errors['prenom'][] = "Le prénom est obligatoire.";
        } elseif (mb_strlen($prenom) > 100) {
            $errors['prenom'][] = "Le prénom ne doit pas dépasser 100 caractères.";
        }

        if (empty($email)) {
            $errors['email'][] = "L'adresse e-mail est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = "Veuillez saisir une adresse e-mail valide.";
        } elseif (mb_strlen($email) > 190) {
            $errors['email'][] = "L'adresse e-mail ne doit pas dépasser 190 caractères.";
        } else {
            // Vérifier l'unicité de l'adresse e-mail
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id AND deleted_at IS NULL LIMIT 1");
            $checkStmt->execute([':email' => $email, ':id' => $userId]);
            if ($checkStmt->fetch()) {
                $errors['email'][] = "Cette adresse e-mail est déjà utilisée par un autre compte.";
            }
        }

        if (!empty($telephone) && mb_strlen($telephone) > 30) {
            $errors['telephone'][] = "Le numéro de téléphone ne doit pas dépasser 30 caractères.";
        }

        // Validation mot de passe (optionnelle, uniquement si renseigné)
        $isChangingPassword = !empty($newPassword) || !empty($currentPassword) || !empty($confirmPassword);

        if ($isChangingPassword) {
            if (empty($currentPassword)) {
                $errors['current_password'][] = "Le mot de passe actuel est requis pour autoriser la modification.";
            } else {
                // Vérifier le mot de passe actuel
                $pwdStmt = $db->prepare("SELECT mot_de_passe FROM users WHERE id = :id");
                $pwdStmt->execute([':id' => $userId]);
                $currentHash = $pwdStmt->fetchColumn();

                if (!$currentHash || !password_verify($currentPassword, (string)$currentHash)) {
                    $errors['current_password'][] = "Le mot de passe actuel est incorrect.";
                }
            }

            if (empty($newPassword)) {
                $errors['new_password'][] = "Veuillez saisir le nouveau mot de passe.";
            } elseif (strlen($newPassword) < 10) {
                $errors['new_password'][] = "Le nouveau mot de passe doit comporter au moins 10 caractères.";
            }

            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'][] = "La confirmation ne correspond pas au nouveau mot de passe.";
            }
        }

        // Si erreurs, réafficher la page de profil avec les messages
        if (!empty($errors)) {
            $this->index([], $errors, $old);
            return;
        }

        // Mise à jour en base de données
        if ($isChangingPassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $updateSql = "
                UPDATE users
                SET nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    telephone = :telephone,
                    mot_de_passe = :pwd,
                    doit_changer_mdp = 0,
                    updated_at = NOW()
                WHERE id = :id
            ";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => !empty($telephone) ? $telephone : null,
                ':pwd' => $passwordHash,
                ':id' => $userId
            ]);
        } else {
            $updateSql = "
                UPDATE users
                SET nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    telephone = :telephone,
                    updated_at = NOW()
                WHERE id = :id
            ";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => !empty($telephone) ? $telephone : null,
                ':id' => $userId
            ]);
        }

        // Mise à jour de la session utilisateur active
        $updatedSession = Auth::user();
        $updatedSession['nom'] = $nom;
        $updatedSession['prenom'] = $prenom;
        $updatedSession['email'] = $email;
        if ($isChangingPassword) {
            $updatedSession['doit_changer_mdp'] = false;
        }
        Session::set('_auth_user', $updatedSession);

        // Journal d'audit
        Logger::log($userId, null, 'user.update_profil', 'users', $userId, null, [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password_changed' => $isChangingPassword
        ], $this->request);

        Session::setFlash('success', "Votre profil a été mis à jour avec succès.");
        $this->redirect('/profil');
    }
}
