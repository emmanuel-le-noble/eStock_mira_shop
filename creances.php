<?php
/**
 * creances.php — Gestion des creances (ventes a credit).
 */
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_article_insert')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard')) { require_once __DIR__ . '/includes/helpers.php'; }
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/helpers.php';

$action = input_string($_GET['action'] ?? 'liste');

// ---- Detail d'une creance ----
if ($action === 'detail') {
    exiger_permission('credit_consulter');
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { flash_error('ID creance invalide.'); redirect('creances.php'); }
    $creance = db_creance_get_by_id($pdo, $id);
    if (!$creance) { flash_error('Creance introuvable.'); redirect('creances.php'); }
    $paiements = db_creance_paiements_list($pdo, $id);

    echo $twig->render('creance_detail.html.twig', [
        'titre_page'   => 'Creance #' . $creance['numero_facture'],
        'creance'      => $creance,
        'paiements'    => $paiements,
        'peut_paiement'=> peut('credit_paiement_creer'),
        'peut_annuler' => peut('credit_annuler'),
    ]);
    exit;
}

// ---- Enregistrer un paiement (POST) ----
if ($action === 'paiement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('credit_paiement_creer');
    csrf_guard('creances.php');
    $creanceId = (int)($_POST['creance_id'] ?? 0);
    $montant = (float)($_POST['montant'] ?? 0);
    $modePaiement = input_string($_POST['mode_paiement'] ?? 'Especes');
    $reference = input_string($_POST['reference'] ?? '');
    $notes = input_string($_POST['notes'] ?? '');

    if ($creanceId <= 0 || $montant <= 0) {
        flash_error('Parametres invalides.');
        redirect('creances.php?action=detail&id=' . $creanceId);
    }

    try {
        db_creance_paiement_insert($pdo, $creanceId, $montant, $modePaiement, $reference ?: null, user_id(), $notes ?: null);
        suivre_activite('CREDIT_PAIEMENT', 'Paiement credit sur creance #' . $creanceId . ' — ' . number_format($montant, 2, ',', ' ') . ' FCFA');
        flash_success('Paiement enregistre avec succes.');
    } catch (RuntimeException $e) {
        flash_error($e->getMessage());
    }
    redirect('creances.php?action=detail&id=' . $creanceId);
}

// ---- Annuler une creance (POST) ----
if ($action === 'annuler' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('credit_annuler');
    csrf_guard('creances.php');
    $creanceId = (int)($_POST['creance_id'] ?? 0);
    $motif = input_string($_POST['motif'] ?? '');

    if ($creanceId <= 0) {
        flash_error('ID creance invalide.');
        redirect('creances.php');
    }

    try {
        db_creance_annuler($pdo, $creanceId, user_id(), $motif ?: null);
        suivre_activite('CREDIT_ANNULATION', 'Creance #' . $creanceId . ' annulee');
        flash_success('Creance annulee.');
    } catch (RuntimeException $e) {
        flash_error($e->getMessage());
    }
    redirect('creances.php');
}

// ---- Marquer les creances en souffrance ----
try { db_creance_marquer_en_souffrance($pdo); } catch (\Throwable $e) { /* table pas encore créée */ }

// ---- Liste des creances ----
exiger_permission('credit_consulter');

$search = input_string($_GET['search'] ?? '');
$statutFilter = input_string($_GET['statut'] ?? '');
$dateDebut = input_string($_GET['date_debut'] ?? '');
$dateFin = input_string($_GET['date_fin'] ?? '');

$filters = ['limit' => 50, 'offset' => max(0, ((int)($_GET['page'] ?? 1) - 1) * 50)];
if ($search) $filters['search'] = $search;
if ($statutFilter) $filters['statut'] = $statutFilter;
if ($dateDebut) $filters['date_debut'] = $dateDebut;
if ($dateFin) $filters['date_fin'] = $dateFin;

try {
    $result = db_creances_search_sql($pdo, $filters);
    $creances = $result['results'];
    $total = $result['total'];
} catch (\Throwable $e) {
    $creances = [];
    $total = 0;
    error_log('[CREANCES] search error: ' . $e->getMessage());
}
$totalPages = max(1, (int)ceil($total / 50));
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$rapport = db_credit_rapport($pdo);

echo $twig->render('creances.html.twig', [
    'titre_page'   => 'Creances — Ventes a credit',
    'creances'     => $creances,
    'total'        => $total,
    'page'         => $currentPage,
    'total_pages'  => $totalPages,
    'search'       => $search,
    'statut_filter'=> $statutFilter,
    'date_debut'   => $dateDebut,
    'date_fin'     => $dateFin,
    'rapport'      => $rapport,
    'peut_paiement'=> peut('credit_paiement_creer'),
    'peut_annuler' => peut('credit_annuler'),
]);
