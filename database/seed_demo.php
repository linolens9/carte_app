<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require dirname(__DIR__) . '/config/config.php';
\App\Core\Database::init($config['db']);
$db = \App\Core\Database::getInstance();

echo "Initialisation du seed de démonstration...\n";

$count = (int)$db->query("SELECT COUNT(*) FROM etablissements")->fetchColumn();
if ($count === 0) {
    $db->exec("
        INSERT INTO etablissements (code, nom, categorie, type, adresse, ville, pays, telephone, email, devise, couleur_principale, annee_active, statut) VALUES
        ('LNLM', 'Lycée National Léon Mba', 'scolaire', 'lycee', 'Boulevard Triomphal', 'Libreville', 'Gabon', '+241 11 76 00 01', 'contact@lyceeleonmba.ga', 'Discipline - Travail - Succès', '#1e40af', '2026-2027', 'actif'),
        ('UOB', 'Université Omar Bongo', 'scolaire', 'universite', 'Boulevard Léon Mba', 'Libreville', 'Gabon', '+241 11 73 25 56', 'info@uob.ga', 'Scientia - Virtus - Civitas', '#059669', '2026-2027', 'actif'),
        ('ANINF', 'Agence Nationale des Infrastructures Numériques', 'professionnel', 'administration', 'Immeuble Rénovation', 'Libreville', 'Gabon', '+241 11 77 88 99', 'contact@aninf.ga', 'L\'innovation au service du citoyen', '#d97706', '2026', 'actif');
    ");
    echo "✓ 3 établissements créés\n";

    $lnlmId = (int)$db->query("SELECT id FROM etablissements WHERE code = 'LNLM'")->fetchColumn();
    $uobId = (int)$db->query("SELECT id FROM etablissements WHERE code = 'UOB'")->fetchColumn();
    $aninfId = (int)$db->query("SELECT id FROM etablissements WHERE code = 'ANINF'")->fetchColumn();

    // Filières LNLM
    $db->exec("
        INSERT INTO filieres (etablissement_id, code, libelle) VALUES
        ($lnlmId, 'S_A1', 'Série Littéraire A1'),
        ($lnlmId, 'S_B', 'Série Économique B'),
        ($lnlmId, 'S_C', 'Série Scientifique C'),
        ($lnlmId, 'S_D', 'Série Scientifique D');
    ");
    echo "✓ 4 filières LNLM créées\n";

    $filiereD = (int)$db->query("SELECT id FROM filieres WHERE etablissement_id = $lnlmId AND code = 'S_D'")->fetchColumn();
    $db->exec("
        INSERT INTO classes (etablissement_id, filiere_id, libelle, niveau, annee_scolaire, effectif_max) VALUES
        ($lnlmId, $filiereD, 'Terminale D1', 'Terminale', '2026-2027', 45),
        ($lnlmId, $filiereD, '1ère D2', '1ère', '2026-2027', 48),
        ($lnlmId, NULL, '2nde LE1', '2nde', '2026-2027', 50);
    ");
    echo "✓ 3 classes LNLM créées\n";

    // Départements UOB
    $db->exec("
        INSERT INTO departements (etablissement_id, code, nom) VALUES
        ($uobId, 'FLSH', 'Faculté des Lettres et Sciences Humaines'),
        ($uobId, 'FDSE', 'Faculté de Droit et Sciences Économiques');
    ");
    echo "✓ 2 départements UOB créés\n";

    // Départements ANINF
    $db->exec("
        INSERT INTO departements (etablissement_id, code, nom) VALUES
        ($aninfId, 'DG', 'Direction Générale'),
        ($aninfId, 'DSI', 'Direction des Systèmes d\'Information'),
        ($aninfId, 'DINFRAS', 'Direction des Infrastructures');
    ");
    echo "✓ 3 départements ANINF créés\n";
} else {
    echo "Des établissements existent déjà ($count trouvés).\n";
}

echo "Seed terminé avec succès !\n";
