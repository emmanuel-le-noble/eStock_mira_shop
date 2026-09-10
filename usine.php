<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_usine_dashboard')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('usine_consulter');

$titre_page = 'Tableau de bord — Usine';

// Charger le dashboard
$dashboard = db_usine_dashboard($pdo);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold">
            <i class="bi bi-building text-primary me-2"></i>Usine de Production
        </h2>
        <p class="text-muted small mb-0">Tableau de bord du jour — <?= date('d/m/Y') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="productions.php?action=nouveau" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nouvelle Production
        </a>
        <a href="stock_usine.php" class="btn btn-outline-secondary">
            <i class="bi bi-boxes"></i> Stock Usine
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card is-primary">
            <div class="stat-icon"><i class="bi bi-gear-wide-connected"></i></div>
            <div class="stat-label">Productions aujourd'hui</div>
            <div class="stat-value"><?= (int)($dashboard['productions_jour']['nb'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-success">
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Produits fabriqués</div>
            <div class="stat-value"><?= number_format((int)($dashboard['productions_jour']['produits'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-warning">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-label">Pertes</div>
            <div class="stat-value"><?= number_format((int)($dashboard['productions_jour']['pertes'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card is-info">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-label">Employés présents</div>
            <div class="stat-value"><?= (int)$dashboard['employes_presents'] ?> / <?= (int)$dashboard['total_employes'] ?></div>
        </div>
    </div>
</div>

<!-- Alertes matières + Stock produits finis -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-exclamation-diamond text-warning me-2"></i>Alertes matières
                    <?php if ($dashboard['matieres_alerte'] > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $dashboard['matieres_alerte'] ?></span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr><th>Matière</th><th>Stock</th><th>Seuil</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard['matieres_stock'] as $m): ?>
                                <tr class="<?= $m['quantite'] <= $m['stock_minimum'] ? 'table-warning' : '' ?>">
                                    <td class="fw-semibold"><?= h($m['nom']) ?></td>
                                    <td><?= number_format((float)$m['quantite']) ?> <?= h($m['unite_mesure']) ?></td>
                                    <td><?= (int)$m['stock_minimum'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-box-seam text-success me-2"></i>Stock produits finis (usine)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr><th>Produit</th><th class="text-end">Quantité</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard['produits_finis'] as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= h($p['nom']) ?></td>
                                    <td class="text-end"><?= number_format((int)$p['quantite']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dashboard['produits_finis'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Aucun produit fini en stock.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-3">
    <div class="col-md-3">
        <a href="matieres_premieres.php" class="card shadow-sm text-decoration-none">
            <div class="card-body text-center py-4">
                <i class="bi bi-droplet fs-2 text-primary mb-2"></i>
                <h6 class="fw-bold">Matières premières</h6>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="recettes.php" class="card shadow-sm text-decoration-none">
            <div class="card-body text-center py-4">
                <i class="bi bi-journal-text fs-2 text-info mb-2"></i>
                <h6 class="fw-bold">Recettes</h6>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="personnel.php" class="card shadow-sm text-decoration-none">
            <div class="card-body text-center py-4">
                <i class="bi bi-people fs-2 text-success mb-2"></i>
                <h6 class="fw-bold">Personnel</h6>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="presences.php" class="card shadow-sm text-decoration-none">
            <div class="card-body text-center py-4">
                <i class="bi bi-clock-history fs-2 text-warning mb-2"></i>
                <h6 class="fw-bold">Présences</h6>
            </div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
