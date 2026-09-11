<?php
/**
 * Script d'application de la migration RBAC + Usine indépendante.
 * Exécuter : php bin/apply_migration.php
 * Options   : php bin/apply_migration.php --dry-run   (affiche sans modifier)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Ce script doit être exécuté en ligne de commande.\n";
    exit(1);
}

require_once __DIR__ . '/../config/connexion.php';

$DRY_RUN = in_array('--dry-run', $argv ?? [], true);
if ($DRY_RUN) {
    echo "=== MODE DRY-RUN — Aucune modification ne sera appliquée ===\n\n";
}

$ok = 0;
$fail = 0;
$dry = 0;

function run($pdo, $label, $sql) {
    global $ok, $fail, $dry, $DRY_RUN;
    if ($DRY_RUN) {
        echo "  DRY  $label\n";
        $dry++;
        return;
    }
    try {
        $pdo->exec($sql);
        echo "  OK  $label\n";
        $ok++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate') !== false || strpos($msg, 'already exists') !== false) {
            echo "  SKIP $label (already exists)\n";
            $ok++;
        } else {
            echo "  FAIL $label: $msg\n";
            $fail++;
        }
    }
}

echo "=== PARTIE A: RBAC DYNAMIQUE ===\n";

run($pdo, 'roles', "CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

run($pdo, 'user_roles', "CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `date_attribution` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

run($pdo, 'roles seed', "INSERT IGNORE INTO `roles` (`code`, `nom`, `description`) VALUES
  ('PROPRIETAIRE','Propriétaire','Accès total au système'),
  ('ADMIN','Administrateur','Gère les paramètres et les utilisateurs'),
  ('MAGASINIER','Magasinier','Gère le stock et les ventes en magasin'),
  ('VENDEUR','Vendeur','Opère la caisse et les ventes')");

run($pdo, 'user_roles mapping', "INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
  SELECT u.id, r.id FROM utilisateurs u
  JOIN roles r ON (
    (u.role = 'chef équipe' AND r.code = 'PROPRIETAIRE')
    OR (u.role = 'Admin' AND r.code = 'ADMIN')
    OR (u.role = 'Magasinier' AND r.code = 'MAGASINIER')
    OR (u.role = 'Vendeur' AND r.code = 'VENDEUR')
  ) WHERE u.actif = 1");

run($pdo, 'role_permissions migrate', "UPDATE `role_permissions` SET `role_nom` = 'PROPRIETAIRE' WHERE `role_nom` = 'chef équipe'");
run($pdo, '', "UPDATE `role_permissions` SET `role_nom` = 'VENDEUR' WHERE `role_nom` = 'Vendeur'");
run($pdo, '', "UPDATE `role_permissions` SET `role_nom` = 'MAGASINIER' WHERE `role_nom` = 'Magasinier'");
run($pdo, '', "UPDATE `role_permissions` SET `role_nom` = 'ADMIN' WHERE `role_nom` = 'Admin'");

// New permissions
$perms = [
    ['roles_consulter','Consulter les rôles','Administration'],
    ['roles_gerer','Créer/modifier/désactiver les rôles','Administration'],
    ['permissions_gerer','Gérer la matrice de permissions','Administration'],
    ['audit_gerer','Consulter l audit trail','Administration'],
    ['magasins_consulter','Consulter les magasins','Magasins'],
    ['magasins_gerer','Gérer les magasins','Magasins'],
    ['statistiques_consulter','Consulter les statistiques','Rapports'],
    ['ventes_consulter','Consulter les ventes et factures','Ventes'],
    ['caisse_gerer','Opérer la caisse','Ventes'],
    ['cloture_gerer','Clôturer la caisse','Ventes'],
    ['depenses_consulter','Consulter les dépenses','Finance'],
    ['depenses_gerer','Gérer les dépenses','Finance'],
    ['transferts_consulter','Consulter les transferts','Stock'],
    ['transferts_gerer','Effectuer les transferts inter-magasins','Stock'],
    ['exports_consulter','Consulter les exports','Rapports'],
    ['suggestions_consulter','Consulter les suggestions d achat','Achats'],
    ['impression_consulter','Consulter les impressions','Impression'],
    ['articles_consulter','Consulter les articles','Articles'],
    ['articles_gerer','Gérer les articles','Articles'],
    ['stock_consulter','Consulter le stock','Stock'],
    ['parametres_gerer','Gérer les paramètres','Administration'],
    ['utilisateurs_gerer','Gérer les utilisateurs','Administration'],
    ['achats_consulter','Consulter les achats','Achats'],
    ['achats_gerer','Gérer les achats','Achats'],
    ['achats_valider','Valider les achats','Achats'],
    ['usine_consulter','Consulter le module usine','Usine'],
    ['usine_gerer','Gérer les matières premières et recettes','Usine'],
    ['production_consulter','Consulter les productions','Usine'],
    ['production_gerer','Créer et gérer les productions','Usine'],
    ['production_cloturer','Clôturer une production','Usine'],
    ['personnel_consulter','Consulter le personnel usine','Usine'],
    ['personnel_gerer','Gérer le personnel usine','Usine'],
    ['presence_consulter','Consulter les présences','Usine'],
    ['presence_gerer','Enregistrer et modifier les présences','Usine'],
    ['transfert_usine_gerer','Effectuer des transferts usine','Usine'],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES (?, ?, ?)');
foreach ($perms as $p) { $stmt->execute($p); }
echo "  OK  permissions (" . count($perms) . ")\n";

// Assign permissions to roles
$all_keys = array_column($perms, 0);
$ph = implode(',', array_fill(0, count($all_keys), '?'));

$pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
  SELECT 'PROPRIETAIRE', id FROM permissions WHERE cle_permission IN ($ph)")->execute($all_keys);
$pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
  SELECT 'ADMIN', id FROM permissions WHERE cle_permission IN ($ph)")->execute($all_keys);

$mag_keys = ['magasins_consulter','ventes_consulter','caisse_gerer','cloture_gerer','depenses_consulter','transferts_consulter','exports_consulter','suggestions_consulter','impression_consulter','articles_consulter','stock_consulter','achats_consulter'];
$ph2 = implode(',', array_fill(0, count($mag_keys), '?'));
$pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
  SELECT 'MAGASINIER', id FROM permissions WHERE cle_permission IN ($ph2)")->execute($mag_keys);

$vendeur_keys = ['ventes_consulter','caisse_gerer'];
$ph3 = implode(',', array_fill(0, count($vendeur_keys), '?'));
$pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
  SELECT 'VENDEUR', id FROM permissions WHERE cle_permission IN ($ph3)")->execute($vendeur_keys);
echo "  OK  role_permissions assign\n";

echo "\n=== PARTIE B: USINE INDÉPENDANTE ===\n";

run($pdo, 'categories_matieres_premieres', "CREATE TABLE IF NOT EXISTS `categories_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

run($pdo, 'matieres_premieres', "CREATE TABLE IF NOT EXISTS `matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `categorie_id` int DEFAULT NULL,
  `unite_mesure` varchar(20) NOT NULL DEFAULT 'KG',
  `cout_reference` decimal(14,4) NOT NULL DEFAULT 0,
  `stock_minimum` int NOT NULL DEFAULT 10,
  `fournisseur_id` int DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mp_ref` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

run($pdo, 'stock_matieres_premieres', "CREATE TABLE IF NOT EXISTS `stock_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int NOT NULL,
  `quantite` decimal(12,4) NOT NULL DEFAULT 0,
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT 0,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_smp_matiere` (`matiere_id`),
  CONSTRAINT `fk_smp_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// NOTE: mouvements_matieres_premieres supprimé — les mouvements sont dans stock_matieres_premieres

run($pdo, 'stock_produits_finis_usine', "CREATE TABLE IF NOT EXISTS `stock_produits_finis_usine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT 0,
  `production_id` int DEFAULT NULL,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_spfu_article` (`article_id`),
  CONSTRAINT `fk_spfu_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// NOTE: mouvements_produits_finis supprimé — les mouvements sont dans mouvements_stock (type 'PRODUCTION'/'PERTE_PRODUCTION')

// Add origine_article column
$cols = $pdo->query("SHOW COLUMNS FROM articles LIKE 'origine_article'")->fetchAll();
if (empty($cols)) {
    run($pdo, 'articles.origine_article', "ALTER TABLE `articles` ADD COLUMN `origine_article` enum('ACHAT_FOURNISSEUR','PRODUCTION_USINE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR' AFTER `type_article`");
} else {
    echo "  SKIP articles.origine_article (already exists)\n";
}

// Seed categories MP
$cats = [
    ['Granulés','Granulés plastiques de base'],
    ['Colorants','Colorants et pigments'],
    ['Additifs','Additifs chimiques'],
    ['Matières recyclées','Matières plastiques recyclées'],
    ['Emballages','Emballages et conditionnements'],
    ['Produits chimiques autorisés','Produits chimiques conformes aux normes'],
    ['Autres matières','Autres matières premières'],
];
$stmt = $pdo->prepare('INSERT IGNORE INTO `categories_matieres_premieres` (`nom`, `description`) VALUES (?, ?)');
foreach ($cats as $c) { $stmt->execute($c); }
echo "  OK  categories_mp seed\n";

// Migrate existing MP stock from old USINE magasin to new tables
echo "\n=== PARTIE C: DATA MIGRATION ===\n";

// Check if old USINE magasin exists and migrate data
try {
    $usine = $pdo->query("SELECT id FROM magasins WHERE type_magasin = 'USINE' LIMIT 1")->fetch();
    if ($usine) {
        $usine_id = (int)$usine['id'];
        echo "  Found USINE magasin id=$usine_id, migrating data...\n";

        // Migrate matières premières from articles -> matieres_premieres
        $old_mp = $pdo->prepare("SELECT a.id, a.nom, a.code_barre, a.unite_mesure, a.prix_achat, a.seuil_alerte, a.categorie_id, a.quantite_stock
            FROM articles a WHERE a.type_article = 'MATIERE_PREMIERE'");
        $old_mp->execute();
        $mps = $old_mp->fetchAll();

        $ref_counter = 1;
        foreach ($mps as $mp) {
            $ref = sprintf('MAT-%03d', $ref_counter++);
            $pdo->prepare("INSERT IGNORE INTO matieres_premieres (reference, nom, unite_mesure, cout_reference, stock_minimum, categorie_id, actif)
                VALUES (?, ?, ?, ?, ?, ?, 1)")->execute([
                $ref, $mp['nom'], $mp['unite_mesure'], $mp['prix_achat'], $mp['seuil_alerte'], $mp['categorie_id']
            ]);
            $new_mp_id = $pdo->lastInsertId();
            if ($new_mp_id > 0) {
                // Migrate stock
                $pdo->prepare("INSERT IGNORE INTO stock_matieres_premieres (matiere_id, quantite, valeur_stock)
                    VALUES (?, ?, ?)")->execute([$new_mp_id, $mp['quantite_stock'], $mp['quantite_stock'] * $mp['prix_achat']]);
                echo "    Migrated MP: {$mp['nom']} (stock: {$mp['quantite_stock']})\n";
            }
        }

        // Migrate finished products stock from stock_magasins -> stock_produits_finis_usine
        $old_pf = $pdo->prepare("SELECT sm.article_id, sm.quantite
            FROM stock_magasins sm
            JOIN articles a ON a.id = sm.article_id
            WHERE sm.magasin_id = ? AND a.type_article = 'PRODUIT_FINI'");
        $old_pf->execute([$usine_id]);
        $pfs = $old_pf->fetchAll();
        foreach ($pfs as $pf) {
            $pdo->prepare("INSERT IGNORE INTO stock_produits_finis_usine (article_id, quantite)
                VALUES (?, ?)")->execute([$pf['article_id'], $pf['quantite']]);
            echo "    Migrated PF: article #{$pf['article_id']} (stock: {$pf['quantite']})\n";
        }

        // Create matching usines record for each USINE magasin
        $usines_check = $pdo->query("SHOW TABLES LIKE 'usines'")->fetchAll();
        if (!empty($usines_check)) {
            $old_usines = $pdo->query("SELECT id, nom FROM magasins WHERE type_magasin = 'USINE'")->fetchAll();
            foreach ($old_usines as $old_u) {
                $existing = $pdo->prepare("SELECT id FROM usines WHERE nom = ?");
                $existing->execute([$old_u['nom']]);
                if (!$existing->fetch()) {
                    if (!$DRY_RUN) {
                        $pdo->prepare("INSERT INTO usines (nom, actif) VALUES (?, 1)")->execute([$old_u['nom']]);
                        $new_usine_id = $pdo->lastInsertId();
                        echo "    Created usine '{$old_u['nom']}' (id=$new_usine_id) from magasin #{$old_u['id']}\n";
                    } else {
                        echo "    DRY: Would create usine '{$old_u['nom']}' from magasin #{$old_u['id']}\n";
                    }
                }
            }
        }

        // Deprecate old USINE magasins instead of deleting them
        if (!$DRY_RUN) {
            $pdo->prepare("UPDATE magasins SET actif = 0 WHERE type_magasin = 'USINE'")->execute();
            echo "  Deprecated USINE magasins (set actif=0, data preserved)\n";
        } else {
            echo "  DRY: Would deprecate USINE magasins (set actif=0)\n";
        }
    } else {
        echo "  No USINE magasin found, skipping data migration\n";
    }
} catch (Throwable $e) {
    echo "  Data migration error: " . $e->getMessage() . "\n";
}

echo "\n=== PARTIE C: ROLE_ID DANS UTILISATEURS ===\n";

run($pdo, 'utilisateurs add role_id', "ALTER TABLE `utilisateurs` ADD COLUMN `role_id` INT NULL AFTER `mot_de_passe`");
run($pdo, 'utilisateurs role_id chef', "UPDATE `utilisateurs` SET `role_id` = (SELECT `id` FROM `roles` WHERE `code` = 'PROPRIETAIRE') WHERE `role` = 'chef équipe'");
run($pdo, 'utilisateurs role_id admin', "UPDATE `utilisateurs` SET `role_id` = (SELECT `id` FROM `roles` WHERE `code` = 'ADMIN') WHERE `role` = 'Admin'");
run($pdo, 'utilisateurs role_id mag', "UPDATE `utilisateurs` SET `role_id` = (SELECT `id` FROM `roles` WHERE `code` = 'MAGASINIER') WHERE `role` = 'Magasinier'");
run($pdo, 'utilisateurs role_id ven', "UPDATE `utilisateurs` SET `role_id` = (SELECT `id` FROM `roles` WHERE `code` = 'VENDEUR') WHERE `role` = 'Vendeur'");
run($pdo, 'utilisateurs role_id not null', "ALTER TABLE `utilisateurs` MODIFY COLUMN `role_id` INT NOT NULL");
// run($pdo, 'utilisateurs drop role', "ALTER TABLE `utilisateurs` DROP COLUMN `role`");
run($pdo, 'utilisateurs fk_role', "ALTER TABLE `utilisateurs` ADD CONSTRAINT `fk_user_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT");

if ($DRY_RUN) {
    echo "\n=== DRY-RUN: $dry opérations simulées, 0 appliquées ===\n";
} else {
    echo "\n=== RESULT: $ok OK, $fail FAIL ===\n";
}
exit($fail > 0 ? 1 : 0);
