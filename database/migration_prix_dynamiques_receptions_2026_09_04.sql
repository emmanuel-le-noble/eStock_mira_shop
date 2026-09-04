-- ============================================================
-- MIGRATION : Prix dynamiques, historique fournisseurs,
--             tranches tarifaires, réceptions, pertes
-- Date : 2026-09-04
-- ============================================================

-- ============================================================
-- 1. HISTORIQUE DES PRIX FOURNISSEURS
-- ============================================================
CREATE TABLE IF NOT EXISTS `fournisseur_prix_historique` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` INT UNSIGNED NOT NULL,
    `fournisseur_id` INT NOT NULL,
    `prix_achat` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `devise` VARCHAR(3) NOT NULL DEFAULT 'XOF',
    `est_actif` TINYINT(1) NOT NULL DEFAULT 1,
    `source` ENUM('commande','reception','manuelle') NOT NULL DEFAULT 'manuelle',
    `reference_id` INT UNSIGNED NULL COMMENT 'ID commande ou reception si source=commande/reception',
    `utilisateur_id` INT UNSIGNED NULL,
    `date_debut` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_fin` DATETIME NULL,
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fph_article` (`article_id`),
    INDEX `idx_fph_fournisseur` (`fournisseur_id`),
    INDEX `idx_fph_actif` (`article_id`, `fournisseur_id`, `est_actif`),
    INDEX `idx_fph_date` (`date_debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TRANCHES TARIFAIRES (prix selon quantité)
-- ============================================================
CREATE TABLE IF NOT EXISTS `tranches_tarifaires` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(150) NOT NULL,
    `article_id` INT UNSIGNED NULL COMMENT 'NULL = règle globale applicable à tous les articles',
    `categorie_id` INT UNSIGNED NULL COMMENT 'NULL = pas de filtre catégorie',
    `qte_min` INT UNSIGNED NOT NULL DEFAULT 1,
    `qte_max` INT UNSIGNED NULL COMMENT 'NULL = pas de limite supérieure',
    `mode_calcul` ENUM('majoration_pct','marge_pct','prix_fixe') NOT NULL DEFAULT 'majoration_pct',
    `valeur` DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Taux ou montant fixe selon mode_calcul',
    `priorite` INT NOT NULL DEFAULT 0 COMMENT 'Plus élevé = prioritaire en cas de chevauchement',
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `date_debut` DATETIME NULL,
    `date_fin` DATETIME NULL,
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tt_article` (`article_id`),
    INDEX `idx_tt_categorie` (`categorie_id`),
    INDEX `idx_tt_actif` (`actif`),
    INDEX `idx_tt_qte` (`qte_min`, `qte_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. RÉCEPTIONS (en-têtes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `receptions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(40) NOT NULL,
    `commande_id` INT NOT NULL,
    `fournisseur_id` INT NOT NULL,
    `magasin_id` INT NOT NULL,
    `utilisateur_id` INT UNSIGNED NULL,
    `statut` ENUM('Brouillon','Validee','Annulee') NOT NULL DEFAULT 'Brouillon',
    `date_reception` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `commentaire` TEXT NULL,
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reception_reference` (`reference`),
    INDEX `idx_reception_commande` (`commande_id`),
    INDEX `idx_reception_fournisseur` (`fournisseur_id`),
    INDEX `idx_reception_magasin` (`magasin_id`),
    INDEX `idx_reception_statut` (`statut`),
    CONSTRAINT `fk_reception_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseur` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reception_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reception_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. LIGNES DE RÉCEPTION
-- ============================================================
CREATE TABLE IF NOT EXISTS `reception_lignes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reception_id` INT UNSIGNED NOT NULL,
    `ligne_commande_id` INT NOT NULL,
    `article_id` INT NOT NULL,
    `quantite_attendue` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantite_recue` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantite_acceptee` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantite_perdue` INT UNSIGNED NOT NULL DEFAULT 0,
    `prix_achat_unitaire` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `numero_lot` VARCHAR(100) NULL,
    `date_peremption` DATE NULL,
    `motif_perte` ENUM('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') NULL,
    `commentaire_perte` VARCHAR(255) NULL,
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_rl_reception` (`reception_id`),
    INDEX `idx_rl_ligne_commande` (`ligne_commande_id`),
    INDEX `idx_rl_article` (`article_id`),
    CONSTRAINT `fk_rl_reception` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rl_ligne_commande` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande_fournisseur` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rl_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PERTES FOURNISSEUR (registre dédié)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pertes_fournisseur` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reception_id` INT UNSIGNED NULL,
    `reception_ligne_id` INT UNSIGNED NULL,
    `commande_id` INT NULL,
    `article_id` INT NOT NULL,
    `fournisseur_id` INT NOT NULL,
    `magasin_id` INT NOT NULL,
    `utilisateur_id` INT UNSIGNED NULL,
    `quantite` INT UNSIGNED NOT NULL DEFAULT 0,
    `motif` ENUM('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') NOT NULL DEFAULT 'autre',
    `commentaire` TEXT NULL,
    `date_perte` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pf_reception` (`reception_id`),
    INDEX `idx_pf_article` (`article_id`),
    INDEX `idx_pf_fournisseur` (`fournisseur_id`),
    INDEX `idx_pf_magasin` (`magasin_id`),
    INDEX `idx_pf_date` (`date_perte`),
    CONSTRAINT `fk_pf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. MODIFICATIONS TABLES EXISTANTES
-- ============================================================

-- 6a. articles : ajouter fournisseur_prix_ref_id
ALTER TABLE `articles`
    ADD COLUMN `fournisseur_prix_ref_id` INT UNSIGNED NULL AFTER `fournisseur_id`,
    ADD INDEX `idx_art_fournisseur_prix_ref` (`fournisseur_prix_ref_id`);

-- 6b. lignes_commande_fournisseur : ajouter quantite_receptionnee (total reçu via réceptions)
--     et quantite_perdue (total perdu via réceptions)
ALTER TABLE `lignes_commande_fournisseur`
    ADD COLUMN `quantite_receptionnee` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `quantite_recue`,
    ADD COLUMN `quantite_perdue` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `quantite_receptionnee`;

-- 6c. lignes_facture : ajouter prix_fournisseur_ref pour traçabilité
ALTER TABLE `lignes_facture`
    ADD COLUMN `prix_fournisseur_ref` DECIMAL(12,2) NULL AFTER `prix_unitaire`,
    ADD COLUMN `fournisseur_id_ref` INT UNSIGNED NULL AFTER `prix_fournisseur_ref`,
    ADD COLUMN `tranche_tarifaire_id` INT UNSIGNED NULL AFTER `fournisseur_id_ref`;

-- ============================================================
-- 7. TRIGGERS IMMUTABILITÉ RÉCEPTIONS
-- ============================================================
DELIMITER $$

CREATE TRIGGER `trg_receptions_immutable_update`
BEFORE UPDATE ON `receptions`
FOR EACH ROW
BEGIN
    IF OLD.statut = 'Validee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une réception validée ne peut pas être modifiée.';
    END IF;
    IF OLD.statut = 'Annulee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une réception annulée ne peut pas être réactivée.';
    END IF;
END$$

CREATE TRIGGER `trg_receptions_immutable_delete`
BEFORE DELETE ON `receptions`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Les réceptions ne peuvent pas être supprimées.';
END$$

DELIMITER ;

-- ============================================================
-- 8. INSÉRER TRANCHE TARIFAIRE PAR DÉFAUT
-- ============================================================
INSERT INTO `tranches_tarifaires` (`nom`, `qte_min`, `qte_max`, `mode_calcul`, `valeur`, `priorite`, `actif`)
VALUES
    ('Défaut 1-9 unités', 1, 9, 'majoration_pct', 40.0000, 0, 1),
    ('Défaut 10-49 unités', 10, 49, 'majoration_pct', 35.0000, 0, 1),
    ('Défaut 50-199 unités', 50, 199, 'majoration_pct', 30.0000, 0, 1),
    ('Défaut 200-499 unités', 200, 499, 'majoration_pct', 25.0000, 0, 1),
    ('Défaut 500+ unités', 500, NULL, 'majoration_pct', 20.0000, 0, 1);

-- ============================================================
-- 9. INSÉRER PERMISSIONS NOUVELLES
-- ============================================================
INSERT INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
    ('receptions_consulter', 'Consulter les réceptions fournisseur', 'achats'),
    ('receptions_gerer', 'Créer et gérer les réceptions fournisseur', 'achats'),
    ('pertes_consulter', 'Consulter les pertes fournisseur', 'achats'),
    ('pertes_gerer', 'Enregistrer les pertes fournisseur', 'achats'),
    ('tarification_consulter', 'Consulter les règles de tarification', 'tarification'),
    ('tarification_gerer', 'Gérer les règles de tarification', 'tarification'),
    ('prix_fournisseur_consulter', 'Consulter l\'historique des prix fournisseurs', 'achats'),
    ('prix_fournisseur_gerer', 'Modifier les prix fournisseurs', 'achats');

-- Assigner aux rôles chef equipe et Admin
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'chef equipe', `id` FROM `permissions` WHERE `cle_permission` IN (
    'receptions_consulter', 'receptions_gerer', 'pertes_consulter', 'pertes_gerer',
    'tarification_consulter', 'tarification_gerer', 'prix_fournisseur_consulter', 'prix_fournisseur_gerer'
);

INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'Admin', `id` FROM `permissions` WHERE `cle_permission` IN (
    'receptions_consulter', 'receptions_gerer', 'pertes_consulter', 'pertes_gerer',
    'tarification_consulter', 'tarification_gerer', 'prix_fournisseur_consulter', 'prix_fournisseur_gerer'
);

-- Magasinier : consultation seulement
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'Magasinier', `id` FROM `permissions` WHERE `cle_permission` IN (
    'receptions_consulter', 'pertes_consulter', 'tarification_consulter', 'prix_fournisseur_consulter'
);
