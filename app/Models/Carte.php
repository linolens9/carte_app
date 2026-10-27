<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Carte
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   t.nom AS template_nom, t.orientation AS template_orientation, 
                   t.couleur_principale, t.couleur_secondaire, t.config_recto, t.config_verso,
                   t.logo AS template_logo, t.signature AS template_signature, t.filigrane AS template_filigrane,
                   e.nom AS etablissement_nom, e.code AS etablissement_code, e.type AS etablissement_type,
                   e.logo AS etablissement_logo, e.cachet AS etablissement_cachet, e.ville AS etablissement_ville, e.telephone AS etablissement_telephone,
                   u.nom AS createur_nom, u.prenom AS createur_prenom,
                   uv.nom AS validateur_nom, uv.prenom AS validateur_prenom
            FROM cartes c
            JOIN templates t ON c.template_id = t.id
            JOIN etablissements e ON c.etablissement_id = e.id
            JOIN users u ON c.created_by = u.id
            LEFT JOIN users uv ON c.valide_par = uv.id
            WHERE c.id = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            return null;
        }

        $this->enrichCard($card);
        return $card;
    }

    public function findByJeton(string $jeton): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   t.nom AS template_nom, t.orientation AS template_orientation, 
                   t.couleur_principale, t.couleur_secondaire, t.config_recto, t.config_verso,
                   t.logo AS template_logo, t.signature AS template_signature,
                   e.nom AS etablissement_nom, e.code AS etablissement_code, e.type AS etablissement_type,
                   e.logo AS etablissement_logo, e.cachet AS etablissement_cachet, e.ville AS etablissement_ville, e.telephone AS etablissement_telephone,
                   e.email AS etablissement_email, e.adresse AS etablissement_adresse
            FROM cartes c
            JOIN templates t ON c.template_id = t.id
            JOIN etablissements e ON c.etablissement_id = e.id
            WHERE c.jeton_verification = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$jeton]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            return null;
        }

        $this->enrichCard($card);
        return $card;
    }

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   t.nom AS template_nom, t.orientation AS template_orientation, 
                   t.couleur_principale, t.couleur_secondaire, t.config_recto, t.config_verso,
                   t.logo AS template_logo, t.signature AS template_signature,
                   e.nom AS etablissement_nom, e.code AS etablissement_code, e.type AS etablissement_type,
                   e.logo AS etablissement_logo, e.cachet AS etablissement_cachet, e.ville AS etablissement_ville
            FROM cartes c
            JOIN templates t ON c.template_id = t.id
            JOIN etablissements e ON c.etablissement_id = e.id
            WHERE c.numero_carte = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$numero]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            return null;
        }

        $this->enrichCard($card);
        return $card;
    }

    private function enrichCard(array &$card): void
    {
        $card['config_recto'] = is_string($card['config_recto']) ? json_decode($card['config_recto'], true) : ($card['config_recto'] ?? []);
        $card['config_verso'] = is_string($card['config_verso']) ? json_decode($card['config_verso'], true) : ($card['config_verso'] ?? []);

        $type = $card['beneficiaire_type'];
        $bId = (int)$card['beneficiaire_id'];

        if ($type === 'eleve') {
            $stmt = $this->pdo->prepare("
                SELECT el.nom, el.prenom, el.matricule, el.sexe, el.date_naissance, el.lieu_naissance,
                       el.photo, el.tuteur_telephone, el.statut AS statut_beneficiaire,
                       cl.libelle AS classe_nom, cl.niveau
                FROM eleves el
                LEFT JOIN classes cl ON el.classe_id = cl.id
                WHERE el.id = ?
            ");
            $stmt->execute([$bId]);
            $bData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $card['beneficiaire'] = array_merge($bData, [
                'type_label' => 'Élève',
                'groupe_label' => $bData['classe_nom'] ?? 'Sans classe',
                'matricule_label' => 'Matricule'
            ]);
        } elseif ($type === 'etudiant') {
            $stmt = $this->pdo->prepare("
                SELECT et.nom, et.prenom, et.matricule, et.sexe, et.date_naissance, et.nationalite,
                       et.photo, et.telephone, et.email, et.niveau, et.statut AS statut_beneficiaire,
                       f.libelle AS filiere_nom, f.code AS filiere_code,
                       cl.libelle AS classe_nom
                FROM etudiants et
                LEFT JOIN filieres f ON et.filiere_id = f.id
                LEFT JOIN classes cl ON et.classe_id = cl.id
                WHERE et.id = ?
            ");
            $stmt->execute([$bId]);
            $bData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $card['beneficiaire'] = array_merge($bData, [
                'type_label' => 'Étudiant',
                'groupe_label' => ($bData['filiere_nom'] ?? '') . (!empty($bData['classe_nom']) ? ' - ' . $bData['classe_nom'] : ''),
                'matricule_label' => 'N° Étudiant'
            ]);
        } else {
            // Personnel
            $stmt = $this->pdo->prepare("
                SELECT p.nom, p.prenom, p.numero_professionnel AS matricule, p.fonction, p.service,
                       p.telephone, p.email, p.photo, p.date_embauche, p.statut AS statut_beneficiaire,
                       d.nom AS departement_nom
                FROM personnels p
                LEFT JOIN departements d ON p.departement_id = d.id
                WHERE p.id = ?
            ");
            $stmt->execute([$bId]);
            $bData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $card['beneficiaire'] = array_merge($bData, [
                'type_label' => 'Personnel',
                'groupe_label' => $bData['fonction'] ?? ($bData['departement_nom'] ?? 'Personnel'),
                'matricule_label' => 'N° Professionnel'
            ]);
        }
    }

    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT c.*, 
                   t.nom AS template_nom, t.logo AS template_logo,
                   e.nom AS etablissement_nom, e.code AS etablissement_code, e.logo AS etablissement_logo, e.cachet AS etablissement_cachet,
                   CASE 
                       WHEN c.beneficiaire_type = 'eleve' THEN (SELECT CONCAT(el.nom, ' ', el.prenom) FROM eleves el WHERE el.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'etudiant' THEN (SELECT CONCAT(et.nom, ' ', et.prenom) FROM etudiants et WHERE et.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'personnel' THEN (SELECT CONCAT(p.nom, ' ', p.prenom) FROM personnels p WHERE p.id = c.beneficiaire_id)
                   END AS beneficiaire_nom_complet,
                   CASE 
                       WHEN c.beneficiaire_type = 'eleve' THEN (SELECT el.matricule FROM eleves el WHERE el.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'etudiant' THEN (SELECT et.matricule FROM etudiants et WHERE et.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'personnel' THEN (SELECT p.numero_professionnel FROM personnels p WHERE p.id = c.beneficiaire_id)
                   END AS beneficiaire_matricule,
                   CASE 
                       WHEN c.beneficiaire_type = 'eleve' THEN (SELECT el.photo FROM eleves el WHERE el.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'etudiant' THEN (SELECT et.photo FROM etudiants et WHERE et.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'personnel' THEN (SELECT p.photo FROM personnels p WHERE p.id = c.beneficiaire_id)
                   END AS beneficiaire_photo,
                   CASE 
                       WHEN c.beneficiaire_type = 'eleve' THEN (SELECT cl.libelle FROM eleves el JOIN classes cl ON el.classe_id = cl.id WHERE el.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'etudiant' THEN (SELECT f.libelle FROM etudiants et JOIN filieres f ON et.filiere_id = f.id WHERE et.id = c.beneficiaire_id)
                       WHEN c.beneficiaire_type = 'personnel' THEN (SELECT p.fonction FROM personnels p WHERE p.id = c.beneficiaire_id)
                   END AS beneficiaire_detail
            FROM cartes c
            JOIN templates t ON c.template_id = t.id
            JOIN etablissements e ON c.etablissement_id = e.id
            WHERE c.deleted_at IS NULL
        ";

        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND c.etablissement_id = ?";
            $params[] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['beneficiaire_type'])) {
            $sql .= " AND c.beneficiaire_type = ?";
            $params[] = $filters['beneficiaire_type'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['template_id'])) {
            $sql .= " AND c.template_id = ?";
            $params[] = (int)$filters['template_id'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                c.numero_carte LIKE ? OR
                c.jeton_verification LIKE ? OR
                c.id IN (
                    SELECT el2.id FROM eleves el2 WHERE el2.nom LIKE ? OR el2.prenom LIKE ? OR el2.matricule LIKE ?
                ) OR
                c.id IN (
                    SELECT et2.id FROM etudiants et2 WHERE et2.nom LIKE ? OR et2.prenom LIKE ? OR et2.matricule LIKE ?
                ) OR
                c.id IN (
                    SELECT p2.id FROM personnels p2 WHERE p2.nom LIKE ? OR p2.prenom LIKE ? OR p2.numero_professionnel LIKE ?
                )
            )";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY c.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM cartes c WHERE c.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND c.etablissement_id = ?";
            $params[] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['beneficiaire_type'])) {
            $sql .= " AND c.beneficiaire_type = ?";
            $params[] = $filters['beneficiaire_type'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = ?";
            $params[] = $filters['statut'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function countByStatut(?int $etablissementId = null): array
    {
        $sql = "
            SELECT statut, COUNT(*) AS total
            FROM cartes
            WHERE deleted_at IS NULL
        ";
        $params = [];

        if ($etablissementId) {
            $sql .= " AND etablissement_id = ?";
            $params[] = $etablissementId;
        }

        $sql .= " GROUP BY statut";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $statuses = ['brouillon', 'validee', 'imprimee', 'active', 'expiree', 'desactivee', 'archivee'];
        $result = [];
        $totalAll = 0;
        foreach ($statuses as $st) {
            $val = (int)($rows[$st] ?? 0);
            $result[$st] = $val;
            $totalAll += $val;
        }
        $result['total'] = $totalAll;

        return $result;
    }

    /**
     * Génère un numéro de carte unique
     */
    public function generateCardNumber(int $etablissementId, string $type): string
    {
        $prefixType = match ($type) {
            'eleve' => 'ELV',
            'etudiant' => 'ETU',
            'personnel' => 'PER',
            default => 'CRT'
        };

        // Obtenir le code établissement
        $stmt = $this->pdo->prepare("SELECT code FROM etablissements WHERE id = ?");
        $stmt->execute([$etablissementId]);
        $codeEtab = strtoupper(trim((string)$stmt->fetchColumn())) ?: 'GEN';

        $annee = date('Y');

        // Trouver le max séquentiel pour cet établissement et cette année
        $pattern = "C-{$annee}-{$codeEtab}-{$prefixType}-%";
        $stmtSeq = $this->pdo->prepare("
            SELECT numero_carte FROM cartes 
            WHERE numero_carte LIKE ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtSeq->execute([$pattern]);
        $lastNum = $stmtSeq->fetchColumn();

        $nextSeq = 1;
        if ($lastNum) {
            $parts = explode('-', (string)$lastNum);
            $lastIndex = end($parts);
            $nextSeq = ((int)$lastIndex) + 1;
        }

        return sprintf("C-%s-%s-%s-%05d", $annee, $codeEtab, $prefixType, $nextSeq);
    }

    /**
     * Génère une carte unitaire
     */
    public function genererUnitaire(
        int $etablissementId,
        int $templateId,
        string $type,
        int $beneficiaireId,
        int $userId,
        ?string $dateExpiration = null
    ): int {
        // Vérifier si une carte valide existe déjà pour ce bénéficiaire
        $stmtCheck = $this->pdo->prepare("
            SELECT id FROM cartes 
            WHERE etablissement_id = ? AND beneficiaire_type = ? AND beneficiaire_id = ? 
              AND statut IN ('brouillon', 'validee', 'imprimee', 'active')
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmtCheck->execute([$etablissementId, $type, $beneficiaireId]);
        $existing = $stmtCheck->fetchColumn();
        if ($existing) {
            throw new \RuntimeException("Ce bénéficiaire possède déjà une carte active ou en cours (ID #{$existing}). Utilisez l'action 'Renouveler' pour créer une nouvelle carte.");
        }

        $numeroCarte = $this->generateCardNumber($etablissementId, $type);
        $jeton = bin2hex(random_bytes(16));
        $dateEmission = date('Y-m-d');

        if (!$dateExpiration) {
            // Par défaut : 12 mois ou fin d'année scolaire (30 juin prochain)
            $dateExpiration = date('Y-m-d', strtotime('+1 year'));
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO cartes (
                etablissement_id, template_id, beneficiaire_type, beneficiaire_id,
                numero_carte, jeton_verification, date_emission, date_expiration,
                statut, created_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                'brouillon', ?
            )
        ");

        $stmt->execute([
            $etablissementId,
            $templateId,
            $type,
            $beneficiaireId,
            $numeroCarte,
            $jeton,
            $dateEmission,
            $dateExpiration,
            $userId
        ]);

        $carteId = (int)$this->pdo->lastInsertId();

        $this->enregistrerHistorique(
            $carteId,
            $userId,
            'CREATION',
            null,
            'brouillon',
            'Génération initiale de la carte'
        );

        return $carteId;
    }

    /**
     * Génération en lot
     */
    public function genererLot(
        int $etablissementId,
        int $templateId,
        string $type,
        array $beneficiaireIds,
        int $userId,
        ?string $dateExpiration = null
    ): array {
        $crees = 0;
        $ignores = 0;
        $cartesIds = [];

        foreach ($beneficiaireIds as $bId) {
            try {
                $id = $this->genererUnitaire($etablissementId, $templateId, $type, (int)$bId, $userId, $dateExpiration);
                $cartesIds[] = $id;
                $crees++;
            } catch (\Exception $e) {
                $ignores++;
            }
        }

        return [
            'succes' => $crees,
            'ignores' => $ignores,
            'cartes_ids' => $cartesIds
        ];
    }

    public function valider(int $id, int $userId): bool
    {
        $carte = $this->find($id);
        if (!$carte) {
            return false;
        }

        if ($carte['statut'] !== 'brouillon') {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE cartes 
            SET statut = 'validee', valide_par = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $res = $stmt->execute([$userId, $id]);

        if ($res) {
            $this->enregistrerHistorique($id, $userId, 'VALIDATION', 'brouillon', 'validee', 'Validation administrative de la carte');
        }

        return $res;
    }

    public function validerLot(array $ids, int $userId): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->valider((int)$id, $userId)) {
                $count++;
            }
        }
        return $count;
    }

    public function marquerImprimee(
        int $id,
        int $userId,
        string $type = 'impression',
        int $exemplaires = 1,
        bool $estReimpression = false,
        ?string $motif = null
    ): bool {
        $carte = $this->find($id);
        if (!$carte) {
            return false;
        }

        $ancienStatut = $carte['statut'];
        $nouveauStatut = in_array($ancienStatut, ['brouillon', 'validee']) ? 'imprimee' : $ancienStatut;

        $stmt = $this->pdo->prepare("UPDATE cartes SET statut = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nouveauStatut, $id]);

        // Enregistrer dans la table impressions
        $stmtImp = $this->pdo->prepare("
            INSERT INTO impressions (carte_id, user_id, type, nb_exemplaires, est_reimpression, motif)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtImp->execute([$id, $userId, $type, $exemplaires, $estReimpression ? 1 : 0, $motif]);

        // Historique
        $this->enregistrerHistorique(
            $id,
            $userId,
            $estReimpression ? 'REIMPRESSION' : 'IMPRESSION',
            $ancienStatut,
            $nouveauStatut,
            $motif ?: ($estReimpression ? 'Réimpression de la carte' : 'Impression de la carte')
        );

        return true;
    }

    public function activer(int $id, int $userId): bool
    {
        $carte = $this->find($id);
        if (!$carte) return false;

        $stmt = $this->pdo->prepare("UPDATE cartes SET statut = 'active', updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$id]);

        if ($res) {
            $this->enregistrerHistorique($id, $userId, 'ACTIVATION', $carte['statut'], 'active', 'Carte remise au bénéficiaire et activée');
        }

        return $res;
    }

    public function desactiver(int $id, int $userId, ?string $motif = null): bool
    {
        $carte = $this->find($id);
        if (!$carte) return false;

        $stmt = $this->pdo->prepare("UPDATE cartes SET statut = 'desactivee', updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$id]);

        if ($res) {
            $this->enregistrerHistorique($id, $userId, 'DESACTIVATION', $carte['statut'], 'desactivee', $motif ?: 'Désactivation de la carte (perte/vol/départ)');
        }

        return $res;
    }

    public function renouveler(int $id, int $userId, ?string $motif = null): int
    {
        $ancienne = $this->find($id);
        if (!$ancienne) {
            throw new \InvalidArgumentException("Carte initiale introuvable.");
        }

        // 1. Archiver l'ancienne carte
        $this->desactiver($id, $userId, 'Remplacée par une nouvelle carte suite à renouvellement : ' . ($motif ?: 'Renouvellement'));

        // 2. Créer la nouvelle carte
        $numeroCarte = $this->generateCardNumber((int)$ancienne['etablissement_id'], $ancienne['beneficiaire_type']);
        $jeton = bin2hex(random_bytes(16));
        $dateEmission = date('Y-m-d');
        $dateExpiration = date('Y-m-d', strtotime('+1 year'));

        $stmt = $this->pdo->prepare("
            INSERT INTO cartes (
                etablissement_id, template_id, beneficiaire_type, beneficiaire_id,
                numero_carte, jeton_verification, date_emission, date_expiration,
                statut, carte_precedente_id, created_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                'validee', ?, ?
            )
        ");

        $stmt->execute([
            $ancienne['etablissement_id'],
            $ancienne['template_id'],
            $ancienne['beneficiaire_type'],
            $ancienne['beneficiaire_id'],
            $numeroCarte,
            $jeton,
            $dateEmission,
            $dateExpiration,
            $id,
            $userId
        ]);

        $nouvelleId = (int)$this->pdo->lastInsertId();

        $this->enregistrerHistorique(
            $nouvelleId,
            $userId,
            'RENOUVELLEMENT',
            null,
            'validee',
            "Renouvellement depuis l'ancienne carte #{$ancienne['numero_carte']}. Motif: " . ($motif ?: 'Renouvellement normal')
        );

        return $nouvelleId;
    }

    public function archiver(int $id, int $userId): bool
    {
        $carte = $this->find($id);
        if (!$carte) return false;

        $stmt = $this->pdo->prepare("UPDATE cartes SET statut = 'archivee', updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$id]);

        if ($res) {
            $this->enregistrerHistorique($id, $userId, 'ARCHIVAGE', $carte['statut'], 'archivee', 'Archivage de la carte');
        }

        return $res;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE cartes SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getHistorique(int $carteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, u.nom AS user_nom, u.prenom AS user_prenom
            FROM historiques h
            LEFT JOIN users u ON h.user_id = u.id
            WHERE h.carte_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$carteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImpressions(int $carteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT imp.*, u.nom AS user_nom, u.prenom AS user_prenom
            FROM impressions imp
            LEFT JOIN users u ON imp.user_id = u.id
            WHERE imp.carte_id = ?
            ORDER BY imp.id DESC
        ");
        $stmt->execute([$carteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function enregistrerHistorique(
        int $carteId,
        ?int $userId,
        string $evenement,
        ?string $ancienStatut,
        ?string $nouveauStatut,
        ?string $commentaire = null
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO historiques (carte_id, user_id, evenement, ancien_statut, nouveau_statut, commentaire)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$carteId, $userId, $evenement, $ancienStatut, $nouveauStatut, $commentaire]);
    }

    /**
     * Liste des bénéficiaires n'ayant pas encore de carte active/en cours
     */
    public function getBeneficiairesSansCarte(int $etablissementId, string $type, ?int $groupeId = null): array
    {
        if ($type === 'eleve') {
            $sql = "
                SELECT el.id, el.nom, el.prenom, el.matricule, el.photo, cl.libelle AS groupe_nom
                FROM eleves el
                LEFT JOIN classes cl ON el.classe_id = cl.id
                WHERE el.etablissement_id = ? AND el.statut = 'actif' AND el.deleted_at IS NULL
                  AND el.id NOT IN (
                      SELECT c.beneficiaire_id FROM cartes c 
                      WHERE c.etablissement_id = ? AND c.beneficiaire_type = 'eleve' 
                        AND c.statut IN ('brouillon', 'validee', 'imprimee', 'active') 
                        AND c.deleted_at IS NULL
                  )
            ";
            $params = [$etablissementId, $etablissementId];
            if ($groupeId) {
                $sql .= " AND el.classe_id = ?";
                $params[] = $groupeId;
            }
            $sql .= " ORDER BY cl.libelle ASC, el.nom ASC, el.prenom ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($type === 'etudiant') {
            $sql = "
                SELECT et.id, et.nom, et.prenom, et.matricule, et.photo, f.libelle AS groupe_nom
                FROM etudiants et
                LEFT JOIN filieres f ON et.filiere_id = f.id
                WHERE et.etablissement_id = ? AND et.statut = 'actif' AND et.deleted_at IS NULL
                  AND et.id NOT IN (
                      SELECT c.beneficiaire_id FROM cartes c 
                      WHERE c.etablissement_id = ? AND c.beneficiaire_type = 'etudiant' 
                        AND c.statut IN ('brouillon', 'validee', 'imprimee', 'active') 
                        AND c.deleted_at IS NULL
                  )
            ";
            $params = [$etablissementId, $etablissementId];
            if ($groupeId) {
                $sql .= " AND et.filiere_id = ?";
                $params[] = $groupeId;
            }
            $sql .= " ORDER BY f.libelle ASC, et.nom ASC, et.prenom ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Personnel
            $sql = "
                SELECT p.id, p.nom, p.prenom, p.numero_professionnel AS matricule, p.photo, d.nom AS groupe_nom
                FROM personnels p
                LEFT JOIN departements d ON p.departement_id = d.id
                WHERE p.etablissement_id = ? AND p.statut = 'en_poste' AND p.deleted_at IS NULL
                  AND p.id NOT IN (
                      SELECT c.beneficiaire_id FROM cartes c 
                      WHERE c.etablissement_id = ? AND c.beneficiaire_type = 'personnel' 
                        AND c.statut IN ('brouillon', 'validee', 'imprimee', 'active') 
                        AND c.deleted_at IS NULL
                  )
            ";
            $params = [$etablissementId, $etablissementId];
            if ($groupeId) {
                $sql .= " AND p.departement_id = ?";
                $params[] = $groupeId;
            }
            $sql .= " ORDER BY d.nom ASC, p.nom ASC, p.prenom ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
