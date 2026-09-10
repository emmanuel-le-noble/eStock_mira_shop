<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_productions_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('production_consulter');
$action = $_GET['action'] ?? 'liste';
$titre_page = 'Productions';

// POST handler: enregistrer production
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enregistrer') {
    exiger_permission('production_gerer');
    csrf_guard('productions.php');
    $data = extract_post_data([
        'article_id'       => ['type' => 'int', 'required' => true],
        'recette_id'       => ['type' => 'int', 'required' => true],
        'quantite_prevue'  => ['type' => 'int', 'required' => true],
        'date_prevue'      => ['type' => 'string', 'default' => null],
        'notes'            => ['type' => 'string', 'default' => null],
    ], 'productions.php');
    $data['utilisateur_id'] = $_SESSION['user']['id'] ?? null;

    try {
        $prod_id = db_production_insert($pdo, $data);
        suivre_activite('PRODUCTION_CREEE', "Production #$prod_id créée");
        flash_success('Production créée.');
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('productions.php?action=detail&id=' . ($prod_id ?? 0));
}

// POST handler: démarrer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'demarrer') {
    exiger_permission('production_gerer');
    csrf_guard('productions.php');
    $id = (int)($_POST['id'] ?? 0);
    try {
        db_production_demarrer($pdo, $id);
        suivre_activite('PRODUCTION_DEMARREE', "Production #$id démarrée");
        flash_success('Production démarrée.');
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('productions.php?action=detail&id=' . $id);
}

// POST handler: annuler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'annuler') {
    exiger_permission('production_gerer');
    csrf_guard('productions.php');
    $id = (int)($_POST['id'] ?? 0);
    try {
        db_production_annuler($pdo, $id);
        suivre_activite('PRODUCTION_ANNULEE', "Production #$id annulée");
        flash_success('Production annulée.');
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('productions.php');
}

// POST handler: cloturer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cloturer') {
    exiger_permission('production_cloturer');
    csrf_guard('productions.php');
    $id = (int)($_POST['id'] ?? 0);
    $quantite_produite = (int)($_POST['quantite_produite'] ?? 0);
    $quantite_perdue = (int)($_POST['quantite_perdue'] ?? 0);

    $matieres_reelles = [];
    $mp_ids = $_POST['mp_id'] ?? [];
    $mp_qtes = $_POST['mp_quantite_reelle'] ?? [];
    foreach ($mp_ids as $i => $mp_id) {
        $mp_id = (int)$mp_id;
        $qte = (float)($mp_qtes[$i] ?? 0);
        if ($mp_id > 0) {
            $matieres_reelles[] = ['matiere_id' => $mp_id, 'quantite_reelle' => $qte];
        }
    }

    try {
        db_production_cloturer($pdo, $id, $matieres_reelles, $quantite_produite, $quantite_perdue);
        suivre_activite('PRODUCTION_TERMINEE', "Production #$id clôturée — $quantite_produite produits, $quantite_perdue pertes");
        flash_success('Production clôturée avec succès.');
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('productions.php?action=detail&id=' . $id);
}

// Nouveau
if ($action === 'nouveau') {
    $produits_finis = $pdo->query("SELECT id, nom FROM articles WHERE type_article = 'PRODUIT_FINI' AND actif = 1 ORDER BY nom")->fetchAll();
    $recettes = $pdo->query("SELECT r.id, r.nom, r.version, a.nom AS article_nom FROM recettes r JOIN articles a ON a.id = r.article_id WHERE r.actif = 1 ORDER BY a.nom, r.version")->fetchAll();
    $employes = db_employes_list($pdo);

    include __DIR__ . '/includes/header.php';
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="h4 mb-0 fw-bold"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Nouvelle production</h2>
        </div>
        <a href="productions.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="productions.php?action=enregistrer">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Produit fini *</label>
                        <select name="article_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($produits_finis as $pf): ?>
                                <option value="<?= $pf['id'] ?>"><?= h($pf['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Recette *</label>
                        <select name="recette_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($recettes as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= h($r['article_nom']) ?> — <?= h($r['nom']) ?> (V<?= $r['version'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Quantité prévue *</label>
                        <input type="number" name="quantite_prevue" class="form-control" required min="1" value="100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Date prévue</label>
                        <input type="date" name="date_prevue" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Créer la production</button>
                </div>
            </form>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Détail production
if ($action === 'detail' && !empty($_GET['id'])) {
    $prod = db_production_get($pdo, (int)$_GET['id']);
    if (!$prod) { flash_error('Production introuvable.'); redirect('productions.php'); }

    include __DIR__ . '/includes/header.php';
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="h4 mb-0 fw-bold">
                <i class="bi bi-gear-wide-connected text-primary me-2"></i><?= h($prod['reference']) ?>
            </h2>
            <p class="text-muted small mb-0"><?= h($prod['article_nom']) ?> — <?= h($prod['recette_nom']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($prod['statut'] === 'BROUILLON' || $prod['statut'] === 'PLANIFIEE'): ?>
                <form method="post" action="productions.php?action=demarrer" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                    <button type="submit" class="btn btn-success"><i class="bi bi-play-fill"></i> Démarrer</button>
                </form>
            <?php endif; ?>
            <?php if ($prod['statut'] === 'EN_COURS'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCloturer">
                    <i class="bi bi-check-circle"></i> Clôturer
                </button>
            <?php endif; ?>
            <?php if ($prod['statut'] === 'BROUILLON' || $prod['statut'] === 'PLANIFIEE' || $prod['statut'] === 'EN_COURS'): ?>
                <form method="post" action="productions.php?action=annuler" style="display:inline" data-confirm="Annuler cette production ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i> Annuler</button>
                </form>
            <?php endif; ?>
            <a href="productions.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
        </div>
    </div>

    <!-- Statut -->
    <div class="mb-3">
        <?php
        $statut_badges = [
            'BROUILLON' => 'bg-secondary', 'PLANIFIEE' => 'bg-info',
            'EN_COURS' => 'bg-warning text-dark', 'TERMINEE' => 'bg-success', 'ANNULEE' => 'bg-danger'
        ];
        ?>
        <span class="badge <?= $statut_badges[$prod['statut']] ?? 'bg-secondary' ?> fs-6"><?= $prod['statut'] ?></span>
        <?php if ($prod['date_debut']): ?>
            <span class="text-muted ms-2">Début: <?= date('d/m/Y H:i', strtotime($prod['date_debut'])) ?></span>
        <?php endif; ?>
        <?php if ($prod['date_fin']): ?>
            <span class="text-muted ms-2">Fin: <?= date('d/m/Y H:i', strtotime($prod['date_fin'])) ?></span>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <!-- Matières -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Matières premières</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead><tr><th>Matière</th><th class="text-end">Prévue</th><th class="text-end">Réelle</th></tr></thead>
                        <tbody>
                            <?php foreach ($prod['matieres'] as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><?= h($m['matiere_nom']) ?></td>
                                    <td class="text-end"><?= number_format((float)$m['quantite_prevue'], 2) ?> <?= h($m['unite']) ?></td>
                                    <td class="text-end"><?= number_format((float)$m['quantite_reelle'], 2) ?> <?= h($m['unite']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Résultat -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Résultat</h6></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col">
                            <div class="text-muted small">Prévu</div>
                            <div class="fs-4 fw-bold"><?= number_format((int)$prod['quantite_prevue']) ?></div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Produit</div>
                            <div class="fs-4 fw-bold text-success"><?= number_format((int)$prod['quantite_produite']) ?></div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Perdu</div>
                            <div class="fs-4 fw-bold text-danger"><?= number_format((int)$prod['quantite_perdue']) ?></div>
                        </div>
                    </div>
                    <?php if ($prod['cout_matieres'] > 0): ?>
                    <hr>
                    <div class="row">
                        <div class="col"><span class="text-muted">Coût matières:</span> <strong><?= money($prod['cout_matieres']) ?></strong></div>
                        <div class="col"><span class="text-muted">Coût unitaire:</span> <strong><?= number_format((float)$prod['cout_unitaire'], 0) ?></strong></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Employés -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Employés affectés</h6></div>
        <div class="card-body">
            <?php if (empty($prod['employes'])): ?>
                <p class="text-muted mb-0">Aucun employé affecté.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($prod['employes'] as $e): ?>
                        <span class="badge bg-light border text-dark"><?= h($e['prenom'] . ' ' . $e['nom']) ?> — <?= h($e['fonction']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pertes -->
    <?php if (!empty($prod['pertes'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Pertes enregistrées</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Type</th><th>Article</th><th>Quantité</th><th>Motif</th></tr></thead>
                <tbody>
                    <?php foreach ($prod['pertes'] as $p): ?>
                        <tr>
                            <td><span class="badge bg-danger"><?= h($p['type_perte']) ?></span></td>
                            <td><?= h($p['article_nom']) ?></td>
                            <td><?= number_format((float)$p['quantite']) ?></td>
                            <td><?= h($p['motif'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lots générés -->
    <?php if (!empty($prod['lots'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Lots générés</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Lot</th><th>Produit</th><th>Quantité</th><th>Coût unitaire</th><th>Magasin</th></tr></thead>
                <tbody>
                    <?php foreach ($prod['lots'] as $lot): ?>
                        <tr>
                            <td><code><?= h($lot['numero_lot']) ?></code></td>
                            <td><?= h($lot['article_nom']) ?></td>
                            <td><?= (int)$lot['quantite'] ?></td>
                            <td><?= number_format((float)$lot['cout_unitaire'], 0) ?></td>
                            <td><?= h($lot['magasin_nom']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Clôturer Production -->
    <?php if ($prod['statut'] === 'EN_COURS'): ?>
    <div class="modal fade" id="modalCloturer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="productions.php?action=cloturer">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-check-circle text-primary me-2"></i>Clôturer la production <?= h($prod['reference']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantité produite *</label>
                                <input type="number" name="quantite_produite" class="form-control" min="0" required value="<?= (int)$prod['quantite_prevue'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantité perdue</label>
                                <input type="number" name="quantite_perdue" class="form-control" min="0" value="0">
                            </div>
                        </div>
                        <h6 class="fw-bold mb-3">Consommation réelle des matières premières</h6>
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Matière</th><th>Prévue</th><th class="w-25">Réelle *</th></tr></thead>
                            <tbody>
                                <?php foreach ($prod['matieres'] as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><?= h($m['matiere_nom']) ?></td>
                                    <td><?= number_format((float)$m['quantite_prevue'], 2) ?> <?= h($m['unite']) ?></td>
                                    <td>
                                        <input type="hidden" name="mp_id[]" value="<?= $m['matiere_id'] ?>">
                                        <input type="number" name="mp_quantite_reelle[]" class="form-control form-control-sm" min="0" step="0.01" required value="<?= number_format((float)$m['quantite_prevue'], 2, '.', '') ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Clôturer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Liste
$statut_filter = $_GET['statut'] ?? null;
$productions = db_productions_list($pdo, $statut_filter);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Productions</h2>
    </div>
    <?php if (peut('production_gerer')): ?>
    <a href="productions.php?action=nouveau" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle production
    </a>
    <?php endif; ?>
</div>

<!-- Filtres -->
<div class="mb-3">
    <div class="btn-group btn-group-sm">
        <a href="productions.php" class="btn <?= !$statut_filter ? 'btn-primary' : 'btn-outline-primary' ?>">Toutes</a>
        <?php foreach (['BROUILLON','PLANIFIEE','EN_COURS','TERMINEE','ANNULEE'] as $s): ?>
            <a href="productions.php?statut=<?= $s ?>" class="btn <?= $statut_filter === $s ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Référence</th><th>Produit</th><th>Recette</th>
                    <th class="text-center">Prévu</th><th class="text-center">Produit</th>
                    <th class="text-center">Pertes</th><th>Statut</th><th>Date</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productions as $p): ?>
                    <tr>
                        <td class="fw-semibold"><a href="productions.php?action=detail&id=<?= $p['id'] ?>"><?= h($p['reference']) ?></a></td>
                        <td><?= h($p['article_nom']) ?></td>
                        <td><?= h($p['recette_nom']) ?></td>
                        <td class="text-center"><?= number_format((int)$p['quantite_prevue']) ?></td>
                        <td class="text-center text-success fw-bold"><?= number_format((int)$p['quantite_produite']) ?></td>
                        <td class="text-center text-danger"><?= number_format((int)$p['quantite_perdue']) ?></td>
                        <td>
                            <?php
                            $badge = ['BROUILLON'=>'bg-secondary','PLANIFIEE'=>'bg-info','EN_COURS'=>'bg-warning text-dark','TERMINEE'=>'bg-success','ANNULEE'=>'bg-danger'];
                            ?>
                            <span class="badge <?= $badge[$p['statut']] ?? 'bg-secondary' ?>"><?= $p['statut'] ?></span>
                        </td>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($p['date_creation'])) ?></td>
                        <td class="text-end">
                            <a href="productions.php?action=detail&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($productions)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Aucune production.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
