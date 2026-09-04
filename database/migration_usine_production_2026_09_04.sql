-- ============================================================================
-- MIGRATION : MODULE USINE DE PRODUCTION
-- Date : 2026-09-04
-- Description : Ajout du module de gestion de l'usine de plastique et production
-- ============================================================================

-- =========================================================================
-- 1. MODIFICATIONS DE TABLES EXISTANTES
-- =========================================================================

-- 1.1 Ajouter type_article aux articles
ALTER TABLE `articles`
  ADD COLUMN `type_article` enum('MATIERE_PREMIERE','PRODUIT_FINI','ARTICLE_COMMERCIAL','CONSOMMABLE')
    NOT NULL DEFAULT 'ARTICLE_COMMERCIAL' AFTER `categorie_id`;

ALTER TABLE `articles`
  ADD INDEX `idx_art_type` (`type_article`);

-- 1.2 Ajouter type_magasin aux magasins
ALTER TABLE `magasins`
  ADD COLUMN `type_magasin` enum('MAGASIN','USINE') NOT NULL DEFAULT 'MAGASIN' AFTER `nom`;

ALTER TABLE `magasins`
  ADD INDEX `idx_mag_type` (`type_magasin`);

-- 1.3 Ajouter source_cout aux tranches_tarifaires pour gérer le coût de production
ALTER TABLE `tranches_tarifaires`
  ADD COLUMN `source_cout` enum('ACHAT_FOURNISSEUR','PRODUCTION_INTERNE')
    NOT NULL DEFAULT 'ACHAT_FOURNISSEUR' AFTER `mode_calcul`;

-- 1.4 Ajouter cout_production_ref aux articles (coût de production de référence)
ALTER TABLE `articles`
  ADD COLUMN `cout_production_ref` decimal(14,4) DEFAULT NULL AFTER `cump`;

-- 1.5 Étendre les types de mouvements de stock
ALTER TABLE `mouvements_stock`
  MODIFY COLUMN `type` enum('Entree','Sortie','Vente','Transfert','Ajustement','Retour_stock','Production','Perte_production')
    NOT NULL;

-- =========================================================================
-- 2. NOUVELLES TABLES — RECETTES / NOMENCLATURES
-- =========================================================================

CREATE TABLE `recettes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) NOT NULL,
  `article_id` int NOT NULL COMMENT 'Produit fini fabriqué',
  `quantite_produite` int NOT NULL DEFAULT 100 COMMENT 'Quantité théorique produite par lot de recette',
  `unite_produit` varchar(20) NOT NULL DEFAULT 'UNITE',
  `version` int NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rec_article` (`article_id`),
  KEY `idx_rec_actif` (`actif`),
  UNIQUE KEY `uk_recette_article_version` (`article_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recettes_lignes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recette_id` int NOT NULL,
  `matiere_id` int NOT NULL COMMENT 'Article matière première',
  `quantite_necessaire` decimal(12,4) NOT NULL DEFAULT 0 COMMENT 'Quantité nécessaire pour quantite_produite',
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `pertes_theoriques_pct` decimal(5,2) NOT NULL DEFAULT 0 COMMENT 'Perte théorique en %',
  `ordre` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rl_recette` (`recette_id`),
  KEY `idx_rl_matiere` (`matiere_id`),
  UNIQUE KEY `uk_recette_ligne` (`recette_id`, `matiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 3. NOUVELLES TABLES — PRODUCTIONS
-- =========================================================================

CREATE TABLE `productions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) NOT NULL COMMENT 'PROD-YYYY-NNNN',
  `article_id` int NOT NULL COMMENT 'Produit fini à fabriquer',
  `recette_id` int NOT NULL COMMENT 'Recette utilisée',
  `recette_version` int NOT NULL DEFAULT 1,
  `quantite_prevue` int NOT NULL DEFAULT 0,
  `quantite_produite` int NOT NULL DEFAULT 0 COMMENT 'Produits conformes',
  `quantite_perdue` int NOT NULL DEFAULT 0 COMMENT 'Produits non conformes',
  `cout_matieres` decimal(14,2) NOT NULL DEFAULT 0 COMMENT 'Coût total matières consommées',
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0 COMMENT 'Coût matière unitaire = cout_matieres / quantite_produite',
  `statut` enum('BROUILLON','PLANIFIEE','EN_COURS','TERMINEE','ANNULEE') NOT NULL DEFAULT 'BROUILLON',
  `date_prevue` date DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL COMMENT 'Responsable de production',
  `magasin_usine_id` int NOT NULL COMMENT 'Magasin usine source',
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prod_ref` (`reference`),
  KEY `idx_prod_article` (`article_id`),
  KEY `idx_prod_recette` (`recette_id`),
  KEY `idx_prod_statut` (`statut`),
  KEY `idx_prod_date` (`date_prevue`),
  KEY `idx_prod_usine` (`magasin_usine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `production_matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `quantite_prevue` decimal(12,4) NOT NULL DEFAULT 0,
  `quantite_reelle` decimal(12,4) NOT NULL DEFAULT 0,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0 COMMENT 'Coût unitaire de référence au moment de la production',
  `cout_total` decimal(14,2) NOT NULL DEFAULT 0 COMMENT 'quantite_reelle × cout_unitaire',
  PRIMARY KEY (`id`),
  KEY `idx_pm_production` (`production_id`),
  KEY `idx_pm_matiere` (`matiere_id`),
  UNIQUE KEY `uk_prod_matiere` (`production_id`, `matiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `production_produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `article_id` int NOT NULL COMMENT 'Produit fini',
  `quantite_produite` int NOT NULL DEFAULT 0,
  `quantite_perdue` int NOT NULL DEFAULT 0,
  `magasin_destination_id` int DEFAULT NULL COMMENT 'Magasin cible (usine par défaut)',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pprod_production` (`production_id`),
  KEY `idx_pprod_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `production_pertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `type_perte` enum('matiere_premiere','produit_non_conforme','casse','defaut_machine','erreur_operateur','rebut','autre') NOT NULL,
  `article_id` int NOT NULL COMMENT 'Article concerné (matière ou produit)',
  `quantite` decimal(12,4) NOT NULL DEFAULT 0,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `motif` varchar(255) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `date_perte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ppert_production` (`production_id`),
  KEY `idx_ppert_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 4. NOUVELLES TABLES — PERSONNEL & PRÉSENCES
-- =========================================================================

CREATE TABLE `employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(30) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `fonction` varchar(100) NOT NULL DEFAULT '' COMMENT 'Poste/fonction',
  `telephone` varchar(30) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employe_matricule` (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `presences_employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employe_id` int NOT NULL,
  `date_presence` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `temps_travaille_minutes` int DEFAULT NULL COMMENT 'Calculé automatiquement',
  `commentaire` varchar(255) DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL COMMENT 'Utilisateur ayant enregistré',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presence_employe_date` (`employe_id`, `date_presence`),
  KEY `idx_pres_date` (`date_presence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `presences_employes_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `presence_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `ancienne_valeur` text DEFAULT NULL COMMENT 'JSON avant modification',
  `nouvelle_valeur` text DEFAULT NULL COMMENT 'JSON après modification',
  `utilisateur_id` int DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pa_presence` (`presence_id`),
  KEY `idx_pa_employe` (`employe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 5. TABLES DE LIAISON — PRODUCTION ↔ EMPLOYÉS
-- =========================================================================

CREATE TABLE `production_employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `employe_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prod_emp` (`production_id`, `employe_id`),
  KEY `idx_pe_employe` (`employe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 6. TABLE DE LOTS DE PRODUCTION
-- =========================================================================

CREATE TABLE `production_lots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `numero_lot` varchar(100) NOT NULL,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT 0,
  `date_fabrication` date NOT NULL,
  `date_peremption` date DEFAULT NULL,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0,
  `magasin_id` int NOT NULL COMMENT 'Magasin où le lot est stocké',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plot_numero` (`numero_lot`),
  KEY `idx_plot_production` (`production_id`),
  KEY `idx_plot_article` (`article_id`),
  KEY `idx_plot_magasin` (`magasin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 7. PERMISSIONS
-- =========================================================================

INSERT INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('usine_consulter', 'Consulter le module usine', 'Usine'),
  ('usine_gerer', 'Gérer les matières premières et recettes', 'Usine'),
  ('production_consulter', 'Consulter les productions', 'Usine'),
  ('production_gerer', 'Créer et gérer les productions', 'Usine'),
  ('production_cloturer', 'Clôturer une production', 'Usine'),
  ('personnel_consulter', 'Consulter le personnel usine', 'Usine'),
  ('personnel_gerer', 'Gérer le personnel usine', 'Usine'),
  ('presence_consulter', 'Consulter les présences', 'Usine'),
  ('presence_gerer', 'Enregistrer et modifier les présences', 'Usine'),
  ('transfert_usine_gerer', 'Effectuer des transferts usine → magasin', 'Usine');

-- Assigner au rôle Admin
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'Admin', id FROM `permissions` WHERE `cle_permission` IN (
  'usine_consulter', 'usine_gerer', 'production_consulter', 'production_gerer',
  'production_cloturer', 'personnel_consulter', 'personnel_gerer',
  'presence_consulter', 'presence_gerer', 'transfert_usine_gerer'
);

-- Assigner au rôle chef equipe
INSERT INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'chef equipe', id FROM `permissions` WHERE `cle_permission` IN (
  'usine_consulter', 'usine_gerer', 'production_consulter', 'production_gerer',
  'production_cloturer', 'personnel_consulter', 'personnel_gerer',
  'presence_consulter', 'presence_gerer', 'transfert_usine_gerer'
);

-- =========================================================================
-- 8. DONNÉES DE BASE — MAGASIN USINE
-- =========================================================================

INSERT INTO `magasins` (`nom`, `type_magasin`, `actif`)
VALUES ('USINE', 'USINE', 1);
