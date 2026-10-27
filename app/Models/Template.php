<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Template
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, e.nom AS etablissement_nom, e.code AS etablissement_code, e.logo AS etablissement_logo,
                   u.nom AS auteur_nom, u.prenom AS auteur_prenom
            FROM templates t
            LEFT JOIN etablissements e ON t.etablissement_id = e.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = ? AND t.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['config_recto'] = is_string($row['config_recto']) ? json_decode($row['config_recto'], true) : ($row['config_recto'] ?? []);
            $row['config_verso'] = is_string($row['config_verso']) ? json_decode($row['config_verso'], true) : ($row['config_verso'] ?? []);
            return $row;
        }

        return null;
    }

    public function all(?int $etablissementId = null, ?string $cible = null): array
    {
        $sql = "
            SELECT t.*, e.nom AS etablissement_nom, e.code AS etablissement_code, e.logo AS etablissement_logo,
                   (SELECT COUNT(*) FROM cartes c WHERE c.template_id = t.id AND c.deleted_at IS NULL) AS nb_cartes
            FROM templates t
            LEFT JOIN etablissements e ON t.etablissement_id = e.id
            WHERE t.deleted_at IS NULL
        ";
        $params = [];

        if ($etablissementId) {
            $sql .= " AND (t.etablissement_id = ? OR t.etablissement_id IS NULL)";
            $params[] = $etablissementId;
        }

        if ($cible) {
            $sql .= " AND t.cible = ?";
            $params[] = $cible;
        }

        $sql .= " ORDER BY t.etablissement_id ASC, t.actif DESC, t.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['config_recto'] = is_string($row['config_recto']) ? json_decode($row['config_recto'], true) : ($row['config_recto'] ?? []);
            $row['config_verso'] = is_string($row['config_verso']) ? json_decode($row['config_verso'], true) : ($row['config_verso'] ?? []);
        }

        return $rows;
    }

    public function findActiveForEtablissement(?int $etablissementId, string $cible): ?array
    {
        $sql = "
            SELECT t.* FROM templates t
            WHERE t.cible = ? AND t.actif = 1 AND t.deleted_at IS NULL
        ";
        $params = [$cible];

        if ($etablissementId) {
            $sql .= " AND (t.etablissement_id = ? OR t.etablissement_id IS NULL)";
            $params[] = $etablissementId;
            $sql .= " ORDER BY (t.etablissement_id IS NOT NULL) DESC, t.id DESC LIMIT 1";
        } else {
            $sql .= " ORDER BY t.id DESC LIMIT 1";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['config_recto'] = is_string($row['config_recto']) ? json_decode($row['config_recto'], true) : [];
            $row['config_verso'] = is_string($row['config_verso']) ? json_decode($row['config_verso'], true) : [];
            return $row;
        }

        return null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO templates (
                etablissement_id, nom, cible, orientation,
                config_recto, config_verso, couleur_principale, couleur_secondaire,
                logo, signature, filigrane, version, actif, created_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, 1, ?, ?
            )
        ");

        $stmt->execute([
            $data['etablissement_id'] ?? null,
            $data['nom'],
            $data['cible'],
            $data['orientation'] ?? 'horizontal',
            is_array($data['config_recto'] ?? null) ? json_encode($data['config_recto']) : ($data['config_recto'] ?? '{}'),
            is_array($data['config_verso'] ?? null) ? json_encode($data['config_verso']) : ($data['config_verso'] ?? '{}'),
            $data['couleur_principale'] ?? '#1e3a8a',
            $data['couleur_secondaire'] ?? '#3b82f6',
            $data['logo'] ?? null,
            $data['signature'] ?? null,
            $data['filigrane'] ?? null,
            isset($data['actif']) ? (int)$data['actif'] : 1,
            $data['created_by'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'nom = ?',
            'cible = ?',
            'orientation = ?',
            'config_recto = ?',
            'config_verso = ?',
            'couleur_principale = ?',
            'couleur_secondaire = ?',
            'version = version + 1',
            'actif = ?'
        ];

        $params = [
            $data['nom'],
            $data['cible'],
            $data['orientation'] ?? 'horizontal',
            is_array($data['config_recto'] ?? null) ? json_encode($data['config_recto']) : ($data['config_recto'] ?? '{}'),
            is_array($data['config_verso'] ?? null) ? json_encode($data['config_verso']) : ($data['config_verso'] ?? '{}'),
            $data['couleur_principale'] ?? '#1e3a8a',
            $data['couleur_secondaire'] ?? '#3b82f6',
            isset($data['actif']) ? (int)$data['actif'] : 1,
        ];

        if (array_key_exists('etablissement_id', $data)) {
            $fields[] = 'etablissement_id = ?';
            $params[] = $data['etablissement_id'];
        }

        if (!empty($data['logo'])) {
            $fields[] = 'logo = ?';
            $params[] = $data['logo'];
        }
        if (!empty($data['signature'])) {
            $fields[] = 'signature = ?';
            $params[] = $data['signature'];
        }
        if (!empty($data['filigrane'])) {
            $fields[] = 'filigrane = ?';
            $params[] = $data['filigrane'];
        }

        $params[] = $id;

        $stmt = $this->pdo->prepare("UPDATE templates SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE templates SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function duplicate(int $id, ?int $targetEtablissementId, int $userId): int
    {
        $original = $this->find($id);
        if (!$original) {
            throw new \InvalidArgumentException("Modèle introuvable.");
        }

        $data = [
            'etablissement_id' => $targetEtablissementId ?? $original['etablissement_id'],
            'nom' => $original['nom'] . ' (Copie)',
            'cible' => $original['cible'],
            'orientation' => $original['orientation'],
            'config_recto' => $original['config_recto'],
            'config_verso' => $original['config_verso'],
            'couleur_principale' => $original['couleur_principale'],
            'couleur_secondaire' => $original['couleur_secondaire'],
            'logo' => $original['logo'],
            'signature' => $original['signature'],
            'filigrane' => $original['filigrane'],
            'actif' => 1,
            'created_by' => $userId
        ];

        return $this->create($data);
    }

    /**
     * Crée les modèles par défaut s'il n'en existe aucun dans la base
     */
    public function seedDefaultsIfEmpty(?int $userId = 1): void
    {
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM templates WHERE deleted_at IS NULL")->fetchColumn();
        if ($count > 0) {
            return;
        }

        // 1. Modèle Scolaire
        $this->create([
            'etablissement_id' => null, // Global
            'nom' => 'Carte Scolaire Nationale (Format CR-80)',
            'cible' => 'eleve',
            'orientation' => 'horizontal',
            'couleur_principale' => '#1e3a8a',
            'couleur_secondaire' => '#0284c7',
            'config_recto' => [
                'titre_haut' => 'RÉPUBLIQUE GABONAISE',
                'sous_titre_haut' => 'Union - Travail - Justice',
                'titre_carte' => 'CARTE D\'IDENTITÉ SCOLAIRE',
                'mention_annee' => 'Année Scolaire 2025-2026',
                'afficher_logo' => true,
                'afficher_armoiries' => true,
                'afficher_photo' => true,
                'afficher_barcode' => true,
                'afficher_puce' => true,
                'champs' => [
                    'nom_complet' => 'Nom & Prénom',
                    'matricule' => 'N° Matricule',
                    'classe' => 'Classe',
                    'date_naissance' => 'Né(e) le',
                    'sexe' => 'Sexe',
                    'statut' => 'Statut',
                    'expiration' => 'Valide jusqu\'au'
                ]
            ],
            'config_verso' => [
                'titre' => 'CONDITIONS & RÈGLEMENT',
                'texte_reglement' => "1. Cette carte est strictement personnelle et incessible.\n2. Elle est obligatoire pour l'accès aux cours, examens et activités de l'établissement.\n3. Toute falsification ou prêt expose à des sanctions disciplinaires.\n4. En cas de perte ou de vol, aviser immédiatement le secrétariat.",
                'afficher_qr' => true,
                'afficher_signature' => true,
                'titre_signature' => 'Le Proviseur / Principal',
                'mention_retour' => 'Trouvée ? Prière de déposer au secrétariat ou commissariat le plus proche.'
            ],
            'actif' => 1,
            'created_by' => $userId
        ]);

        // 2. Modèle Universitaire
        $this->create([
            'etablissement_id' => null, // Global
            'nom' => 'Carte Étudiant LMD (Format CR-80)',
            'cible' => 'etudiant',
            'orientation' => 'horizontal',
            'couleur_principale' => '#065f46',
            'couleur_secondaire' => '#10b981',
            'config_recto' => [
                'titre_haut' => 'MINISTÈRE DE L\'ENSEIGNEMENT SUPÉRIEUR',
                'sous_titre_haut' => 'Enseignement Supérieur & Recherche Scientifique',
                'titre_carte' => 'CARTE D\'ÉTUDIANT',
                'mention_annee' => 'Année Académique 2025-2026',
                'afficher_logo' => true,
                'afficher_armoiries' => true,
                'afficher_photo' => true,
                'afficher_barcode' => true,
                'afficher_puce' => true,
                'champs' => [
                    'nom_complet' => 'Nom & Prénom',
                    'matricule' => 'N° Étudiant',
                    'filiere' => 'Filière / Département',
                    'niveau' => 'Cycle / Niveau',
                    'date_naissance' => 'Date de Naissance',
                    'expiration' => 'Expiration'
                ]
            ],
            'config_verso' => [
                'titre' => 'SERVICES ÉTUDIANTS & RÈGLEMENT',
                'texte_reglement' => "Cette carte donne accès aux bibliothèques universitaires, restaurants universitaires et services des œuvres estudiantines. Elle doit être présentée lors de tout contrôle ou examen.",
                'afficher_qr' => true,
                'afficher_signature' => true,
                'titre_signature' => 'Le Secrétaire Général / Recteur',
                'mention_retour' => 'Université Omar Bongo - B.P. 1313 Libreville, Gabon'
            ],
            'actif' => 1,
            'created_by' => $userId
        ]);

        // 3. Modèle Professionnel
        $this->create([
            'etablissement_id' => null, // Global
            'nom' => 'Badge Professionnel Sécurisé (Format CR-80)',
            'cible' => 'personnel',
            'orientation' => 'horizontal',
            'couleur_principale' => '#7c2d12',
            'couleur_secondaire' => '#ea580c',
            'config_recto' => [
                'titre_haut' => 'RÉPUBLIQUE GABONAISE',
                'sous_titre_haut' => 'Administration & Organismes Publics',
                'titre_carte' => 'BADGE PROFESSIONNEL',
                'mention_annee' => 'Carte de Service',
                'afficher_logo' => true,
                'afficher_armoiries' => true,
                'afficher_photo' => true,
                'afficher_barcode' => true,
                'afficher_puce' => true,
                'champs' => [
                    'nom_complet' => 'Nom & Prénom',
                    'matricule' => 'N° Professionnel',
                    'fonction' => 'Fonction',
                    'service' => 'Direction / Service',
                    'date_embauche' => 'Date de prise de service',
                    'expiration' => 'Validité'
                ]
            ],
            'config_verso' => [
                'titre' => 'PROPRIÉTÉ DE L\'ORGANISME',
                'texte_reglement' => "Ce badge est la propriété de l'organisme. Il atteste de l'appartenance de son titulaire à l'institution. Doit être porté de façon visible dans les locaux. En cas de cessation de fonction, il doit être restitué.",
                'afficher_qr' => true,
                'afficher_signature' => true,
                'titre_signature' => 'Le Directeur Général / DRH',
                'mention_retour' => 'En cas de découverte, merci de contacter la Direction des Ressources Humaines.'
            ],
            'actif' => 1,
            'created_by' => $userId
        ]);
    }
}
