<?php
/**
 * roles.php — Gestion des rôles et matrice de permissions RBAC.
 */
if (!function_exists('est_connecte'))   { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_article_insert')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard'))     { require_once __DIR__ . '/includes/helpers.php'; }
require_once __DIR__ . '/config/connexion.php';
exiger_permission('roles_gerer');

// ============================================================
//  TRAITEMENT POST : CRÉATION / MODIFICATION DE RÔLE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_role') {
    if (!csrf_validate()) {
        flash_error('Token de sécurité invalide.');
        redirect('roles.php');
    }

    $role_code = strtoupper(trim($_POST['role_code'] ?? ''));
    $role_nom = trim($_POST['role_nom'] ?? '');
    $role_description = trim($_POST['role_description'] ?? '');

    if ($role_code === '' || $role_nom === '') {
        flash_error('Le code et le nom du rôle sont obligatoires.');
        redirect('roles.php');
    }
    if (!preg_match('/^[A-Z_]{2,50}$/', $role_code)) {
        flash_error('Le code rôle doit contenir uniquement des lettres majuscules et underscores (2-50 caractères).');
        redirect('roles.php');
    }

    $existing = db_role_get($pdo, $role_code);
    if ($existing) {
        db_role_update($pdo, $role_code, $role_nom, $role_description);
        suivre_activite('MODIFICATION_ROLE', 'Modification du rôle : ' . $role_code);
        flash_success("Rôle « $role_nom » modifié.");
    } else {
        db_role_insert($pdo, $role_code, $role_nom, $role_description);
        suivre_activite('CREATION_ROLE', 'Création du rôle : ' . $role_code);
        flash_success("Rôle « $role_nom » créé.");
    }
    redirect('roles.php');
}

// ============================================================
//  TRAITEMENT POST : SUPPRESSION DE RÔLE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_role') {
    if (!csrf_validate()) {
        flash_error('Token de sécurité invalide.');
        redirect('roles.php');
    }

    $role_code = $_POST['role_code'] ?? '';
    $protected = ROLES_PROTEGES;
    if (in_array($role_code, $protected, true)) {
        flash_error('Les rôles système ne peuvent pas être supprimés.');
        redirect('roles.php');
    }

    $user_count = db_role_user_count($pdo, $role_code);
    if ($user_count > 0) {
        flash_error("Ce rôle est attribué à $user_count utilisateur(s). Désassignez-le d'abord.");
        redirect('roles.php');
    }

    db_role_delete($pdo, $role_code);
    suivre_activite('SUPPRESSION_ROLE', 'Suppression du rôle : ' . $role_code);
    flash_success('Rôle supprimé.');
    redirect('roles.php');
}

// ============================================================
//  TRAITEMENT POST : SAUVEGARDE DES PERMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_permissions') {
    if (!csrf_validate()) {
        flash_error('Token de sécurité invalide.');
        redirect('roles.php');
    }

    $permissions_post = $_POST['permissions'] ?? [];
    $roles = db_permissions_get_roles($pdo);

    foreach ($roles as $role) {
        $perm_ids = array_map('intval', $permissions_post[$role] ?? []);
        $perm_ids = array_filter($perm_ids, fn($id) => $id > 0);
        db_permissions_save_for_role($pdo, $role, $perm_ids);
    }

    suivre_activite('MODIFICATION_PERMISSIONS', 'Modification des droits d\'accès RBAC');
    flash_success('Droits mis à jour avec succès. Les changements prennent effet immédiatement.');
    redirect('roles.php');
}

// ============================================================
//  DONNÉES & RENDU
// ============================================================
$titre_page = 'Gestion des Droits';
$sous_titre = param_shop_name();

$all_roles = db_roles_list($pdo);
$roles = db_permissions_get_roles($pdo);
$all_perms = db_permissions_all($pdo);

$grouped = [];
foreach ($all_perms as $perm) {
    $cat = $perm['categorie'];
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $active = [];
    foreach ($roles as $role) {
        $active[$role] = db_role_has_permission($pdo, $role, $perm['cle_permission']);
    }
    $grouped[$cat][] = [
        'id' => $perm['id'],
        'cle_permission' => $perm['cle_permission'],
        'description' => $perm['description'],
        'active' => $active,
    ];
}

$permissions_data = [
    'roles' => $roles,
    'grouped' => $grouped,
    'total_permissions' => count($all_perms ?? []),
];

include __DIR__ . '/includes/header.php';
?>

<h4 class="mb-4"><i class="bi bi-shield-lock text-primary"></i> Gestion des Droits & Permissions</h4>

<!-- ====== SECTION : GESTION DES RÔLES ====== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-shield-lock"></i> Rôles du système</h6>
        <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#formRole" aria-expanded="false">
            <i class="bi bi-plus-lg"></i> Nouveau rôle
        </button>
    </div>
    <div class="card-body p-0">
        <div class="collapse <?= ($_GET['edit_role'] ?? '') !== '' ? 'show' : '' ?>" id="formRole">
            <form method="post" class="p-3 border-bottom bg-light">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_role">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Code <span class="text-danger">*</span></label>
                        <input type="text" name="role_code" class="form-control form-control-sm"
                               value="<?= h($_GET['edit_role'] ?? '') ?>" required pattern="[A-Z_]{2,50}"
                               placeholder="EX: COMMERCIAL" <?= ($_GET['edit_role'] ?? '') !== '' ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Nom affiché <span class="text-danger">*</span></label>
                        <input type="text" name="role_nom" class="form-control form-control-sm"
                               value="<?= h($_GET['edit_nom'] ?? '') ?>" required placeholder="Commercial">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Description</label>
                        <input type="text" name="role_description" class="form-control form-control-sm"
                               value="<?= h($_GET['edit_desc'] ?? '') ?>" placeholder="Description optionnelle">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Enregistrer</button>
                        <a href="roles.php" class="btn btn-outline-secondary btn-sm">Annuler</a>
                    </div>
                </div>
            </form>
        </div>

        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th class="text-center">Permissions</th>
                    <th class="text-center">État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_roles as $r): ?>
                <tr class="<?= !$r['actif'] ? 'table-secondary' : '' ?>">
                    <td><code class="text-primary"><?= h($r['code']) ?></code></td>
                    <td class="fw-semibold"><?= h($r['nom']) ?></td>
                    <td class="text-muted small"><?= h($r['description'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-info"><?= $r['nb_permissions'] ?? 0 ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($r['actif']): ?>
                            <span class="badge bg-success">Actif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="?edit_role=<?= h($r['code']) ?>&edit_nom=<?= h($r['nom']) ?>&edit_desc=<?= h($r['description'] ?? '') ?>"
                           class="btn btn-outline-primary btn-sm" title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if (!in_array($r['code'], ROLES_PROTEGES, true)): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce rôle ?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_role">
                                <input type="hidden" name="role_code" value="<?= h($r['code']) ?>">
                                <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====== SECTION : MATRICE DE PERMISSIONS ====== -->
<h5 class="mb-3"><i class="bi bi-key"></i> Matrice de permissions par rôle</h5>

<?php echo $twig->render('permissions.html.twig', [
    'permissions_data' => $permissions_data,
    'titre_page' => $titre_page,
    'sous_titre' => $sous_titre,
]); ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
