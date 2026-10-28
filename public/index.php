<?php
declare(strict_types=1);

/**
 * Point d'entrée unique de l'application web
 */

// Affichage des erreurs en dev
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoloading
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Configuration
$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Africa/Libreville');

// Base de données
\App\Core\Database::init($config['db']);

// Session
\App\Core\Session::start((int)($config['app']['session_lifetime'] ?? 1800));

// Request & Response
$request = new \App\Core\Request();
$response = new \App\Core\Response();
$router = new \App\Core\Router($request, $response);

// ============================================================
// DÉFINITION DES ROUTES
// ============================================================

// 1. Authentification & Mot de passe
$router->get('/', [\App\Controllers\AuthController::class, 'showLogin'], ['guest']);
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login'], ['guest']);
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout']);

$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword'], ['guest']);
$router->post('/forgot-password', [\App\Controllers\AuthController::class, 'sendResetLink'], ['guest']);
$router->get('/reset-password/{token}', [\App\Controllers\AuthController::class, 'showResetPassword'], ['guest']);
$router->post('/reset-password', [\App\Controllers\AuthController::class, 'resetPassword'], ['guest']);

$router->get('/profil', [\App\Controllers\ProfileController::class, 'index'], ['auth']);
$router->post('/profil', [\App\Controllers\ProfileController::class, 'update'], ['auth']);
$router->get('/change-password', [\App\Controllers\ProfileController::class, 'index'], ['auth']);
$router->post('/change-password', [\App\Controllers\ProfileController::class, 'update'], ['auth']);

// 2. Tableau de bord
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], ['auth']);
$router->post('/dashboard/switch-etablissement', [\App\Controllers\DashboardController::class, 'switchEtablissement'], ['auth']);

// 3. Établissements
$router->get('/etablissements', [\App\Controllers\EtablissementController::class, 'index'], ['auth', 'permission:etablissement.consulter']);
$router->get('/etablissements/creer', [\App\Controllers\EtablissementController::class, 'create'], ['auth', 'permission:etablissement.creer']);
$router->post('/etablissements/creer', [\App\Controllers\EtablissementController::class, 'store'], ['auth', 'permission:etablissement.creer']);
$router->get('/etablissements/{id}', [\App\Controllers\EtablissementController::class, 'show'], ['auth', 'permission:etablissement.consulter']);
$router->get('/etablissements/{id}/modifier', [\App\Controllers\EtablissementController::class, 'edit'], ['auth', 'permission:etablissement.modifier']);
$router->post('/etablissements/{id}/modifier', [\App\Controllers\EtablissementController::class, 'update'], ['auth', 'permission:etablissement.modifier']);
$router->post('/etablissements/{id}/supprimer', [\App\Controllers\EtablissementController::class, 'delete'], ['auth', 'permission:etablissement.supprimer']);
$router->post('/etablissements/{id}/restaurer', [\App\Controllers\EtablissementController::class, 'restore'], ['auth', 'permission:etablissement.modifier']);

// Structure interne de l'établissement
$router->post('/etablissements/{id}/departements/ajouter', [\App\Controllers\EtablissementController::class, 'addDepartement'], ['auth', 'permission:etablissement.modifier']);
$router->post('/etablissements/{id}/filieres/ajouter', [\App\Controllers\EtablissementController::class, 'addFiliere'], ['auth', 'permission:etablissement.modifier']);
$router->post('/etablissements/{id}/classes/ajouter', [\App\Controllers\EtablissementController::class, 'addClasse'], ['auth', 'permission:etablissement.modifier']);

// 3.1 CRUD Classes
$router->get('/classes', [\App\Controllers\ClasseController::class, 'index'], ['auth']);
$router->post('/classes/creer', [\App\Controllers\ClasseController::class, 'store'], ['auth']);
$router->post('/classes/{id}/modifier', [\App\Controllers\ClasseController::class, 'update'], ['auth']);
$router->post('/classes/{id}/supprimer', [\App\Controllers\ClasseController::class, 'delete'], ['auth']);

// 3.2 CRUD Filières
$router->get('/filieres', [\App\Controllers\FiliereController::class, 'index'], ['auth']);
$router->post('/filieres/creer', [\App\Controllers\FiliereController::class, 'store'], ['auth']);
$router->post('/filieres/{id}/modifier', [\App\Controllers\FiliereController::class, 'update'], ['auth']);
$router->post('/filieres/{id}/supprimer', [\App\Controllers\FiliereController::class, 'delete'], ['auth']);

// 3.3 CRUD Départements & Services
$router->get('/departements', [\App\Controllers\DepartementController::class, 'index'], ['auth']);
$router->post('/departements/creer', [\App\Controllers\DepartementController::class, 'store'], ['auth']);
$router->post('/departements/{id}/modifier', [\App\Controllers\DepartementController::class, 'update'], ['auth']);
$router->post('/departements/{id}/supprimer', [\App\Controllers\DepartementController::class, 'delete'], ['auth']);

// 4. Utilisateurs
$router->get('/users', [\App\Controllers\UserController::class, 'index'], ['auth', 'permission:utilisateur.consulter']);
$router->get('/users/creer', [\App\Controllers\UserController::class, 'create'], ['auth', 'permission:utilisateur.creer']);
$router->post('/users/creer', [\App\Controllers\UserController::class, 'store'], ['auth', 'permission:utilisateur.creer']);
$router->get('/users/{id}/modifier', [\App\Controllers\UserController::class, 'edit'], ['auth', 'permission:utilisateur.modifier']);
$router->post('/users/{id}/modifier', [\App\Controllers\UserController::class, 'update'], ['auth', 'permission:utilisateur.modifier']);
$router->post('/users/{id}/toggle-statut', [\App\Controllers\UserController::class, 'toggleStatus'], ['auth', 'permission:utilisateur.modifier']);
$router->post('/users/{id}/supprimer', [\App\Controllers\UserController::class, 'delete'], ['auth', 'permission:utilisateur.supprimer']);

// 5. Rôles & Permissions
$router->get('/roles', [\App\Controllers\RoleController::class, 'index'], ['auth', 'permission:role.gerer']);
$router->get('/roles/{id}/modifier', [\App\Controllers\RoleController::class, 'edit'], ['auth', 'permission:role.gerer']);
$router->post('/roles/{id}/modifier', [\App\Controllers\RoleController::class, 'update'], ['auth', 'permission:role.gerer']);

// 8. Module Élèves (Sprint 2)
$router->get('/eleves', [\App\Controllers\EleveController::class, 'index'], ['auth', 'permission:eleve.consulter']);
$router->get('/eleves/creer', [\App\Controllers\EleveController::class, 'create'], ['auth', 'permission:eleve.creer']);
$router->post('/eleves/creer', [\App\Controllers\EleveController::class, 'store'], ['auth', 'permission:eleve.creer']);
$router->get('/eleves/importer', [\App\Controllers\EleveController::class, 'importForm'], ['auth', 'permission:eleve.importer']);
$router->post('/eleves/importer', [\App\Controllers\EleveController::class, 'importProcess'], ['auth', 'permission:eleve.importer']);
$router->get('/eleves/modele-csv', [\App\Controllers\EleveController::class, 'downloadTemplate'], ['auth', 'permission:eleve.importer']);
$router->post('/eleves/promouvoir', [\App\Controllers\EleveController::class, 'promoteBatch'], ['auth', 'permission:eleve.modifier']);
$router->get('/eleves/{id}', [\App\Controllers\EleveController::class, 'show'], ['auth', 'permission:eleve.consulter']);
$router->get('/eleves/{id}/modifier', [\App\Controllers\EleveController::class, 'edit'], ['auth', 'permission:eleve.modifier']);
$router->post('/eleves/{id}/modifier', [\App\Controllers\EleveController::class, 'update'], ['auth', 'permission:eleve.modifier']);
$router->post('/eleves/{id}/supprimer', [\App\Controllers\EleveController::class, 'delete'], ['auth', 'permission:eleve.supprimer']);

// 9. Module Étudiants (Sprint 2)
$router->get('/etudiants', [\App\Controllers\EtudiantController::class, 'index'], ['auth', 'permission:etudiant.consulter']);
$router->get('/etudiants/creer', [\App\Controllers\EtudiantController::class, 'create'], ['auth', 'permission:etudiant.creer']);
$router->post('/etudiants/creer', [\App\Controllers\EtudiantController::class, 'store'], ['auth', 'permission:etudiant.creer']);
$router->get('/etudiants/importer', [\App\Controllers\EtudiantController::class, 'importForm'], ['auth', 'permission:etudiant.importer']);
$router->post('/etudiants/importer', [\App\Controllers\EtudiantController::class, 'importProcess'], ['auth', 'permission:etudiant.importer']);
$router->get('/etudiants/modele-csv', [\App\Controllers\EtudiantController::class, 'downloadTemplate'], ['auth', 'permission:etudiant.importer']);
$router->post('/etudiants/promouvoir', [\App\Controllers\EtudiantController::class, 'promoteBatch'], ['auth', 'permission:etudiant.modifier']);
$router->get('/etudiants/ajax-filieres', [\App\Controllers\EtudiantController::class, 'ajaxGetFilieres'], ['auth']);
$router->get('/etudiants/{id}', [\App\Controllers\EtudiantController::class, 'show'], ['auth', 'permission:etudiant.consulter']);
$router->get('/etudiants/{id}/modifier', [\App\Controllers\EtudiantController::class, 'edit'], ['auth', 'permission:etudiant.modifier']);
$router->post('/etudiants/{id}/modifier', [\App\Controllers\EtudiantController::class, 'update'], ['auth', 'permission:etudiant.modifier']);
$router->post('/etudiants/{id}/supprimer', [\App\Controllers\EtudiantController::class, 'delete'], ['auth', 'permission:etudiant.supprimer']);

// 10. Module Personnel (Sprint 2)
$router->get('/personnels', [\App\Controllers\PersonnelController::class, 'index'], ['auth', 'permission:personnel.consulter']);
$router->get('/personnels/creer', [\App\Controllers\PersonnelController::class, 'create'], ['auth', 'permission:personnel.creer']);
$router->post('/personnels/creer', [\App\Controllers\PersonnelController::class, 'store'], ['auth', 'permission:personnel.creer']);
$router->get('/personnels/importer', [\App\Controllers\PersonnelController::class, 'importForm'], ['auth', 'permission:personnel.importer']);
$router->post('/personnels/importer', [\App\Controllers\PersonnelController::class, 'importProcess'], ['auth', 'permission:personnel.importer']);
$router->get('/personnels/modele-csv', [\App\Controllers\PersonnelController::class, 'downloadTemplate'], ['auth', 'permission:personnel.importer']);
$router->get('/personnels/ajax-departements', [\App\Controllers\PersonnelController::class, 'ajaxGetDepartements'], ['auth']);
$router->get('/personnels/{id}', [\App\Controllers\PersonnelController::class, 'show'], ['auth', 'permission:personnel.consulter']);
$router->get('/personnels/{id}/modifier', [\App\Controllers\PersonnelController::class, 'edit'], ['auth', 'permission:personnel.modifier']);
$router->post('/personnels/{id}/modifier', [\App\Controllers\PersonnelController::class, 'update'], ['auth', 'permission:personnel.modifier']);
$router->post('/personnels/{id}/supprimer', [\App\Controllers\PersonnelController::class, 'delete'], ['auth', 'permission:personnel.supprimer']);

// Module Templates (Sprint 3)
$router->get('/templates', [\App\Controllers\TemplateController::class, 'index'], ['auth', 'permission:template.consulter']);
$router->get('/templates/creer', [\App\Controllers\TemplateController::class, 'create'], ['auth', 'permission:template.creer']);
$router->post('/templates/creer', [\App\Controllers\TemplateController::class, 'store'], ['auth', 'permission:template.creer']);
$router->get('/templates/{id}', [\App\Controllers\TemplateController::class, 'show'], ['auth', 'permission:template.consulter']);
$router->get('/templates/{id}/modifier', [\App\Controllers\TemplateController::class, 'edit'], ['auth', 'permission:template.modifier']);
$router->post('/templates/{id}/modifier', [\App\Controllers\TemplateController::class, 'update'], ['auth', 'permission:template.modifier']);
$router->get('/templates/{id}/dupliquer', [\App\Controllers\TemplateController::class, 'duplicate'], ['auth', 'permission:template.creer']);
$router->post('/templates/{id}/supprimer', [\App\Controllers\TemplateController::class, 'delete'], ['auth', 'permission:template.supprimer']);

// Module Cartes (Sprint 3)
$router->get('/cartes', [\App\Controllers\CarteController::class, 'index'], ['auth', 'permission:carte.consulter']);
$router->get('/cartes/generer', [\App\Controllers\CarteController::class, 'create'], ['auth', 'permission:carte.creer']);
$router->post('/cartes/generer', [\App\Controllers\CarteController::class, 'store'], ['auth', 'permission:carte.creer']);
$router->get('/cartes/generer-lot', [\App\Controllers\CarteController::class, 'generateBatchForm'], ['auth', 'permission:carte.creer']);
$router->post('/cartes/generer-lot', [\App\Controllers\CarteController::class, 'generateBatchProcess'], ['auth', 'permission:carte.creer']);
$router->get('/cartes/ajax-beneficiaires', [\App\Controllers\CarteController::class, 'ajaxGetBeneficiairesSansCarte'], ['auth']);
$router->post('/cartes/valider-lot', [\App\Controllers\CarteController::class, 'validateBatch'], ['auth', 'permission:carte.valider']);
$router->get('/cartes/{id}', [\App\Controllers\CarteController::class, 'show'], ['auth', 'permission:carte.consulter']);
$router->get('/cartes/{id}/valider', [\App\Controllers\CarteController::class, 'validateCard'], ['auth', 'permission:carte.valider']);
$router->get('/cartes/{id}/activer', [\App\Controllers\CarteController::class, 'activate'], ['auth', 'permission:carte.valider']);
$router->post('/cartes/{id}/desactiver', [\App\Controllers\CarteController::class, 'deactivate'], ['auth', 'permission:carte.desactiver']);
$router->get('/cartes/{id}/renouveler', [\App\Controllers\CarteController::class, 'renew'], ['auth', 'permission:carte.renouveler']);
$router->get('/cartes/{id}/archiver', [\App\Controllers\CarteController::class, 'archive'], ['auth', 'permission:carte.archiver']);
$router->post('/cartes/{id}/supprimer', [\App\Controllers\CarteController::class, 'delete'], ['auth', 'permission:carte.archiver']);

// Module Impression & Planches A4 (Sprint 3)
$router->get('/impressions', [\App\Controllers\ImpressionController::class, 'index'], ['auth', 'permission:carte.imprimer']);
$router->get('/impressions/carte/{id}', [\App\Controllers\ImpressionController::class, 'printSingle'], ['auth', 'permission:carte.imprimer']);
$router->post('/impressions/planche-a4', [\App\Controllers\ImpressionController::class, 'printA4Sheet'], ['auth', 'permission:carte.imprimer']);
$router->get('/impressions/planche-a4', [\App\Controllers\ImpressionController::class, 'printA4Sheet'], ['auth', 'permission:carte.imprimer']);

// Vérification Publique via QR Code (Sans Authentification)
$router->get('/verifier', [\App\Controllers\VerificationController::class, 'verify'], []);
$router->get('/verifier/{jeton}', [\App\Controllers\VerificationController::class, 'verify'], []);

// Module Statistiques (Sprint 4)
$router->get('/statistiques', [\App\Controllers\StatistiqueController::class, 'index'], ['auth', 'permission:statistique.consulter']);
$router->get('/statistiques/export/csv', [\App\Controllers\StatistiqueController::class, 'exportCsv'], ['auth', 'permission:statistique.consulter']);

// Module Sauvegardes (Sprint 4)
$router->get('/sauvegardes', [\App\Controllers\SauvegardeController::class, 'index'], ['auth', 'permission:sauvegarde.gerer']);
$router->post('/sauvegardes/creer', [\App\Controllers\SauvegardeController::class, 'create'], ['auth', 'permission:sauvegarde.gerer']);
$router->get('/sauvegardes/{id}/telecharger', [\App\Controllers\SauvegardeController::class, 'download'], ['auth', 'permission:sauvegarde.gerer']);
$router->post('/sauvegardes/{id}/restaurer', [\App\Controllers\SauvegardeController::class, 'restore'], ['auth', 'permission:sauvegarde.gerer']);
$router->post('/sauvegardes/restaurer-fichier', [\App\Controllers\SauvegardeController::class, 'restoreUpload'], ['auth', 'permission:sauvegarde.gerer']);
$router->post('/sauvegardes/{id}/supprimer', [\App\Controllers\SauvegardeController::class, 'delete'], ['auth', 'permission:sauvegarde.gerer']);

// Module Journal d'Audit & Logs (Sprint 4)
$router->get('/logs', [\App\Controllers\LogController::class, 'index'], ['auth', 'permission:log.consulter']);
$router->get('/logs/export/csv', [\App\Controllers\LogController::class, 'exportCsv'], ['auth', 'permission:log.consulter']);

// Module Paramètres (Sprint 4)
$router->get('/parametres', [\App\Controllers\ParametreController::class, 'index'], ['auth', 'permission:parametre.gerer']);
$router->post('/parametres', [\App\Controllers\ParametreController::class, 'update'], ['auth', 'permission:parametre.gerer']);

// Dispatch
try {
    $router->dispatch();
} catch (\Throwable $e) {
    if (str_contains($e->getMessage(), 'base de données') || str_contains($e->getMessage(), 'Access denied') || str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'SQLSTATE')) {
        $base = $config['app']['base_path'] ?? '';
        $installUrl = rtrim($base, '/') . '/install.php';
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Configuration requise - MBOLO Carte App</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"><style>body{font-family:\'Plus Jakarta Sans\',sans-serif;background:#0f172a;color:#f8fafc;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}</style></head><body>';
        echo '<div class="card border-0 shadow-lg text-dark p-4 p-md-5 rounded-4" style="max-width:580px;background:#ffffff;">';
        echo '<div class="text-center mb-3"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle p-2 px-3 rounded-pill fw-bold fs-6">Configuration requise</span></div>';
        echo '<h3 class="fw-bold text-center mb-2">Base de données non connectée</h3>';
        echo '<p class="text-muted text-center mb-4">L\'application ne parvient pas encore à se connecter à votre base de données MySQL. Lancez l\'assistant d\'installation pour configurer vos accès InfinityFree.</p>';
        echo '<div class="alert alert-danger small mb-4"><code>' . htmlspecialchars($e->getMessage()) . '</code></div>';
        echo '<div class="d-grid gap-2"><a href="' . htmlspecialchars($installUrl) . '" class="btn btn-primary btn-lg rounded-pill fw-semibold py-3">Lancer l\'assistant d\'installation &rarr;</a></div>';
        echo '</div></body></html>';
        exit;
    }
    throw $e;
}

