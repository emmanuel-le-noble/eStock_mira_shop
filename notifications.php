<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_notifications_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('notifications_usine_consulter');

$titre_page = 'Notifications — Usine';

// POST: marquer lu / tout lu / supprimer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exiger_permission('notifications_usine_consulter');
    csrf_guard('notifications.php');
    $action_post = $_POST['action'] ?? '';
    try {
        if ($action_post === 'lire') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) db_notification_marquer_lue($pdo, $id);
        } elseif ($action_post === 'tout_lu') {
            $role = user_role();
            $user_id = $_SESSION['user']['id'] ?? null;
            db_notification_tout_lu($pdo, $role, $user_id);
            flash_success('Toutes les notifications marquées comme lues.');
        } elseif ($action_post === 'supprimer') {
            exiger_permission('notifications_usine_gerer');
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) db_notification_supprimer($pdo, $id);
        }
    } catch (Throwable $e) {
        flash_error($e->getMessage());
    }
    redirect('notifications.php');
}

$role = user_role();
$user_id = $_SESSION['user']['id'] ?? null;
$type_filter = $_GET['type'] ?? null;
$non_lues = !empty($_GET['non_lues']);

$notifications = db_notifications_list($pdo, $type_filter, $role, $user_id, $non_lues);
$nb_non_lues = db_notifications_nb_non_lues($pdo, $role, $user_id);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-bell text-warning me-2"></i>Notifications Usine</h2>
        <p class="text-muted small mb-0"><?= $nb_non_lues ?> non lue(s)</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($nb_non_lues > 0): ?>
        <form method="post" action="notifications.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="tout_lu">
            <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check-all"></i> Tout marquer lu</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filtres -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="notifications.php" class="btn btn-sm <?= !$type_filter && !$non_lues ? 'btn-primary' : 'btn-outline-primary' ?>">Toutes</a>
    <a href="notifications.php?non_lues=1" class="btn btn-sm <?= $non_lues ? 'btn-warning' : 'btn-outline-warning' ?>">
        Non lues <?php if ($nb_non_lues > 0): ?><span class="badge bg-danger ms-1"><?= $nb_non_lues ?></span><?php endif; ?>
    </a>
    <a href="notifications.php?type=retard_employe" class="btn btn-sm <?= $type_filter === 'retard_employe' ? 'btn-danger' : 'btn-outline-danger' ?>">
        <i class="bi bi-clock-history"></i> Retards
    </a>
    <a href="notifications.php?type=absence_employe" class="btn btn-sm <?= $type_filter === 'absence_employe' ? 'btn-danger' : 'btn-outline-danger' ?>">
        <i class="bi bi-person-x"></i> Absences
    </a>
    <a href="notifications.php?type=machine_arretee" class="btn btn-sm <?= $type_filter === 'machine_arretee' ? 'btn-warning' : 'btn-outline-warning' ?>">
        <i class="bi bi-gear"></i> Machines
    </a>
    <a href="notifications.php?type=perte_elevee" class="btn btn-sm <?= $type_filter === 'perte_elevee' ? 'btn-info' : 'btn-outline-info' ?>">
        <i class="bi bi-exclamation-triangle"></i> Pertes
    </a>
    <a href="notifications.php?type=rendement_bas" class="btn btn-sm <?= $type_filter === 'rendement_bas' ? 'btn-info' : 'btn-outline-info' ?>">
        <i class="bi bi-graph-down"></i> Rendement
    </a>
    <a href="notifications.php?type=stock_faible_matiere" class="btn btn-sm <?= $type_filter === 'stock_faible_matiere' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
        <i class="bi bi-box"></i> Stock
    </a>
</div>

<!-- Liste des notifications -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                Aucune notification.
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $icon = match($n['type']) {
                        'retard_employe' => 'bi-clock-history text-danger',
                        'absence_employe' => 'bi-person-x text-danger',
                        'machine_arretee', 'machine_demarree' => 'bi-gear text-warning',
                        'machine_panee' => 'bi-exclamation-octagon text-danger',
                        'perte_elevee' => 'bi-exclamation-triangle text-info',
                        'rendement_bas' => 'bi-graph-down text-info',
                        'stock_faible_matiere' => 'bi-box text-secondary',
                        default => 'bi-bell text-primary',
                    };
                    ?>
                    <div class="list-group-item <?= $n['lu'] ? '' : 'list-group-item-light border-start border-primary border-3' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex gap-3">
                                <i class="bi <?= $icon ?> fs-4 mt-1"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold <?= $n['lu'] ? '' : 'text-dark' ?>"><?= h($n['titre']) ?></h6>
                                    <p class="mb-1 text-muted small"><?= h($n['message']) ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($n['date_creation'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <?php if (!$n['lu']): ?>
                                <form method="post" action="notifications.php" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="lire">
                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer lu"><i class="bi bi-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (peut('notifications_usine_gerer')): ?>
                                <form method="post" action="notifications.php" style="display:inline" onsubmit="return confirm('Supprimer cette notification ?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
