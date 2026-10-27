<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Logger;
use App\Services\PhotoService;
use PDO;
use Throwable;

/**
 * Contrôleur de gestion du profil de l'utilisateur connecté
 */
class ProfileController extends Controller
{
    /**
     * S'assure que la colonne photo existe dans la table users (migration tolérante)
     */
    private function ensurePhotoColumnExists(PDO $db): void
    {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'photo'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL AFTER telephone");
            }
        } catch (Throwable $e) {
            // Silencieux si déjà existant ou restrictions de droits
        }
    }

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
        $this->ensurePhotoColumnExists($db);

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
     * Mise à jour des informations du profil (nom, prénom, e-mail, photo, mot de passe)
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
        $this->ensurePhotoColumnExists($db);

        // Récupérer l'utilisateur actuel
        $stmtCurrent = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmtCurrent->execute([':id' => $userId]);
        $currentUser = $stmtCurrent->fetch();

        // Données postées
        $nom = strtoupper(trim((string)$this->request->post('nom', '')));
        $prenom = ucfirst(trim((string)$this->request->post('prenom', '')));
        $email = strtolower(trim((string)$this->request->post('email', '')));
        $telephone = trim((string)$this->request->post('telephone', ''));
        
        $currentPassword = (string)$this->request->post('current_password', '');
        $newPassword = (string)$this->request->post('new_password', '');
        $confirmPassword = (string)$this->request->post('confirm_password', '');

        $deletePhoto = (bool)$this->request->post('delete_photo', false);
        $photoBase64 = (string)$this->request->post('photo_base64', '');
        $photoFile = $this->request->file('photo') ?? ($_FILES['photo'] ?? null);

        $errors = [];
        $old = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'photo' => $currentUser['photo'] ?? null
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

        // Traitement de la photo de profil
        $newPhotoPath = $currentUser['photo'] ?? null;
        $photoUpdated = false;

        if ($deletePhoto) {
            // Suppression de l'ancienne photo sur le disque
            if (!empty($currentUser['photo'])) {
                $oldDiskPath = dirname(__DIR__, 2) . '/public/' . $currentUser['photo'];
                if (file_exists($oldDiskPath)) {
                    @unlink($oldDiskPath);
                }
            }
            $newPhotoPath = null;
            $photoUpdated = true;
        } elseif (!empty($photoBase64)) {
            try {
                $photoService = new PhotoService();
                $newPhotoPath = $photoService->processBase64($photoBase64);
                $photoUpdated = true;
            } catch (Throwable $e) {
                $errors['photo'][] = "Erreur lors du traitement de la photo : " . $e->getMessage();
            }
        } elseif ($photoFile && isset($photoFile['error']) && $photoFile['error'] === UPLOAD_ERR_OK) {
            try {
                $photoService = new PhotoService();
                $newPhotoPath = $photoService->processUploadedFile($photoFile);
                $photoUpdated = true;
            } catch (Throwable $e) {
                $errors['photo'][] = "Erreur de téléversement : " . $e->getMessage();
            }
        }

        // Validation mot de passe (optionnelle)
        $isChangingPassword = !empty($newPassword) || !empty($currentPassword) || !empty($confirmPassword);

        if ($isChangingPassword) {
            if (empty($currentPassword)) {
                $errors['current_password'][] = "Le mot de passe actuel est requis pour autoriser la modification.";
            } else {
                $currentHash = $currentUser['mot_de_passe'] ?? '';
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
        $params = [
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':telephone' => !empty($telephone) ? $telephone : null,
            ':id' => $userId
        ];

        $updateFields = [
            'nom = :nom',
            'prenom = :prenom',
            'email = :email',
            'telephone = :telephone',
            'updated_at = NOW()'
        ];

        if ($photoUpdated) {
            $updateFields[] = 'photo = :photo';
            $params[':photo'] = $newPhotoPath;
        }

        if ($isChangingPassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $updateFields[] = 'mot_de_passe = :pwd';
            $updateFields[] = 'doit_changer_mdp = 0';
            $params[':pwd'] = $passwordHash;
        }

        $updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($params);

        // Mise à jour de la session utilisateur active
        $updatedSession = Auth::user();
        $updatedSession['nom'] = $nom;
        $updatedSession['prenom'] = $prenom;
        $updatedSession['email'] = $email;
        if ($photoUpdated) {
            $updatedSession['photo'] = $newPhotoPath;
        }
        if ($isChangingPassword) {
            $updatedSession['doit_changer_mdp'] = false;
        }
        Session::set('_auth_user', $updatedSession);

        // Journal d'audit
        Logger::log($userId, null, 'user.update_profil', 'users', $userId, null, [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'photo_updated' => $photoUpdated,
            'password_changed' => $isChangingPassword
        ], $this->request);

        Session::setFlash('success', "Votre profil a été mis à jour avec succès.");
        $this->redirect('/profil');
    }
}
