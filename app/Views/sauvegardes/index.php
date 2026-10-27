<?php
/**
 * Vue : Gestion des sauvegardes et restaurations de la base de données
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-database-check me-2 text-primary"></i> Sauvegardes & Restauration
        </h4>
        <p class="text-secondary mb-0 small">
            Gestion des archives de la base de données, planification automatique et restauration guidée.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalUploadBackup">
            <i class="bi bi-upload"></i>
            <span>Téléverser un dump</span>
        </button>
        <form method="POST" action="<?= $base_path ?>/sauvegardes/creer" class="d-inline">
            <?= \App\Core\Csrf::inputField() ?>
            <input type="hidden" name="compress" value="1">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>Créer une sauvegarde maintenant</span>
            </button>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 fs-4">
                <i class="bi bi-archive"></i>
            </div>
            <div>
                <div class="text-muted small">Total Sauvegardes</div>
                <div class="fs-4 fw-bold text-dark"><?= $count ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-info bg-opacity-10 text-info p-3 fs-4">
                <i class="bi bi-hdd-network"></i>
            </div>
            <div>
                <div class="text-muted small">Espace Archives</div>
                <div class="fs-4 fw-bold text-dark"><?= $totalSizeFormatted ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 fs-4">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="text-muted small">Dernière Sauvegarde</div>
                <div class="fs-6 fw-bold text-dark">
                    <?= $latest ? date('d/m/Y H:i', strtotime($latest['created_at'])) : 'Aucune' ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 fs-4">
                <i class="bi bi-pie-chart"></i>
            </div>
            <div>
                <div class="text-muted small">Espace Disque Libre</div>
                <div class="fs-4 fw-bold text-dark"><?= $diskFreeFormatted ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des sauvegardes -->
<div class="card-custom p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Historique des sauvegardes</h6>
        <span class="badge bg-light text-secondary border">Répertoire : storage/backups/ (Sécurisé)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fichier</th>
                    <th>Type</th>
                    <th>Taille</th>
                    <th>Statut</th>
                    <th>Durée</th>
                    <th>Créateur</th>
                    <th>Date & Heure</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sauvegardes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Aucune sauvegarde disponible. Cliquez sur "Créer une sauvegarde maintenant".
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sauvegardes as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-zip text-primary fs-5"></i>
                                    <div>
                                        <span class="fw-semibold font-monospace small d-block"><?= htmlspecialchars($s['nom_fichier']) ?></span>
                                        <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars(substr($s['tables_incluses'] ?? '', 0, 50)) ?>...</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $s['type'] === 'automatique' ? 'bg-secondary' : 'bg-primary' ?>">
                                    <?= ucfirst($s['type']) ?>
                                </span>
                            </td>
                            <td class="font-monospace small">
                                <?= number_format($s['taille_octets'] / 1024, 2, ',', ' ') ?> Ko
                            </td>
                            <td>
                                <?php if ($s['statut'] === 'reussie'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i> Réussie
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-x-circle me-1"></i> Échec
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted font-monospace">
                                <?= $s['duree_secondes'] ?>s
                            </td>
                            <td class="small">
                                <?= !empty($s['auteur_nom']) ? htmlspecialchars($s['auteur_prenom'] . ' ' . $s['auteur_nom']) : '<span class="text-muted">Système (Cron)</span>' ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y H:i:s', strtotime($s['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= $base_path ?>/sauvegardes/<?= $s['id'] ?>/telecharger" class="btn btn-outline-secondary" title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-warning btn-trigger-restore" 
                                            data-id="<?= $s['id'] ?>" 
                                            data-filename="<?= htmlspecialchars($s['nom_fichier']) ?>" 
                                            title="Restaurer cette version" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalRestore">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <form method="POST" action="<?= $base_path ?>/sauvegardes/<?= $s['id'] ?>/supprimer" class="d-inline" onsubmit="return confirm('Supprimer définitivement cette archive ?');">
                                        <?= \App\Core\Csrf::inputField() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Guide de planification automatique CRON -->
<div class="card-custom p-4 bg-light border">
    <div class="d-flex align-items-start gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2">
            <i class="bi bi-terminal fs-5"></i>
        </div>
        <div class="flex-1">
            <h6 class="fw-bold mb-1">Automatisation des sauvegardes (Tâche planifiée)</h6>
            <p class="text-secondary small mb-2">
                Pour exécuter la sauvegarde automatique quotidienne de la base de données la nuit à 02h00, configurez votre planificateur de tâches ou crontab avec la commande suivante :
            </p>
            <div class="p-2 bg-dark text-white rounded font-monospace small d-flex justify-content-between align-items-center">
                <code>0 2 * * * php <?= addslashes(dirname(dirname(dirname(__DIR__)))) ?>/bin/cron_backup.php &gt;&gt; /var/log/carte_app_backup.log 2&gt;&amp;1</code>
                <button class="btn btn-sm btn-outline-light py-0" onclick="navigator.clipboard.writeText('0 2 * * * php <?= addslashes(dirname(dirname(dirname(__DIR__)))) ?>/bin/cron_backup.php'); alert('Commande copiée !');">
                    <i class="bi bi-clipboard me-1"></i> Copier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Restauration -->
<div class="modal fade" id="modalRestore" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formRestore" action="">
            <?= \App\Core\Csrf::inputField() ?>
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmation de Restauration
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-exclamation-octagon-fill fs-5 mt-1"></i>
                        <div class="small">
                            <strong>ATTENTION : ACTION DESTRUCTIVE !</strong><br>
                            La restauration va écraser l'ensemble des tables et données actuelles par celles contenues dans l'archive sélectionnée :
                            <div class="font-monospace fw-bold text-dark mt-1" id="restoreTargetFilename">...</div>
                        </div>
                    </div>

                    <p class="small text-muted mb-3">
                        Toutes les modifications créées après cette sauvegarde seront définitivement remplacées. Pour confirmer, veuillez saisir exactement le mot <strong class="text-danger">RESTAURER</strong> ci-dessous :
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Mot de confirmation :</label>
                        <input type="text" name="confirmation" class="form-control font-monospace" placeholder="RESTAURER" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Écraser et Restaurer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Téléversement Dump -->
<div class="modal fade" id="modalUploadBackup" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= $base_path ?>/sauvegardes/restaurer-fichier" enctype="multipart/form-data">
            <?= \App\Core\Csrf::inputField() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-upload me-2 text-primary"></i> Téléverser et restaurer un dump SQL
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Sélectionnez un fichier SQL brut (<code>.sql</code>) ou compressé avec Gzip (<code>.sql.gz</code>) exporté depuis Carte App.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Fichier archive SQL :</label>
                        <input type="file" name="backup_file" class="form-control" accept=".sql,.gz" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Confirmation :</label>
                        <p class="small text-muted mb-1">Saisissez <strong class="text-danger">RESTAURER</strong> pour confirmer :</p>
                        <input type="text" name="confirmation" class="form-control font-monospace" placeholder="RESTAURER" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Téléverser & Restaurer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-trigger-restore').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const filename = btn.getAttribute('data-filename');
            document.getElementById('restoreTargetFilename').textContent = filename;
            document.getElementById('formRestore').action = '<?= $base_path ?>/sauvegardes/' + id + '/restaurer';
        });
    });
</script>
