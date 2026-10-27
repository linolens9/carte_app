<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Logger;
use App\Models\Template;
use App\Models\Etablissement;

class TemplateController extends Controller
{
    private Template $templateModel;
    private Etablissement $etablissementModel;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->templateModel = new Template();
        $this->etablissementModel = new Etablissement();
    }

    public function index(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $cible = $this->request->query('cible');

        $templates = $this->templateModel->all($activeEtabId ? (int)$activeEtabId : null, $cible ?: null);
        $etablissements = $this->etablissementModel->all();

        $this->render('templates/index', [
            'title' => 'Modèles de cartes (Templates)',
            'templates' => $templates,
            'etablissements' => $etablissements,
            'cible_filter' => $cible,
            'active_etab_id' => $activeEtabId
        ]);
    }

    public function create(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $etablissements = $this->etablissementModel->all();
        $cible = $this->request->query('cible') ?: 'eleve';

        $this->render('templates/create', [
            'title' => 'Créer un modèle de carte',
            'etablissements' => $etablissements,
            'selected_etab_id' => $activeEtabId,
            'default_cible' => $cible
        ]);
    }

    public function store(array $params = []): void
    {
        $nom = trim((string)$this->request->post('nom'));
        $cible = trim((string)$this->request->post('cible'));
        $orientation = trim((string)$this->request->post('orientation', 'horizontal'));
        if (!in_array($orientation, ['horizontal', 'vertical'], true)) {
            $orientation = 'horizontal';
        }
        $etablissementId = ($this->request->post('etablissement_id') !== '' && $this->request->post('etablissement_id') !== null)
            ? (int)$this->request->post('etablissement_id')
            : null;
        $couleurPrincipale = $this->request->post('couleur_principale') ?: '#1e3a8a';
        $couleurSecondaire = $this->request->post('couleur_secondaire') ?: '#3b82f6';

        if ($nom === '' || !in_array($cible, ['eleve', 'etudiant', 'personnel'], true)) {
            Session::setFlash('danger', 'Veuillez saisir un nom valide et sélectionner le public cible.');
            $this->response->redirect('/templates/creer');
            return;
        }

        $configRecto = [
            'titre_haut' => trim((string)$this->request->post('titre_haut', 'RÉPUBLIQUE GABONAISE')),
            'sous_titre_haut' => trim((string)$this->request->post('sous_titre_haut', 'Union - Travail - Justice')),
            'titre_carte' => trim((string)$this->request->post('titre_carte', 'CARTE D\'IDENTITÉ')),
            'mention_annee' => trim((string)$this->request->post('mention_annee', 'Année 2025-2026')),
            'afficher_logo' => !empty($this->request->post('afficher_logo')),
            'afficher_armoiries' => !empty($this->request->post('afficher_armoiries')),
            'afficher_photo' => !empty($this->request->post('afficher_photo')),
            'afficher_barcode' => !empty($this->request->post('afficher_barcode')),
            'afficher_puce' => !empty($this->request->post('afficher_puce')),
        ];

        $configVerso = [
            'titre' => trim((string)$this->request->post('titre_verso', 'CONDITIONS D\'UTILISATION')),
            'texte_reglement' => trim((string)$this->request->post('texte_reglement', "Cette carte est strictement personnelle.\nEn cas de perte, merci de la rapporter à l'administration.")),
            'afficher_qr' => !empty($this->request->post('afficher_qr')),
            'afficher_signature' => !empty($this->request->post('afficher_signature')),
            'titre_signature' => trim((string)$this->request->post('titre_signature', 'Le Responsable')),
            'mention_retour' => trim((string)$this->request->post('mention_retour', 'Contact : +241 01 00 00 00'))
        ];

        $user = Auth::user();

        $id = $this->templateModel->create([
            'etablissement_id' => $etablissementId,
            'nom' => $nom,
            'cible' => $cible,
            'orientation' => $orientation,
            'couleur_principale' => $couleurPrincipale,
            'couleur_secondaire' => $couleurSecondaire,
            'config_recto' => $configRecto,
            'config_verso' => $configVerso,
            'actif' => !empty($this->request->post('actif')) ? 1 : 0,
            'created_by' => $user['id'] ?? null
        ]);

        Logger::log(Auth::id(), $etablissementId, 'template.creer', 'templates', $id, null, [
            'nom' => $nom,
            'cible' => $cible
        ], $this->request);

        Session::setFlash('success', "Le modèle « {$nom} » a été créé avec succès.");
        $this->response->redirect("/templates/{$id}");
    }

    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $template = $this->templateModel->find($id);

        if (!$template) {
            Session::setFlash('danger', 'Modèle de carte introuvable.');
            $this->response->redirect('/templates');
            return;
        }

        $this->render('templates/show', [
            'title' => 'Aperçu du modèle : ' . $template['nom'],
            'template' => $template
        ]);
    }

    public function edit(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $template = $this->templateModel->find($id);

        if (!$template) {
            Session::setFlash('danger', 'Modèle de carte introuvable.');
            $this->response->redirect('/templates');
            return;
        }

        $etablissements = $this->etablissementModel->all();

        $this->render('templates/edit', [
            'title' => 'Modifier le modèle : ' . $template['nom'],
            'template' => $template,
            'etablissements' => $etablissements
        ]);
    }

    public function update(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $template = $this->templateModel->find($id);

        if (!$template) {
            Session::setFlash('danger', 'Modèle introuvable.');
            $this->response->redirect('/templates');
            return;
        }

        $nom = trim((string)$this->request->post('nom'));
        $cible = trim((string)$this->request->post('cible'));
        $orientation = trim((string)$this->request->post('orientation', $template['orientation'] ?? 'horizontal'));
        if (!in_array($orientation, ['horizontal', 'vertical'], true)) {
            $orientation = 'horizontal';
        }

        if ($nom === '' || !in_array($cible, ['eleve', 'etudiant', 'personnel'], true)) {
            Session::setFlash('danger', 'Veuillez saisir un nom valide et sélectionner le public cible.');
            $this->response->redirect("/templates/{$id}/modifier");
            return;
        }

        $etablissementId = ($this->request->post('etablissement_id') !== '' && $this->request->post('etablissement_id') !== null)
            ? (int)$this->request->post('etablissement_id')
            : null;

        $couleurPrincipale = $this->request->post('couleur_principale') ?: ($template['couleur_principale'] ?? '#1e3a8a');
        $couleurSecondaire = $this->request->post('couleur_secondaire') ?: ($template['couleur_secondaire'] ?? '#3b82f6');

        $existingRecto = is_array($template['config_recto']) ? $template['config_recto'] : (json_decode($template['config_recto'] ?? '{}', true) ?: []);
        $existingVerso = is_array($template['config_verso']) ? $template['config_verso'] : (json_decode($template['config_verso'] ?? '{}', true) ?: []);

        $configRecto = array_merge($existingRecto, [
            'titre_haut' => trim((string)$this->request->post('titre_haut', $existingRecto['titre_haut'] ?? 'RÉPUBLIQUE GABONAISE')),
            'sous_titre_haut' => trim((string)$this->request->post('sous_titre_haut', $existingRecto['sous_titre_haut'] ?? 'Union - Travail - Justice')),
            'titre_carte' => trim((string)$this->request->post('titre_carte', $existingRecto['titre_carte'] ?? 'CARTE D\'IDENTITÉ')),
            'mention_annee' => trim((string)$this->request->post('mention_annee', $existingRecto['mention_annee'] ?? 'Année 2025-2026')),
            'afficher_logo' => !empty($this->request->post('afficher_logo')),
            'afficher_armoiries' => !empty($this->request->post('afficher_armoiries')),
            'afficher_photo' => !empty($this->request->post('afficher_photo')),
            'afficher_barcode' => !empty($this->request->post('afficher_barcode')),
            'afficher_puce' => !empty($this->request->post('afficher_puce')),
        ]);

        $configVerso = array_merge($existingVerso, [
            'titre' => trim((string)$this->request->post('titre_verso', $existingVerso['titre'] ?? 'CONDITIONS D\'UTILISATION')),
            'texte_reglement' => trim((string)$this->request->post('texte_reglement', $existingVerso['texte_reglement'] ?? '')),
            'afficher_qr' => !empty($this->request->post('afficher_qr')),
            'afficher_signature' => !empty($this->request->post('afficher_signature')),
            'titre_signature' => trim((string)$this->request->post('titre_signature', $existingVerso['titre_signature'] ?? 'Le Responsable')),
            'mention_retour' => trim((string)$this->request->post('mention_retour', $existingVerso['mention_retour'] ?? ''))
        ]);

        $updatePayload = [
            'etablissement_id' => $etablissementId,
            'nom' => $nom,
            'cible' => $cible,
            'orientation' => $orientation,
            'couleur_principale' => $couleurPrincipale,
            'couleur_secondaire' => $couleurSecondaire,
            'config_recto' => $configRecto,
            'config_verso' => $configVerso,
            'actif' => !empty($this->request->post('actif')) ? 1 : 0
        ];

        $this->templateModel->update($id, $updatePayload);

        Logger::log(Auth::id(), $etablissementId, 'template.modifier', 'templates', $id, $template, $updatePayload, $this->request);

        Session::setFlash('success', "Le modèle « {$nom} » a été mis à jour avec succès.");
        $this->response->redirect("/templates/{$id}");
    }

    public function duplicate(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = Auth::user();

        try {
            $newId = $this->templateModel->duplicate($id, null, (int)($user['id'] ?? 1));
            Session::setFlash('success', 'Modèle dupliqué avec succès. Vous pouvez maintenant le personnaliser.');
            $this->response->redirect("/templates/{$newId}/modifier");
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur lors de la duplication : ' . $e->getMessage());
            $this->response->redirect('/templates');
        }
    }

    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $template = $this->templateModel->find($id);
        $this->templateModel->delete($id);

        if ($template) {
            Logger::log(Auth::id(), $template['etablissement_id'] ?? null, 'template.supprimer', 'templates', $id, $template, null, $this->request);
        }

        Session::setFlash('info', 'Le modèle a été supprimé.');
        $this->response->redirect('/templates');
    }
}
