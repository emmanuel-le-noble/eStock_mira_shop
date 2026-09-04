-- ============================================================================
-- MIGRATION : RBAC DYNAMIQUE + USINE INDÉPENDANTE
-- Date : 2026-09-04
-- Description : 
--   PARTIE A — RBAC dynamique (roles table, user_roles, migration anciens rôles)
--   PARTIE B — Usine indépendante (categories_mp, matieres_premieres, stock_mp, etc.)
-- ============================================================================

-- =========================================================================
-- PARTIE A — RBAC DYNAMIQUE
-- =========================================================================

-- A.1 Table des rôles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Code unique du rôle (ex: PROPRIETAIRE)',
  `nom` varchar(100) NOT NULL COMMENT 'Nom affichable (ex: Propriétaire)',
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.2 Table de liaison utilisateurs ↔ rôles (multi-rôles)
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `date_attribution` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  KEY `fk_ur_role` (`role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.3 Migrer les 4 rôles existants
INSERT INTO `roles` (`code`, `nom`, `description`) VALUES
  ('PROPRIETAIRE', 'Propriétaire', 'Propriétaire / Directeur — accès total au système'),
  ('ADMIN', 'Administrateur', 'Administrateur — gère les paramètres et les utilisateurs'),
  ('MAGASINIER', 'Magasinier', 'Magasinier — gère le stock et les ventes en magasin'),
  ('VENDEUR', 'Vendeur', 'Vendeur — opère la caisse et les ventes')
ON DUPLICATE KEY UPDATE `nom` = VALUES(`nom`);

-- A.4 Mapper les utilisateurs existants vers user_roles
-- Mapping: utilisateurs.role -> roles.code
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM utilisateurs u
JOIN roles r ON (
    (u.role = 'chef équipe' AND r.code = 'PROPRIETAIRE')
    OR (u.role = 'Admin' AND r.code = 'ADMIN')
    OR (u.role = 'Magasinier' AND r.code = 'MAGASINIER')
    OR (u.role = 'Vendeur' AND r.code = 'VENDEUR')
)
WHERE u.actif = 1;

-- A.5 Migrer role_permissions pour utiliser les codes de rôles
-- Créer une table temporaire avec les anciens noms → nouveaux codes
CREATE TEMPORARY TABLE IF NOT EXISTS `_role_map` (
  `ancien_nom` varchar(50) NOT NULL,
  `nouveau_code` varchar(50) NOT NULL
);
INSERT INTO `_role_map` VALUES
  ('chef équipe', 'PROPRIETAIRE'),
  ('Admin', 'ADMIN'),
  ('Magasinier', 'MAGASINIER'),
  ('Vendeur', 'VENDEUR');

-- Réécrire role_permissions avec les nouveaux codes
-- D'abord sauvegarder les associations existantes
CREATE TEMPORARY TABLE IF NOT EXISTS `_rp_backup` AS
SELECT rp.role_nom, rp.permission_id, rm.nouveau_code
FROM role_permissions rp
JOIN _role_map rm ON rp.role_nom = rm.ancien_nom;

-- Supprimer les anciennes lignes
DELETE FROM role_permissions;

-- Réinsérer avec les nouveaux codes
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT `_rp_backup`.`nouveau_code`, `_rp_backup`.`permission_id`
FROM `_rp_backup`;

-- Supprimer les doublons potentiels
DELETE rp1 FROM role_permissions rp1
INNER JOIN role_permissions rp2
WHERE rp1.role_nom = rp2.role_nom
  AND rp1.permission_id = rp2.permission_id
  AND rp1.ctid < rp2.ctid;

-- Nettoyer
DROP TEMPORARY TABLE IF EXISTS `_role_map`;
DROP TEMPORARY TABLE IF EXISTS `_rp_backup`;

-- A.6 Permissions manquantes pour couvrir tous les anciens exiger_role()
INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('roles_consulter', 'Consulter les rôles', 'Administration'),
  ('roles_gerer', 'Créer / modifier / désactiver les rôles', 'Administration'),
  ('permissions_gerer', 'Gérer la matrice de permissions', 'Administration'),
  ('audit_gerer', 'Consulter l\'audit trail', 'Administration'),
  ('magasins_consulter', 'Consulter la liste des magasins', 'Magasins'),
  ('magasins_gerer', 'Gérer les magasins', 'Magasins'),
  ('statistiques_consulter', 'Consulter les statistiques', 'Rapports'),
  ('ventes_consulter', 'Consulter les ventes et factures', 'Ventes'),
  ('caisse_gerer', 'Opérer la caisse', 'Ventes'),
  ('cloture_gerer', 'Clôturer la caisse', 'Ventes'),
  ('depenses_consulter', 'Consulter les dépenses', 'Finance'),
  ('depenses_gerer', 'Gérer les dépenses', 'Finance'),
  ('transferts_consulter', 'Consulter les transferts', 'Stock'),
  ('transferts_gerer', 'Effectuer les transferts inter-magasins', 'Stock'),
  ('exports_consulter', 'Consulter les exports', 'Rapports'),
  ('suggestions_consulter', 'Consulter les suggestions d\'achat', 'Achats'),
  ('impression_consulter', 'Consulter les impressions', 'Impression');

-- Donner ces permissions au PROPRIETAIRE et ADMIN
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'PROPRIETAIRE', p.id FROM permissions p WHERE p.cle_permission IN (
  'roles_consulter', 'roles_gerer', 'permissions_gerer',
  'audit_gerer', 'magasins_consulter', 'magasins_gerer',
  'statistiques_consulter', 'ventes_consulter', 'caisse_gerer', 'cloture_gerer',
  'depenses_consulter', 'depenses_gerer', 'transferts_consulter', 'transferts_gerer',
  'exports_consulter', 'suggestions_consulter', 'impression_consulter'
)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'ADMIN', p.id FROM permissions p WHERE p.cle_permission IN (
  'roles_consulter', 'roles_gerer', 'permissions_gerer',
  'audit_gerer', 'magasins_consulter', 'magasins_gerer',
  'statistiques_consulter', 'ventes_consulter', 'caisse_gerer', 'cloture_gerer',
  'depenses_consulter', 'depenses_gerer', 'transferts_consulter', 'transferts_gerer',
  'exports_consulter', 'suggestions_consulter', 'impression_consulter'
)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

-- MAGASINIER : consultation et ventes
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'MAGASINIER', p.id FROM permissions p WHERE p.cle_permission IN (
  'magasins_consulter', 'ventes_consulter', 'caisse_gerer', 'cloture_gerer',
  'depenses_consulter', 'transferts_consulter', 'exports_consulter',
  'suggestions_consulter', 'impression_consulter'
)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

-- VENDEUR : ventes et caisse seulement
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'VENDEUR', p.id FROM permissions p WHERE p.cle_permission IN (
  'ventes_consulter', 'caisse_gerer'
)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

-- =========================================================================
-- PARTIE B — USINE INDÉPENDANTE
-- =========================================================================

-- B.1 Catégories matières premières (indépendant de `categories`)
CREATE TABLE IF NOT EXISTS `categories_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B.2 Matières premières (indépendant de `articles`)
CREATE TABLE IF NOT EXISTS `matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL COMMENT 'MAT-001, MAT-002...',
  `nom` varchar(200) NOT NULL,
  `categorie_id` int DEFAULT NULL,
  `unite_mesure` varchar(20) NOT NULL DEFAULT 'KG',
  `cout_reference` decimal(14,4) NOT NULL DEFAULT 0 COMMENT 'Coût unitaire de référence',
  `stock_minimum` int NOT NULL DEFAULT 10,
  `fournisseur_id` int DEFAULT NULL COMMENT 'Fournisseur principal optionnel',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mp_ref` (`reference`),
  KEY `idx_mp_categorie` (`categorie_id`),
  KEY `idx_mp_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B.3 Stock matières premières
CREATE TABLE IF NOT EXISTS `stock_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int NOT NULL,
  `quantite` decimal(12,4) NOT NULL DEFAULT 0,
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT 0,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_smp_matiere` (`matiere_id`),
  CONSTRAINT `fk_smp_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B.4 Mouvements matières premières
CREATE TABLE IF NOT EXISTS `mouvements_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `type` enum('ENTREE_ACHAT','ENTREE_RETOUR','ENTREE_RECEPTION','SORTIE_PRODUCTION','SORTIE_PERTE','AJUSTEMENT_ENTREE','AJUSTEMENT_SORTIE') NOT NULL,
  `quantite` decimal(12,4) NOT NULL,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0,
  `cout_total` decimal(14,2) NOT NULL DEFAULT 0,
  `production_id` int DEFAULT NULL,
  `reception_id` int DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mmp_matiere` (`matiere_id`),
  KEY `idx_mmp_type` (`type`),
  KEY `idx_mmp_date` (`date_mouvement`),
  KEY `idx_mmp_production` (`production_id`),
  CONSTRAINT `fk_mmp_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B.5 Stock produits finis usine (avant transfert vers magasin)
CREATE TABLE IF NOT EXISTS `stock_produits_finis_usine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL COMMENT 'FK -> articles (le produit fini = un article commercial)',
  `quantite` int NOT NULL DEFAULT 0,
  `production_id` int DEFAULT NULL COMMENT 'Dernière production ayant généré ce stock',
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_spfu_article` (`article_id`),
  CONSTRAINT `fk_spfu_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B.6 Ajouter origine_article aux articles
ALTER TABLE `articles`
  ADD COLUMN `origine_article` enum('ACHAT_FOURNISSEUR','PRODUCTION_USINE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR' AFTER `type_article`;

ALTER TABLE `articles`
  ADD INDEX `idx_art_origine` (`origine_article`);

-- B.7 Catégories matières premières — données de base
INSERT INTO `categories_matieres_premieres` (`nom`, `description`) VALUES
  ('Granulés', 'Granulés plastiques de base (PP, PEHD, PS, etc.)'),
  ('Colorants', 'Colorants et pigments pour matières plastiques'),
  ('Additifs', 'Additifs chimiques (stabilisants, lubrifiants, etc.)'),
  ('Matières recyclées', 'Matières plastiques recyclées'),
  ('Emballages', 'Emballages et conditionnements'),
  ('Produits chimiques autorisés', 'Produits chimiques conformes aux normes'),
  ('Autres matières', 'Autres matières premières');

-- B.8 Mouvements produits finis usine
CREATE TABLE IF NOT EXISTS `mouvements_produits_finis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `type` enum('PRODUCTION','TRANSFERT_SORTIE','RETOUR','AJUSTEMENT_ENTREE','AJUSTEMENT_SORTIE') NOT NULL,
  `quantite` int NOT NULL,
  `production_id` int DEFAULT NULL,
  `magasin_destination_id` int DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mpf_article` (`article_id`),
  KEY `idx_mpf_type` (`type`),
  KEY `idx_mpf_date` (`date_mouvement`),
  CONSTRAINT `fk_mpf_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- PARTIE C — NETTOYAGE ARCHITECTURE ANCIENNE
-- =========================================================================

-- C.1 Supprimer la colonne magasin_usine_id de productions
DROP PROCEDURE IF EXISTS `sp_drop_column_if_exists`;
DELIMITER //
CREATE PROCEDURE `sp_drop_column_if_exists`(IN p_table VARCHAR(64), IN p_column VARCHAR(64))
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_column, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL sp_drop_column_if_exists('productions', 'magasin_usine_id');
DROP PROCEDURE IF EXISTS `sp_drop_column_if_exists`;

-- C.2 Migrer le stock usine existant vers les nouvelles tables
INSERT IGNORE INTO `stock_matieres_premieres` (`matiere_id`, `quantite`, `valeur_stock`)
SELECT sm.article_id, sm.quantite, sm.valeur_stock
FROM `stock_magasins` sm
JOIN `magasins` m ON m.id = sm.magasin_id
WHERE m.type_magasin = 'USINE'
  AND EXISTS (SELECT 1 FROM `matieres_premieres` mp WHERE mp.id = sm.article_id);

INSERT IGNORE INTO `stock_produits_finis_usine` (`article_id`, `quantite`)
SELECT sm.article_id, sm.quantite
FROM `stock_magasins` sm
JOIN `magasins` m ON m.id = sm.magasin_id
WHERE m.type_magasin = 'USINE'
  AND EXISTS (SELECT 1 FROM `articles` a WHERE a.id = sm.article_id AND a.origine_article = 'PRODUCTION_USINE');

-- C.3 Supprimer les anciennes entrées de stock_magasins liées à l'usine
DELETE sm FROM `stock_magasins` sm
JOIN `magasins` m ON m.id = sm.magasin_id
WHERE m.type_magasin = 'USINE';

-- C.4 Supprimer l'ancien magasin USINE
DELETE FROM `magasins` WHERE `type_magasin` = 'USINE';
