<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Sauvegarde;
use App\Services\BackupService;
use App\Services\LogService;

class SauvegardeController extends Controller
{
    private Sauvegarde $model;
    private BackupService $service;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->model = new Sauvegarde();
        $this->service = new BackupService();
    }

    /**
     * Liste des sauvegardes et état du système
     */
    public function index(array $params = []): void
    {
        $sauvegardes = $this->model->all(100);
        $latest = $this->model->latest();
        $totalSize = $this->model->totalSize();
        $count = $this->model->count();

        // Espace libre sur le disque
        $diskFree = @disk_free_space($this->service->getBackupDir());
        $diskFreeFormatted = $diskFree !== false ? $this->formatBytes((int)$diskFree) : 'N/A';

        $this->render('sauvegardes/index', [
            'sauvegardes' => $sauvegardes,
            'latest' => $latest,
            'totalSizeFormatted' => $this->formatBytes($totalSize),
            'count' => $count,
            'diskFreeFormatted' => $diskFreeFormatted,
            'backupDir' => $this->service->getBackupDir(),
        ]);
    }

    /**
     * Déclenchement d'une sauvegarde manuelle
     */
    public function create(array $params = []): void
    {
        $compress = $this->request->post('compress', '1') === '1';
        $res = $this->service->createBackup('manuelle', Auth::id(), $compress);

        if ($res['success']) {
            Session::flash('success', "Sauvegarde générée avec succès : {$res['filename']} (" . $this->formatBytes($res['size']) . " en {$res['duration']}s).");
        } else {
            Session::flash('error', "Échec de la sauvegarde : " . ($res['error'] ?? 'Erreur inconnue'));
        }

        $this->response->redirect('/sauvegardes');
    }

    /**
     * Téléchargement sécurisé d'un fichier de sauvegarde
     */
    public function download(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $sauvegarde = $this->model->find($id);

        if (!$sauvegarde) {
            Session::flash('error', "Sauvegarde introuvable.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $filePath = dirname(dirname(__DIR__)) . '/' . ltrim($sauvegarde['chemin'], '/');
        if (!file_exists($filePath)) {
            Session::flash('error', "Le fichier physique est introuvable sur le serveur.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        LogService::log(
            'sauvegarde.telechargee',
            'sauvegardes',
            "Téléchargement de la sauvegarde {$sauvegarde['nom_fichier']}",
            null,
            $id,
            Auth::id()
        );

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Restauration de la base depuis une sauvegarde existante
     */
    public function restore(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $sauvegarde = $this->model->find($id);

        if (!$sauvegarde) {
            Session::flash('error', "Sauvegarde introuvable.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $confirmation = trim($this->request->post('confirmation', ''));
        if ($confirmation !== 'RESTAURER') {
            Session::flash('error', "Confirmation invalide. Vous devez taper 'RESTAURER' en majuscules pour confirmer.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $filePath = dirname(dirname(__DIR__)) . '/' . ltrim($sauvegarde['chemin'], '/');
        $res = $this->service->restoreBackup($filePath, Auth::id());

        if ($res['success']) {
            Session::flash('success', "Base de données restaurée avec succès depuis {$sauvegarde['nom_fichier']} ({$res['queries_count']} requêtes en {$res['duration']}s).");
        } else {
            Session::flash('error', "Erreur lors de la restauration : " . ($res['error'] ?? 'Erreur inconnue'));
        }

        $this->response->redirect('/sauvegardes');
    }

    /**
     * Restauration depuis un fichier uploadé
     */
    public function restoreUpload(array $params = []): void
    {
        $confirmation = trim($this->request->post('confirmation', ''));
        if ($confirmation !== 'RESTAURER') {
            Session::flash('error', "Confirmation invalide. Vous devez taper 'RESTAURER' pour confirmer l'écrasement de la base.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $file = $_FILES['backup_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', "Erreur lors du téléversement du fichier.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql' && !str_ends_with(strtolower($file['name']), '.sql.gz')) {
            Session::flash('error', "Format de fichier non supporté. Veuillez fournir un fichier .sql ou .sql.gz");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $dest = $this->service->getBackupDir() . '/upload_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $file['name']);
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', "Impossible de déplacer le fichier téléversé.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $res = $this->service->restoreBackup($dest, Auth::id());
        @unlink($dest);

        if ($res['success']) {
            Session::flash('success', "Base de données restaurée avec succès depuis le fichier téléversé ({$res['queries_count']} requêtes en {$res['duration']}s).");
        } else {
            Session::flash('error', "Erreur lors de la restauration : " . ($res['error'] ?? 'Erreur inconnue'));
        }

        $this->response->redirect('/sauvegardes');
    }

    /**
     * Suppression d'une sauvegarde
     */
    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $sauvegarde = $this->model->find($id);

        if (!$sauvegarde) {
            Session::flash('error', "Sauvegarde introuvable.");
            $this->response->redirect('/sauvegardes');
            return;
        }

        $filePath = dirname(dirname(__DIR__)) . '/' . ltrim($sauvegarde['chemin'], '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $this->model->delete($id);

        LogService::log(
            'sauvegarde.supprimee',
            'sauvegardes',
            "Suppression de la sauvegarde {$sauvegarde['nom_fichier']}",
            null,
            $id,
            Auth::id()
        );

        Session::flash('success', "Sauvegarde supprimée avec succès.");
        $this->response->redirect('/sauvegardes');
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
