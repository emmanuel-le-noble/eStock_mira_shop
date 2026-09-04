<?php
/**
 * pertes.php - Historique des pertes fournisseurs.
 *
 * Affiche les pertes enregistrées lors des réceptions (produits endommagés,
 * manquants, expirés, etc.) avec filtres par fournisseur, magasin et période.
 */
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_pertes_list')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard')) { require_once __DIR__ . '/includes/helpers.php'; }
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('pertes_consulter');

$filters = [
    'fournisseur_id' => $_GET['fournisseur_id'] ?? '',
    'magasin_id'     => $_GET['magasin_id'] ?? '',
    'date_debut'     => $_GET['date_debut'] ?? '',
    'date_fin'       => $_GET['date_fin'] ?? '',
];

$pertes = db_pertes_list(
    $pdo,
    !empty($filters['fournisseur_id']) ? (int)$filters['fournisseur_id'] : null,
    !empty($filters['magasin_id']) ? (int)$filters['magasin_id'] : null,
    !empty($filters['date_debut']) ? $filters['date_debut'] : null,
    !empty($filters['date_fin']) ? $filters['date_fin'] : null,
    500
);

$fournisseurs = db_fournisseurs_list($pdo);
$magasins = db_magasins_list_all($pdo);
$titre_page = 'Pertes fournisseur';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <a href="exports.php?type=pertes" class="btn btn-outline-success" target="_blank">
        <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="pertes.php" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Fournisseur</label>
                <select name="fournisseur_id" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <?php foreach ($fournisseurs as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $filters['fournisseur_id'] == $f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Magasin</label>
                <select name="magasin_id" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <?php foreach ($magasins as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= $filters['magasin_id'] == $m['id'] ? 'selected' : '' ?>>
                            <?= h($m['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date début</label>
                <input type="date" name="date_debut" class="form-select form-select-sm" value="<?= h($filters['date_debut']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date fin</label>
                <input type="date" name="date_fin" class="form-select form-select-sm" value="<?= h($filters['date_fin']) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="pertes.php" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$total_quantite = 0;
foreach ($pertes as $p) {
    $total_quantite += (int)$p['quantite'];
}
?>

<?php if ($total_quantite > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>
        <strong><?= count($pertes) ?></strong> perte(s) enregistrée(s) — total : <strong><?= $total_quantite ?></strong> unité(s) perdue(s)
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Article</th>
                    <th>Fournisseur</th>
                    <th>Magasin</th>
                    <th class="text-center">Quantité</th>
                    <th>Motif</th>
                    <th>Réception</th>
                    <th>Enregistré par</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pertes)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Aucune perte enregistrée.
                        </td>
                    </tr>
                <?php else: foreach ($pertes as $p): ?>
                    <tr>
                        <td class="small"><?= date_fr($p['date_perte']) ?></td>
                        <td class="fw-semibold"><?= h($p['article_nom'] ?? '—') ?></td>
                        <td><?= h($p['fournisseur_nom'] ?? '—') ?></td>
                        <td><?= h($p['magasin_nom'] ?? '—') ?></td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-6"><?= (int)$p['quantite'] ?></span>
                        </td>
                        <td>
                            <?php
                            $motifs_labels = [
                                'endommage' => 'Endommagé',
                                'manquant' => 'Manquant',
                                'expire' => 'Expiré',
                                'non_conforme' => 'Non conforme',
                                'casse_livraison' => 'Casse livraison',
                                'erreur_fournisseur' => 'Erreur fournisseur',
                                'autre' => 'Autre',
                            ];
                            $label = $motifs_labels[$p['motif']] ?? $p['motif'];
                            ?>
                            <span class="badge bg-warning text-dark"><?= h($label) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($p['reception_reference'])): ?>
                                <?= h($p['reception_reference']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= h($p['utilisateur_nom'] ?? '—') ?></td>
                        <td class="small text-muted"><?= h($p['commentaire'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
