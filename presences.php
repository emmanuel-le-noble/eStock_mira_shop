<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_presences_list_date')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('presence_consulter');

$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
$titre_page = 'Présences — ' . date('d/m/Y', strtotime($date));

// POST: enregistrer présence
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('presence_gerer');
    csrf_guard('presences.php');
    $employe_id = (int)($_POST['employe_id'] ?? 0);
    $date_presence = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_presence'] ?? '') ? $_POST['date_presence'] : date('Y-m-d');
    $heure_arrivee = $_POST['heure_arrivee'] ?? null;
    $heure_depart = $_POST['heure_depart'] ?? null;
    $commentaire = $_POST['commentaire'] ?? null;

    if ($employe_id > 0) {
        try {
            db_presence_upsert($pdo, $employe_id, $date_presence, $heure_arrivee, $heure_depart, $commentaire);
            suivre_activite('PRESENCE_ENREGISTREE', "Présence employé #$employe_id le $date_presence");
            flash_success('Présence enregistrée.');
        } catch (Throwable $e) {
            flash_error($e->getMessage());
        }
    }
    redirect('presences.php?date=' . urlencode($date_presence));
}

$presences = db_presences_list_date($pdo, $date);
$employes = db_employes_list($pdo);

// Identifier les employés déjà enregistrés
$present_ids = array_column($presences, 'employe_id');
$absents = array_filter($employes, fn($e) => !in_array($e['id'], $present_ids));

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-clock-history text-warning me-2"></i>Présences du personnel</h2>
        <p class="text-muted small mb-0"><?= date('l d F Y', strtotime($date)) ?></p>
    </div>
</div>

<!-- Navigation date -->
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="presences.php?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-chevron-left"></i>
    </a>
    <input type="date" id="datePicker" class="form-control form-control-sm" style="width:160px" value="<?= $date ?>">
    <a href="presences.php?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-chevron-right"></i>
    </a>
    <a href="presences.php?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Aujourd'hui</a>
</div>

<!-- Tableau des présences -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">Présences enregistrées (<?= count($presences) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Employé</th><th>Matricule</th><th>Fonction</th><th>Arrivée</th><th>Départ</th><th>Travaillé</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($presences as $p): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($p['prenom'] . ' ' . $p['nom']) ?></td>
                        <td><code><?= h($p['matricule']) ?></code></td>
                        <td><?= h($p['fonction']) ?></td>
                        <td><?= $p['heure_arrivee'] ? date('H:i', strtotime($p['heure_arrivee'])) : '—' ?></td>
                        <td><?= $p['heure_depart'] ? date('H:i', strtotime($p['heure_depart'])) : '—' ?></td>
                        <td>
                            <?php if ($p['temps_travaille_minutes']): ?>
                                <?= floor((int)$p['temps_travaille_minutes'] / 60) ?>h<?= sprintf('%02d', (int)$p['temps_travaille_minutes'] % 60) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-edit-presence"
                                    data-id="<?= $p['id'] ?>"
                                    data-employe="<?= h($p['prenom'] . ' ' . $p['nom']) ?>"
                                    data-arrivee="<?= h($p['heure_arrivee'] ?? '') ?>"
                                    data-depart="<?= h($p['heure_depart'] ?? '') ?>"
                                    data-commentaire="<?= h($p['commentaire'] ?? '') ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($presences)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Aucune présence enregistrée pour cette date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Formulaire d'ajout rapide -->
<?php if (!empty($absents)): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Ajouter une présence</h6></div>
    <div class="card-body">
        <form method="post" action="presences.php?date=<?= $date ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="date_presence" value="<?= $date ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Employé</label>
                    <select name="employe_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($absents as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= h($e['prenom'] . ' ' . $e['nom']) ?> (<?= h($e['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Arrivée</label>
                    <input type="time" name="heure_arrivee" class="form-control" value="07:30">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Départ</label>
                    <input type="time" name="heure_depart" class="form-control" value="17:00">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Commentaire</label>
                    <input type="text" name="commentaire" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal modification -->
<div class="modal fade" id="modalEditPresence" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="presences.php?date=<?= $date ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="date_presence" value="<?= $date ?>">
                <input type="hidden" name="employe_id" id="editEmpId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Modifier présence — <span id="editEmpNom"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Heure d'arrivée</label>
                        <input type="time" name="heure_arrivee" id="editArrivee" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Heure de départ</label>
                        <input type="time" name="heure_depart" id="editDepart" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Commentaire</label>
                        <input type="text" name="commentaire" id="editCommentaire" class="form-control">
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

<script nonce="<?= csp_nonce_val() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('datePicker').addEventListener('change', function() {
        window.location.href = 'presences.php?date=' + this.value;
    });

    document.querySelectorAll('.btn-edit-presence').forEach(btn => {
        btn.addEventListener('click', function() {
            const empId = this.dataset.id;
            const findEmp = <?= json_encode(array_map(fn($e) => ['id' => $e['id'], 'nom' => $e['prenom'] . ' ' . $e['nom']], $employes)) ?>;
            const emp = findEmp.find(e => e.id == empId);

            document.getElementById('editEmpId').value = empId;
            document.getElementById('editEmpNom').textContent = emp ? emp.nom : '';
            document.getElementById('editArrivee').value = this.dataset.arrivee.substring(0, 5);
            document.getElementById('editDepart').value = this.dataset.depart.substring(0, 5);
            document.getElementById('editCommentaire').value = this.dataset.commentaire || '';

            new bootstrap.Modal(document.getElementById('modalEditPresence')).show();
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
