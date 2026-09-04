<?php
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_employes_list')) { require_once __DIR__ . '/includes/usine_functions.php'; }
require_once __DIR__ . '/includes/helpers.php';

exiger_permission('personnel_consulter');
$action = $_GET['action'] ?? 'liste';
$titre_page = 'Personnel usine';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enregistrer') {
    csrf_guard('personnel.php');
    $data = extract_post_data([
        'id'         => ['type' => 'int', 'default' => 0],
        'matricule'  => ['type' => 'string', 'required' => true],
        'nom'        => ['type' => 'string', 'required' => true],
        'prenom'     => ['type' => 'string', 'default' => ''],
        'fonction'   => ['type' => 'string', 'default' => ''],
        'telephone'  => ['type' => 'string', 'default' => ''],
        'actif'      => ['type' => 'int', 'default' => 1],
    ], 'personnel.php');
    db_transaction(
        function(PDO $pdo) use ($data) {
            if ($data['id'] > 0) {
                db_employe_update($pdo, $data['id'], $data);
            } else {
                db_employe_insert($pdo, $data);
            }
        },
        'Employé enregistré.',
        'Erreur enregistrement employé.',
        'personnel.php'
    );
    redirect('personnel.php');
}

$edit = null;
if ($action === 'editer' && !empty($_GET['id'])) {
    $edit = db_employe_get($pdo, (int)$_GET['id']);
}

$employes = db_employes_list($pdo, false);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-people text-success me-2"></i>Personnel usine</h2>
    </div>
    <a href="personnel.php?action=ajouter" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouvel employé</a>
</div>

<?php if ($action === 'ajouter' || $action === 'editer'): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><?= $edit ? 'Modifier' : 'Nouvel' ?> employé</h6></div>
    <div class="card-body">
        <form method="post" action="personnel.php?action=enregistrer">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Matricule *</label>
                    <input type="text" name="matricule" class="form-control" required value="<?= h($edit['matricule'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nom *</label>
                    <input type="text" name="nom" class="form-control" required value="<?= h($edit['nom'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="<?= h($edit['prenom'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Fonction</label>
                    <input type="text" name="fonction" class="form-control" value="<?= h($edit['fonction'] ?? '') ?>" placeholder="Ex: Opérateur">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="<?= h($edit['telephone'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                <a href="personnel.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>#</th><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Fonction</th><th>Téléphone</th><th>État</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($employes as $e): ?>
                    <tr>
                        <td class="text-muted">#<?= $e['id'] ?></td>
                        <td><code><?= h($e['matricule']) ?></code></td>
                        <td class="fw-semibold"><?= h($e['nom']) ?></td>
                        <td><?= h($e['prenom']) ?></td>
                        <td><?= h($e['fonction']) ?></td>
                        <td><?= h($e['telephone'] ?? '—') ?></td>
                        <td><?= $e['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                        <td class="text-end"><a href="personnel.php?action=editer&id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($employes)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Aucun employé.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
