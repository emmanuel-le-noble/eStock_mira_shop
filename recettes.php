<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_recettes_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('usine_consulter');
$action = $_GET['action'] ?? 'liste';
$titre_page = 'Recettes de production';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enregistrer') {
    exiger_permission('usine_gerer');
    csrf_guard('recettes.php');
    $id = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $article_id = (int)($_POST['article_id'] ?? 0);
    $quantite_produite = (int)($_POST['quantite_produite'] ?? 100);
    $unite_produit = trim($_POST['unite_produit'] ?? 'UNITE');
    $lignes_json = $_POST['lignes_json'] ?? '[]';
    $lignes = json_decode($lignes_json, true) ?: [];

    if (empty($nom) || $article_id <= 0) {
        flash_error('Nom et produit fini requis.');
        redirect('recettes.php');
    }

    db_transaction(
        function(PDO $pdo) use ($id, $nom, $article_id, $quantite_produite, $unite_produit, $lignes) {
            if ($id > 0) {
                db_recette_update($pdo, $id, [
                    'nom' => $nom, 'quantite_produite' => $quantite_produite,
                    'unite_produit' => $unite_produit, 'actif' => 1, 'lignes' => $lignes,
                ]);
            } else {
                db_recette_insert($pdo, [
                    'nom' => $nom, 'article_id' => $article_id,
                    'quantite_produite' => $quantite_produite, 'unite_produit' => $unite_produit,
                    'actif' => 1, 'lignes' => $lignes,
                ]);
            }
        },
        'Recette enregistrée.',
        'Erreur enregistrement recette.',
        'recettes.php'
    );
    redirect('recettes.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'supprimer') {
    exiger_permission('usine_gerer');
    csrf_guard('recettes.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_recette_delete($pdo, $id);
        suivre_activite('RECETTE_SUPPRIMEE', "Recette #$id supprimée");
    }
    redirect('recettes.php');
}

// Edit view
$edit = null;
if ($action === 'editer' && !empty($_GET['id'])) {
    $edit = db_recette_get($pdo, (int)$_GET['id']);
}
if ($action === 'nouveau' || $action === 'ajouter') {
    $action = 'ajouter';
}

$produits_finis = $pdo->query(
    "SELECT id, nom, code_barre FROM articles WHERE type_article = 'PRODUIT_FINI' AND actif = 1 ORDER BY nom"
)->fetchAll();
$matieres = db_matiere_premiere_list($pdo);
$recettes = db_recettes_list($pdo);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-journal-text text-info me-2"></i>Recettes de production</h2>
        <p class="text-muted small mb-0">Définir comment les produits finis sont fabriqués</p>
    </div>
    <a href="recettes.php?action=ajouter" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle recette
    </a>
</div>

<?php if ($action === 'ajouter' || $action === 'editer'): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><?= $edit ? 'Modifier' : 'Nouvelle' ?> recette</h6>
    </div>
    <div class="card-body">
        <form method="post" action="recettes.php?action=enregistrer" id="formRecette">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            <input type="hidden" name="lignes_json" id="lignesJson" value="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nom de la recette *</label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= h($edit['nom'] ?? '') ?>" placeholder="Ex: Seau 20L — V2">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Produit fini *</label>
                    <select name="article_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($produits_finis as $pf): ?>
                            <option value="<?= $pf['id'] ?>" <?= ($edit['article_id'] ?? '') == $pf['id'] ? 'selected' : '' ?>>
                                <?= h($pf['nom']) ?> (<?= h($pf['code_barre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Qté produite</label>
                    <input type="number" name="quantite_produite" class="form-control"
                           value="<?= $edit['quantite_produite'] ?? 100 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Unité</label>
                    <input type="text" name="unite_produit" class="form-control"
                           value="<?= h($edit['unite_produit'] ?? 'UNITE') ?>">
                </div>
            </div>

            <h6 class="mt-4 mb-3 fw-bold">Matières premières nécessaires</h6>
            <div id="lignesContainer">
                <?php
                $lignes = $edit['lignes'] ?? [];
                if (empty($lignes)) $lignes = [['matiere_id' => '', 'quantite_necessaire' => '', 'unite' => 'KG', 'pertes_theoriques_pct' => 0]];
                foreach ($lignes as $i => $l):
                ?>
                <div class="row g-2 mb-2 ligne-matiere">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm matiere-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($matieres as $mp): ?>
                                <option value="<?= $mp['id'] ?>" <?= ($l['matiere_id'] ?? '') == $mp['id'] ? 'selected' : '' ?>>
                                    <?= h($mp['nom']) ?> (<?= h($mp['unite_mesure']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.001" class="form-control form-control-sm qte-input"
                               placeholder="Quantité" value="<?= $l['quantite_necessaire'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm unite-select">
                            <?php foreach (['KG','G','LITRE','ML','UNITE'] as $u): ?>
                                <option value="<?= $u ?>" <?= ($l['unite'] ?? 'KG') === $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" class="form-control form-control-sm perte-input"
                               placeholder="Pertes %" value="<?= $l['pertes_theoriques_pct'] ?? 0 ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-ligne">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success mb-3" id="btnAjouterLigne">
                <i class="bi bi-plus"></i> Ajouter une matière
            </button>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                <a href="recettes.php" class="btn btn-outline-secondary">Annuler</a>
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
                    <th>#</th><th>Nom</th><th>Produit fini</th>
                    <th class="text-center">Qté produite</th><th class="text-center">Version</th>
                    <th class="text-center">Matières</th><th>État</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recettes as $r): ?>
                    <tr>
                        <td class="text-muted">#<?= $r['id'] ?></td>
                        <td class="fw-semibold"><?= h($r['nom']) ?></td>
                        <td><?= h($r['article_nom']) ?></td>
                        <td class="text-center"><?= (int)$r['quantite_produite'] ?> <?= h($r['unite_produit']) ?></td>
                        <td class="text-center">V<?= (int)$r['version'] ?></td>
                        <td class="text-center"><span class="badge bg-info"><?= (int)$r['nb_matieres'] ?></span></td>
                        <td><?= $r['actif'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end text-nowrap">
                            <a href="recettes.php?action=editer&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="recettes.php?action=supprimer" style="display:inline" data-confirm="Supprimer cette recette ?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recettes)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Aucune recette.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('lignesContainer');
    const btnAjouter = document.getElementById('btnAjouterLigne');
    const matieres = <?= json_encode(array_map(fn($m) => ['id' => $m['id'], 'nom' => $m['nom'], 'unite' => $m['unite_mesure']], $matieres)) ?>;

    if (!container) return;

    if (btnAjouter) {
        btnAjouter.addEventListener('click', function() {
            const tpl = `
            <div class="row g-2 mb-2 ligne-matiere">
                <div class="col-md-4">
                    <select class="form-select form-select-sm matiere-select" required>
                        <option value="">— Choisir —</option>
                        ${matieres.map(m => `<option value="${m.id}">${m.nom} (${m.unite})</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.001" class="form-control form-control-sm qte-input" placeholder="Quantité" required></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm unite-select">
                        <option value="KG">KG</option><option value="G">G</option>
                        <option value="LITRE">LITRE</option><option value="ML">ML</option>
                        <option value="UNITE">UNITE</option>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.01" class="form-control form-control-sm perte-input" placeholder="Pertes %" value="0"></div>
                <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-ligne"><i class="bi bi-trash"></i></button></div>
            </div>`;
            container.insertAdjacentHTML('beforeend', tpl);
            bindRemoveButtons();
        });
    }

    function bindRemoveButtons() {
        container.querySelectorAll('.btn-remove-ligne').forEach(btn => {
            btn.onclick = function() { this.closest('.ligne-matiere').remove(); };
        });
    }
    bindRemoveButtons();

    // Serialize avant submit
    document.getElementById('formRecette').addEventListener('submit', function() {
        const lignes = [];
        container.querySelectorAll('.ligne-matiere').forEach(row => {
            const matiere_id = row.querySelector('.matiere-select').value;
            const qte = row.querySelector('.qte-input').value;
            if (matiere_id && qte) {
                lignes.push({
                    matiere_id: parseInt(matiere_id),
                    quantite_necessaire: parseFloat(qte),
                    unite: row.querySelector('.unite-select').value,
                    pertes_theoriques_pct: parseFloat(row.querySelector('.perte-input').value) || 0
                });
            }
        });
        document.getElementById('lignesJson').value = JSON.stringify(lignes);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
