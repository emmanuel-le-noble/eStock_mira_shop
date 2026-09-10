<?php
/**
 * sidebar.php - Sidebar unifiée (source de vérité unique).
 *
 * Variables attendues :
 *   $nav_sections   : array de sections (construit par build_nav_sections())
 *   $page_courante  : slug de la page courante
 *   $user           : données utilisateur courant
 *   $role           : rôle de l'utilisateur
 *   $initiale       : initiale du nom
 *   $app_nom        : nom de l'application
 */

if (empty($nav_sections)) return;

// Déterminer si au moins une section contient la page courante
$any_active = false;
foreach ($nav_sections as $sec) {
    if (in_array($page_courante, array_column($sec['items'], 'slug'), true)) {
        $any_active = true;
        break;
    }
}
?>
    <!-- ============ SIDEBAR ============ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= BASE_URL ?>assets/images/logo-eStock-3.png" alt="Logo <?= h($app_nom) ?>" class="logo-img">
            <span class="brand-text"><?= h($app_nom) ?></span>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($nav_sections as $sec):
                $page_actuelle_dans = in_array($page_courante, array_column($sec['items'], 'slug'), true);
                $should_open = $page_actuelle_dans || !$any_active;
            ?>
            <div class="nav-section <?= $should_open ? 'open' : '' ?>" data-section="<?= h($sec['key']) ?>">
                <button type="button" class="nav-section-head" aria-expanded="<?= $should_open ? 'true' : 'false' ?>">
                    <span class="nav-section-title"><i class="bi <?= h($sec['icon']) ?>"></i> <?= h($sec['titre']) ?></span>
                    <i class="bi bi-chevron-down nav-section-chevron"></i>
                </button>
                <div class="nav-section-body">
                    <?php foreach ($sec['items'] as $item): ?>
                        <a class="nav-link <?= $page_courante === $item['slug'] ? 'active' : '' ?>"
                           href="<?= $item['href'] ?? ($item['slug'] . '.php') ?>">
                            <span class="nav-icon <?= $item['color'] ?>"><i class="bi <?= $item['icon'] ?>"></i></span>
                            <span><?= h($item['label']) ?></span>
                            <?php if (!empty($item['badge']) && (int)$item['badge'] > 0): ?>
                                <span class="badge bg-danger ms-auto" style="font-size:.65rem;"><?= (int)$item['badge'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a class="side-user <?= $page_courante === 'profil' ? 'active' : '' ?>" href="profil.php" title="Mon profil">
                <span class="avatar avatar-initial"><?= h(strtoupper($initiale)) ?></span>
                <span class="meta">
                    <span class="nm"><?= h($user['nom'] ?? '') ?></span>
                    <span class="rl"><?= h($role) ?></span>
                </span>
            </a>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
