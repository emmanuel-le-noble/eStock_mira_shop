<?php
/**
 * receptions.php - Historique des réceptions fournisseurs.
 *
 * Affiche la liste de toutes les réceptions avec détail des lignes.
 * Permet de consulter l'historique complet des livraisons reçues.
 */
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_reception_get_by_id')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard')) { require_once __DIR__ . '/includes/helpers.php'; }
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('receptions_consulter');

$action = $_GET['action'] ?? 'liste';
$id_get = (int)($_GET['id'] ?? 0);

// ---- VUE DÉTAIL D'UNE RÉCEPTION ----
if ($action === 'voir' && $id_get > 0) {
    if (!verify_url_signature($id_get, input_string($_GET['token'] ?? ''), ['action' => 'voir'])) {
        flash_error('Lien expiré ou invalide.');
        redirect('receptions.php');
    }

    $reception = db_reception_get_by_id($pdo, $id_get);
    if (!$reception) {
        flash_error('Réception introuvable.');
        redirect('receptions.php');
    }

    $lignes = db_reception_lignes($pdo, $id_get);
    $titre_page = 'Réception ' . h($reception['reference']);
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="h4 mb-0 fw-bold">
                <i class="bi bi-box-seam text-primary me-2"></i>
                Réception {{ reception.reference }}
            </h2>
            <p class="text-muted small mb-0">Détail de la réception fournisseur</p>
        </div>
        <div class="d-flex gap-2">
            <a href="receptions.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <a href="<?= h(generate_signed_url('commandes_fournisseur.php', (int)$reception['commande_id'], ['action' => 'voir'])) ?>" class="btn btn-outline-primary">
                <i class="bi bi-cart-plus"></i> Voir la commande
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><?= h($reception['reference']) ?></h5>
                            <p class="text-muted small mb-0">
                                Commande #<?= (int)$reception['commande_id'] ?> —
                                Créée par <?= h($reception['utilisateur_nom'] ?? '—') ?> le <?= date_fr($reception['date_reception']) ?>
                            </p>
                        </div>
                        <?php
                        $badge_class = ['Brouillon' => 'secondary', 'Validee' => 'success', 'Annulee' => 'danger'][$reception['statut']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $badge_class ?> fs-6"><?= h($reception['statut']) ?></span>
                    </div>

                    <div class="row g-3 mt-2 border-top pt-3">
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Fournisseur :</span>
                            <strong><?= h($reception['fournisseur_nom'] ?? '—') ?></strong>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Magasin :</span>
                            <strong><?= h($reception['magasin_nom'] ?? '—') ?></strong>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Date de réception :</span>
                            <span><?= date_fr($reception['date_reception']) ?></span>
                        </div>
                        <?php if (!empty($reception['commentaire'])): ?>
                        <div class="col-12">
                            <span class="text-muted small d-block">Commentaire :</span>
                            <div class="p-2 bg-light rounded small"><?= h($reception['commentaire']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <?php
                    $total_recu = 0;
                    $total_accepte = 0;
                    $total_perdu = 0;
                    foreach ($lignes as $l) {
                        $total_recu += (int)$l['quantite_recue'];
                        $total_accepte += (int)$l['quantite_acceptee'];
                        $total_perdu += (int)$l['quantite_perdue'];
                    }
                    ?>
                    <div class="mb-3">
                        <div class="text-muted small">Total reçu</div>
                        <div class="fs-3 fw-bold text-primary"><?= $total_recu ?></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="text-muted small">Accepté</div>
                                <div class="fs-5 fw-bold text-success"><?= $total_accepte ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="text-muted small">Perdu</div>
                                <div class="fs-5 fw-bold text-danger"><?= $total_perdu ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Lignes de réception</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Article</th>
                        <th class="text-center">Qté attendue</th>
                        <th class="text-center">Qté reçue</th>
                        <th class="text-center">Acceptée</th>
                        <th class="text-center">Perdue</th>
                        <th class="text-end">Prix achat</th>
                        <th>N° Lot</th>
                        <th>Motif perte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lignes)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune ligne.</td></tr>
                    <?php else: foreach ($lignes as $l): ?>
                        <tr>
                            <td class="fw-semibold"><?= h($l['article_nom'] ?? '—') ?></td>
                            <td class="text-center"><?= (int)$l['quantite_attendue'] ?></td>
                            <td class="text-center text-primary fw-bold"><?= (int)$l['quantite_recue'] ?></td>
                            <td class="text-center text-success fw-bold"><?= (int)$l['quantite_acceptee'] ?></td>
                            <td class="text-center text-danger fw-bold"><?= (int)$l['quantite_perdue'] ?></td>
                            <td class="text-end"><?= money($l['prix_achat_unitaire']) ?></td>
                            <td><code><?= h($l['numero_lot'] ?? '—') ?></code></td>
                            <td>
                                <?php if (!empty($l['motif_perte'])): ?>
                                    <span class="badge bg-danger"><?= h($l['motif_perte']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ---- LISTE DES RÉCEPTIONS ----
$filters = [
    'fournisseur_id' => $_GET['fournisseur_id'] ?? '',
    'magasin_id'     => $_GET['magasin_id'] ?? '',
    'statut'         => $_GET['statut'] ?? '',
];

$where = ["1=1"];
$params = [];

if (!empty($filters['fournisseur_id'])) {
    $where[] = "r.fournisseur_id = ?";
    $params[] = (int)$filters['fournisseur_id'];
}
if (!empty($filters['magasin_id'])) {
    $where[] = "r.magasin_id = ?";
    $params[] = (int)$filters['magasin_id'];
}
if (!empty($filters['statut'])) {
    $where[] = "r.statut = ?";
    $params[] = $filters['statut'];
}

$sql = "SELECT r.*, f.nom AS fournisseur_nom, m.nom AS magasin_nom, u.nom AS utilisateur_nom,
               CONCAT('CMD-', c.id) AS numero_commande,
               (SELECT COUNT(*) FROM reception_lignes rl WHERE rl.reception_id = r.id) AS nb_lignes,
               (SELECT COALESCE(SUM(rl.quantite_recue), 0) FROM reception_lignes rl WHERE rl.reception_id = r.id) AS total_recu,
               (SELECT COALESCE(SUM(rl.quantite_acceptee), 0) FROM reception_lignes rl WHERE rl.reception_id = r.id) AS total_accepte,
               (SELECT COALESCE(SUM(rl.quantite_perdue), 0) FROM reception_lignes rl WHERE rl.reception_id = r.id) AS total_perdu
        FROM receptions r
        JOIN fournisseurs f ON f.id = r.fournisseur_id
        JOIN magasins m ON m.id = r.magasin_id
        LEFT JOIN utilisateurs u ON u.id = r.utilisateur_id
        LEFT JOIN commandes_fournisseur c ON c.id = r.commande_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.date_reception DESC, r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$receptions = $stmt->fetchAll();

$fournisseurs = db_fournisseurs_list($pdo);
$magasins = db_magasins_list_all($pdo);
$titre_page = 'Réceptions';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <a href="exports.php?type=receptions" class="btn btn-outline-success" target="_blank">
        <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="receptions.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Fournisseur</label>
                <select name="fournisseur_id" class="form-select form-select-sm">
                    <option value="">Tous les fournisseurs</option>
                    <?php foreach ($fournisseurs as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $filters['fournisseur_id'] == $f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Magasin</label>
                <select name="magasin_id" class="form-select form-select-sm">
                    <option value="">Tous les magasins</option>
                    <?php foreach ($magasins as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= $filters['magasin_id'] == $m['id'] ? 'selected' : '' ?>>
                            <?= h($m['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Statut</label>
                <select name="statut" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="Brouillon" <?= $filters['statut'] === 'Brouillon' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="Validee" <?= $filters['statut'] === 'Validee' ? 'selected' : '' ?>>Validée</option>
                    <option value="Annulee" <?= $filters['statut'] === 'Annulee' ? 'selected' : '' ?>>Annulée</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="receptions.php" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Référence</th>
                    <th>Commande</th>
                    <th>Fournisseur</th>
                    <th>Magasin</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Qté reçue</th>
                    <th class="text-center">Acceptée</th>
                    <th class="text-center">Perdue</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receptions)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Aucune réception trouvée.
                        </td>
                    </tr>
                <?php else: foreach ($receptions as $r): ?>
                    <?php
                    $badge_class = ['Brouillon' => 'secondary', 'Validee' => 'success', 'Annulee' => 'danger'][$r['statut']] ?? 'secondary';
                    ?>
                    <tr>
                        <td class="fw-bold"><?= h($r['reference']) ?></td>
                        <td>
                            <?php if (!empty($r['commande_id'])): ?>
                                <a href="<?= h(generate_signed_url('commandes_fournisseur.php', (int)$r['commande_id'], ['action' => 'voir'])) ?>" class="text-decoration-none">
                                    #<?= (int)$r['commande_id'] ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= h($r['fournisseur_nom'] ?? '—') ?></td>
                        <td><?= h($r['magasin_nom'] ?? '—') ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $badge_class ?>"><?= h($r['statut']) ?></span>
                        </td>
                        <td class="text-center fw-bold"><?= (int)$r['total_recu'] ?></td>
                        <td class="text-center text-success fw-bold"><?= (int)$r['total_accepte'] ?></td>
                        <td class="text-center text-danger fw-bold"><?= (int)$r['total_perdu'] ?></td>
                        <td class="small"><?= date_fr($r['date_reception']) ?></td>
                        <td class="text-end">
                            <a href="<?= h(generate_signed_url('receptions.php', (int)$r['id'], ['action' => 'voir'])) ?>" class="btn btn-sm btn-outline-primary" title="Voir détail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
