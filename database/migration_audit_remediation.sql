-- ============================================================
-- MIGRATION: Audit Remediation — eStock v2.7.1
-- Date: 2026-09-11
-- Description: Correction des problèmes identifiés par l'audit
--              (C3, C4, H7, H8, M-moyens)
--
-- RÈGLES:
--   - Aucune donnée existante ne doit être perdue
--   - Toute opération destructrice est précédée d'une vérification
--   - Idempotent : peut être exécuté plusieurs fois sans effets secondaires
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- C4 / H7: CORRIGER LES FOREIGN KEY ON DELETE CASCADE DANGEREUSES
-- ============================================================
-- mouvements_stock.article_id : CASCADE → RESTRICT
-- (supprimer un article ne doit PAS détruire l'historique des mouvements)
-- ============================================================

-- Supprimer l'ancienne FK si elle existe
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'mouvements_stock'
  AND CONSTRAINT_NAME = 'fk_mvt_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `mouvements_stock` DROP FOREIGN KEY `fk_mvt_article`',
    'SELECT "SKIP fk_mvt_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Recréer avec RESTRICT
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `fk_mvt_article` FOREIGN KEY (`article_id`)
  REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- stock_magasins : article_id CASCADE → RESTRICT
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'stock_magasins'
  AND CONSTRAINT_NAME = 'fk_sm_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `stock_magasins` DROP FOREIGN KEY `fk_sm_article`',
    'SELECT "SKIP fk_sm_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `stock_magasins`
  ADD CONSTRAINT `fk_sm_article` FOREIGN KEY (`article_id`)
  REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- stock_magasins : magasin_id CASCADE → RESTRICT
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'stock_magasins'
  AND CONSTRAINT_NAME = 'fk_sm_magasin'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `stock_magasins` DROP FOREIGN KEY `fk_sm_magasin`',
    'SELECT "SKIP fk_sm_magasin (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `stock_magasins`
  ADD CONSTRAINT `fk_sm_magasin` FOREIGN KEY (`magasin_id`)
  REFERENCES `magasins` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- article_couts : article_id CASCADE → RESTRICT
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'article_couts'
  AND CONSTRAINT_NAME = 'fk_aco_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `article_couts` DROP FOREIGN KEY `fk_aco_article`',
    'SELECT "SKIP fk_aco_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `article_couts`
  ADD CONSTRAINT `fk_aco_article` FOREIGN KEY (`article_id`)
  REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- article_lots : article_id CASCADE → RESTRICT
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'article_lots'
  AND CONSTRAINT_NAME = 'fk_lot_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `article_lots` DROP FOREIGN KEY `fk_lot_article`',
    'SELECT "SKIP fk_lot_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `article_lots`
  ADD CONSTRAINT `fk_lot_article` FOREIGN KEY (`article_id`)
  REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- stock_produits_finis_usine : article_id CASCADE → RESTRICT
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'stock_produits_finis_usine'
  AND CONSTRAINT_NAME = 'fk_spfu_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `stock_produits_finis_usine` DROP FOREIGN KEY `fk_spfu_article`',
    'SELECT "SKIP fk_spfu_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `stock_produits_finis_usine`
  ADD CONSTRAINT `fk_spfu_article` FOREIGN KEY (`article_id`)
  REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- ============================================================
-- H7: FK MANQUANTES — Ajouter les FK identifiées par l'audit
-- ============================================================

-- lignes_commande_fournisseur.article_id → articles
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'lignes_commande_fournisseur'
  AND CONSTRAINT_NAME = 'fk_lcf_article'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `lignes_commande_fournisseur` ADD CONSTRAINT `fk_lcf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT "SKIP fk_lcf_article (already exists)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- H8: CHECK CONSTRAINTS MANQUANTES
-- ============================================================

-- articles : prix_vente >= 0
SELECT COUNT(*) INTO @chk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'articles'
  AND CONSTRAINT_NAME = 'chk_articles_prix_vente'
  AND CONSTRAINT_TYPE = 'CHECK';

SET @sql = IF(@chk_exists = 0,
    'ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_prix_vente` CHECK (`prix_vente` >= 0)',
    'SELECT "SKIP chk_articles_prix_vente (already exists)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- articles : prix_achat >= 0
SELECT COUNT(*) INTO @chk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'articles'
  AND CONSTRAINT_NAME = 'chk_articles_prix_achat'
  AND CONSTRAINT_TYPE = 'CHECK';

SET @sql = IF(@chk_exists = 0,
    'ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_prix_achat` CHECK (`prix_achat` >= 0)',
    'SELECT "SKIP chk_articles_prix_achat (already exists)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- articles : quantite_stock >= 0
SELECT COUNT(*) INTO @chk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'articles'
  AND CONSTRAINT_NAME = 'chk_articles_stock'
  AND CONSTRAINT_TYPE = 'CHECK';

SET @sql = IF(@chk_exists = 0,
    'ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_stock` CHECK (`quantite_stock` >= 0)',
    'SELECT "SKIP chk_articles_stock (already exists)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- retours_factures : montant_total >= 0
SELECT COUNT(*) INTO @chk_exists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'retours_factures'
  AND CONSTRAINT_NAME = 'chk_ret_montant'
  AND CONSTRAINT_TYPE = 'CHECK';

SET @sql = IF(@chk_exists = 0,
    'ALTER TABLE `retours_factures` ADD CONSTRAINT `chk_ret_montant` CHECK (`montant_total` >= 0)',
    'SELECT "SKIP chk_ret_montant (already exists)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- MOYENS: ENUM mouvements_stock — uniformiser le casing
-- ============================================================
-- L'ENUM contient des doublons (ENTREE + Entree, etc.)
-- On convertit d'abord en VARCHAR, puis on normalise, puis on recrée l'ENUM
-- ============================================================

-- D'abord, convertir en VARCHAR pour éviter les conflits de doublons
ALTER TABLE `mouvements_stock` MODIFY COLUMN `type` VARCHAR(30) NOT NULL DEFAULT 'ENTREE';

-- Normaliser toutes les valeurs en UPPERCASE
UPDATE `mouvements_stock` SET `type` = UPPER(`type`);

-- Recréer la colonne ENUM propre
ALTER TABLE `mouvements_stock` MODIFY COLUMN `type` ENUM(
    'ENTREE','SORTIE','VENTE','TRANSFERT','AJUSTEMENT','RETOUR_STOCK',
    'RECEPTION','PERTE','PRODUCTION','PERTE_PRODUCTION',
    'ENTREE_ACHAT','SORTIE_VENTE','TRANSFERT_ENTREE','TRANSFERT_SORTIE'
) NOT NULL DEFAULT 'ENTREE';

-- ============================================================
-- MOYENS: Index dupliqués — nettoyer
-- ============================================================

-- article_couts : idx_ac_article est redondant avec idx_ac_art_mag (composite sur article_id, magasin_id)
SELECT COUNT(*) INTO @idx_exists FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'article_couts'
  AND INDEX_NAME = 'idx_ac_article';

SET @sql = IF(@idx_exists > 0,
    'ALTER TABLE `article_couts` DROP INDEX `idx_ac_article`',
    'SELECT "SKIP idx_ac_article (not found)"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- C3: Marquer config/estock_db.sql comme déprécié
-- (Ne pas supprimer — peut contenir des données utiles)
-- ============================================================
-- La source canonique est database/estock_db.sql
-- Ceci est juste un index de documentation.

-- ============================================================
-- FIN DE LA MIGRATION
-- ============================================================

SET FOREIGN_KEY_CHECKS=1;

-- Vérification post-migration
SELECT 'Migration audit remediation terminée' AS status;
SELECT COUNT(*) AS 'Articles avec stock negatif' FROM articles WHERE quantite_stock < 0;
SELECT COUNT(*) AS 'Mouvements orphelins (article supprime)' FROM mouvements_stock m LEFT JOIN articles a ON a.id = m.article_id WHERE a.id IS NULL;
SELECT COUNT(*) AS 'Stock orphelin (article supprime)' FROM stock_magasins s LEFT JOIN articles a ON a.id = s.article_id WHERE a.id IS NULL;
