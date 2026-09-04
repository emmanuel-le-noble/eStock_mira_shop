<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_stock_mp_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('usine_consulter');
$titre_page = 'Stock usine';

// POST: transfert usine → magasin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_guard('stock_usine.php');
    $article_id = (int)($_POST['article_id'] ?? 0);
    $destination = (int)($_POST['magasin_destination_id'] ?? 0);
    $quantite = (int)($_POST['quantite'] ?? 0);

    if ($article_id > 0 && $destination > 0 && $quantite > 0) {
        try {
            db_transfert_usine_vers_magasin($pdo, $article_id, $destination, $quantite);
            suivre_activite('TRANSFERT_USINE_MAGASIN', "Article #$article_id → Magasin #$destination: $quantite unités");
            flash_success('Transfert effectué.');
        } catch (Throwable $e) {
            flash_error($e->getMessage());
        }
    }
    redirect('stock_usine.php');
}

// Charger les deux types de stock
$stock_mp = db_stock_mp_list($pdo);
$stock_pf = db_stock_pf_usine_list($pdo);
$magasins = $pdo->query("SELECT id, nom FROM magasins WHERE actif = 1 ORDER BY nom")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-boxes text-primary me-2"></i>Stock usine</h2>
        <p class="text-muted small mb-0">Matières premières et produits finis en usine</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <!-- MATIÈRES PREMIÈRES -->
        <h6 class="px-3 pt-3"><i class="bi bi-droplet text-info"></i> Matières premières</h6>
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th><th>Nom</th><th>Réf.</th><th>Unité</th>
                    <th class="text-end">Quantité</th><th class="text-end">Valeur</th>
                    <th class="text-end">Seuil</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_mp as $s): ?>
                    <tr class="<?= (float)($s['quantite'] ?? 0) <= ($s['stock_minimum'] ?? 10) ? 'table-warning' : '' ?>">
                        <td class="text-muted">#<?= $s['id'] ?></td>
                        <td class="fw-semibold"><?= h($s['nom']) ?></td>
                        <td><code><?= h($s['reference'] ?? '—') ?></code></td>
                        <td><?= h($s['unite_mesure']) ?></td>
                        <td class="text-end fw-bold"><?= number_format((float)$s['quantite']) ?> <?= h($s['unite_mesure']) ?></td>
                        <td class="text-end"><?= money($s['valeur_stock'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($s['stock_minimum'] ?? 10) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($stock_mp)): ?>
                    <tr><td colspan="7" class="text-center py-3 text-muted">Aucune matière première en stock.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- PRODUITS FINIS -->
        <h6 class="px-3 pt-4"><i class="bi bi-box-seam text-success"></i> Produits finis</h6>
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th><th>Nom</th><th>Code-barres</th><th>Unité</th>
                    <th class="text-end">Quantité</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_pf as $s): ?>
                    <tr>
                        <td class="text-muted">#<?= $s['id'] ?></td>
                        <td class="fw-semibold"><?= h($s['nom']) ?></td>
                        <td><code><?= h($s['code_barre']) ?></code></td>
                        <td><?= h($s['unite_mesure']) ?></td>
                        <td class="text-end fw-bold"><?= number_format((float)$s['quantite']) ?></td>
                        <td class="text-end">
                            <?php if ((float)$s['quantite'] > 0 && !empty($magasins) && peut('transferts_gerer')): ?>
                                <button class="btn btn-sm btn-outline-success btn-transferer"
                                        data-article-id="<?= $s['id'] ?>"
                                        data-article-nom="<?= h($s['nom']) ?>"
                                        data-qte-max="<?= (int)$s['quantite'] ?>">
                                    <i class="bi bi-arrow-right-circle"></i> Transférer
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($stock_pf)): ?>
                    <tr><td colspan="6" class="text-center py-3 text-muted">Aucun produit fini en usine.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </table>
    </div>
</div>

<!-- Modal transfert -->
<div class="modal fade" id="modalTransfert" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="stock_usine.php">
                <?= csrf_field() ?>
                <input type="hidden" name="article_id" id="txArticleId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Transfert usine → magasin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Article: <strong id="txArticleNom"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Magasin destination *</label>
                        <select name="magasin_destination_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($magasins as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= h($m['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantité *</label>
                        <input type="number" name="quantite" id="txQte" class="form-control" required min="1">
                        <small class="text-muted">Disponible: <span id="txQteMax"></span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-arrow-right-circle"></i> Transférer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce_val() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-transferer').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('txArticleId').value = this.dataset.articleId;
            document.getElementById('txArticleNom').textContent = this.dataset.articleNom;
            document.getElementById('txQte').max = this.dataset.qteMax;
            document.getElementById('txQteMax').textContent = this.dataset.qteMax;
            new bootstrap.Modal(document.getElementById('modalTransfert')).show();
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
