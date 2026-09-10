<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_machines_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('machines_consulter');

$titre_page = 'Machines — Usine';

// POST: créer / modifier machine
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('machines_gerer');
    csrf_guard('machines.php');
    $action_post = $_POST['action'] ?? 'creer';
    try {
        if ($action_post === 'creer') {
            if (empty($_POST['nom'])) throw new RuntimeException('Nom requis.');
            $id = db_machine_insert($pdo, [
                'reference' => trim($_POST['reference'] ?? ''),
                'nom' => trim($_POST['nom']),
                'type' => trim($_POST['type'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'actif' => 1,
            ]);
            suivre_activite('MACHINE_CREEE', "Machine #$id: {$_POST['nom']}");
            flash_success('Machine créée.');
        } elseif ($action_post === 'modifier') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID invalide.');
            db_machine_update($pdo, $id, [
                'nom' => trim($_POST['nom'] ?? ''),
                'type' => trim($_POST['type'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'actif' => isset($_POST['actif']) ? (int)$_POST['actif'] : 1,
            ]);
            suivre_activite('MACHINE_MODIFIEE', "Machine #$id modifiée");
            flash_success('Machine modifiée.');
        } elseif ($action_post === 'demarrer') {
            $id = (int)($_POST['id'] ?? 0);
            $production_id = !empty($_POST['production_id']) ? (int)$_POST['production_id'] : null;
            db_machine_demarrer($pdo, $id, $production_id);
            $machine = db_machine_get($pdo, $id);
            try { db_notif_machine($pdo, $machine['type'] ?? 'Machine', $machine['nom'], 'démarrée'); } catch (Throwable $ignored) {}
            suivre_activite('MACHINE_DEMARREE', "Machine #$id démarrée");
            flash_success('Machine démarrée.');
        } elseif ($action_post === 'arreter') {
            $id = (int)($_POST['id'] ?? 0);
            $motif = !empty($_POST['motif']) ? trim($_POST['motif']) : null;
            db_machine_arreter($pdo, $id, $motif);
            $machine = db_machine_get($pdo, $id);
            // Mapper le motif vers l'action de notification
            $notif_action = match(true) {
                stripos($motif ?? '', 'panne') !== false => 'en panne',
                stripos($motif ?? '', 'maintenance') !== false => 'en maintenance',
                default => 'arrêtée',
            };
            try { db_notif_machine($pdo, $machine['type'] ?? 'Machine', $machine['nom'], $notif_action, $motif ? "Motif : $motif" : null); } catch (Throwable $ignored) {}
            suivre_activite('MACHINE_ARRETEE', "Machine #$id $notif_action");
            flash_success('Machine ' . $notif_action . '.');
        } elseif ($action_post === 'etat') {
            $id = (int)($_POST['id'] ?? 0);
            $etat = $_POST['etat'] ?? 'ARRETEE';
            $motif = !empty($_POST['motif']) ? trim($_POST['motif']) : null;
            db_machine_set_etat($pdo, $id, $etat, $motif);
            suivre_activite('MACHINE_ETAT', "Machine #$id → $etat");
            flash_success('État mis à jour.');
        } elseif ($action_post === 'config_notif') {
            $user_ids = $_POST['notif_utilisateurs'] ?? [];
            usine_notif_machines_set($pdo, $user_ids);
            flash_success('Configuration des notifications mise à jour.');
        }
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('machines.php');
}

$machines = db_machines_list($pdo, false);
$productions_en_cours = db_productions_list($pdo, 'EN_COURS', 20);
$users_all = db_users_list($pdo);
$notif_user_ids = usine_notif_machines_get($pdo);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-gear-wide-connected text-warning me-2"></i>Machines de l'Usine</h2>
        <p class="text-muted small mb-0">Gestion et suivi des machines</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (peut('machines_gerer')): ?>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalConfigNotif" title="Configurer les notifications">
            <i class="bi bi-bell me-1"></i>Notifications
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreer">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle Machine
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPIs -->
<?php
$nb_total = count($machines);
$nb_en_cours = count(array_filter($machines, fn($m) => $m['etat'] === 'EN_FONCTIONNEMENT' || ($m['etat_actuel'] ?? '') === 'EN_FONCTIONNEMENT'));
$nb_arretees = count(array_filter($machines, fn($m) => ($m['etat_actuel'] ?? $m['etat']) === 'ARRETEE'));
$nb_maintenance = count(array_filter($machines, fn($m) => ($m['etat_actuel'] ?? $m['etat']) === 'EN_MAINTENANCE'));
$nb_panee = count(array_filter($machines, fn($m) => ($m['etat_actuel'] ?? $m['etat']) === 'EN_PANNE'));
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card is-primary">
            <div class="stat-icon"><i class="bi bi-gear-wide-connected"></i></div>
            <div class="stat-label">Total machines</div>
            <div class="stat-value"><?= $nb_total ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-success">
            <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
            <div class="stat-label">En fonctionnement</div>
            <div class="stat-value text-success"><?= $nb_en_cours ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-warning">
            <div class="stat-icon"><i class="bi bi-pause-circle"></i></div>
            <div class="stat-label">Arrêtées</div>
            <div class="stat-value text-secondary"><?= $nb_arretees ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-danger">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-label">Maintenance / Panne</div>
            <div class="stat-value text-danger"><?= $nb_maintenance + $nb_panee ?></div>
        </div>
    </div>
</div>

<!-- Tableau des machines -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">Liste des machines</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Référence</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th class="text-center">État</th>
                        <th>Dernière action</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($machines as $m): ?>
                        <?php $etat = $m['etat_actuel'] ?? $m['etat']; ?>
                        <tr>
                            <td><code><?= h($m['reference']) ?></code></td>
                            <td class="fw-semibold"><?= h($m['nom']) ?></td>
                            <td><?= h($m['type'] ?? '—') ?></td>
                            <td class="text-center">
                                <?php if ($etat === 'EN_FONCTIONNEMENT'): ?>
                                    <span class="badge bg-success"><i class="bi bi-play-fill"></i> En fonctionnement</span>
                                <?php elseif ($etat === 'EN_MAINTENANCE'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-tools"></i> Maintenance</span>
                                <?php elseif ($etat === 'EN_PANNE'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-octagon"></i> En panne</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-stop-fill"></i> Arrêtée</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= $m['derniere_action'] ? date('d/m/Y H:i', strtotime($m['derniere_action'])) : '—' ?>
                            </td>
                            <td class="text-center">
                                <?php if (peut('machines_gerer')): ?>
                                <button class="btn btn-sm btn-outline-primary btn-edit-machine"
                                        data-id="<?= $m['id'] ?>"
                                        data-nom="<?= h($m['nom']) ?>"
                                        data-type="<?= h($m['type'] ?? '') ?>"
                                        data-description="<?= h($m['description'] ?? '') ?>"
                                        data-actif="<?= $m['actif'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (peut('machines_demarrer')): ?>
                                    <?php if ($etat !== 'EN_FONCTIONNEMENT'): ?>
                                    <form method="post" action="machines.php" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="demarrer">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Démarrer"><i class="bi bi-play"></i></button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-warning btn-arreter-machine"
                                            data-id="<?= $m['id'] ?>"
                                            data-nom="<?= h($m['nom']) ?>"
                                            title="Arrêter">
                                        <i class="bi bi-stop"></i>
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($machines)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune machine enregistrée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Créer Machine -->
<?php if (peut('machines_gerer')): ?>
<div class="modal fade" id="modalCreer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="machines.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="creer">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nouvelle Machine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom *</label>
                        <input type="text" name="nom" class="form-control" required placeholder="Ex: Injecteuse 01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Référence</label>
                        <input type="text" name="reference" class="form-control" placeholder="Auto-générée si vide">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" class="form-select">
                            <option value="">— Choisir —</option>
                            <option value="Injecteuse">Injecteuse</option>
                            <option value="Extrudeuse">Extrudeuse</option>
                            <option value="Souffleuse">Souffleuse</option>
                            <option value="Thermoformeuse">Thermoformeuse</option>
                            <option value="Presse">Presse</option>
                            <option value="Four">Four</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Machine -->
<div class="modal fade" id="modalModifier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="machines.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="editMachineId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Modifier Machine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom *</label>
                        <input type="text" name="nom" id="editMachineNom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" id="editMachineType" class="form-select">
                            <option value="">— Choisir —</option>
                            <option value="Injecteuse">Injecteuse</option>
                            <option value="Extrudeuse">Extrudeuse</option>
                            <option value="Souffleuse">Souffleuse</option>
                            <option value="Thermoformeuse">Thermoformeuse</option>
                            <option value="Presse">Presse</option>
                            <option value="Four">Four</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="editMachineDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" name="actif" id="editMachineActif" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="editMachineActif">Actif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Arrêter Machine -->
<div class="modal fade" id="modalArreter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="machines.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="arreter">
                <input type="hidden" name="id" id="arreterMachineId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Arrêter — <span id="arreterMachineNom"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motif d'arrêt</label>
                        <select name="motif" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <option value="Fin de production">Fin de production</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Panne">Panne</option>
                            <option value="Changement de moule">Changement de moule</option>
                            <option value="Manque matière">Manque matière</option>
                            <option value="Pause">Pause</option>
                            <option value="Nettoyage">Nettoyage</option>
                            <option value="Réglage">Réglage</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-stop"></i> Arrêter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Config Notifications Machines -->
<div class="modal fade" id="modalConfigNotif" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="machines.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="config_notif">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bell text-warning me-2"></i>Notifications Machines</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Sélectionnez les utilisateurs qui recevront une notification quand une machine est arrêtée, en panne ou en maintenance.</p>
                    <div class="mb-3">
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllUsers">Tout sélectionner</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllUsers">Tout désélectionner</button>
                        </div>
                        <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($users_all as $u): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notif_utilisateurs[]" value="<?= $u['id'] ?>" id="notif_user_<?= $u['id'] ?>" <?= in_array($u['id'], $notif_user_ids) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notif_user_<?= $u['id'] ?>">
                                    <?= h($u['nom']) ?> <span class="text-muted small">(<?= h($u['role']) ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($users_all)): ?>
                            <p class="text-muted text-center py-3 mb-0">Aucun utilisateur disponible.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-machine').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editMachineId').value = this.dataset.id;
            document.getElementById('editMachineNom').value = this.dataset.nom;
            document.getElementById('editMachineType').value = this.dataset.type;
            document.getElementById('editMachineDesc').value = this.dataset.description;
            document.getElementById('editMachineActif').checked = this.dataset.actif == 1;
            new bootstrap.Modal(document.getElementById('modalModifier')).show();
        });
    });

    document.querySelectorAll('.btn-arreter-machine').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('arreterMachineId').value = this.dataset.id;
            document.getElementById('arreterMachineNom').textContent = this.dataset.nom;
            new bootstrap.Modal(document.getElementById('modalArreter')).show();
        });
    });

    document.getElementById('selectAllUsers')?.addEventListener('click', function() {
        document.querySelectorAll('input[name="notif_utilisateurs[]"]').forEach(cb => cb.checked = true);
    });
    document.getElementById('deselectAllUsers')?.addEventListener('click', function() {
        document.querySelectorAll('input[name="notif_utilisateurs[]"]').forEach(cb => cb.checked = false);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
