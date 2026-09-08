<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_matiere_premiere_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('usine_consulter');
$action = $_GET['action'] ?? 'liste';
$titre_page = 'Matières premières';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enregistrer') {
    exiger_permission('usine_gerer');
    csrf_guard('matieres_premieres.php');
    $data = extract_post_data([
        'id'          => ['type' => 'int', 'default' => 0],
        'nom'         => ['type' => 'string', 'required' => true],
        'reference'   => ['type' => 'string'],
        'unite_mesure'=> ['type' => 'string', 'default' => 'KG'],
        'categorie_id'=> ['type' => 'int', 'default' => null],
        'cout_reference' => ['type' => 'float', 'default' => 0],
        'stock_minimum'=> ['type' => 'int', 'default' => 10],
        'fournisseur_id'=> ['type' => 'int', 'default' => null],
        'notes'       => ['type' => 'string'],
    ], 'matieres_premieres.php');
    db_transaction(
        function(PDO $pdo) use ($data) {
            if ($data['id'] > 0) {
                db_matiere_premiere_update($pdo, $data['id'], $data);
            } else {
                db_matiere_premiere_insert($pdo, $data);
            }
        },
        'Matière première enregistrée.',
        'Erreur enregistrement matière première.',
        'matieres_premieres.php'
    );
    redirect('matieres_premieres.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'supprimer') {
    exiger_permission('usine_gerer');
    csrf_guard('matieres_premieres.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_matiere_premiere_update($pdo, $id, ['actif' => 0]);
        suivre_activite('MATIERE_PREMIERE_SUPPRIMEE', "Matière #$id désactivée");
    }
    redirect('matieres_premieres.php');
}

// Edit mode
$edit = null;
if ($action === 'editer' && !empty($_GET['id'])) {
    $edit = db_matiere_premiere_get($pdo, (int)$_GET['id']);
}

$categories = $pdo->query("SELECT id, nom FROM categories_matieres_premieres WHERE actif = 1 ORDER BY nom")->fetchAll();
$matieres = db_matiere_premiere_list($pdo, 500);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-droplet text-primary me-2"></i>Matières premières</h2>
        <p class="text-muted small mb-0">Gestion des matières premières de l'usine</p>
    </div>
    <a href="matieres_premieres.php?action=ajouter" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle matière
    </a>
</div>

<?php if ($action === 'ajouter' || $action === 'editer'): ?>
<!-- Formulaire -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><?= $edit ? 'Modifier' : 'Nouvelle' ?> matière première</h6>
    </div>
    <div class="card-body">
        <form method="post" action="matieres_premieres.php?action=enregistrer">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nom *</label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= h($edit['nom'] ?? '') ?>" placeholder="Ex: Granulés PP">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Référence</label>
                    <input type="text" name="reference" class="form-control"
                           value="<?= h($edit['reference'] ?? '') ?>" placeholder="Auto-générée si vide">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Unité</label>
                    <select name="unite_mesure" class="form-select">
                        <?php foreach (['KG','G','LITRE','ML','UNITE'] as $u): ?>
                            <option value="<?= $u ?>" <?= ($edit['unite_mesure'] ?? 'KG') === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Catégorie</label>
                    <select name="categorie_id" class="form-select">
                        <option value="">— Aucune —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($edit['categorie_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Coût de référence</label>
                    <input type="number" step="0.01" name="cout_reference" class="form-control"
                           value="<?= $edit['cout_reference'] ?? 0 ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Stock minimum</label>
                    <input type="number" name="stock_minimum" class="form-control"
                           value="<?= $edit['stock_minimum'] ?? 10 ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= h($edit['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                <a href="matieres_premieres.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Liste -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th><th>Nom</th><th>Référence</th><th>Unité</th>
                    <th>Catégorie</th><th class="text-end">Coût réf.</th>
                    <th class="text-end">Stock usine</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matieres as $m): ?>
                    <tr>
                        <td class="text-muted">#<?= $m['id'] ?></td>
                        <td class="fw-semibold"><?= h($m['nom']) ?></td>
                        <td><code><?= h($m['reference'] ?? '—') ?></code></td>
                        <td><?= h($m['unite_mesure']) ?></td>
                        <td><?= h($m['categorie_nom'] ?? '—') ?></td>
                        <td class="text-end"><?= money($m['cout_reference'] ?? 0) ?></td>
                        <td class="text-end">
                            <?= number_format((float)($m['stock_usine'] ?? 0)) ?>
                            <?php if ((float)($m['stock_usine'] ?? 0) <= ($m['stock_minimum'] ?? 10)): ?>
                                <span class="badge bg-danger">Bas</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="matieres_premieres.php?action=editer&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($matieres)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Aucune matière première.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
