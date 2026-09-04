<?php
/**
 * tarification.php - Gestion des règles de tarification (tranches tarifaires)
 * Permissions: tarification_consulter (lecture), tarification_gerer (écriture)
 */

if (!function_exists('est_connecte')) {
    require_once __DIR__ . '/config/connexion.php';
}
if (!function_exists('db_tranches_tarifaires_list')) {
    require_once __DIR__ . '/includes/db_functions.php';
}
if (!function_exists('csrf_guard')) {
    require_once __DIR__ . '/includes/helpers.php';
}

$action = $_GET['action'] ?? 'liste';

// ---- Traitement POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('tarification_gerer');
    csrf_guard('tarification.php');

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'creer' || $postAction === 'editer') {
        $data = [
            'nom'          => trim($_POST['nom'] ?? ''),
            'article_id'   => !empty($_POST['article_id']) ? (int)$_POST['article_id'] : null,
            'categorie_id' => !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null,
            'qte_min'      => max(1, (int)($_POST['qte_min'] ?? 1)),
            'qte_max'      => !empty($_POST['qte_max']) ? (int)$_POST['qte_max'] : null,
            'mode_calcul'  => $_POST['mode_calcul'] ?? 'majoration_pct',
            'valeur'       => (float)($_POST['valeur'] ?? 0),
            'priorite'     => (int)($_POST['priorite'] ?? 0),
            'actif'        => isset($_POST['actif']) ? 1 : 0,
            'date_debut'   => !empty($_POST['date_debut']) ? $_POST['date_debut'] : null,
            'date_fin'     => !empty($_POST['date_fin']) ? $_POST['date_fin'] : null,
        ];

        if (empty($data['nom'])) {
            flash_error('Le nom est requis.');
            redirect('tarification.php');
        }

        if ($postAction === 'creer') {
            $id = db_tranche_insert($pdo, $data);
            suivre_activite('TRANCHE_CREE', 'Tranche #' . $id . ' : ' . $data['nom']);
            flash_success('Règle de tarification créée.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                db_tranche_update($pdo, $id, $data);
                suivre_activite('TRANCHE_MODIFIEE', 'Tranche #' . $id . ' : ' . $data['nom']);
                flash_success('Règle de tarification mise à jour.');
            }
        }
        redirect('tarification.php');
    }

    if ($postAction === 'supprimer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            db_tranche_delete($pdo, $id);
            suivre_activite('TRANCHE_SUPPRIMEE', 'Tranche #' . $id);
            flash_success('Règle supprimée.');
        }
        redirect('tarification.php');
    }
}

// ---- Vue détail (formulaire édition) ----
if ($action === 'editer' || $action === 'nouveau') {
    exiger_permission('tarification_gerer');
    $tranche = null;
    $id_get = (int)($_GET['id'] ?? 0);
    if ($action === 'editer' && $id_get > 0) {
        $tranche = db_tranche_get_by_id($pdo, $id_get);
        if (!$tranche) {
            flash_error('Règle introuvable.');
            redirect('tarification.php');
        }
    }
    $articles = db_articles_list_active($pdo);
    $categories = function_exists('db_categories_all') ? db_categories_all($pdo) : [];

    include __DIR__ . '/includes/header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-currency-exchange"></i>
                        <?= $tranche ? 'Modifier la règle' : 'Nouvelle règle de tarification' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="tarification.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="<?= $tranche ? 'editer' : 'creer' ?>">
                        <?php if ($tranche): ?>
                        <input type="hidden" name="id" value="<?= (int)$tranche['id'] ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nom de la règle *</label>
                                <input type="text" name="nom" class="form-control" required
                                       value="<?= h($tranche['nom'] ?? '') ?>" placeholder="ex: Gros - Tarif special">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Article (laisser vide = tous)</label>
                                <select name="article_id" class="form-select">
                                    <option value="">— Tous les articles —</option>
                                    <?php foreach ($articles as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>" <?= (int)($tranche['article_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= h($a['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie (laisser vide = toutes)</label>
                                <select name="categorie_id" class="form-select">
                                    <option value="">— Toutes les catégories —</option>
                                    <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)($tranche['categorie_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= h($c['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantité min *</label>
                                <input type="number" name="qte_min" class="form-control" min="1" required
                                       value="<?= (int)($tranche['qte_min'] ?? 1) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantité max (vide = illimité)</label>
                                <input type="number" name="qte_max" class="form-control" min="1"
                                       value="<?= $tranche['qte_max'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priorité</label>
                                <input type="number" name="priorite" class="form-control" min="0"
                                       value="<?= (int)($tranche['priorite'] ?? 0) ?>">
                                <small class="text-muted">Plus élevé = prioritaire</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mode de calcul *</label>
                                <select name="mode_calcul" class="form-select" required>
                                    <option value="majoration_pct" <?= ($tranche['mode_calcul'] ?? '') === 'majoration_pct' ? 'selected' : '' ?>>Majoration (%)</option>
                                    <option value="marge_pct" <?= ($tranche['mode_calcul'] ?? '') === 'marge_pct' ? 'selected' : '' ?>>Marge sur prix de vente (%)</option>
                                    <option value="prix_fixe" <?= ($tranche['mode_calcul'] ?? '') === 'prix_fixe' ? 'selected' : '' ?>>Prix fixe</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valeur *</label>
                                <input type="number" step="0.01" min="0" name="valeur" class="form-control" required
                                       value="<?= h($tranche['valeur'] ?? 0) ?>">
                                <small class="text-muted" id="valeurHint">% ou montant selon le mode</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" name="actif" class="form-check-input" id="actifCheck"
                                           <?= ($tranche['actif'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="actifCheck">Active</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date début (facultatif)</label>
                                <input type="datetime-local" name="date_debut" class="form-control"
                                       value="<?= !empty($tranche['date_debut']) ? date('Y-m-d\TH:i', strtotime($tranche['date_debut'])) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date fin (facultatif)</label>
                                <input type="datetime-local" name="date_fin" class="form-control"
                                       value="<?= !empty($tranche['date_fin']) ? date('Y-m-d\TH:i', strtotime($tranche['date_fin'])) : '' ?>">
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                            <a href="tarification.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ---- LISTE ----
exiger_permission('tarification_consulter');
$tranches = db_tranches_tarifaires_list($pdo, null, null, false);
$titre_page = 'Tarification';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <?php if (peut('tarification_gerer')): ?>
    <a href="tarification.php?action=nouveau" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle règle
    </a>
    <?php endif; ?>
</div>
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-currency-exchange"></i> Règles de tarification (prix selon quantité)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Article</th>
                        <th>Catégorie</th>
                        <th class="text-center">Tranche</th>
                        <th>Mode</th>
                        <th class="text-end">Valeur</th>
                        <th class="text-center">Priorité</th>
                        <th class="text-center">État</th>
                        <?php if (peut('tarification_gerer')): ?>
                        <th class="text-center">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tranches)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune règle de tarification.</td></tr>
                <?php else: foreach ($tranches as $t): ?>
                    <tr class="<?= $t['actif'] ? '' : 'text-muted' ?>">
                        <td class="fw-semibold"><?= h($t['nom']) ?></td>
                        <td><?= h($t['article_nom'] ?? '— Tous —') ?></td>
                        <td><?= h($t['categorie_nom'] ?? '— Toutes —') ?></td>
                        <td class="text-center">
                            <span class="badge text-bg-info"><?= (int)$t['qte_min'] ?> – <?= $t['qte_max'] ? (int)$t['qte_max'] : '∞' ?></span>
                        </td>
                        <td>
                            <?php
                            $modes = ['majoration_pct' => 'Majoration %', 'marge_pct' => 'Marge %', 'prix_fixe' => 'Prix fixe'];
                            echo h($modes[$t['mode_calcul']] ?? $t['mode_calcul']);
                            ?>
                        </td>
                        <td class="text-end"><?= number_format((float)$t['valeur'], 4, ',', ' ') ?></td>
                        <td class="text-center"><?= (int)$t['priorite'] ?></td>
                        <td class="text-center">
                            <?= $t['actif'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <?php if (peut('tarification_gerer')): ?>
                        <td class="text-center text-nowrap">
                            <a href="tarification.php?action=editer&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="tarification.php" style="display:inline" data-confirm="Supprimer cette règle ?">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$modes_json = json_encode(['majoration_pct' => '%', 'marge_pct' => '%', 'prix_fixe' => 'FCFA']);
?>
<script nonce="<?= h(csp_nonce()) ?>">
document.addEventListener('DOMContentLoaded', function() {
    var modes = <?= $modes_json ?>;
    var modeSelect = document.querySelector('select[name="mode_calcul"]');
    var valeurInput = document.querySelector('input[name="valeur"]');
    var valeurHint = document.getElementById('valeurHint');
    if (modeSelect && valeurHint) {
        modeSelect.addEventListener('change', function() {
            valeurHint.textContent = modes[this.value] === '%' ? '% ou montant' : 'Montant en FCFA';
        });
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
