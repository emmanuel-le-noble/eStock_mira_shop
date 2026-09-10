<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_horaires_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('horaires_consulter');

$titre_page = 'Horaires de Travail — Usine';

// POST: créer / supprimer horaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('horaires_gerer');
    csrf_guard('horaires.php');
    $action_post = $_POST['action'] ?? '';
    try {
        if ($action_post === 'creer') {
            $nom = trim($_POST['nom'] ?? 'Usine');
            $jour = strtoupper(trim($_POST['jour'] ?? ''));
            $heure_debut = $_POST['heure_debut'] ?? '08:00';
            $heure_fin = $_POST['heure_fin'] ?? '17:00';
            $tolerance = (int)($_POST['tolerance_retard_minutes'] ?? 5);

            $valid_jours = ['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'];
            if (!in_array($jour, $valid_jours, true)) throw new RuntimeException('Jour invalide.');
            if (empty($nom)) throw new RuntimeException('Nom requis.');

            db_horaire_insert($pdo, $nom, $jour, $heure_debut, $heure_fin, $tolerance);
            suivre_activite('HORAIRE_CREE', "Horaire $nom — $jour");
            flash_success("Horaire enregistré pour $jour.");
        } elseif ($action_post === 'creer_semaine') {
            $nom = trim($_POST['nom'] ?? 'Usine');
            $heure_debut = $_POST['heure_debut'] ?? '08:00';
            $heure_fin = $_POST['heure_fin'] ?? '17:00';
            $tolerance = (int)($_POST['tolerance_retard_minutes'] ?? 5);
            $jours = $_POST['jours'] ?? ['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI'];

            foreach ($jours as $jour) {
                $jour = strtoupper(trim($jour));
                db_horaire_insert($pdo, $nom, $jour, $heure_debut, $heure_fin, $tolerance);
            }
            suivre_activite('HORAIRE_CREE', "Horaire $nom — semaine complète");
            flash_success("Horaires enregistrés pour " . count($jours) . " jour(s).");
        } elseif ($action_post === 'supprimer') {
            $nom = trim($_POST['nom'] ?? '');
            $jour = $_POST['jour'] ?? null;
            if (empty($nom)) throw new RuntimeException('Nom requis.');
            db_horaire_delete($pdo, $nom, $jour);
            flash_success('Horaire supprimé.');
        }
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('horaires.php');
}

$horaires = db_horaires_list($pdo);

// Grouper par nom
$grouped = [];
foreach ($horaires as $h) {
    $grouped[$h['nom']][] = $h;
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-clock text-warning me-2"></i>Horaires de Travail</h2>
        <p class="text-muted small mb-0">Configuration des horaires et tolérance de retard</p>
    </div>
    <?php if (peut('horaires_gerer')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreer">
        <i class="bi bi-plus-lg me-1"></i>Nouvel Horaire
    </button>
    <?php endif; ?>
</div>

<?php if (empty($grouped)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-clock fs-1 d-block mb-2"></i>
            Aucun horaire configuré. Créez un horaire pour activer la détection automatique des retards.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $nom => $entries): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-clock me-1"></i> <?= h($nom) ?></h6>
            <?php if (peut('horaires_gerer')): ?>
            <form method="post" action="horaires.php" style="display:inline" onsubmit="return confirm('Supprimer tous les horaires de « <?= h($nom) ?> » ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="nom" value="<?= h($nom) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Jour</th>
                        <th>Heure début</th>
                        <th>Heure fin</th>
                        <th class="text-center">Durée</th>
                        <th class="text-center">Tolérance retard</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $e):
                        $ts_debut = strtotime($e['heure_debut']);
                        $ts_fin = strtotime($e['heure_fin']);
                        $duree_min = ($ts_fin - $ts_debut) / 60;
                        $duree_h = floor($duree_min / 60);
                        $duree_m = $duree_min % 60;
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= h($e['jour']) ?></td>
                        <td><?= h($e['heure_debut']) ?></td>
                        <td><?= h($e['heure_fin']) ?></td>
                        <td class="text-center"><?= $duree_h ?>h<?= sprintf('%02d', $duree_m) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $e['tolerance_retard_minutes'] <= 5 ? 'success' : ($e['tolerance_retard_minutes'] <= 15 ? 'warning' : 'danger') ?>">
                                <?= $e['tolerance_retard_minutes'] ?> min
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal Créer Horaire -->
<?php if (peut('horaires_gerer')): ?>
<div class="modal fade" id="modalCreer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="horaires.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="creer_semaine">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nouvel Horaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom de l'horaire</label>
                        <input type="text" name="nom" class="form-control" value="Usine" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Heure de début</label>
                            <input type="time" name="heure_debut" class="form-control" value="08:00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Heure de fin</label>
                            <input type="time" name="heure_fin" class="form-control" value="17:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tolérance retard (minutes)</label>
                        <input type="number" name="tolerance_retard_minutes" class="form-control" value="5" min="0" max="60">
                        <div class="form-text">Dépassement de cette durée = retard signalé.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jours</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'] as $j): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="jours[]" value="<?= $j ?>" id="jour_<?= $j ?>" <?= in_array($j, ['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="jour_<?= $j ?>"><?= substr($j, 0, 3) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
