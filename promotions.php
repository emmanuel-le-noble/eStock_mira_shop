<?php
/**
 * promotions.php — Promotions (codes promo) + Ventes Flash (règles auto).
 */
if (!function_exists('est_connecte'))    { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_article_insert')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard'))      { require_once __DIR__ . '/includes/helpers.php'; }

exiger_permission('promotions_consulter');

$action = $_GET['action'] ?? 'liste';
$tab = $_GET['tab'] ?? 'promo';

// ---- Traitement POST : enregistrer une promotion (code promo) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enregistrer') {
    csrf_guard('promotions.php');
    exiger_permission('promotions_gerer');

    $data = extract_post_data([
        'id'                  => ['type' => 'int', 'default' => 0],
        'nom'                 => ['type' => 'string', 'trim' => true, 'required' => true, 'max' => 150, 'redirect' => 'promotions.php'],
        'code_promo'          => ['type' => 'string', 'trim' => true, 'nullable' => true, 'max' => 40, 'redirect' => 'promotions.php'],
        'type_reduction'      => ['type' => 'string', 'default' => 'pourcentage'],
        'valeur'              => ['type' => 'float', 'min' => 0],
        'article_id'          => ['type' => 'int', 'default' => 0],
        'categorie_id'        => ['type' => 'int', 'default' => 0],
        'montant_min_achat'   => ['type' => 'float', 'min' => 0, 'default' => 0],
        'date_debut'          => ['type' => 'string', 'nullable' => true],
        'date_fin'            => ['type' => 'string', 'nullable' => true],
        'limite_utilisations' => ['type' => 'int', 'nullable' => true],
        'actif'               => ['type' => 'int', 'default' => 1],
    ], 'promotions.php');

    $id = $data['id'];
    db_transaction(
        function(PDO $pdo) use ($data, $id) {
            if ($id > 0) {
                db_promotion_update($pdo, $id, $data);
            } else {
                db_promotion_insert($pdo, $data);
            }
        },
        'Promotion enregistrée avec succès.',
        'Erreur lors de l\'enregistrement.',
        'promotions.php'
    );

    suivre_activite('PROMOTION_ENREGISTREMENT', ($id > 0 ? 'Modification' : 'Création') . ' promotion : ' . $data['nom']);
    redirect('promotions.php');
}

// ---- Traitement POST : désactiver une promotion ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'desactiver') {
    csrf_guard('promotions.php');
    exiger_permission('promotions_gerer');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_promotion_deactivate($pdo, $id);
        suivre_activite('PROMOTION_DESACTIVATION', 'Désactivation promotion #' . $id);
        flash_success('Promotion désactivée.');
    }
    redirect('promotions.php');
}

// ============================================================
//  TRAITEMENT POST : SAUVEGARDE D'UNE RÈGLE DE VENTE FLASH
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_flash') {
    csrf_guard('promotions.php?tab=flash');
    exiger_permission('promotions_gerer');

    $promo_data = [
        'nom'                 => input_string($_POST['nom'] ?? ''),
        'condition_type'      => $_POST['condition_type'] ?? 'PEREMPTION_PROCHE',
        'jours_limite'        => ($_POST['jours_limite'] ?? '') !== '' ? (int)($_POST['jours_limite'] ?? 0) : null,
        'seuil_stock'         => ($_POST['seuil_stock'] ?? '') !== '' ? (int)($_POST['seuil_stock'] ?? 0) : null,
        'pourcentage_remise'  => (float)($_POST['pourcentage_remise'] ?? 0),
        'actif'               => isset($_POST['actif']) ? 1 : 0,
    ];

    $erreurs_promo = [];
    if ($promo_data['nom'] === '') {
        $erreurs_promo[] = 'Le nom de la règle est obligatoire.';
    }
    if ($promo_data['pourcentage_remise'] <= 0 || $promo_data['pourcentage_remise'] > 100) {
        $erreurs_promo[] = 'Le pourcentage de remise doit être compris entre 0 et 100.';
    }
    if ($promo_data['condition_type'] === 'PEREMPTION_PROCHE') {
        if ($promo_data['jours_limite'] === null || $promo_data['jours_limite'] <= 0) {
            $erreurs_promo[] = 'Le nombre de jours limite est obligatoire pour une règle de péremption.';
        }
    } elseif ($promo_data['condition_type'] === 'SURSTOCK') {
        if ($promo_data['seuil_stock'] === null || $promo_data['seuil_stock'] <= 0) {
            $erreurs_promo[] = 'Le seuil de stock est obligatoire pour une règle de surstock.';
        }
    }

    if (!empty($erreurs_promo)) {
        flash_error(implode(' ', $erreurs_promo));
        redirect('promotions.php?tab=flash');
    }

    if (isset($_POST['promo_id']) && (int)$_POST['promo_id'] > 0) {
        $promo_data['id'] = (int)$_POST['promo_id'];
    }

    db_promotion_save($pdo, $promo_data);
    suivre_activite('MODIFICATION_PROMOTION', 'Sauvegarde règle vente flash : ' . $promo_data['nom']);
    flash_success('Règle de vente flash enregistrée.');
    redirect('promotions.php?tab=flash');
}

// ============================================================
//  TRAITEMENT POST : SUPPRESSION D'UNE RÈGLE DE VENTE FLASH
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_flash') {
    csrf_guard('promotions.php?tab=flash');
    exiger_permission('promotions_gerer');

    $promo_id = (int)($_POST['promo_id'] ?? 0);
    if ($promo_id > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM regles_promotions WHERE id = ?");
            $stmt->execute([$promo_id]);
            $pdo->commit();
            suivre_activite('SUPPRESSION_PROMOTION', 'Suppression règle vente flash #' . $promo_id);
            flash_success('Règle de vente flash supprimée avec succès.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur critique suppression promotion: " . $e->getMessage());
            flash_error("Une erreur système est survenue lors de la suppression.");
        }
    }
    redirect('promotions.php?tab=flash');
}

// ---- Données communes ----
$promotions_all = db_promotions_get_all($pdo);
$peut_gerer = peut('promotions_gerer');

include __DIR__ . '/includes/header.php';
?>

<!-- Sous-navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'promo' ? 'active' : '' ?>" href="?tab=promo">
            <i class="bi bi-percent"></i> Promotions & Codes Promo
        </a>
    </li>
    <?php if ($peut_gerer): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'flash' ? 'active' : '' ?>" href="?tab=flash">
            <i class="bi bi-lightning"></i> Ventes Flash
        </a>
    </li>
    <?php endif; ?>
</ul>

<?php if ($tab === 'flash'): ?>
<!-- ============================================================ -->
<!--  ONGLET : VENTES FLASH — RÈGLES DE REMISE AUTO              -->
<!-- ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-lightning text-warning"></i> Ventes Flash — Règles de Remise</h4>
        <p class="text-muted mb-0 small">Configurez les remises automatiques pour les articles proches de la péremption ou en surstock.</p>
    </div>
    <?php if ($peut_gerer): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPromotion" id="btnAjouterPromo">
        <i class="bi bi-plus-lg"></i> Nouvelle règle
    </button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card is-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Règles actives</div>
                    <div class="stat-value"><?= count(array_filter($promotions_all ?? [], fn($r) => (int)$r['actif'] === 1)) ?></div>
                </div>
                <span class="stat-icon"><i class="bi bi-lightning"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card is-danger">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Anti-gaspillage</div>
                    <div class="stat-value"><?= count(array_filter($promotions_all ?? [], fn($r) => $r['condition_type'] === 'PEREMPTION_PROCHE')) ?></div>
                </div>
                <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card is-info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Surstock</div>
                    <div class="stat-value"><?= count(array_filter($promotions_all ?? [], fn($r) => $r['condition_type'] === 'SURSTOCK')) ?></div>
                </div>
                <span class="stat-icon"><i class="bi bi-box-seam"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="glass-card mb-4">
    <div class="glass-header">
        <i class="bi bi-list-check"></i>
        <span>Règles configurées</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Condition</th>
                        <th class="text-center">Remise</th>
                        <th class="text-center">Statut</th>
                        <?php if ($peut_gerer): ?>
                        <th class="text-center">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotions_all ?? [] as $promo): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($promo['nom']) ?></td>
                        <td class="text-center">
                            <?php if ($promo['condition_type'] === 'PEREMPTION_PROCHE'): ?>
                                <span class="badge glass-badge-qty is-warning"><i class="bi bi-clock-history"></i> Péremption</span>
                            <?php else: ?>
                                <span class="badge glass-badge-qty is-info"><i class="bi bi-box-seam"></i> Surstock</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($promo['condition_type'] === 'PEREMPTION_PROCHE'): ?>
                                ≤ <?= (int)$promo['jours_limite'] ?> jours
                            <?php else: ?>
                                ≥ <?= (int)$promo['seuil_stock'] ?> unités
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge glass-badge-qty is-danger">−<?= h(number_format((float)$promo['pourcentage_remise'], 0, ',', '')) ?>%</span>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$promo['actif'] === 1): ?>
                                <span class="badge glass-badge-qty is-success">Actif</span>
                            <?php else: ?>
                                <span class="badge glass-badge-qty is-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($peut_gerer): ?>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-promo"
                                    data-id="<?= $promo['id'] ?>"
                                    data-nom="<?= h($promo['nom']) ?>"
                                    data-type="<?= h($promo['condition_type']) ?>"
                                    data-jours="<?= h($promo['jours_limite'] ?? '') ?>"
                                    data-seuil="<?= h($promo['seuil_stock'] ?? '') ?>"
                                    data-remise="<?= h($promo['pourcentage_remise']) ?>"
                                    data-actif="<?= $promo['actif'] ?>"
                                    title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="promotions.php?action=delete_flash" class="d-inline" data-confirm="Supprimer cette règle ?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="promo_id" value="<?= $promo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($promotions_all)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-lightning fs-1 d-block mb-2"></i>
                            Aucune règle configurée. Ajoutez-en une pour activer les ventes flash.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($peut_gerer): ?>
<div class="modal fade" id="modalPromotion" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="promotions.php?action=save_flash" class="modal-content" id="formPromotion">
            <?= csrf_field() ?>
            <input type="hidden" name="promo_id" id="promoId" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-lightning"></i> <span id="promoModalTitle">Nouvelle règle</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom de la règle *</label>
                    <input type="text" name="nom" id="promoNom" class="form-control" required
                           placeholder="ex : Anti-gaspillage - 7 jours" maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label">Type de condition *</label>
                    <select name="condition_type" id="promoType" class="form-select" required>
                        <option value="PEREMPTION_PROCHE">Péremption proche (DLC/DDM)</option>
                        <option value="SURSTOCK">Surstock (quantité en stock)</option>
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6" id="grpJours">
                        <label class="form-label">Jours limite</label>
                        <input type="number" name="jours_limite" id="promoJours" class="form-control"
                               min="1" max="365" placeholder="ex : 7">
                        <div class="form-text">Appliquer la remise si ≤ X jours avant DLC.</div>
                    </div>
                    <div class="col-6" id="grpSeuil" style="display:none">
                        <label class="form-label">Seuil stock</label>
                        <input type="number" name="seuil_stock" id="promoSeuil" class="form-control"
                               min="1" placeholder="ex : 100">
                        <div class="form-text">Appliquer la remise si stock ≥ X unités.</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Pourcentage de remise *</label>
                        <div class="input-group">
                            <input type="number" name="pourcentage_remise" id="promoRemise" class="form-control"
                                   min="1" max="90" step="0.5" required placeholder="ex : 15">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="actif" id="promoActif" value="1" checked>
                    <label class="form-check-label fw-semibold" for="promoActif">Règle active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script nonce="<?= h(csp_nonce()) ?>">
document.addEventListener('DOMContentLoaded', function() {
    var promoType = document.getElementById('promoType');
    var grpJours = document.getElementById('grpJours');
    var grpSeuil = document.getElementById('grpSeuil');

    function toggleConditionFields() {
        if (!promoType || !grpJours || !grpSeuil) return;
        if (promoType.value === 'PEREMPTION_PROCHE') {
            grpJours.style.display = '';
            grpSeuil.style.display = 'none';
        } else {
            grpJours.style.display = 'none';
            grpSeuil.style.display = '';
        }
    }
    if (promoType) {
        promoType.addEventListener('change', toggleConditionFields);
        toggleConditionFields();
    }

    document.querySelectorAll('.btn-edit-promo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var el;
            el = document.getElementById('promoModalTitle'); if (el) el.textContent = 'Modifier la règle';
            el = document.getElementById('promoId'); if (el) el.value = btn.dataset.id;
            el = document.getElementById('promoNom'); if (el) el.value = btn.dataset.nom;
            el = document.getElementById('promoType'); if (el) el.value = btn.dataset.type;
            el = document.getElementById('promoJours'); if (el) el.value = btn.dataset.jours;
            el = document.getElementById('promoSeuil'); if (el) el.value = btn.dataset.seuil;
            el = document.getElementById('promoRemise'); if (el) el.value = btn.dataset.remise;
            el = document.getElementById('promoActif'); if (el) el.checked = btn.dataset.actif === '1';
            toggleConditionFields();
            var modal = document.getElementById('modalPromotion');
            if (modal) new bootstrap.Modal(modal).show();
        });
    });

    var btnAjouter = document.getElementById('btnAjouterPromo');
    if (btnAjouter) {
        btnAjouter.addEventListener('click', function() {
            var el;
            el = document.getElementById('promoModalTitle'); if (el) el.textContent = 'Nouvelle règle';
            el = document.getElementById('promoId'); if (el) el.value = '';
            el = document.getElementById('formPromotion'); if (el) el.reset();
            el = document.getElementById('promoActif'); if (el) el.checked = true;
            toggleConditionFields();
        });
    }
});
</script>

<?php else: ?>
<!-- ============================================================ -->
<!--  ONGLET : PROMOTIONS & CODES PROMO                           -->
<!-- ============================================================ -->
<?php
$filters = [
    'search' => $_GET['search'] ?? '',
    'actif'  => $_GET['actif'] ?? '',
];
$built = db_promotions_search_sql($filters);
$pagination = paginate($built['sql'], $built['params'], 15);

$articles   = db_articles_list_active($pdo, 1000);
$categories = function_exists('db_categories_all') ? db_categories_all($pdo) : [];
$devise = param('devise_symbole', 'FCFA');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0"><i class="bi bi-percent"></i> Promotions & Codes Promo</h1>
    <?php if ($peut_gerer): ?>
    <button class="btn btn-primary" id="btnNewPromo" data-bs-toggle="modal" data-bs-target="#modalPromo">
        <i class="bi bi-plus-lg"></i> Nouvelle promotion
    </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="promotions.php?tab=promo" class="row g-2">
            <input type="hidden" name="tab" value="promo">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Nom ou code promo…" value="<?= h($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <select name="actif" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="1" <?= $filters['actif'] === '1' ? 'selected' : '' ?>>Actifs</option>
                    <option value="0" <?= $filters['actif'] === '0' ? 'selected' : '' ?>>Inactifs</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrer</button>
                <a href="promotions.php?tab=promo" class="btn btn-outline-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Code Promo</th>
                    <th>Type / Valeur</th>
                    <th>Cible</th>
                    <th>Min. Achat</th>
                    <th>Période</th>
                    <th>Utilisations</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pagination['data'] as $row): ?>
            <tr class="<?= !$row['actif'] ? 'table-secondary opacity-75' : '' ?>">
                <td><strong><?= h($row['nom']) ?></strong></td>
                <td>
                    <?php if (!empty($row['code_promo'])): ?>
                        <code class="fs-6 text-primary"><?= h($row['code_promo']) ?></code>
                    <?php else: ?>
                        <span class="text-muted small">Auto</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['type_reduction'] === 'pourcentage'): ?>
                        <span class="badge bg-danger fs-6">-<?= (int)$row['valeur'] ?>%</span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6">-<?= h(number_format((float)$row['valeur'], 0, ',', ' ')) ?> <?= h($devise) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['article_nom'])): ?>
                        <i class="bi bi-box-seam"></i> <?= h($row['article_nom']) ?>
                    <?php elseif (!empty($row['categorie_nom'])): ?>
                        <i class="bi bi-tags"></i> Cat. <?= h($row['categorie_nom']) ?>
                    <?php else: ?>
                        <span class="text-muted">Tout le catalogue</span>
                    <?php endif; ?>
                </td>
                <td><?= $row['montant_min_achat'] > 0 ? h(number_format((float)$row['montant_min_achat'], 0, ',', ' ')) . ' ' . h($devise) : '—' ?></td>
                <td class="small">
                    <?= !empty($row['date_debut']) ? date('d/m/Y', strtotime($row['date_debut'])) : '—' ?>
                    au
                    <?= !empty($row['date_fin']) ? date('d/m/Y', strtotime($row['date_fin'])) : '—' ?>
                </td>
                <td class="text-center">
                    <?= (int)$row['nb_utilisations'] ?> / <?= $row['limite_utilisations'] ? (int)$row['limite_utilisations'] : '∞' ?>
                </td>
                <td>
                    <?php if ($row['actif']): ?>
                        <span class="badge bg-success">Actif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($peut_gerer): ?>
                    <button class="btn btn-sm btn-outline-primary me-1" data-edit-promo="<?= h(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php if ($row['actif']): ?>
                    <form method="post" action="promotions.php?action=desactiver" class="d-inline" data-confirm="Désactiver cette promotion ?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Aucune promotion enregistrée.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <?php
        $base_qs = 'promotions.php?tab=promo';
        if (!empty($filters['search'])) $base_qs .= '&search=' . urlencode($filters['search']);
        if (!empty($filters['actif'])) $base_qs .= '&actif=' . urlencode($filters['actif']);
        pagination_links($pagination['page'], $pagination['total_pages'], $base_qs);
        ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($peut_gerer): ?>
<div class="modal fade" id="modalPromo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" action="promotions.php?action=enregistrer" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="promoId" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nouvelle promotion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nom de la promotion *</label>
                    <input type="text" name="nom" id="promoNom" class="form-control" required placeholder="ex : Solde d'été 20%">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code Promo (optionnel)</label>
                    <input type="text" name="code_promo" id="promoCode" class="form-control text-uppercase" placeholder="ex : SUMMER20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type de réduction *</label>
                    <select name="type_reduction" id="promoType" class="form-select">
                        <option value="pourcentage">Pourcentage (%)</option>
                        <option value="montant_fixe">Montant fixe (<?= h($devise) ?>)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valeur *</label>
                    <input type="number" step="0.01" min="0" name="valeur" id="promoValeur" class="form-control" required placeholder="ex : 20 pour 20%">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Restreindre à un article (optionnel)</label>
                    <select name="article_id" id="promoArticle" class="form-select">
                        <option value="">Tous les articles</option>
                        <?php foreach ($articles as $art): ?>
                        <option value="<?= $art['id'] ?>"><?= h($art['nom']) ?> (<?= h($art['code_barre']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Restreindre à une catégorie (optionnel)</label>
                    <select name="categorie_id" id="promoCat" class="form-select">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Montant min. achat</label>
                    <input type="number" step="0.01" min="0" name="montant_min_achat" id="promoMinAchat" class="form-control" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" id="promoDebut" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" id="promoFin" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Limite d'utilisations (laisser vide = illimité)</label>
                    <input type="number" min="1" name="limite_utilisations" id="promoLimite" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="actif" id="promoActif" class="form-select">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script nonce="<?= h(csp_nonce()) ?>">
document.addEventListener('DOMContentLoaded', function() {
    function resetForm() {
        document.getElementById('promoId').value = 0;
        document.getElementById('modalTitle').textContent = 'Nouvelle promotion';
        document.getElementById('promoNom').value = '';
        document.getElementById('promoCode').value = '';
        document.getElementById('promoType').value = 'pourcentage';
        document.getElementById('promoValeur').value = '';
        document.getElementById('promoArticle').value = '';
        document.getElementById('promoCat').value = '';
        document.getElementById('promoMinAchat').value = 0;
        document.getElementById('promoDebut').value = '';
        document.getElementById('promoFin').value = '';
        document.getElementById('promoLimite').value = '';
        document.getElementById('promoActif').value = 1;
    }

    function editPromo(p) {
        document.getElementById('promoId').value = p.id;
        document.getElementById('modalTitle').textContent = 'Modifier promotion #' + p.id;
        document.getElementById('promoNom').value = p.nom;
        document.getElementById('promoCode').value = p.code_promo || '';
        document.getElementById('promoType').value = p.type_reduction;
        document.getElementById('promoValeur').value = p.valeur;
        document.getElementById('promoArticle').value = p.article_id || '';
        document.getElementById('promoCat').value = p.categorie_id || '';
        document.getElementById('promoMinAchat').value = p.montant_min_achat;
        document.getElementById('promoDebut').value = p.date_debut ? p.date_debut.substring(0, 10) : '';
        document.getElementById('promoFin').value = p.date_fin ? p.date_fin.substring(0, 10) : '';
        document.getElementById('promoLimite').value = p.limite_utilisations || '';
        document.getElementById('promoActif').value = p.actif;
        var modal = new bootstrap.Modal(document.getElementById('modalPromo'));
        modal.show();
    }

    var btnNew = document.getElementById('btnNewPromo');
    if (btnNew) btnNew.addEventListener('click', function() { resetForm(); });

    document.querySelectorAll('[data-edit-promo]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            editPromo(JSON.parse(btn.getAttribute('data-edit-promo')));
        });
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
