<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Service d'authentification et de contrôle d'accès RBAC
 */
class Auth
{
    private const SESSION_USER_KEY = '_auth_user';
    private const SESSION_ETAB_KEY = '_active_etab_id';

    public static function attempt(string $email, string $password, Request $request): array
    {
        $db = Database::getInstance();

        // Recherche de l'utilisateur par e-mail
        $stmt = $db->prepare("
            SELECT u.*, r.code AS role_code, r.libelle AS role_libelle
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();

        // Sécurité : message générique pour éviter la détection d'adresses
        $genericError = "Identifiants incorrects.";

        if (!$user) {
            // Petite pause constante pour mitigation du timing attack
            password_verify($password, '$argon2id$v=19$m=65536,t=4,p=1$dummy$dummyhashformitigation');
            Logger::log(null, null, 'auth.echec', 'users', null, null, ['email' => $email], $request);
            return ['success' => false, 'message' => $genericError];
        }

        $now = date('Y-m-d H:i:s');

        // Vérification du verrouillage
        if ($user['statut'] === 'verrouille' || (!empty($user['verrouille_jusqua']) && $user['verrouille_jusqua'] > $now)) {
            $lockUntil = strtotime($user['verrouille_jusqua']);
            $remainingMinutes = max(1, (int)ceil(($lockUntil - time()) / 60));
            Logger::log((int)$user['id'], null, 'auth.verrouille', 'users', (int)$user['id'], null, null, $request);
            return [
                'success' => false,
                'message' => "Compte temporairement verrouillé suite à plusieurs tentatives. Veuillez patienter $remainingMinutes minute(s)."
            ];
        }

        // Vérification de compte actif
        if ($user['statut'] !== 'actif') {
            Logger::log((int)$user['id'], null, 'auth.inactif', 'users', (int)$user['id'], null, null, $request);
            return ['success' => false, 'message' => "Ce compte est désactivé. Veuillez contacter un administrateur."];
        }

        // Vérification du mot de passe
        if (!password_verify($password, $user['mot_de_passe'])) {
            $attempts = (int)$user['tentatives_echec'] + 1;
            $updateSql = "UPDATE users SET tentatives_echec = :att";
            $params = [':att' => $attempts, ':id' => $user['id']];

            if ($attempts >= 5) {
                $lockDuration = 15; // 15 minutes
                $lockUntil = date('Y-m-d H:i:s', strtotime("+$lockDuration minutes"));
                $updateSql .= ", statut = 'verrouille', verrouille_jusqua = :lock";
                $params[':lock'] = $lockUntil;
            }

            $updateSql .= " WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute($params);

            Logger::log((int)$user['id'], null, 'auth.echec_mdp', 'users', (int)$user['id'], null, ['tentatives' => $attempts], $request);

            if ($attempts >= 5) {
                return [
                    'success' => false,
                    'message' => "Trop de tentatives infructueuses. Votre compte est verrouillé pour 15 minutes."
                ];
            }

            $restantes = 5 - $attempts;
            return [
                'success' => false,
                'message' => "Identifiants incorrects. Plus que $restantes tentative(s) avant verrouillage."
            ];
        }

        // Succès : réinitialiser les échecs et mettre à jour la date de dernière connexion
        $rehash = password_needs_rehash($user['mot_de_passe'], PASSWORD_ARGON2ID);
        $newHash = $rehash ? password_hash($password, PASSWORD_ARGON2ID) : $user['mot_de_passe'];

        $upStmt = $db->prepare("
            UPDATE users
            SET tentatives_echec = 0,
                verrouille_jusqua = NULL,
                statut = 'actif',
                derniere_connexion = :now,
                mot_de_passe = :hash
            WHERE id = :id
        ");
        $upStmt->execute([':now' => $now, ':hash' => $newHash, ':id' => $user['id']]);

        // Régénération de l'ID de session pour prévenir la fixation
        Session::regenerate();

        // Récupération des permissions de l'utilisateur
        $permStmt = $db->prepare("
            SELECT p.code
            FROM role_permission rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
        ");
        $permStmt->execute([':role_id' => $user['role_id']]);
        $permissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);

        // Récupération des établissements assignés
        $etabStmt = $db->prepare("
            SELECT e.id, e.nom, e.code, e.categorie, e.type, e.logo, e.couleur_principale
            FROM etablissements e
            JOIN user_etablissement ue ON e.id = ue.etablissement_id
            WHERE ue.user_id = :uid AND e.deleted_at IS NULL AND e.statut != 'archive'
            ORDER BY e.nom ASC
        ");
        $etabStmt->execute([':uid' => $user['id']]);
        $etablissements = $etabStmt->fetchAll();

        // Si admin, il a accès à tous les établissements
        if ($user['role_code'] === 'admin') {
            $allEtabStmt = $db->query("
                SELECT id, nom, code, categorie, type, logo, couleur_principale
                FROM etablissements
                WHERE deleted_at IS NULL AND statut != 'archive'
                ORDER BY nom ASC
            ");
            $etablissements = $allEtabStmt->fetchAll();
        }

        // Établissement actif par défaut
        $activeEtabId = $etablissements[0]['id'] ?? null;

        // Stockage en session
        Session::set(self::SESSION_USER_KEY, [
            'id' => (int)$user['id'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'photo' => $user['photo'] ?? null,
            'role_id' => (int)$user['role_id'],
            'role_code' => $user['role_code'],
            'role_libelle' => $user['role_libelle'],
            'doit_changer_mdp' => (bool)$user['doit_changer_mdp'],
            'permissions' => $permissions,
            'etablissements' => $etablissements,
        ]);

        if ($activeEtabId !== null) {
            Session::set(self::SESSION_ETAB_KEY, (int)$activeEtabId);
        }

        Logger::log((int)$user['id'], $activeEtabId ? (int)$activeEtabId : null, 'auth.connexion', 'users', (int)$user['id'], null, null, $request);

        return [
            'success' => true,
            'must_change_password' => (bool)$user['doit_changer_mdp']
        ];
    }

    public static function check(): bool
    {
        return Session::has(self::SESSION_USER_KEY);
    }

    public static function user(): ?array
    {
        return Session::get(self::SESSION_USER_KEY);
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role_code'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        return in_array($u['role_code'], $roles, true);
    }

    public static function hasPermission(string $permission): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        // L'admin principal a toujours toutes les permissions
        if ($u['role_code'] === 'admin') {
            return true;
        }
        return in_array($permission, $u['permissions'] ?? [], true);
    }

    public static function can(string $permission): bool
    {
        return self::hasPermission($permission);
    }

    public static function getActiveEtablissementId(): ?int
    {
        $id = Session::get(self::SESSION_ETAB_KEY);
        if ($id === null) {
            $u = self::user();
            if ($u && $u['role_code'] !== 'admin') {
                $accessible = self::getAccessibleEtablissements();
                if (!empty($accessible[0]['id'])) {
                    $id = (int)$accessible[0]['id'];
                    Session::set(self::SESSION_ETAB_KEY, $id);
                }
            }
        }
        return $id ? (int)$id : null;
    }

    public static function setActiveEtablissementId(int $etablissementId): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        // Vérifier si l'utilisateur a le droit sur cet établissement
        if ($u['role_code'] !== 'admin') {
            $allowedIds = array_column($u['etablissements'] ?? [], 'id');
            if (!in_array($etablissementId, $allowedIds, false)) {
                return false;
            }
        }
        Session::set(self::SESSION_ETAB_KEY, $etablissementId);
        return true;
    }

    public static function getActiveEtablissement(): ?array
    {
        $activeId = self::getActiveEtablissementId();
        if (!$activeId) {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM etablissements WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $activeId]);
        $etab = $stmt->fetch();
        return $etab ?: null;
    }

    /**
     * Retourne la liste des établissements accessibles pour l'utilisateur connecté.
     * Pour un admin : tous les établissements actifs.
     * Pour les autres utilisateurs (notamment responsable d'établissement) : uniquement leurs établissements assignés.
     */
    public static function getAccessibleEtablissements(?string $categorie = null): array
    {
        $u = self::user();
        if (!$u) {
            return [];
        }

        $db = Database::getInstance();

        if ($u['role_code'] === 'admin') {
            if ($categorie !== null) {
                $stmt = $db->prepare("
                    SELECT id, nom, code, categorie, type, logo, couleur_principale
                    FROM etablissements
                    WHERE categorie = :cat AND deleted_at IS NULL AND statut != 'archive'
                    ORDER BY nom ASC
                ");
                $stmt->execute([':cat' => $categorie]);
            } else {
                $stmt = $db->query("
                    SELECT id, nom, code, categorie, type, logo, couleur_principale
                    FROM etablissements
                    WHERE deleted_at IS NULL AND statut != 'archive'
                    ORDER BY nom ASC
                ");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Pour les responsables et autres non-admins : uniquement leurs établissements assignés
        $sql = "
            SELECT e.id, e.nom, e.code, e.categorie, e.type, e.logo, e.couleur_principale
            FROM etablissements e
            JOIN user_etablissement ue ON e.id = ue.etablissement_id
            WHERE ue.user_id = :uid AND e.deleted_at IS NULL AND e.statut != 'archive'
        ";
        if ($categorie !== null) {
            $sql .= " AND e.categorie = :cat ";
        }
        $sql .= " ORDER BY e.nom ASC";

        $stmt = $db->prepare($sql);
        $params = [':uid' => $u['id']];
        if ($categorie !== null) {
            $params[':cat'] = $categorie;
        }
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Secours si la catégorie ne correspond pas exactement mais que l'utilisateur est assigné à un établissement
        if (empty($results) && $categorie !== null) {
            $stmtFallback = $db->prepare("
                SELECT e.id, e.nom, e.code, e.categorie, e.type, e.logo, e.couleur_principale
                FROM etablissements e
                JOIN user_etablissement ue ON e.id = ue.etablissement_id
                WHERE ue.user_id = :uid AND e.deleted_at IS NULL AND e.statut != 'archive'
                ORDER BY e.nom ASC
            ");
            $stmtFallback->execute([':uid' => $u['id']]);
            $results = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        return $results;
    }

    /**
     * Retourne les identifiants d'établissements accessibles pour l'utilisateur connecté.
     */
    public static function getAccessibleEtablissementIds(?string $categorie = null): array
    {
        $etabs = self::getAccessibleEtablissements($categorie);
        return array_map('intval', array_column($etabs, 'id'));
    }

    public static function logout(?Request $request = null): void
    {
        $uid = self::id();
        $etabId = self::getActiveEtablissementId();

        if ($uid && $request) {
            Logger::log($uid, $etabId, 'auth.deconnexion', 'users', $uid, null, null, $request);
        }

        Session::destroy();
    }
}
