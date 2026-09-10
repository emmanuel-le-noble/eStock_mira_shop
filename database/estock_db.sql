-- ============================================================
-- ESTOCK_DB — STRUCTURE COMPLÈTE AVEC TOUTES LES MIGRATIONS
-- Date : 10 septembre 2026
-- Base de données : estock_db
-- MySQL : 8.0.31+
-- Charset : utf8mb4 / utf8mb4_unicode_ci
-- ============================================================
-- Ce fichier contient la structure finale de la base de données
-- après application de toutes les migrations.
-- Il remplace les 13 fichiers de migration individuels.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- 1. TABLES DE BASE (core ERP)
-- ============================================================

DROP TABLE IF EXISTS `archives_caisse`;
CREATE TABLE IF NOT EXISTS `archives_caisse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `magasin_id` int DEFAULT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `nb_factures` int NOT NULL DEFAULT '0',
  `nb_paiements` int NOT NULL DEFAULT '0',
  `total_ht` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_tva` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_ttc` decimal(14,2) NOT NULL DEFAULT '0.00',
  `hash_sommet` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fichier_archive` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_archivage` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `conserve_jusqua` date NOT NULL,
  `statut` enum('CONSERVE','EXPURGE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CONSERVE',
  PRIMARY KEY (`id`),
  KEY `idx_arch_magasin` (`magasin_id`),
  KEY `idx_arch_periode` (`periode_debut`,`periode_fin`),
  KEY `idx_arch_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raison_sociale` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nif` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rccm` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `code_fidelite` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points_fidelite` int NOT NULL DEFAULT '0',
  `consentement_fidelite` tinyint(1) NOT NULL DEFAULT '0',
  `date_consentement` datetime DEFAULT NULL,
  `source_consentement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_dernier_achat` datetime DEFAULT NULL,
  `anonymise` tinyint(1) NOT NULL DEFAULT '0',
  `date_anonymisation` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_code_fidelite` (`code_fidelite`),
  KEY `idx_client_email` (`email`),
  KEY `idx_client_nom` (`nom`),
  KEY `idx_client_anonymise` (`anonymise`),
  KEY `idx_client_dernier_achat` (`date_dernier_achat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `consentements_log`;
CREATE TABLE IF NOT EXISTS `consentements_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `type_consentement` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consenti` tinyint(1) NOT NULL DEFAULT '1',
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_source` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cons_client` (`client_id`),
  KEY `idx_cons_date` (`date_action`),
  KEY `idx_cl_client_type` (`client_id`,`type_consentement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `depenses`;
CREATE TABLE IF NOT EXISTS `depenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `magasin_id` int DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Autre',
  `montant` decimal(12,2) NOT NULL DEFAULT '0.00',
  `date_depense` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dep_magasin` (`magasin_id`),
  KEY `idx_dep_categorie` (`categorie`),
  KEY `idx_dep_date` (`date_depense`),
  KEY `fk_dep_user` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `emails_consentements`;
CREATE TABLE IF NOT EXISTS `emails_consentements` (
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opt_in_marketing` tinyint(1) NOT NULL DEFAULT '0',
  `date_consentement` datetime DEFAULT NULL,
  `source_consentement` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `opposition_globale` tinyint(1) NOT NULL DEFAULT '0',
  `date_opposition` datetime DEFAULT NULL,
  `token_desinscription` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`),
  UNIQUE KEY `uq_ec_token` (`token_desinscription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `emails_queue`;
CREATE TABLE IF NOT EXISTS `emails_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destinataire` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('transactionnel','alerte','marketing') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'alerte',
  `sujet` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corps_html` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('EN_ATTENTE','ENVOYE','ECHEC') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `nb_tentatives` tinyint NOT NULL DEFAULT '0',
  `prochaine_tentative` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `erreur` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_envoi` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eq_statut` (`statut`,`prochaine_tentative`),
  KEY `idx_eq_date` (`date_creation`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `devise` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  PRIMARY KEY (`id`),
  KEY `idx_fournisseur_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `historique_points`;
CREATE TABLE IF NOT EXISTS `historique_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `facture_id` int DEFAULT NULL,
  `points` int NOT NULL,
  `type_operation` enum('GAIN','UTILISATION','EXPIRATION','AJUSTEMENT','ANNULE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentaire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_operation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hp_client_facture_type` (`client_id`,`facture_id`,`type_operation`),
  KEY `idx_hp_client` (`client_id`),
  KEY `idx_hp_date` (`date_operation`),
  KEY `idx_hp_type_operation` (`type_operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_ip` (`ip_address`),
  KEY `idx_la_date` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `logs_activite`;
CREATE TABLE IF NOT EXISTS `logs_activite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_user` (`utilisateur_id`),
  KEY `idx_la_action` (`action`),
  KEY `idx_la_date` (`date_action`)
) ENGINE=InnoDB AUTO_INCREMENT=318 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE IF NOT EXISTS `parametres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cle` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categorie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `ordre` int NOT NULL DEFAULT '0',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_modif` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`),
  KEY `idx_par_cat` (`categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `sequences`;
CREATE TABLE IF NOT EXISTS `sequences` (
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` bigint NOT NULL DEFAULT '0',
  `date_maj` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABLES MAGASINS & STOCK
-- ============================================================

DROP TABLE IF EXISTS `magasins`;
CREATE TABLE IF NOT EXISTS `magasins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_magasin` enum('MAGASIN','USINE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MAGASIN' COMMENT 'OBSOLETE: les usines sont dans la table usines',
  `equipe_id` int DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nif` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rccm` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mag_type` (`type_magasin`),
  KEY `idx_magasin_equipe` (`equipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `usines`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_magasins`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code_barre` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix_achat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cump` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `cout_production_ref` decimal(14,4) DEFAULT NULL,
  `prix_vente` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unite_mesure` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNITE',
  `vente_au_poids` tinyint(1) NOT NULL DEFAULT '0',
  `poids_precision` tinyint NOT NULL DEFAULT '3',
  `taux_tva` decimal(5,2) DEFAULT NULL,
  `quantite_stock` int NOT NULL DEFAULT '0',
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT '0.00',
  `seuil_alerte` int NOT NULL DEFAULT '5',
  `emplacement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `fournisseur_prix_ref_id` int unsigned DEFAULT NULL,
  `categorie_id` int DEFAULT NULL,
  `type_article` enum('MATIERE_PREMIERE','PRODUIT_FINI','ARTICLE_COMMERCIAL','CONSOMMABLE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ARTICLE_COMMERCIAL',
  `origine_article` enum('ACHAT_FOURNISSEUR','PRODUCTION_USINE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACHAT_FOURNISSEUR',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_barre` (`code_barre`),
  KEY `idx_art_nom` (`nom`),
  KEY `fk_art_fournisseur` (`fournisseur_id`),
  KEY `idx_art_low_stock` (`actif`,`quantite_stock`,`seuil_alerte`),
  KEY `idx_art_categorie` (`categorie_id`),
  KEY `idx_art_type` (`type_article`),
  KEY `idx_art_origine` (`origine_article`),
  KEY `idx_art_fournisseur_prix_ref` (`fournisseur_prix_ref_id`),
  CONSTRAINT `chk_articles_prix_vente` CHECK (`prix_vente` >= 0),
  CONSTRAINT `chk_articles_prix_achat` CHECK (`prix_achat` >= 0),
  CONSTRAINT `chk_articles_stock` CHECK (`quantite_stock` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `article_couts`;
CREATE TABLE IF NOT EXISTS `article_couts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `magasin_id` int NOT NULL DEFAULT '1',
  `quantite` int NOT NULL DEFAULT '0',
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `date_entree` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_acc_article` (`article_id`,`magasin_id`),
  KEY `idx_acc_date` (`date_entree`),
  KEY `idx_ac_fifo` (`article_id`,`quantite`,`date_entree`),
  CONSTRAINT `fk_aco_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aco_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `article_lots`;
CREATE TABLE IF NOT EXISTS `article_lots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `numero_lot` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL DEFAULT '0',
  `date_peremption` date DEFAULT NULL,
  `date_reception` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cree_le` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lot_article_magasin` (`article_id`,`magasin_id`,`numero_lot`),
  KEY `idx_lot_fefo` (`article_id`,`magasin_id`,`date_peremption`,`quantite`),
  KEY `idx_lot_magasin` (`magasin_id`,`date_peremption`),
  KEY `idx_lot_article` (`article_id`,`magasin_id`),
  KEY `idx_lot_alerte` (`date_peremption`,`quantite`,`magasin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_magasins`;
CREATE TABLE IF NOT EXISTS `stock_magasins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `magasin_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '0',
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT '0.00',
  `stock_alerte` int NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_mag_art` (`magasin_id`,`article_id`),
  KEY `idx_sm_magasin` (`magasin_id`),
  KEY `idx_sm_article` (`article_id`),
  KEY `idx_stock_mag_art` (`article_id`),
  KEY `idx_stock_mag_mag` (`magasin_id`),
  CONSTRAINT `chk_sm_quantite` CHECK (`quantite` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `mouvements_stock`;
CREATE TABLE IF NOT EXISTS `mouvements_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `magasin_id` int DEFAULT NULL,
  `type` enum('ENTREE','SORTIE','VENTE','TRANSFERT','AJUSTEMENT','RETOUR_STOCK','RECEPTION','PERTE','PRODUCTION','PERTE_PRODUCTION','ENTREE_ACHAT','SORTIE_VENTE','TRANSFERT_ENTREE','TRANSFERT_SORTIE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL DEFAULT '0',
  `stock_avant` int DEFAULT NULL,
  `stock_apres` int DEFAULT NULL,
  `cout_unitaire` decimal(14,4) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `lot_id` int DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mvt_art` (`article_id`),
  KEY `idx_mvt_type` (`type`),
  KEY `idx_mvt_date` (`date_mouvement`),
  KEY `fk_mvt_user` (`utilisateur_id`),
  KEY `idx_mvt_magasin` (`magasin_id`),
  KEY `idx_mvt_art_mag_date` (`article_id`,`magasin_id`,`date_mouvement`),
  KEY `idx_mvt_ref` (`reference_type`,`reference_id`),
  KEY `idx_ms_type_magasin` (`type`,`magasin_id`),
  CONSTRAINT `chk_mvt_quantite` CHECK (`quantite` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `transferts_stock`;
CREATE TABLE IF NOT EXISTS `transferts_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `magasin_source_id` int NOT NULL,
  `magasin_destination_id` int NOT NULL,
  `quantite` int NOT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_transfert` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tr_date` (`date_transfert`),
  KEY `idx_tr_art` (`article_id`),
  KEY `idx_tr_src` (`magasin_source_id`),
  KEY `idx_tr_dst` (`magasin_destination_id`),
  KEY `fk_tr_user` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABLES FACTURATION & PAIEMENTS
-- ============================================================

DROP TABLE IF EXISTS `factures`;
CREATE TABLE IF NOT EXISTS `factures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_facture` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `utilisateur_id` int DEFAULT NULL,
  `magasin_id` int DEFAULT NULL,
  `cloture_id` int DEFAULT NULL,
  `client_sale_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ht` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tva_taux` decimal(5,2) NOT NULL DEFAULT '0.00',
  `remise_fidelite` decimal(12,2) NOT NULL DEFAULT '0.00',
  `points_utilises` int NOT NULL DEFAULT '0',
  `total_ttc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `montant_paye` decimal(12,2) NOT NULL DEFAULT '0.00',
  `monnaie_rendue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `statut` enum('Payee','Annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Payee',
  `statut_transmission` enum('non_transmise','transmise','non_applicable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'non_transmise',
  `hash_chaine` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_chaine_precedent` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horodatage_certifie` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_annulation` datetime DEFAULT NULL,
  `motif_annulation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annulee_par` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `client_raison_sociale` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_siret` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_nom` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  UNIQUE KEY `idx_fact_client_sale` (`client_sale_id`),
  KEY `idx_fact_date` (`date_facture`),
  KEY `fk_fact_user` (`utilisateur_id`),
  KEY `idx_fact_date_statut` (`date_facture`,`statut`),
  KEY `idx_fact_magasin` (`magasin_id`),
  KEY `idx_fact_cloture` (`cloture_id`),
  KEY `idx_fact_client` (`client_id`),
  CONSTRAINT `chk_fact_total` CHECK (`total_ttc` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `lignes_facture`;
CREATE TABLE IF NOT EXISTS `lignes_facture` (
  `id` int NOT NULL AUTO_INCREMENT,
  `facture_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `quantite_poids` decimal(10,3) DEFAULT NULL,
  `prix_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_fournisseur_ref` decimal(12,2) DEFAULT NULL,
  `fournisseur_id_ref` int unsigned DEFAULT NULL,
  `tranche_tarifaire_id` int unsigned DEFAULT NULL,
  `prix_original` decimal(12,2) DEFAULT NULL,
  `remise_pct` decimal(5,2) DEFAULT NULL,
  `taux_tva` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lf_facture` (`facture_id`),
  KEY `fk_lf_article` (`article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `paiements_facture`;
CREATE TABLE IF NOT EXISTS `paiements_facture` (
  `id` int NOT NULL AUTO_INCREMENT,
  `facture_id` int NOT NULL,
  `mode_paiement` enum('Especes','Mobile_Money','Carte_Bancaire','Virement','Autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Especes',
  `montant` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_paiement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pf_facture` (`facture_id`),
  KEY `idx_pf_mode` (`mode_paiement`),
  CONSTRAINT `chk_paiement_montant` CHECK (`montant` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `clotures_caisse`;
CREATE TABLE IF NOT EXISTS `clotures_caisse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `date_cloture` date NOT NULL,
  `montant_attendu` decimal(12,2) NOT NULL DEFAULT '0.00',
  `montant_reel` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ecart` decimal(12,2) NOT NULL DEFAULT '0.00',
  `statut` enum('VALIDE','ANNULEE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VALIDE',
  `hash_chaine` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_chaine_precedent` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `empreinte_journal` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horodatage_certifie` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cloture_jour` (`magasin_id`,`utilisateur_id`,`date_cloture`),
  KEY `fk_cloture_user` (`utilisateur_id`),
  KEY `idx_cloture_date` (`date_cloture`),
  KEY `idx_cloture_magasin` (`magasin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `retours_factures`;
CREATE TABLE IF NOT EXISTS `retours_factures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_retour` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facture_id` int NOT NULL,
  `magasin_id` int NOT NULL DEFAULT '1',
  `utilisateur_id` int DEFAULT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_retour` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('Valide','Annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Valide',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ret_num` (`numero_retour`),
  KEY `fk_ret_user` (`utilisateur_id`),
  KEY `idx_ret_facture` (`facture_id`),
  KEY `idx_ret_magasin` (`magasin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `lignes_retour`;
CREATE TABLE IF NOT EXISTS `lignes_retour` (
  `id` int NOT NULL AUTO_INCREMENT,
  `retour_id` int NOT NULL,
  `ligne_facture_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_lret_lfact` (`ligne_facture_id`),
  KEY `fk_lret_article` (`article_id`),
  KEY `idx_lret_retour` (`retour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABLES COMMANDES & RECEPTIONS
-- ============================================================

DROP TABLE IF EXISTS `commandes_fournisseur`;
CREATE TABLE IF NOT EXISTS `commandes_fournisseur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fournisseur_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `statut` enum('Brouillon','En_Attente','Envoyee','Recue_Partielle','Recue','Annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Brouillon',
  `date_commande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `devise` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `taux_change` decimal(12,6) NOT NULL DEFAULT '1.000000',
  `date_reception_prevue` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cf_fournisseur` (`fournisseur_id`),
  KEY `idx_cf_magasin` (`magasin_id`),
  KEY `idx_cf_user` (`utilisateur_id`),
  KEY `idx_cf_statut` (`statut`),
  KEY `idx_cf_date_commande` (`date_commande`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `lignes_commande_fournisseur`;
CREATE TABLE IF NOT EXISTS `lignes_commande_fournisseur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite_commandee` int NOT NULL DEFAULT '1',
  `quantite_recue` int NOT NULL DEFAULT '0',
  `quantite_receptionnee` int unsigned NOT NULL DEFAULT '0',
  `quantite_perdue` int unsigned NOT NULL DEFAULT '0',
  `prix_achat_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_lcf_commande` (`commande_id`),
  KEY `idx_lcf_article` (`article_id`),
  CONSTRAINT `fk_lcf_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `receptions`;
CREATE TABLE IF NOT EXISTS `receptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) NOT NULL,
  `commande_id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int unsigned DEFAULT NULL,
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `reception_lignes`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `pertes_fournisseur`;
CREATE TABLE IF NOT EXISTS `pertes_fournisseur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reception_id` int unsigned NULL,
  `reception_ligne_id` int unsigned NULL,
  `commande_id` int NULL,
  `article_id` int NOT NULL,
  `fournisseur_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  `utilisateur_id` int unsigned NULL,
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
-- 5. TABLES PROMOTIONS & TARIFS
-- ============================================================

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_promo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_reduction` enum('pourcentage','montant_fixe') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pourcentage',
  `valeur` decimal(10,2) NOT NULL DEFAULT '0.00',
  `article_id` int DEFAULT NULL,
  `categorie_id` int DEFAULT NULL,
  `montant_min_achat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `limite_utilisations` int DEFAULT NULL,
  `nb_utilisations` int NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_promo` (`code_promo`),
  KEY `fk_promo_article` (`article_id`),
  KEY `fk_promo_categorie` (`categorie_id`),
  KEY `idx_promo_code` (`code_promo`),
  KEY `idx_promo_dates` (`date_debut`,`date_fin`),
  KEY `idx_promo_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `regles_promotions`;
CREATE TABLE IF NOT EXISTS `regles_promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition_type` enum('PEREMPTION_PROCHE','SURSTOCK') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jours_limite` int DEFAULT NULL,
  `seuil_stock` int DEFAULT NULL,
  `pourcentage_remise` decimal(5,2) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `cree_le` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_promo_condition` (`condition_type`,`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `fournisseur_prix_historique`;
CREATE TABLE IF NOT EXISTS `fournisseur_prix_historique` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `fournisseur_id` int NOT NULL,
  `prix_achat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `source` enum('commande','reception','manuelle') NOT NULL DEFAULT 'manuelle',
  `reference_id` int unsigned NULL,
  `utilisateur_id` int unsigned NULL,
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `tranches_tarifaires`;
CREATE TABLE IF NOT EXISTS `tranches_tarifaires` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `article_id` int unsigned NULL,
  `categorie_id` int unsigned NULL,
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

-- ============================================================
-- 6. TABLES INVENTAIRE
-- ============================================================

DROP TABLE IF EXISTS `inventaires`;
CREATE TABLE IF NOT EXISTS `inventaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `magasin_id` int NOT NULL DEFAULT '1',
  `statut` enum('En cours','Validé','Annulé') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'En cours',
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inv_ref` (`reference`),
  KEY `fk_inv_user` (`utilisateur_id`),
  KEY `idx_inv_magasin` (`magasin_id`),
  KEY `idx_inv_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `inventaire_lignes`;
CREATE TABLE IF NOT EXISTS `inventaire_lignes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventaire_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite_theorique` int NOT NULL DEFAULT '0',
  `quantite_comptee` int NOT NULL DEFAULT '0',
  `ecart` int NOT NULL DEFAULT '0',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invl_art` (`inventaire_id`,`article_id`),
  KEY `fk_invl_article` (`article_id`),
  KEY `idx_invl_inventaire` (`inventaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABLES RBAC (rôles, permissions, équipes)
-- ============================================================

DROP TABLE IF EXISTS `roles`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cle_permission` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle_permission` (`cle_permission`),
  KEY `idx_perm_cat` (`categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_nom`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_role_code` FOREIGN KEY (`role_nom`) REFERENCES `roles` (`code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `equipes`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `equipe_membres`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `equipe_magasins`;
CREATE TABLE IF NOT EXISTS `equipe_magasins` (
  `equipe_id` int NOT NULL,
  `magasin_id` int NOT NULL,
  PRIMARY KEY (`equipe_id`, `magasin_id`),
  KEY `idx_em_magasin` (`magasin_id`),
  CONSTRAINT `fk_em_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_em_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `unites_mesure`;
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

-- ============================================================
-- 8. UTILISATEURS
-- ============================================================

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL DEFAULT '4',
  `totp_secret` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `totp_actif` tinyint(1) NOT NULL DEFAULT '0',
  `google_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `magasin_id` int DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `idx_user_magasin` (`magasin_id`),
  KEY `idx_user_google_id` (`google_id`),
  UNIQUE KEY `idx_user_google_email` (`google_email`),
  KEY `idx_user_role_id` (`role_id`),
  CONSTRAINT `fk_user_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_user_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_roles`;
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

-- ============================================================
-- 9. TABLES USINE / PRODUCTION
-- ============================================================

DROP TABLE IF EXISTS `categories_matieres_premieres`;
CREATE TABLE IF NOT EXISTS `categories_matieres_premieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `matieres_premieres`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_matieres_premieres`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `categories_pertes_production`;
CREATE TABLE IF NOT EXISTS `categories_pertes_production` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpp_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `recettes`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `recettes_lignes`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `productions`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `production_matieres`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `production_pertes`;
CREATE TABLE IF NOT EXISTS `production_pertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_id` int NOT NULL,
  `type_perte` enum('matiere_premiere','produit_non_conforme','casse','defaut_machine','erreur_operateur','rebut','autre') NOT NULL,
  `categorie_perte_id` int DEFAULT NULL,
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

-- ============================================================
-- 10. TABLES PERSONNEL
-- ============================================================

DROP TABLE IF EXISTS `employes`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `presences_employes`;
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

-- ============================================================
-- 11. TABLES TRAÇABILITÉ USINE (machines, notifications, horaires)
-- ============================================================

DROP TABLE IF EXISTS `machines`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `machine_etats`;
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

-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;
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
  KEY `idx_notif_type` (`type_notif`),
  KEY `idx_notif_equipe` (`cible_equipe_id`),
  KEY `idx_notif_user` (`cible_utilisateur_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`cible_utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `horaires_travail`;
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

-- ============================================================
-- 12. DÉCLENCHEURS (triggers)
-- ============================================================

DROP TRIGGER IF EXISTS `trg_clotures_immutable_delete`;
DELIMITER $$
CREATE TRIGGER `trg_clotures_immutable_delete` BEFORE DELETE ON `clotures_caisse` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de cloture interdite';
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_clotures_immutable_update`;
DELIMITER $$
CREATE TRIGGER `trg_clotures_immutable_update` BEFORE UPDATE ON `clotures_caisse` FOR EACH ROW BEGIN
    IF OLD.montant_attendu <> NEW.montant_attendu OR OLD.montant_reel <> NEW.montant_reel
       OR OLD.ecart <> NEW.ecart THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les montants d une cloture sont immuables';
    END IF;
    IF OLD.hash_chaine IS NOT NULL AND OLD.hash_chaine <> NEW.hash_chaine THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le hash de chaine d une cloture est immuable';
    END IF;
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_factures_immutable_delete`;
DELIMITER $$
CREATE TRIGGER `trg_factures_immutable_delete` BEFORE DELETE ON `factures` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de facture interdite';
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_factures_immutable_update`;
DELIMITER $$
CREATE TRIGGER `trg_factures_immutable_update` BEFORE UPDATE ON `factures` FOR EACH ROW BEGIN
    IF OLD.numero_facture <> NEW.numero_facture THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le numero de facture est immuable';
    END IF;
    IF OLD.total_ht <> NEW.total_ht OR OLD.total_ttc <> NEW.total_ttc
       OR OLD.montant_paye <> NEW.montant_paye OR OLD.monnaie_rendue <> NEW.monnaie_rendue THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les montants d une facture validee sont immuables';
    END IF;
    IF OLD.hash_chaine IS NOT NULL AND OLD.hash_chaine <> NEW.hash_chaine THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le hash de chaine d une facture est immuable';
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

DROP TRIGGER IF EXISTS `trg_lignes_facture_immutable_delete`;
DELIMITER $$
CREATE TRIGGER `trg_lignes_facture_immutable_delete` BEFORE DELETE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de ligne de facture interdite';
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_lignes_facture_immutable_update`;
DELIMITER $$
CREATE TRIGGER `trg_lignes_facture_immutable_update` BEFORE UPDATE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les lignes de facture sont immuables';
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_paiements_immutable_delete`;
DELIMITER $$
CREATE TRIGGER `trg_paiements_immutable_delete` BEFORE DELETE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de paiement interdite';
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_paiements_immutable_update`;
DELIMITER $$
CREATE TRIGGER `trg_paiements_immutable_update` BEFORE UPDATE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les paiements sont immuables';
END
$$
DELIMITER ;

-- ============================================================
-- 13. DONNÉES INITIALES
-- ============================================================

-- Rôles
INSERT INTO `roles` (`code`, `nom`, `description`, `actif`) VALUES
  ('PROPRIETAIRE', 'Propriétaire', 'Accès total au système', 1),
  ('ADMIN', 'Administrateur', 'Gestion paramètres et utilisateurs', 1),
  ('MAGASINIER', 'Magasinier', 'Gestion stock et ventes', 1),
  ('VENDEUR', 'Vendeur', 'Opération caisse et ventes', 1),
  ('CHEF_EQUIPE', 'Chef d''équipe', 'Chef d''équipe de section', 1),
  ('CHEF_EQUIPE_USINE', 'Chef d''équipe usine', 'Chef d''équipe usine', 1)
ON DUPLICATE KEY UPDATE `nom` = VALUES(`nom`);

-- Utilisateurs
INSERT INTO `utilisateurs` (`id`, `nom`, `login`, `mot_de_passe`, `role_id`, `magasin_id`, `actif`, `date_creation`) VALUES
  (1, 'Directrice', 'directeur', '$2y$10$aZJiIFrCmjsIjXDJI9p2G.4R.gLH2ElDdsk/ZrgFkE7dXzny32TJC', 1, 1, 1, '2026-07-01 16:51:22'),
  (3, 'Vendeur Comptoir', 'vendeur', '$2y$10$fIRsNoDrYmOyvUYOovJ6peHj1c6kLDl6lrbgMTndFlT9mI78MaBLK', 4, 1, 1, '2026-07-01 16:51:22'),
  (7, 'Magasinier', 'magasinier', '$2y$10$OhLg67qKu4bbjAE0L36zQubBcb19RcBLYKQzREEUPVB9Zczg.TOnW', 3, 1, 1, '2026-08-12 09:15:55'),
  (10, 'Administrateur', 'admin', '$2y$10$4DPz6CSMQlFmS2TH3xS/ZO7DwhkMOfO4tN4mKGKZol7sLHqgijnj6', 2, 1, 1, '2026-08-22 22:50:07');

-- User roles
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `date_debut`, `actif`) VALUES
  (1, 1, '2026-07-01 16:51:22', 1),
  (3, 4, '2026-07-01 16:51:22', 1),
  (7, 3, '2026-08-12 09:15:55', 1),
  (10, 2, '2026-08-22 22:50:07', 1);

-- Permissions
INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('clients_consulter', 'Consulter la fiche client', 'Clients'),
  ('clients_gerer', 'Creer / modifier / supprimer les clients', 'Clients'),
  ('conformite_archives', 'Gerer les archives de conformite', 'Conformite'),
  ('conformite_export_syscohada', 'Exporter en format SYSCOHADA', 'Conformite'),
  ('articles_modifier', 'Modifier les articles via l''API', 'Articles'),
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
  ('stock_usine_transfert', 'Transfert depuis l''usine', 'Usine');

-- Role permissions
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
   'stock_usine_consulter','transferts_magasins_gerer','stock_usine_transfert');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'MAGASINIER', id FROM `permissions` WHERE `cle_permission` IN
  ('clients_consulter','clients_gerer','articles_modifier',
   'receptions_consulter','receptions_gerer','receptions_valider','receptions_creer',
   'pertes_consulter','pertes_gerer','pertes_creer');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'VENDEUR', id FROM `permissions` WHERE `cle_permission` IN ('clients_consulter');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE', id FROM `permissions` WHERE `cle_permission` IN
  ('clients_consulter','clients_gerer','conformite_archives','conformite_export_syscohada','articles_modifier',
   'equipes_consulter','equipes_gerer','retards_consulter','absences_consulter');
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE_USINE', id FROM `permissions` WHERE `cle_permission` IN
  ('usine_consulter','production_consulter','production_gerer','production_cloturer',
   'machines_consulter','machines_gerer','machines_historique',
   'personnel_consulter','personnel_gerer','presence_consulter','presence_gerer',
   'transfert_usine_gerer','stock_usine_consulter','stock_usine_transfert');

-- Categories matières premières
INSERT IGNORE INTO `categories_matieres_premieres` (`nom`, `description`) VALUES
  ('Matieres plastiques', 'Plastiques en granules, poudre ou feuilles'),
  ('Additifs', 'Agents de coloration, stabilisants, etc.'),
  ('Emballages', 'Films, cartons, caisses'),
  ('Produits chimiques', 'Colles, solvants, nettoyants'),
  ('Consommables usine', 'Huiles, graisses, outillage consommable'),
  ('Matières organiques', 'Fibres, bois, caoutchouc'),
  ('Métaux', 'Alu, fer, acier');

-- Unités de mesure
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

-- Tranches tarifaires
INSERT IGNORE INTO `tranches_tarifaires` (`nom`, `qte_min`, `qte_max`, `mode_calcul`, `source_cout`, `valeur`, `priorite`, `actif`) VALUES
  ('Défaut 1-9 unités', 1, 9, 'majoration_pct', 'ACHAT_FOURNISSEUR', 15.0000, 0, 1),
  ('Défaut 10-49 unités', 10, 49, 'majoration_pct', 'ACHAT_FOURNISSEUR', 12.0000, 10, 1),
  ('Défaut 50-199 unités', 50, 199, 'majoration_pct', 'ACHAT_FOURNISSEUR', 10.0000, 20, 1),
  ('Défaut 200+ unités', 200, NULL, 'majoration_pct', 'ACHAT_FOURNISSEUR', 8.0000, 30, 1),
  ('Catégorie promotion', 1, NULL, 'marge_pct', 'ACHAT_FOURNISSEUR', 5.0000, 100, 0);

-- Catégories pertes production
INSERT IGNORE INTO `categories_pertes_production` (`nom`) VALUES
  ('Défaut production'), ('Chute matière'), ('Démarrage machine'),
  ('Réglage machine'), ('Matière contaminée'), ('Produit non conforme'),
  ('Casse'), ('Autre');

-- Magasins
INSERT INTO `magasins` (`id`, `nom`, `type_magasin`, `actif`) VALUES
  (1, 'Magasin Principal', 'MAGASIN', 1),
  (2, 'Dépôt', 'MAGASIN', 1);

-- ============================================================
-- 14. CONTRAINTES ÉTRANGÈRES (restantes)
-- ============================================================

ALTER TABLE `articles`
  ADD CONSTRAINT `fk_art_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_art_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `article_lots`
  ADD CONSTRAINT `fk_lot_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lot_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `clotures_caisse`
  ADD CONSTRAINT `fk_cloture_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_cloture_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `commandes_fournisseur`
  ADD CONSTRAINT `fk_cf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_cf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_cf_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `depenses`
  ADD CONSTRAINT `fk_dep_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dep_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `factures`
  ADD CONSTRAINT `fk_fact_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fact_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facture_cloture` FOREIGN KEY (`cloture_id`) REFERENCES `clotures_caisse` (`id`) ON DELETE SET NULL;

ALTER TABLE `inventaires`
  ADD CONSTRAINT `fk_inv_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_inv_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

ALTER TABLE `inventaire_lignes`
  ADD CONSTRAINT `fk_invl_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_invl_inventaire` FOREIGN KEY (`inventaire_id`) REFERENCES `inventaires` (`id`) ON DELETE CASCADE;

ALTER TABLE `lignes_commande_fournisseur`
  ADD CONSTRAINT `fk_lcf_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseur` (`id`) ON DELETE CASCADE;

ALTER TABLE `lignes_facture`
  ADD CONSTRAINT `fk_lf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lf_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `lignes_retour`
  ADD CONSTRAINT `fk_lret_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_lret_lfact` FOREIGN KEY (`ligne_facture_id`) REFERENCES `lignes_facture` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_lret_ret` FOREIGN KEY (`retour_id`) REFERENCES `retours_factures` (`id`) ON DELETE CASCADE;

ALTER TABLE `logs_activite`
  ADD CONSTRAINT `fk_la_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `fk_mvt_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mvt_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mvt_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `paiements_facture`
  ADD CONSTRAINT `fk_pf_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `promotions`
  ADD CONSTRAINT `fk_promo_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_promo_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `retours_factures`
  ADD CONSTRAINT `fk_ret_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_ret_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_ret_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

ALTER TABLE `stock_magasins`
  ADD CONSTRAINT `fk_sm_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `transferts_stock`
  ADD CONSTRAINT `fk_tr_art` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_dst` FOREIGN KEY (`magasin_destination_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_src` FOREIGN KEY (`magasin_source_id`) REFERENCES `magasins` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
