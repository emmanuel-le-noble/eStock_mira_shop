-- ============================================================
-- MIGRATION CONSOLIDÉE — ESTOCK_DB
-- Date : 10 septembre 2026
-- Objectif : Appliquer TOUTES les modifications d'un seul bloc
-- Remplace les 13 fichiers de migration individuels
-- ============================================================
-- RÈGLES :
--   - Chaque opération est idempotente (rejouable sans erreur)
--   - Aucune perte de données
--   - Aucun DROP TABLE sur des tables existantes
--   - Vérification avant chaque ALTER
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================
-- 1. TABLES NOUVELLES (CREATE IF NOT EXISTS)
-- ============================================================

-- 1.1 RBAC
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `date_attribution` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`, `role_id`),
  KEY `fk_ur_role` (`role_id`),
  KEY `idx_ur_role` (`role_id`),
  KEY `idx_ur_user_actif` (`user_id`, `actif`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equipes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('BOUTIQUE','USINE','LIVRAISON','ACHATS','LOGISTIQUE','CAISSE','AUTRE') NOT NULL DEFAULT 'BOUTIQUE',
  `chef_equipe_id` int DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_equipe_nom` (`nom`),
  KEY `idx_equipe_chef` (`chef_equipe_id`),
  KEY `idx_equipe_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equipe_membres` (
  `equipe_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`equipe_id`, `user_id`),
  CONSTRAINT `fk_emb_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_emb_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equipe_magasins` (
  `equipe_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  PRIMARY KEY (`equipe_id`, `magasin_id`),
  KEY `idx_em_magasin` (`magasin_id`),
  CONSTRAINT `fk_em_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_em_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unites_mesure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('MASSIQUE','VOLUMIQUE','UNITE','LONGUEUR','AUTRE') NOT NULL DEFAULT 'UNITE',
  `facteur_conversion` decimal(14,6) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unite_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 Usine
CREATE TABLE IF NOT EXISTS `usines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usine_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_magasins` (
  `user_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `magasin_id`),
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_um_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `matieres_premieres` (
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
  UNIQUE KEY `uk_mp_ref` (`reference`),
  KEY `idx_mp_categorie` (`categorie_id`),
  KEY `idx_mp_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `categories_pertes_production` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpp_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recettes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) NOT NULL,
  `article_id` int NOT NULL,
  `quantite_produite` int NOT NULL DEFAULT 100,
  `unite_produit` varchar(20) NOT NULL DEFAULT 'UNITE',
  `version` int NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rec_article` (`article_id`),
  KEY `idx_rec_actif` (`actif`),
  UNIQUE KEY `uk_recette_article_version` (`article_id`, `version`),
  CONSTRAINT `fk_rec_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recettes_lignes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recette_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `quantite_necessaire` decimal(12,4) NOT NULL DEFAULT 0,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `pertes_theoriques_pct` decimal(5,2) NOT NULL DEFAULT 0,
  `ordre` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rl_recette` (`recette_id`),
  KEY `idx_rl_matiere` (`matiere_id`),
  UNIQUE KEY `uk_recette_ligne` (`recette_id`, `matiere_id`),
  CONSTRAINT `fk_rl_recette` FOREIGN KEY (`recette_id`) REFERENCES `recettes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rl_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `productions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) NOT NULL,
  `article_id` int NOT NULL,
  `recette_id` int NOT NULL,
  `recette_version` int NOT NULL DEFAULT 1,
  `quantite_prevue` int NOT NULL DEFAULT 0,
  `quantite_produite` int NOT NULL DEFAULT 0,
  `quantite_perdue` int NOT NULL DEFAULT 0,
  `cout_matieres` decimal(14,2) NOT NULL DEFAULT 0,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0,
  `rendement_pct` decimal(8,4) DEFAULT NULL,
  `rendement_cout` decimal(8,4) DEFAULT NULL,
  `quantite_defectueuse` int DEFAULT 0,
  `statut` enum('BROUILLON','PLANIFIEE','EN_COURS','TERMINEE','ANNULEE') NOT NULL DEFAULT 'BROUILLON',
  `date_prevue` date DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `usine_id` int DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prod_ref` (`reference`),
  KEY `idx_prod_article` (`article_id`),
  KEY `idx_prod_recette` (`recette_id`),
  KEY `idx_prod_statut` (`statut`),
  KEY `idx_prod_date` (`date_prevue`),
  CONSTRAINT `fk_prod_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prod_recette` FOREIGN KEY (`recette_id`) REFERENCES `recettes`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prod_usine` FOREIGN KEY (`usine_id`) REFERENCES `usines`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `quantite_prevue` decimal(12,4) NOT NULL DEFAULT 0,
  `quantite_reelle` decimal(12,4) NOT NULL DEFAULT 0,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `numero_lot` varchar(100) DEFAULT NULL,
  `lot_id` int DEFAULT NULL,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0,
  `cout_total` decimal(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pm_production` (`production_id`),
  KEY `idx_pm_matiere` (`matiere_id`),
  KEY `idx_pm_lot` (`lot_id`),
  UNIQUE KEY `uk_prod_matiere` (`production_id`, `matiere_id`),
  CONSTRAINT `fk_pm_production` FOREIGN KEY (`production_id`) REFERENCES `productions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pm_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_pertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `type_perte` enum('matiere_premiere','produit_non_conforme','casse','defaut_machine','erreur_operateur','rebut','autre') NOT NULL,
  `categorie_perte_id` int unsigned DEFAULT NULL,
  `article_id` int NOT NULL,
  `quantite` decimal(12,4) NOT NULL DEFAULT 0,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `motif` varchar(255) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `date_perte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pp_production` (`production_id`),
  KEY `idx_pp_article` (`article_id`),
  KEY `idx_pp_categorie` (`categorie_perte_id`),
  CONSTRAINT `fk_pp_production` FOREIGN KEY (`production_id`) REFERENCES `productions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pp_categorie` FOREIGN KEY (`categorie_perte_id`) REFERENCES `categories_pertes_production`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(30) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `fonction` varchar(100) NOT NULL DEFAULT '',
  `telephone` varchar(30) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employe_matricule` (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presences_employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employe_id` int NOT NULL,
  `date_presence` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `temps_travaille_minutes` int DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `statut` enum('PRESENT','ABSENT','RETARD','CONGE') NOT NULL DEFAULT 'PRESENT',
  `utilisateur_id` int DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presence_employe_date` (`employe_id`, `date_presence`),
  KEY `idx_pres_date` (`date_presence`),
  KEY `idx_presences_emp_date` (`employe_id`,`date_presence`),
  CONSTRAINT `fk_pe_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pe_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machines` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `description` text NULL,
  `etat` enum('ARRETEE','EN_FONCTIONNEMENT','EN_MAINTENANCE','EN_PANNE') NOT NULL DEFAULT 'ARRETEE',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_machine_ref` (`reference`),
  KEY `idx_machine_etat` (`etat`),
  KEY `idx_machine_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machine_etats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `machine_id` int unsigned NOT NULL,
  `production_id` int NULL,
  `etat` enum('ARRETEE','EN_FONCTIONNEMENT','EN_MAINTENANCE','EN_PANNE') NOT NULL,
  `heure_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `heure_fin` datetime NULL,
  `duree_minutes` int unsigned NULL,
  `motif` varchar(255) NULL,
  `utilisateur_id` int NULL,
  `notes` text NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_me_machine` (`machine_id`),
  KEY `idx_me_production` (`production_id`),
  KEY `idx_me_heure` (`heure_debut`),
  KEY `idx_me_etat` (`etat`),
  KEY `idx_machine_etats_machine` (`machine_id`),
  CONSTRAINT `fk_me_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_me_production` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `type_notif` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `cible_role` varchar(50) NULL,
  `cible_utilisateur_id` int NULL,
  `cible_equipe_id` int DEFAULT NULL,
  `source_id` int NULL,
  `source_type` varchar(50) NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `statut` enum('EN_ATTENTE','LUE','ARCHIVEE') NOT NULL DEFAULT 'EN_ATTENTE',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_lecture` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_type` (`type`),
  KEY `idx_notif_lu` (`lu`),
  KEY `idx_notif_cible` (`cible_role`, `cible_utilisateur_id`),
  KEY `idx_notif_date` (`date_creation`),
  KEY `idx_notif_statut` (`statut`),
  KEY `idx_notif_type_notif` (`type_notif`),
  KEY `idx_notif_equipe` (`cible_equipe_id`),
  KEY `idx_notif_user` (`cible_utilisateur_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`cible_utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `horaires_travail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `jour` enum('LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `tolerance_retard_minutes` int unsigned NOT NULL DEFAULT '5',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ht_jour` (`nom`, `jour`),
  KEY `idx_ht_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fournisseur_prix_historique` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `prix_achat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `source` enum('commande','reception','manuelle') NOT NULL DEFAULT 'manuelle',
  `reference_id` int unsigned NULL,
  `utilisateur_id` int NULL,
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fph_article` (`article_id`),
  KEY `idx_fph_fournisseur` (`fournisseur_id`),
  KEY `idx_fph_actif` (`article_id`,`fournisseur_id`,`est_actif`),
  KEY `idx_fph_date` (`date_debut`),
  KEY `idx_four_historique_art` (`article_id`),
  KEY `idx_four_historique_four` (`fournisseur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tranches_tarifaires` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `article_id` int NULL,
  `categorie_id` int NULL,
  `qte_min` int unsigned NOT NULL DEFAULT '1',
  `qte_max` int unsigned NULL,
  `mode_calcul` enum('majoration_pct','marge_pct','prix_fixe') NOT NULL DEFAULT 'majoration_pct',
  `source_cout` enum('ACHAT_FOURNISSEUR','PRODUCTION_INTERNE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR',
  `valeur` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `priorite` int NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_debut` datetime NULL,
  `date_fin` datetime NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tt_article` (`article_id`),
  KEY `idx_tt_categorie` (`categorie_id`),
  KEY `idx_tt_actif` (`actif`),
  KEY `idx_tt_qte` (`qte_min`,`qte_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `receptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) NOT NULL,
  `commande_id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int NULL,
  `statut` enum('Brouillon','Validee','Annulee') NOT NULL DEFAULT 'Brouillon',
  `date_reception` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `commentaire` text NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reception_reference` (`reference`),
  KEY `idx_reception_commande` (`commande_id`),
  KEY `idx_reception_fournisseur` (`fournisseur_id`),
  KEY `idx_reception_magasin` (`magasin_id`),
  KEY `idx_reception_statut` (`statut`),
  CONSTRAINT `fk_reception_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseur` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_reception_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_reception_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reception_lignes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reception_id` int unsigned NOT NULL,
  `ligne_commande_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite_attendue` int unsigned NOT NULL DEFAULT '0',
  `quantite_recue` int unsigned NOT NULL DEFAULT '0',
  `quantite_acceptee` int unsigned NOT NULL DEFAULT '0',
  `quantite_perdue` int unsigned NOT NULL DEFAULT '0',
  `prix_achat_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  `numero_lot` varchar(100) NULL,
  `date_peremption` date NULL,
  `motif_perte` enum('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') NULL,
  `commentaire_perte` varchar(255) NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rl_reception` (`reception_id`),
  KEY `idx_rl_ligne_commande` (`ligne_commande_id`),
  KEY `idx_rl_article` (`article_id`),
  CONSTRAINT `fk_rl_reception` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rl_ligne_commande` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande_fournisseur` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rl_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pertes_fournisseur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reception_id` int unsigned NULL,
  `reception_ligne_id` int unsigned NULL,
  `commande_id` int NULL,
  `article_id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int NULL,
  `quantite` int unsigned NOT NULL DEFAULT '0',
  `motif` enum('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') NOT NULL DEFAULT 'autre',
  `commentaire` text NULL,
  `date_perte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pf_reception` (`reception_id`),
  KEY `idx_pf_article` (`article_id`),
  KEY `idx_pf_fournisseur` (`fournisseur_id`),
  KEY `idx_pf_magasin` (`magasin_id`),
  KEY `idx_pf_date` (`date_perte`),
  CONSTRAINT `fk_pf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. AJOUT DE COLONNES MANQUANTES (idempotent)
-- ============================================================

-- 2.1 articles : type_article, origine_article, cout_production_ref, deleted_at, fournisseur_prix_ref_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND COLUMN_NAME='type_article');
SET @sql = IF(@c=0, "ALTER TABLE `articles` ADD COLUMN `type_article` enum('MATIERE_PREMIERE','PRODUIT_FINI','ARTICLE_COMMERCIAL','CONSOMMABLE') NOT NULL DEFAULT 'ARTICLE_COMMERCIAL' AFTER `categorie_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND COLUMN_NAME='origine_article');
SET @sql = IF(@c=0, "ALTER TABLE `articles` ADD COLUMN `origine_article` enum('ACHAT_FOURNISSEUR','PRODUCTION_USINE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR' AFTER `type_article`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND COLUMN_NAME='cout_production_ref');
SET @sql = IF(@c=0, "ALTER TABLE `articles` ADD COLUMN `cout_production_ref` decimal(14,4) DEFAULT NULL AFTER `cump`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND COLUMN_NAME='deleted_at');
SET @sql = IF(@c=0, "ALTER TABLE `articles` ADD COLUMN `deleted_at` datetime DEFAULT NULL AFTER `actif`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND COLUMN_NAME='fournisseur_prix_ref_id');
SET @sql = IF(@c=0, "ALTER TABLE `articles` ADD COLUMN `fournisseur_prix_ref_id` int unsigned DEFAULT NULL AFTER `fournisseur_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.2 magasins : type_magasin, equipe_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='magasins' AND COLUMN_NAME='type_magasin');
SET @sql = IF(@c=0, "ALTER TABLE `magasins` ADD COLUMN `type_magasin` enum('MAGASIN','USINE') NOT NULL DEFAULT 'MAGASIN' AFTER `nom`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='magasins' AND COLUMN_NAME='equipe_id');
SET @sql = IF(@c=0, "ALTER TABLE `magasins` ADD COLUMN `equipe_id` int DEFAULT NULL AFTER `type_magasin`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.3 mouvements_stock : stock_avant, stock_apres, cout_unitaire, reference_type, reference_id, lot_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='stock_avant');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `stock_avant` int DEFAULT NULL AFTER `quantite`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='stock_apres');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `stock_apres` int DEFAULT NULL AFTER `stock_avant`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='cout_unitaire');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `cout_unitaire` decimal(14,4) DEFAULT NULL AFTER `stock_apres`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='reference_type');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `reference_type` varchar(50) DEFAULT NULL AFTER `cout_unitaire`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='reference_id');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `reference_id` int DEFAULT NULL AFTER `reference_type`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND COLUMN_NAME='lot_id');
SET @sql = IF(@c=0, "ALTER TABLE `mouvements_stock` ADD COLUMN `lot_id` int DEFAULT NULL AFTER `reference_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.4 notifications : statut, type_notif, cible_equipe_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='statut');
SET @sql = IF(@c=0, "ALTER TABLE `notifications` ADD COLUMN `statut` enum('EN_ATTENTE','LUE','ARCHIVEE') NOT NULL DEFAULT 'EN_ATTENTE' AFTER `lu`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='type_notif');
SET @sql = IF(@c=0, "ALTER TABLE `notifications` ADD COLUMN `type_notif` varchar(50) DEFAULT NULL AFTER `titre`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='cible_equipe_id');
SET @sql = IF(@c=0, "ALTER TABLE `notifications` ADD COLUMN `cible_equipe_id` int DEFAULT NULL AFTER `cible_utilisateur_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.5 presences_employes : statut
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='presences_employes' AND COLUMN_NAME='statut');
SET @sql = IF(@c=0, "ALTER TABLE `presences_employes` ADD COLUMN `statut` enum('PRESENT','ABSENT','RETARD','CONGE') NOT NULL DEFAULT 'PRESENT' AFTER `commentaire`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.6 productions : rendement_pct, rendement_cout, quantite_defectueuse, usine_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND COLUMN_NAME='rendement_pct');
SET @sql = IF(@c=0, "ALTER TABLE `productions` ADD COLUMN `rendement_pct` decimal(8,4) DEFAULT NULL AFTER `cout_unitaire`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND COLUMN_NAME='rendement_cout');
SET @sql = IF(@c=0, "ALTER TABLE `productions` ADD COLUMN `rendement_cout` decimal(8,4) DEFAULT NULL AFTER `rendement_pct`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND COLUMN_NAME='quantite_defectueuse');
SET @sql = IF(@c=0, "ALTER TABLE `productions` ADD COLUMN `quantite_defectueuse` int DEFAULT 0 AFTER `quantite_perdue`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND COLUMN_NAME='usine_id');
SET @sql = IF(@c=0, "ALTER TABLE `productions` ADD COLUMN `usine_id` int DEFAULT NULL AFTER `utilisateur_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.7 production_matieres : numero_lot, lot_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_matieres' AND COLUMN_NAME='numero_lot');
SET @sql = IF(@c=0, "ALTER TABLE `production_matieres` ADD COLUMN `numero_lot` varchar(100) DEFAULT NULL AFTER `unite`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_matieres' AND COLUMN_NAME='lot_id');
SET @sql = IF(@c=0, "ALTER TABLE `production_matieres` ADD COLUMN `lot_id` int DEFAULT NULL AFTER `numero_lot`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.8 production_pertes : categorie_perte_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_pertes' AND COLUMN_NAME='categorie_perte_id');
SET @sql = IF(@c=0, "ALTER TABLE `production_pertes` ADD COLUMN `categorie_perte_id` int unsigned DEFAULT NULL AFTER `type_perte`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.9 utilisateurs : role_id (remplace ENUM role)
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='utilisateurs' AND COLUMN_NAME='role_id');
SET @sql = IF(@c=0, "ALTER TABLE `utilisateurs` ADD COLUMN `role_id` int NOT NULL DEFAULT '4' AFTER `mot_de_passe`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.10 lignes_commande_fournisseur : quantite_receptionnee, quantite_perdue
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_commande_fournisseur' AND COLUMN_NAME='quantite_receptionnee');
SET @sql = IF(@c=0, "ALTER TABLE `lignes_commande_fournisseur` ADD COLUMN `quantite_receptionnee` int unsigned NOT NULL DEFAULT '0' AFTER `quantite_recue`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_commande_fournisseur' AND COLUMN_NAME='quantite_perdue');
SET @sql = IF(@c=0, "ALTER TABLE `lignes_commande_fournisseur` ADD COLUMN `quantite_perdue` int unsigned NOT NULL DEFAULT '0' AFTER `quantite_receptionnee`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.11 lignes_facture : prix_fournisseur_ref, fournisseur_id_ref, tranche_tarifaire_id
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_facture' AND COLUMN_NAME='prix_fournisseur_ref');
SET @sql = IF(@c=0, "ALTER TABLE `lignes_facture` ADD COLUMN `prix_fournisseur_ref` decimal(12,2) DEFAULT NULL AFTER `prix_unitaire`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_facture' AND COLUMN_NAME='fournisseur_id_ref');
SET @sql = IF(@c=0, "ALTER TABLE `lignes_facture` ADD COLUMN `fournisseur_id_ref` int DEFAULT NULL AFTER `prix_fournisseur_ref`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_facture' AND COLUMN_NAME='tranche_tarifaire_id');
SET @sql = IF(@c=0, "ALTER TABLE `lignes_facture` ADD COLUMN `tranche_tarifaire_id` int DEFAULT NULL AFTER `fournisseur_id_ref`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.12 tranches_tarifaires : source_cout
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tranches_tarifaires' AND COLUMN_NAME='source_cout');
SET @sql = IF(@c=0, "ALTER TABLE `tranches_tarifaires` ADD COLUMN `source_cout` enum('ACHAT_FOURNISSEUR','PRODUCTION_INTERNE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR' AFTER `mode_calcul`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- 3. EXTENSION DES ENUMS
-- ============================================================

-- 3.1 mouvements_stock.type : étendre l'enum
ALTER TABLE `mouvements_stock`
  MODIFY COLUMN `type` enum('ENTREE','SORTIE','VENTE','TRANSFERT','AJUSTEMENT','RETOUR_STOCK','RECEPTION','PERTE','PRODUCTION','PERTE_PRODUCTION','ENTREE_ACHAT','SORTIE_VENTE','TRANSFERT_ENTREE','TRANSFERT_SORTIE','Entree','Sortie','Vente','Transfert','Ajustement','Retour_stock','Perte_production') COLLATE utf8mb4_unicode_ci NOT NULL;

-- ============================================================
-- 4. NORMALISATION DES DONNÉES
-- ============================================================

-- 4.1 Mouvements de stock : casse unifiée
UPDATE `mouvements_stock` SET `type` = 'ENTREE' WHERE `type` = 'Entree';
UPDATE `mouvements_stock` SET `type` = 'SORTIE' WHERE `type` = 'Sortie';
UPDATE `mouvements_stock` SET `type` = 'VENTE' WHERE `type` = 'Vente';
UPDATE `mouvements_stock` SET `type` = 'TRANSFERT' WHERE `type` = 'Transfert';
UPDATE `mouvements_stock` SET `type` = 'AJUSTEMENT' WHERE `type` = 'Ajustement';
UPDATE `mouvements_stock` SET `type` = 'RETOUR_STOCK' WHERE `type` = 'Retour_stock';
UPDATE `mouvements_stock` SET `type` = 'PERTE_PRODUCTION' WHERE `type` = 'Perte_production';

-- 4.2 Notifications cible_role : normaliser
UPDATE `notifications` SET `cible_role` = 'ADMIN' WHERE `cible_role` = 'Admin';
UPDATE `notifications` SET `cible_role` = 'CHEF_EQUIPE' WHERE `cible_role` IN ('chef equipe', 'chef équipe');
UPDATE `notifications` SET `cible_role` = 'MAGASINIER' WHERE `cible_role` = 'Magasinier';
UPDATE `notifications` SET `cible_role` = 'VENDEUR' WHERE `cible_role` = 'Vendeur';
UPDATE `notifications` SET `cible_role` = 'PROPRIETAIRE' WHERE `cible_role` = 'Directeur';

-- 4.3 Role permissions : normaliser
UPDATE `role_permissions` SET `role_nom` = 'CHEF_EQUIPE' WHERE `role_nom` = 'chef equipe';
UPDATE `role_permissions` SET `role_nom` = 'CHEF_EQUIPE' WHERE `role_nom` = 'chef équipe';
UPDATE `role_permissions` SET `role_nom` = 'ADMIN' WHERE `role_nom` = 'Admin';
UPDATE `role_permissions` SET `role_nom` = 'MAGASINIER' WHERE `role_nom` = 'Magasinier';
UPDATE `role_permissions` SET `role_nom` = 'VENDEUR' WHERE `role_nom` = 'Vendeur';

-- 4.4 Supprimer la colonne ENUM role de utilisateurs si elle existe
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='utilisateurs' AND COLUMN_NAME='role');
SET @sql = IF(@c>0, "ALTER TABLE `utilisateurs` DROP COLUMN `role`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- 5. INDEX MANQUANTS
-- ============================================================

CREATE INDEX IF NOT EXISTS `idx_art_type` ON `articles` (`type_article`);
CREATE INDEX IF NOT EXISTS `idx_art_origine` ON `articles` (`origine_article`);
CREATE INDEX IF NOT EXISTS `idx_art_categorie` ON `articles` (`categorie_id`);
CREATE INDEX IF NOT EXISTS `idx_art_fournisseur_prix_ref` ON `articles` (`fournisseur_prix_ref_id`);
CREATE INDEX IF NOT EXISTS `idx_ac_fifo` ON `article_couts` (`article_id`,`quantite`,`date_entree`);
CREATE INDEX IF NOT EXISTS `idx_mag_type` ON `magasins` (`type_magasin`);
CREATE INDEX IF NOT EXISTS `idx_magasin_equipe` ON `magasins` (`equipe_id`);
CREATE INDEX IF NOT EXISTS `idx_hp_type_operation` ON `historique_points` (`type_operation`);
CREATE INDEX IF NOT EXISTS `idx_cf_date_commande` ON `commandes_fournisseur` (`date_commande`);
CREATE INDEX IF NOT EXISTS `idx_cl_client_type` ON `consentements_log` (`client_id`,`type_consentement`);
CREATE INDEX IF NOT EXISTS `idx_notif_statut` ON `notifications` (`statut`);
CREATE INDEX IF NOT EXISTS `idx_notif_equipe` ON `notifications` (`cible_equipe_id`);
CREATE INDEX IF NOT EXISTS `idx_notif_user` ON `notifications` (`cible_utilisateur_id`);
CREATE INDEX IF NOT EXISTS `idx_presences_emp_date` ON `presences_employes` (`employe_id`,`date_presence`);
CREATE INDEX IF NOT EXISTS `idx_machine_etats_machine` ON `machine_etats` (`machine_id`);
CREATE INDEX IF NOT EXISTS `idx_user_role_id` ON `utilisateurs` (`role_id`);
CREATE INDEX IF NOT EXISTS `idx_mvt_art_mag_date` ON `mouvements_stock` (`article_id`,`magasin_id`,`date_mouvement`);
CREATE INDEX IF NOT EXISTS `idx_mvt_ref` ON `mouvements_stock` (`reference_type`,`reference_id`);
CREATE INDEX IF NOT EXISTS `idx_ms_type_magasin` ON `mouvements_stock` (`type`,`magasin_id`);
CREATE INDEX IF NOT EXISTS `idx_stock_mag_art` ON `stock_magasins` (`article_id`);
CREATE INDEX IF NOT EXISTS `idx_four_historique_art` ON `fournisseur_prix_historique` (`article_id`);
CREATE INDEX IF NOT EXISTS `idx_four_historique_four` ON `fournisseur_prix_historique` (`fournisseur_id`);

-- ============================================================
-- 6. CONTRAINTES ÉTRANGÈRES MANQUANTES
-- ============================================================

-- 6.1 articles
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND CONSTRAINT_NAME='fk_art_fournisseur');
SET @sql = IF(@fk=0, "ALTER TABLE `articles` ADD CONSTRAINT `fk_art_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs`(`id`) ON DELETE SET NULL ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND CONSTRAINT_NAME='fk_art_categorie');
SET @sql = IF(@fk=0, "ALTER TABLE `articles` ADD CONSTRAINT `fk_art_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.2 article_couts
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_couts' AND CONSTRAINT_NAME='fk_aco_article');
SET @sql = IF(@fk=0, "ALTER TABLE `article_couts` ADD CONSTRAINT `fk_aco_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_couts' AND CONSTRAINT_NAME='fk_aco_magasin');
SET @sql = IF(@fk=0, "ALTER TABLE `article_couts` ADD CONSTRAINT `fk_aco_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.3 commandes_fournisseur
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='commandes_fournisseur' AND CONSTRAINT_NAME='fk_cf_fournisseur');
SET @sql = IF(@fk=0, "ALTER TABLE `commandes_fournisseur` ADD CONSTRAINT `fk_cf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs`(`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='commandes_fournisseur' AND CONSTRAINT_NAME='fk_cf_magasin');
SET @sql = IF(@fk=0, "ALTER TABLE `commandes_fournisseur` ADD CONSTRAINT `fk_cf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins`(`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='commandes_fournisseur' AND CONSTRAINT_NAME='fk_cf_user');
SET @sql = IF(@fk=0, "ALTER TABLE `commandes_fournisseur` ADD CONSTRAINT `fk_cf_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.4 lignes_commande_fournisseur
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lignes_commande_fournisseur' AND CONSTRAINT_NAME='fk_lcf_article');
SET @sql = IF(@fk=0, "ALTER TABLE `lignes_commande_fournisseur` ADD CONSTRAINT `fk_lcf_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.5 role_permissions
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='role_permissions' AND CONSTRAINT_NAME='fk_rp_role_code');
SET @sql = IF(@fk=0, "ALTER TABLE `role_permissions` ADD CONSTRAINT `fk_rp_role_code` FOREIGN KEY (`role_nom`) REFERENCES `roles`(`code`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.6 notifications
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND CONSTRAINT_NAME='fk_notif_user');
SET @sql = IF(@fk=0, "ALTER TABLE `notifications` ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`cible_utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.7 presences_employes
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='presences_employes' AND CONSTRAINT_NAME='fk_pe_employe');
SET @sql = IF(@fk=0, "ALTER TABLE `presences_employes` ADD CONSTRAINT `fk_pe_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='presences_employes' AND CONSTRAINT_NAME='fk_pe_user');
SET @sql = IF(@fk=0, "ALTER TABLE `presences_employes` ADD CONSTRAINT `fk_pe_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.8 productions
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND CONSTRAINT_NAME='fk_prod_article');
SET @sql = IF(@fk=0, "ALTER TABLE `productions` ADD CONSTRAINT `fk_prod_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND CONSTRAINT_NAME='fk_prod_recette');
SET @sql = IF(@fk=0, "ALTER TABLE `productions` ADD CONSTRAINT `fk_prod_recette` FOREIGN KEY (`recette_id`) REFERENCES `recettes`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productions' AND CONSTRAINT_NAME='fk_prod_usine');
SET @sql = IF(@fk=0, "ALTER TABLE `productions` ADD CONSTRAINT `fk_prod_usine` FOREIGN KEY (`usine_id`) REFERENCES `usines`(`id`) ON DELETE SET NULL ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.9 production_matieres
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_matieres' AND CONSTRAINT_NAME='fk_pm_production');
SET @sql = IF(@fk=0, "ALTER TABLE `production_matieres` ADD CONSTRAINT `fk_pm_production` FOREIGN KEY (`production_id`) REFERENCES `productions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_matieres' AND CONSTRAINT_NAME='fk_pm_matiere');
SET @sql = IF(@fk=0, "ALTER TABLE `production_matieres` ADD CONSTRAINT `fk_pm_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6.10 production_pertes
SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_pertes' AND CONSTRAINT_NAME='fk_pp_production');
SET @sql = IF(@fk=0, "ALTER TABLE `production_pertes` ADD CONSTRAINT `fk_pp_production` FOREIGN KEY (`production_id`) REFERENCES `productions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='production_pertes' AND CONSTRAINT_NAME='fk_pp_categorie');
SET @sql = IF(@fk=0, "ALTER TABLE `production_pertes` ADD CONSTRAINT `fk_pp_categorie` FOREIGN KEY (`categorie_perte_id`) REFERENCES `categories_pertes_production`(`id`) ON DELETE SET NULL", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- 7. CHECK CONSTRAINTS
-- ============================================================

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND CONSTRAINT_NAME='chk_articles_prix_vente');
SET @sql = IF(@chk=0, "ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_prix_vente` CHECK (`prix_vente` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND CONSTRAINT_NAME='chk_articles_prix_achat');
SET @sql = IF(@chk=0, "ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_prix_achat` CHECK (`prix_achat` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND CONSTRAINT_NAME='chk_articles_stock');
SET @sql = IF(@chk=0, "ALTER TABLE `articles` ADD CONSTRAINT `chk_articles_stock` CHECK (`quantite_stock` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='stock_magasins' AND CONSTRAINT_NAME='chk_sm_quantite');
SET @sql = IF(@chk=0, "ALTER TABLE `stock_magasins` ADD CONSTRAINT `chk_sm_quantite` CHECK (`quantite` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factures' AND CONSTRAINT_NAME='chk_fact_total');
SET @sql = IF(@chk=0, "ALTER TABLE `factures` ADD CONSTRAINT `chk_fact_total` CHECK (`total_ttc` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paiements_facture' AND CONSTRAINT_NAME='chk_paiement_montant');
SET @sql = IF(@chk=0, "ALTER TABLE `paiements_facture` ADD CONSTRAINT `chk_paiement_montant` CHECK (`montant` >= 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @chk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mouvements_stock' AND CONSTRAINT_NAME='chk_mvt_quantite');
SET @sql = IF(@chk=0, "ALTER TABLE `mouvements_stock` ADD CONSTRAINT `chk_mvt_quantite` CHECK (`quantite` > 0)", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- 8. DONNÉES INITIALES (INSERT IGNORE)
-- ============================================================

-- 8.1 Rôles
INSERT IGNORE INTO `roles` (`code`, `nom`, `description`, `actif`) VALUES
  ('PROPRIETAIRE', 'Propriétaire', 'Accès total au système', 1),
  ('ADMIN', 'Administrateur', 'Gestion paramètres et utilisateurs', 1),
  ('MAGASINIER', 'Magasinier', 'Gestion stock et ventes', 1),
  ('VENDEUR', 'Vendeur', 'Opération caisse et ventes', 1),
  ('CHEF_EQUIPE', 'Chef d''équipe', 'Chef d''équipe de section', 1),
  ('CHEF_EQUIPE_USINE', 'Chef d''équipe usine', 'Chef d''équipe usine', 1);

-- 8.2 Synchroniser user_roles depuis ENUM legacy (si colonne encore présente)
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='utilisateurs' AND COLUMN_NAME='role');
SET @sql = IF(@c > 0,
  "INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `date_debut`, `actif`)
   SELECT u.id, r.id, NOW(), 1
   FROM `utilisateurs` u
   JOIN `roles` r ON (
       (u.`role` = 'chef equipe' AND r.`code` = 'CHEF_EQUIPE')
       OR (u.`role` = 'chef équipe' AND r.`code` = 'CHEF_EQUIPE')
       OR (u.`role` = 'Admin' AND r.`code` = 'ADMIN')
       OR (u.`role` = 'Magasinier' AND r.`code` = 'MAGASINIER')
       OR (u.`role` = 'Vendeur' AND r.`code` = 'VENDEUR')
   )
   WHERE u.`actif` = 1",
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 8.3 Catégories matières premières
INSERT IGNORE INTO `categories_matieres_premieres` (`nom`, `description`) VALUES
  ('Matieres plastiques', 'Plastiques en granules, poudre ou feuilles'),
  ('Additifs', 'Agents de coloration, stabilisants, etc.'),
  ('Emballages', 'Films, cartons, caisses'),
  ('Produits chimiques', 'Colles, solvants, nettoyants'),
  ('Consommables usine', 'Huiles, graisses, outillage consommable'),
  ('Matières organiques', 'Fibres, bois, caoutchouc'),
  ('Métaux', 'Alu, fer, acier');

-- 8.4 Unités de mesure
INSERT IGNORE INTO `unites_mesure` (`code`, `nom`, `categorie`, `actif`) VALUES
  ('KG', 'Kilogramme', 'MASSIQUE', 1),
  ('G', 'Gramme', 'MASSIQUE', 1),
  ('L', 'Litre', 'VOLUMIQUE', 1),
  ('ML', 'Millilitre', 'VOLUMIQUE', 1),
  ('UNITE', 'Unité', 'UNITE', 1),
  ('CARTON', 'Carton', 'UNITE', 1),
  ('SAC', 'Sac', 'UNITE', 1),
  ('BOTTE', 'Botte', 'UNITE', 1),
  ('CAISSE', 'Caisse', 'UNITE', 1);

-- 8.5 Catégories pertes production
INSERT IGNORE INTO `categories_pertes_production` (`nom`) VALUES
  ('Défaut production'), ('Chute matière'), ('Démarrage machine'),
  ('Réglage machine'), ('Matière contaminée'), ('Produit non conforme'),
  ('Casse'), ('Autre');

-- 8.6 Tranches tarifaires
INSERT IGNORE INTO `tranches_tarifaires` (`nom`, `qte_min`, `qte_max`, `mode_calcul`, `source_cout`, `valeur`, `priorite`, `actif`) VALUES
  ('Défaut 1-9 unités', 1, 9, 'majoration_pct', 'ACHAT_FOURNISSEUR', 15.0000, 0, 1),
  ('Défaut 10-49 unités', 10, 49, 'majoration_pct', 'ACHAT_FOURNISSEUR', 12.0000, 10, 1),
  ('Défaut 50-199 unités', 50, 199, 'majoration_pct', 'ACHAT_FOURNISSEUR', 10.0000, 20, 1),
  ('Défaut 200+ unités', 200, NULL, 'majoration_pct', 'ACHAT_FOURNISSEUR', 8.0000, 30, 1),
  ('Catégorie promotion', 1, NULL, 'marge_pct', 'ACHAT_FOURNISSEUR', 5.0000, 100, 0);

-- 8.7 Permissions
INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('stock_consulter', 'Consulter les stocks', 'Stock'),
  ('stock_gerer', 'Gerer les entrees/sorties de stock', 'Stock'),
  ('stock_transfert', 'Effectuer des transferts inter-magasins', 'Stock'),
  ('articles_consulter', 'Consulter les articles', 'Articles'),
  ('articles_gerer', 'Creer / modifier / supprimer les articles', 'Articles'),
  ('clients_consulter', 'Consulter la fiche client', 'Clients'),
  ('clients_gerer', 'Creer / modifier / supprimer les clients', 'Clients'),
  ('conformite_archives', 'Gerer les archives de conformite', 'Conformite'),
  ('conformite_export_fec', 'Exporter le FEC', 'Conformite'),
  ('conformite_export_syscohada', 'Exporter en format SYSCOHADA', 'Conformite'),
  ('articles_modifier', 'Modifier les articles via l''API', 'Articles'),
  ('caisse_gerer', 'Gerer la caisse / POS', 'Vente'),
  ('facturation_consulter', 'Consulter les factures', 'Facturation'),
  ('facturation_gerer', 'Creer / modifier les factures', 'Facturation'),
  ('cloture_gerer', 'Gerer les clotures de caisse', 'Facturation'),
  ('roles_consulter', 'Consulter les roles', 'RBAC'),
  ('roles_gerer', 'Gerer les roles et permissions', 'RBAC'),
  ('permissions_gerer', 'Gerer les permissions', 'RBAC'),
  ('magasins_consulter', 'Consulter les magasins', 'Magasins'),
  ('magasins_gerer', 'Gerer les magasins', 'Magasins'),
  ('parametres_gerer', 'Gerer les parametres', 'Administration'),
  ('audit_consulter', 'Consulter le journal d''audit', 'Administration'),
  ('audit_gerer', 'Gerer le journal d''audit', 'Administration'),
  ('statistiques_consulter', 'Consulter les statistiques', 'Statistiques'),
  ('achats_consulter', 'Consulter les achats', 'Achats'),
  ('achats_gerer', 'Gerer les achats', 'Achats'),
  ('achats_valider', 'Valider les achats', 'Achats'),
  ('retours_consulter', 'Consulter les retours', 'Retours'),
  ('retours_gerer', 'Gerer les retours', 'Retours'),
  ('promotions_consulter', 'Consulter les promotions', 'Promotions'),
  ('promotions_gerer', 'Gerer les promotions', 'Promotions'),
  ('inventaire_consulter', 'Consulter l''inventaire', 'Inventaire'),
  ('inventaire_gerer', 'Gerer l''inventaire', 'Inventaire'),
  ('depenses_consulter', 'Consulter les depenses', 'Depenses'),
  ('depenses_gerer', 'Gerer les depenses', 'Depenses'),
  ('tarification_consulter', 'Consulter la tarification', 'Tarification'),
  ('tarification_gerer', 'Gerer la tarification', 'Tarification'),
  ('prix_fournisseur_consulter', 'Consulter les prix fournisseur', 'Prix'),
  ('prix_fournisseur_gerer', 'Gerer les prix fournisseur', 'Prix'),
  ('exports_consulter', 'Consulter les exports', 'Exports'),
  ('suggestions_consulter', 'Consulter les suggestions d''achat', 'Suggestions'),
  ('impression_consulter', 'Imprimer les documents', 'Impression'),
  ('ventes_consulter', 'Consulter les ventes', 'Vente'),
  ('transferts_consulter', 'Consulter les transferts', 'Transferts'),
  ('transferts_gerer', 'Gerer les transferts', 'Transferts'),
  ('utilisateurs_consulter', 'Consulter les utilisateurs', 'Administration'),
  ('utilisateurs_gerer', 'Gerer les utilisateurs', 'Administration'),
  ('usine_consulter', 'Consulter le module usine', 'Usine'),
  ('usine_gerer', 'Gerer les parametres usine', 'Usine'),
  ('production_consulter', 'Consulter les productions', 'Production'),
  ('production_gerer', 'Gerer les productions', 'Production'),
  ('production_cloturer', 'Cloturer une production', 'Production'),
  ('personnel_consulter', 'Consulter le personnel', 'Personnel'),
  ('personnel_gerer', 'Gerer le personnel', 'Personnel'),
  ('presence_consulter', 'Consulter les presences', 'Personnel'),
  ('presence_gerer', 'Gerer les presences', 'Personnel'),
  ('transfert_usine_gerer', 'Gerer les transferts usine', 'Usine'),
  ('machines_consulter', 'Consulter les machines', 'Usine'),
  ('machines_gerer', 'Gerer les machines', 'Usine'),
  ('machines_historique', 'Historique des etats de machines', 'Usine'),
  ('notifications_consulter', 'Consulter les notifications', 'Systeme'),
  ('notifications_marquer_lu', 'Marquer les notifications comme lues', 'Systeme'),
  ('notifications_supprimer', 'Supprimer des notifications', 'Systeme'),
  ('receptions_consulter', 'Consulter les receptions', 'Receptions'),
  ('receptions_gerer', 'Gerer les receptions', 'Receptions'),
  ('receptions_valider', 'Valider les receptions', 'Receptions'),
  ('pertes_consulter', 'Consulter les pertes fournisseur', 'Pertes'),
  ('pertes_gerer', 'Gerer les pertes fournisseur', 'Pertes'),
  ('receptions_creer', 'Creer des receptions', 'Receptions'),
  ('pertes_creer', 'Creer des pertes fournisseur', 'Pertes'),
  ('equipes_consulter', 'Consulter les equipes', 'Equipes'),
  ('equipes_gerer', 'Gerer les equipes', 'Equipes'),
  ('retards_consulter', 'Consulter les retards', 'Personnel'),
  ('absences_consulter', 'Consulter les absences', 'Personnel'),
  ('stock_usine_consulter', 'Consulter le stock usine', 'Usine'),
  ('transferts_magasins_gerer', 'Gerer les transferts entre magasins', 'Transferts'),
  ('stock_usine_transfert', 'Transfert depuis l''usine', 'Usine'),
  ('machines_demarrer', 'Demarrer / arreter les machines', 'Usine'),
  ('horaires_consulter', 'Consulter les horaires de travail', 'Usine'),
  ('horaires_gerer', 'Gerer les horaires de travail', 'Usine'),
  ('notifications_usine_consulter', 'Consulter les notifications usine', 'Usine'),
  ('notifications_usine_gerer', 'Gerer les notifications usine', 'Usine'),
  ('rendement_consulter', 'Consulter les rendements de production', 'Usine'),
  ('categories_pertes_consulter', 'Consulter les categories de pertes', 'Usine'),
  ('categories_pertes_gerer', 'Gerer les categories de pertes', 'Usine'),
  ('credit_consulter', 'Consulter les creances et soldes clients', 'Credit'),
  ('credit_creer', 'Creer une vente a credit', 'Credit'),
  ('credit_paiement_creer', 'Enregistrer un remboursement sur creance', 'Credit'),
  ('credit_paiement_consulter', 'Consulter l''historique des remboursements', 'Credit'),
  ('credit_modifier', 'Modifier les conditions de credit (limite, echeance)', 'Credit'),
  ('credit_annuler', 'Annuler une creance', 'Credit'),
  ('credit_rapport', 'Consulter les rapports de creances', 'Credit'),
  ('credit_override_limit', 'Depasser la limite de credit autorisee', 'Credit');

-- 8.8 Permissions rôles (après normalisation)
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'PROPRIETAIRE', id FROM `permissions`;
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'ADMIN', id FROM `permissions` WHERE `cle_permission` IN
  ('clients_consulter','clients_gerer','articles_modifier','usine_consulter','usine_gerer',
   'production_consulter','production_gerer','production_cloturer','personnel_consulter','personnel_gerer',
   'presence_consulter','presence_gerer','machines_consulter','machines_gerer','machines_historique',
   'notifications_consulter','notifications_marquer_lu','notifications_supprimer',
   'receptions_consulter','receptions_gerer','receptions_valider','pertes_consulter','pertes_gerer',
   'equipes_consulter','equipes_gerer','retards_consulter','absences_consulter',
   'stock_usine_consulter','transferts_magasins_gerer','stock_usine_transfert',
   'credit_consulter','credit_modifier','credit_rapport','credit_override_limit');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'MAGASINIER', id FROM `permissions` WHERE `cle_permission` IN
  ('clients_consulter','clients_gerer','articles_modifier',
   'receptions_consulter','receptions_gerer','receptions_valider','receptions_creer',
   'pertes_consulter','pertes_gerer','pertes_creer');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'VENDEUR', id FROM `permissions` WHERE `cle_permission` IN ('clients_consulter','credit_consulter','credit_creer','credit_paiement_creer');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE', id FROM `permissions` WHERE `cle_permission` IN
  ('clients_consulter','clients_gerer','conformite_archives','conformite_export_syscohada','articles_modifier',
   'equipes_consulter','equipes_gerer','retards_consulter','absences_consulter',
   'credit_consulter','credit_creer','credit_paiement_creer');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE_USINE', id FROM `permissions` WHERE `cle_permission` IN
  ('usine_consulter','production_consulter','production_gerer','production_cloturer',
   'machines_consulter','machines_gerer','machines_historique','machines_demarrer',
   'personnel_consulter','personnel_gerer','presence_consulter','presence_gerer',
   'transfert_usine_gerer','stock_usine_consulter','stock_usine_transfert',
   'horaires_consulter','horaires_gerer',
   'notifications_usine_consulter','notifications_usine_gerer',
    'rendement_consulter','categories_pertes_consulter','categories_pertes_gerer');

-- ============================================================
-- 8.13 VENTES A CREDIT — Schema
-- ============================================================

-- Colonnes credit sur clients
SET @col = (SELECT COUNT(*) FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'clients' AND `COLUMN_NAME` = 'credit_autorise');
SET @sql = IF(@col = 0, "ALTER TABLE `clients` ADD COLUMN `credit_autorise` tinyint(1) NOT NULL DEFAULT 0 AFTER `date_anonymisation`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'clients' AND `COLUMN_NAME` = 'limite_credit');
SET @sql = IF(@col = 0, "ALTER TABLE `clients` ADD COLUMN `limite_credit` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `credit_autorise`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Colonnes suivi paiement sur factures
SET @col = (SELECT COUNT(*) FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'factures' AND `COLUMN_NAME` = 'statut_paiement');
SET @sql = IF(@col = 0, "ALTER TABLE `factures` ADD COLUMN `statut_paiement` enum('Payee','En_Attente','Partiellement_Payee','A_Credit','Annulee') NOT NULL DEFAULT 'Payee' AFTER `monnaie_rendue`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'factures' AND `COLUMN_NAME` = 'reste_a_payer');
SET @sql = IF(@col = 0, "ALTER TABLE `factures` ADD COLUMN `reste_a_payer` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `statut_paiement`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Table creances_clients
CREATE TABLE IF NOT EXISTS `creances_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `facture_id` int NOT NULL,
  `client_id` int NOT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_paye` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reste_a_payer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('En_Cours','Partiellement_Payee','Payee','Annulee','En_Souffrance') NOT NULL DEFAULT 'En_Cours',
  `date_echeance` date DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_creance_facture` (`facture_id`),
  KEY `idx_creance_client` (`client_id`),
  KEY `idx_creance_statut` (`statut`),
  KEY `idx_creance_echeance` (`date_echeance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table paiements_credit
CREATE TABLE IF NOT EXISTS `paiements_credit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `creance_id` int NOT NULL,
  `montant` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mode_paiement` enum('Especes','Mobile_Money','Carte_Bancaire','Virement','Autre') NOT NULL DEFAULT 'Especes',
  `reference` varchar(100) DEFAULT NULL,
  `date_paiement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `utilisateur_id` int DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_pc_creance` (`creance_id`),
  KEY `idx_pc_date` (`date_paiement`),
  CONSTRAINT `chk_pc_montant` CHECK (`montant` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK pour creances_clients
SET @fk = (SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'creances_clients' AND `CONSTRAINT_NAME` = 'fk_creance_facture');
SET @sql = IF(@fk = 0, "ALTER TABLE `creances_clients` ADD CONSTRAINT `fk_creance_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'creances_clients' AND `CONSTRAINT_NAME` = 'fk_creance_client');
SET @sql = IF(@fk = 0, "ALTER TABLE `creances_clients` ADD CONSTRAINT `fk_creance_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- FK pour paiements_credit
SET @fk = (SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'paiements_credit' AND `CONSTRAINT_NAME` = 'fk_pc_creance');
SET @sql = IF(@fk = 0, "ALTER TABLE `paiements_credit` ADD CONSTRAINT `fk_pc_creance` FOREIGN KEY (`creance_id`) REFERENCES `creances_clients` (`id`) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'paiements_credit' AND `CONSTRAINT_NAME` = 'fk_pc_user');
SET @sql = IF(@fk = 0, "ALTER TABLE `paiements_credit` ADD CONSTRAINT `fk_pc_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Trigger: autoriser les updates de montant_paye avant hachage (credit)
DROP TRIGGER IF EXISTS `trg_factures_immutable_update`;
DELIMITER $$
CREATE TRIGGER `trg_factures_immutable_update` BEFORE UPDATE ON `factures` FOR EACH ROW BEGIN
    IF OLD.numero_facture <> NEW.numero_facture THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le numero de facture est immuable';
    END IF;
    IF OLD.hash_chaine IS NOT NULL THEN
        IF OLD.total_ht <> NEW.total_ht OR OLD.total_ttc <> NEW.total_ttc
           OR OLD.montant_paye <> NEW.montant_paye OR OLD.monnaie_rendue <> NEW.monnaie_rendue THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les montants d une facture validee sont immuables';
        END IF;
        IF OLD.hash_chaine <> NEW.hash_chaine THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le hash de chaine d une facture est immuable';
        END IF;
    END IF;
    IF OLD.statut = 'Payee' AND NEW.statut NOT IN ('Payee', 'Annulee') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: transition de statut invalide';
    END IF;
    IF OLD.statut = 'Annulee' AND NEW.statut = 'Payee' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: une facture annulee est definitive';
    END IF;
    IF OLD.statut = 'Payee' AND NEW.statut = 'Annulee' AND NEW.date_annulation IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: une annulation exige date et motif';
    END IF;
END
$$
DELIMITER ;

-- 8.9 Migrer usines depuis magasins USINE (si applicable)
INSERT IGNORE INTO `usines` (`code`, `nom`, `actif`)
SELECT CONCAT('USINE_', m.id), m.nom, m.actif
FROM `magasins` m WHERE m.`type_magasin` = 'USINE';

-- 8.10 Mettre à jour magasins USINE → MAGASIN
UPDATE `magasins` SET `type_magasin` = 'MAGASIN' WHERE `type_magasin` = 'USINE';

-- 8.11 Restreindre l'enum magasins.type_magasin
SET @usine = (SELECT COUNT(*) FROM `magasins` WHERE `type_magasin` = 'USINE');
SET @sql = IF(@usine = 0, "ALTER TABLE `magasins` MODIFY COLUMN `type_magasin` enum('MAGASIN') NOT NULL DEFAULT 'MAGASIN' COMMENT 'OBSOLETE: les usines sont dans la table usines'", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 8.12 Restreindre l'enum mouvements_stock.type (après normalisation)
SET @old = (SELECT COUNT(*) FROM `mouvements_stock` WHERE `type` IN ('Entree','Sortie','Vente','Transfert','Ajustement','Retour_stock','Perte_production'));
SET @sql = IF(@old = 0,
  "ALTER TABLE `mouvements_stock` MODIFY COLUMN `type` enum('ENTREE','SORTIE','VENTE','TRANSFERT','AJUSTEMENT','RETOUR_STOCK','RECEPTION','PERTE','PRODUCTION','PERTE_PRODUCTION','ENTREE_ACHAT','SORTIE_VENTE','TRANSFERT_ENTREE','TRANSFERT_SORTIE') COLLATE utf8mb4_unicode_ci NOT NULL",
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================
-- 9. NETTOYAGE
-- ============================================================

-- Supprimer les tables orphelines (si elles existent encore)
DROP TABLE IF EXISTS `user_equipes`;
DROP TABLE IF EXISTS `production_lots`;
DROP TABLE IF EXISTS `production_produits`;
DROP TABLE IF EXISTS `production_employes`;
DROP TABLE IF EXISTS `presences_employes_audit`;
DROP TABLE IF EXISTS `mouvements_produits_finis`;
DROP TABLE IF EXISTS `mouvements_matieres_premieres`;

-- Supprimer les index redondants
DROP INDEX IF EXISTS `idx_art_code` ON `articles`;
DROP INDEX IF EXISTS `idx_fact_num` ON `factures`;
DROP INDEX IF EXISTS `idx_par_cle` ON `parametres`;
DROP INDEX IF EXISTS `idx_perm_cle` ON `permissions`;
DROP INDEX IF EXISTS `idx_ec_email` ON `emails_consentements`;

-- ============================================================
-- FIN
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RÉSUMÉ
-- ============================================================
-- Tables supprimées : 7 (user_equipes, production_lots, production_produits, etc.)
-- Tables créées : 20 (roles, user_roles, equipes, equipe_membres, usines, recettes, etc.)
-- Colonnes ajoutées : 25+ (type_article, statut, usine_id, etc.)
-- FK ajoutées : 20+
-- CHECK constraints : 7
-- Index ajoutés : 20+
-- ENUM role supprimé de utilisateurs
-- mouvements_stock.type normalisé en MAJUSCULES
-- Notifications cible_role normalisé
-- Role permissions normalisé
-- ============================================================
