-- ============================================================
-- ⚠️  DÉPRÉCIÉ — NE PAS UTILISER POUR L'INSTALLATION
-- ============================================================
-- Ce fichier est conservé pour référence historique uniquement.
-- La source canonique du schéma est : database/estock_db.sql
-- Ce fichier est divergent (MariaDB 10.4 vs MySQL 8.0) et
-- ne contient pas toutes les tables/contraintes du schéma actuel.
-- ============================================================

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 08 sep. 2026 à 11:35
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `estock_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `archives_caisse`
--

CREATE TABLE `archives_caisse` (
  `id` int(11) NOT NULL,
  `magasin_id` int(11) DEFAULT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `nb_factures` int(11) NOT NULL DEFAULT 0,
  `nb_paiements` int(11) NOT NULL DEFAULT 0,
  `total_ht` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_tva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(14,2) NOT NULL DEFAULT 0.00,
  `hash_sommet` varchar(64) NOT NULL,
  `signature` varchar(128) NOT NULL,
  `fichier_archive` varchar(255) DEFAULT NULL,
  `date_archivage` datetime NOT NULL DEFAULT current_timestamp(),
  `conserve_jusqua` date NOT NULL,
  `statut` enum('CONSERVE','EXPURGE') NOT NULL DEFAULT 'CONSERVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `code_barre` varchar(64) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `prix_achat` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cump` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `cout_production_ref` decimal(14,4) DEFAULT NULL,
  `prix_vente` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unite_mesure` varchar(20) NOT NULL DEFAULT 'UNITE',
  `vente_au_poids` tinyint(1) NOT NULL DEFAULT 0,
  `poids_precision` tinyint(4) NOT NULL DEFAULT 3,
  `taux_tva` decimal(5,2) DEFAULT NULL,
  `quantite_stock` int(11) NOT NULL DEFAULT 0,
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT 0.00,
  `seuil_alerte` int(11) NOT NULL DEFAULT 5,
  `emplacement` varchar(100) DEFAULT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `fournisseur_prix_ref_id` int(10) UNSIGNED DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `type_article` enum('MATIERE_PREMIERE','PRODUIT_FINI','ARTICLE_COMMERCIAL','CONSOMMABLE') NOT NULL DEFAULT 'ARTICLE_COMMERCIAL',
  `origine_article` enum('ACHAT_FOURNISSEUR','PRODUCTION_USINE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `articles`
--

INSERT INTO `articles` (`id`, `code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `cout_production_ref`, `prix_vente`, `unite_mesure`, `vente_au_poids`, `poids_precision`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `emplacement`, `fournisseur_id`, `fournisseur_prix_ref_id`, `categorie_id`, `type_article`, `origine_article`, `actif`, `date_creation`) VALUES
(1, '2025292770613', 'rush', 'RUS250', 2000.00, 0.0000, NULL, 2500.00, 'UNITE', 0, 3, NULL, 30, 0.00, 5, 'A1', 1, NULL, 1, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1, '2026-09-01 17:39:31');

-- --------------------------------------------------------

--
-- Structure de la table `article_couts`
--

CREATE TABLE `article_couts` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL DEFAULT 1,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `date_entree` datetime NOT NULL DEFAULT current_timestamp(),
  `reference` varchar(60) DEFAULT NULL COMMENT 'Référence d''entrée (commandes, mouvements)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article_lots`
--

CREATE TABLE `article_lots` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `numero_lot` varchar(100) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `date_peremption` date DEFAULT NULL,
  `date_reception` datetime NOT NULL DEFAULT current_timestamp(),
  `cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `article_lots`
--

INSERT INTO `article_lots` (`id`, `article_id`, `magasin_id`, `numero_lot`, `quantite`, `date_peremption`, `date_reception`, `cree_le`) VALUES
(1, 1, 1, 'LOT-GENERAL', 30, NULL, '2026-09-01 17:44:26', '2026-09-01 17:44:26');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `actif`) VALUES
(1, 'JUS', '', 1),
(2, 'EAU MINERALE', '', 1),
(3, 'PLASTIQUE', '', 1);

-- --------------------------------------------------------

--
-- Structure de la table `categories_matieres_premieres`
--

CREATE TABLE `categories_matieres_premieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories_matieres_premieres`
--

INSERT INTO `categories_matieres_premieres` (`id`, `nom`, `description`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'Granulés', 'Granulés plastiques de base', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(2, 'Colorants', 'Colorants et pigments', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(3, 'Additifs', 'Additifs chimiques', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(4, 'Matières recyclées', 'Matières plastiques recyclées', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(5, 'Emballages', 'Emballages et conditionnements', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(6, 'Produits chimiques autorisés', 'Produits chimiques conformes aux normes', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(7, 'Autres matières', 'Autres matières premières', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(8, 'Granulés', 'Granulés plastiques de base', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(9, 'Colorants', 'Colorants et pigments', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(10, 'Additifs', 'Additifs chimiques', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(11, 'Matières recyclées', 'Matières plastiques recyclées', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(12, 'Emballages', 'Emballages et conditionnements', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(13, 'Produits chimiques autorisés', 'Produits chimiques conformes aux normes', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16'),
(14, 'Autres matières', 'Autres matières premières', 1, '2026-09-04 16:13:16', '2026-09-04 16:13:16');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) DEFAULT NULL COMMENT 'Nom / prénom du client (B2C)',
  `raison_sociale` varchar(200) DEFAULT NULL COMMENT 'Raison sociale (B2B)',
  `nif` varchar(30) DEFAULT NULL,
  `rccm` varchar(30) DEFAULT NULL,
  `siret` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL COMMENT 'Date de naissance (optionnel, fidélité)',
  `code_fidelite` varchar(40) DEFAULT NULL COMMENT 'Code barre de la carte de fidélité',
  `points_fidelite` int(11) NOT NULL DEFAULT 0,
  `consentement_fidelite` tinyint(1) NOT NULL DEFAULT 0,
  `date_consentement` datetime DEFAULT NULL,
  `source_consentement` varchar(100) DEFAULT NULL COMMENT 'Où le consentement a été recueilli',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_dernier_achat` datetime DEFAULT NULL,
  `anonymise` tinyint(1) NOT NULL DEFAULT 0,
  `date_anonymisation` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `raison_sociale`, `nif`, `rccm`, `siret`, `adresse`, `telephone`, `email`, `date_naissance`, `code_fidelite`, `points_fidelite`, `consentement_fidelite`, `date_consentement`, `source_consentement`, `date_creation`, `date_dernier_achat`, `anonymise`, `date_anonymisation`) VALUES
(1, 'CLIENT TEST', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-08-29 10:39:38', 'interface', '2026-08-29 12:39:38', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `clotures_caisse`
--

CREATE TABLE `clotures_caisse` (
  `id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_cloture` date NOT NULL,
  `montant_attendu` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_reel` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ecart` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('VALIDE','ANNULEE') NOT NULL DEFAULT 'ANNULEE',
  `hash_chaine` varchar(64) DEFAULT NULL,
  `hash_chaine_precedent` varchar(64) DEFAULT NULL,
  `empreinte_journal` varchar(64) DEFAULT NULL,
  `horodatage_certifie` varchar(32) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `clotures_caisse`
--
DELIMITER $$
CREATE TRIGGER `trg_clotures_immutable_delete` BEFORE DELETE ON `clotures_caisse` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de cloture interdite';
END
$$
DELIMITER ;
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

-- --------------------------------------------------------

--
-- Structure de la table `commandes_fournisseur`
--

CREATE TABLE `commandes_fournisseur` (
  `id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `statut` enum('Brouillon','En_Attente','Envoyee','Recue_Partielle','Recue','Annulee') NOT NULL DEFAULT 'Brouillon',
  `date_commande` datetime NOT NULL DEFAULT current_timestamp(),
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `taux_change` decimal(12,6) NOT NULL DEFAULT 1.000000,
  `date_reception_prevue` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consentements_log`
--

CREATE TABLE `consentements_log` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_consentement` varchar(50) NOT NULL COMMENT 'fidelite / prospection',
  `consenti` tinyint(1) NOT NULL DEFAULT 1,
  `date_action` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_source` varchar(45) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'Vendeur ayant recueilli le consentement'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `consentements_log`
--

INSERT INTO `consentements_log` (`id`, `client_id`, `type_consentement`, `consenti`, `date_action`, `ip_source`, `utilisateur_id`) VALUES
(1, 1, 'fidelite', 1, '2026-08-29 12:39:39', '196.170.216.135', 1);

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id` int(11) NOT NULL,
  `magasin_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `categorie` varchar(60) NOT NULL DEFAULT 'Autre',
  `montant` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_depense` date NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emails_consentements`
--

CREATE TABLE `emails_consentements` (
  `email` varchar(190) NOT NULL,
  `opt_in_marketing` tinyint(1) NOT NULL DEFAULT 0,
  `date_consentement` datetime DEFAULT NULL,
  `source_consentement` varchar(100) NOT NULL DEFAULT '',
  `opposition_globale` tinyint(1) NOT NULL DEFAULT 0,
  `date_opposition` datetime DEFAULT NULL,
  `token_desinscription` char(64) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emails_queue`
--

CREATE TABLE `emails_queue` (
  `id` int(11) NOT NULL,
  `destinataire` varchar(190) NOT NULL,
  `type` enum('transactionnel','alerte','marketing') NOT NULL DEFAULT 'alerte',
  `sujet` varchar(190) NOT NULL,
  `corps_html` text NOT NULL,
  `statut` enum('EN_ATTENTE','ENVOYE','ECHEC') NOT NULL DEFAULT 'EN_ATTENTE',
  `nb_tentatives` tinyint(4) NOT NULL DEFAULT 0,
  `prochaine_tentative` datetime NOT NULL DEFAULT current_timestamp(),
  `erreur` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_envoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

CREATE TABLE `employes` (
  `id` int(11) NOT NULL,
  `matricule` varchar(30) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `fonction` varchar(100) NOT NULL DEFAULT '' COMMENT 'Poste/fonction',
  `telephone` varchar(30) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipes`
--

CREATE TABLE `equipes` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('BOUTIQUE','USINE','LIVRAISON','ACHATS','LOGISTIQUE','CAISSE','AUTRE') NOT NULL DEFAULT 'BOUTIQUE',
  `chef_equipe_id` int(11) DEFAULT NULL COMMENT 'ID utilisateur chef déquipe',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_equipes`
--

CREATE TABLE `user_equipes` (
  `user_id` int(11) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `date_attribution` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipe_magasins`
--

CREATE TABLE `equipe_magasins` (
  `equipe_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id` int(11) NOT NULL,
  `numero_facture` varchar(40) NOT NULL,
  `date_facture` datetime NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id` int(11) DEFAULT NULL,
  `magasin_id` int(11) DEFAULT NULL,
  `cloture_id` int(11) DEFAULT NULL,
  `client_sale_id` varchar(36) DEFAULT NULL,
  `total_ht` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tva_taux` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remise_fidelite` decimal(12,2) NOT NULL DEFAULT 0.00,
  `points_utilises` int(11) NOT NULL DEFAULT 0,
  `total_ttc` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_paye` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monnaie_rendue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('Payee','Annulee') NOT NULL DEFAULT 'Payee',
  `statut_transmission` enum('non_transmise','transmise','non_applicable') NOT NULL DEFAULT 'non_transmise',
  `hash_chaine` varchar(64) DEFAULT NULL,
  `hash_chaine_precedent` varchar(64) DEFAULT NULL,
  `horodatage_certifie` varchar(32) DEFAULT NULL,
  `date_annulation` datetime DEFAULT NULL,
  `motif_annulation` varchar(255) DEFAULT NULL,
  `annulee_par` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `client_raison_sociale` varchar(200) DEFAULT NULL,
  `client_siret` varchar(20) DEFAULT NULL,
  `client_adresse` varchar(255) DEFAULT NULL,
  `client_nom` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `factures`
--

INSERT INTO `factures` (`id`, `numero_facture`, `date_facture`, `utilisateur_id`, `magasin_id`, `cloture_id`, `client_sale_id`, `total_ht`, `tva_taux`, `remise_fidelite`, `points_utilises`, `total_ttc`, `montant_paye`, `monnaie_rendue`, `statut`, `statut_transmission`, `hash_chaine`, `hash_chaine_precedent`, `horodatage_certifie`, `date_annulation`, `motif_annulation`, `annulee_par`, `client_id`, `client_raison_sociale`, `client_siret`, `client_adresse`, `client_nom`) VALUES
(1, 'FAC-20260901-0001', '2026-09-01 17:44:26', 1, 1, NULL, NULL, 50000.00, 20.00, 0.00, 0, 60000.00, 70000.00, 10000.00, 'Payee', 'non_transmise', 'af7669d94b6fcfe69b7598e909a06831a2f4636b67e910638cdf468fbe69299c', 'GENESIS-ESTOCK-INTEGRITE-V1', '2026-09-01T15:44:27Z', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Déclencheurs `factures`
--
DELIMITER $$
CREATE TRIGGER `trg_factures_immutable_delete` BEFORE DELETE ON `factures` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de facture interdite';
END
$$
DELIMITER ;
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

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `contact` varchar(150) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'XOF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact`, `telephone`, `devise`) VALUES
(1, 'GHANA', NULL, NULL, 'XOF');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur_prix_historique`
--

CREATE TABLE `fournisseur_prix_historique` (
  `id` int(10) UNSIGNED NOT NULL,
  `article_id` int(10) UNSIGNED NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `prix_achat` decimal(12,2) NOT NULL DEFAULT 0.00,
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `source` enum('commande','reception','manuelle') NOT NULL DEFAULT 'manuelle',
  `reference_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID commande ou reception si source=commande/reception',
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `date_debut` datetime NOT NULL DEFAULT current_timestamp(),
  `date_fin` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_points`
--

CREATE TABLE `historique_points` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `facture_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL COMMENT 'Positif = gain, négatif = utilisation',
  `type_operation` enum('GAIN','UTILISATION','EXPIRATION','AJUSTEMENT','ANNULE') NOT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `date_operation` datetime NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventaires`
--

CREATE TABLE `inventaires` (
  `id` int(11) NOT NULL,
  `reference` varchar(40) NOT NULL,
  `magasin_id` int(11) NOT NULL DEFAULT 1,
  `statut` enum('En cours','Validé','Annulé') NOT NULL DEFAULT 'En cours',
  `date_debut` datetime NOT NULL DEFAULT current_timestamp(),
  `date_fin` datetime DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventaire_lignes`
--

CREATE TABLE `inventaire_lignes` (
  `id` int(11) NOT NULL,
  `inventaire_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite_theorique` int(11) NOT NULL DEFAULT 0,
  `quantite_comptee` int(11) NOT NULL DEFAULT 0,
  `ecart` int(11) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_commande_fournisseur`
--

CREATE TABLE `lignes_commande_fournisseur` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite_commandee` int(11) NOT NULL DEFAULT 1,
  `quantite_recue` int(11) NOT NULL DEFAULT 0,
  `quantite_receptionnee` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `quantite_perdue` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `prix_achat_unitaire` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_facture`
--

CREATE TABLE `lignes_facture` (
  `id` int(11) NOT NULL,
  `facture_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `quantite_poids` decimal(10,3) DEFAULT NULL,
  `prix_unitaire` decimal(12,2) NOT NULL DEFAULT 0.00,
  `prix_fournisseur_ref` decimal(12,2) DEFAULT NULL,
  `fournisseur_id_ref` int(10) UNSIGNED DEFAULT NULL,
  `tranche_tarifaire_id` int(10) UNSIGNED DEFAULT NULL,
  `prix_original` decimal(12,2) DEFAULT NULL COMMENT 'Prix unitaire avant remise (null = pas de remise)',
  `remise_pct` decimal(5,2) DEFAULT NULL COMMENT 'Pourcentage de remise appliqué (ex: 30.00 = 30%)',
  `taux_tva` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lignes_facture`
--

INSERT INTO `lignes_facture` (`id`, `facture_id`, `article_id`, `quantite`, `quantite_poids`, `prix_unitaire`, `prix_fournisseur_ref`, `fournisseur_id_ref`, `tranche_tarifaire_id`, `prix_original`, `remise_pct`, `taux_tva`) VALUES
(1, 1, 1, 20, NULL, 2500.00, NULL, NULL, NULL, 2500.00, NULL, 20.00);

--
-- Déclencheurs `lignes_facture`
--
DELIMITER $$
CREATE TRIGGER `trg_lignes_facture_immutable_delete` BEFORE DELETE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de ligne de facture interdite';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_lignes_facture_immutable_update` BEFORE UPDATE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les lignes de facture sont immuables';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_retour`
--

CREATE TABLE `lignes_retour` (
  `id` int(11) NOT NULL,
  `retour_id` int(11) NOT NULL,
  `ligne_facture_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `login` varchar(60) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `logs_activite`
--

INSERT INTO `logs_activite` (`id`, `utilisateur_id`, `action`, `details`, `ip_address`, `date_action`) VALUES
(1, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.169.2.118', '2026-08-29 10:34:11'),
(2, 1, 'MODIFICATION_PARAMETRES', 'Modification des paramètres boutique', '196.170.216.135', '2026-08-29 10:38:30'),
(3, 1, 'DECONNEXION', 'Déconnexion', '196.170.216.135', '2026-08-29 10:38:38'),
(4, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.170.216.135', '2026-08-29 10:39:07'),
(5, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '196.170.216.135', '2026-08-29 10:39:10'),
(6, 1, 'CLIENT_CREE', 'Fiche client #1', '196.170.216.135', '2026-08-29 10:39:39'),
(7, 1, 'DECONNEXION', 'Déconnexion', '196.170.216.135', '2026-08-29 10:42:23'),
(8, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.169.2.118', '2026-08-29 10:44:37'),
(9, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '196.169.2.118', '2026-08-29 10:44:39'),
(10, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.171.108.79', '2026-08-29 16:53:39'),
(11, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '196.171.108.79', '2026-08-29 16:53:58'),
(12, 1, 'DECONNEXION', 'Déconnexion', '196.171.108.79', '2026-08-29 16:54:50'),
(13, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.171.108.79', '2026-08-29 17:03:30'),
(14, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '196.171.108.79', '2026-08-29 17:03:56'),
(15, 1, 'CATEGORIE_CREEE', 'Création de la catégorie « JUS » (#1)', '196.171.108.79', '2026-08-29 17:09:35'),
(16, 1, 'CATEGORIE_CREEE', 'Création de la catégorie « EAU MINERALE » (#2)', '196.171.108.79', '2026-08-29 17:12:31'),
(17, 1, 'CATEGORIE_CREEE', 'Création de la catégorie « PLASTIQUE » (#3)', '196.171.108.79', '2026-08-29 17:12:54'),
(18, 1, 'MODIFICATION_FOURNISSEUR', 'Création fournisseur \"GHANA\"', '196.171.108.79', '2026-08-29 17:21:01'),
(19, 1, 'MODIFICATION_FOURNISSEUR', 'Modification fournisseur \"GHANA\"', '196.171.108.79', '2026-08-29 17:23:11'),
(20, 1, 'DECONNEXION', 'Déconnexion', '196.171.108.79', '2026-08-29 17:39:23'),
(21, 1, 'CONNEXION', 'Connexion réussie: directeur', '196.171.108.79', '2026-09-01 14:57:22'),
(22, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '196.171.108.79', '2026-09-01 14:57:30'),
(23, 1, 'MODIFICATION_ARTICLE', 'Création article #1 - rush', '196.171.108.79', '2026-09-01 15:39:31'),
(24, 1, 'SORTIE_STOCK_LOT', 'Vente FAC-20260901-0001 : -20 unité(s) du lot LOT-GENERAL', '196.171.108.79', '2026-09-01 15:44:27'),
(25, 1, 'VENTE', 'Facture FAC-20260901-0001 validée — 1 ligne(s), TTC=60 000,00', '196.171.108.79', '2026-09-01 15:44:27'),
(26, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 09:46:29'),
(27, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 09:46:31'),
(28, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 10:20:14'),
(29, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 10:20:16'),
(30, 1, 'MODIFICATION_ARTICLE', 'Modification article #1 - rush', '::1', '2026-09-04 10:33:12'),
(31, 1, 'TRANCHE_SUPPRIMEE', 'Tranche #1', '::1', '2026-09-04 10:38:16'),
(32, 1, 'TRANCHE_SUPPRIMEE', 'Tranche #2', '::1', '2026-09-04 10:38:29'),
(33, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 11:59:46'),
(34, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 11:59:49'),
(35, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 13:13:38'),
(36, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: USINE', '::1', '2026-09-04 13:13:44'),
(37, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 13:13:58'),
(38, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 13:54:49'),
(39, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 13:54:53'),
(40, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 14:49:44'),
(41, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: USINE', '::1', '2026-09-04 14:49:51'),
(42, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 14:50:01'),
(43, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 15:25:48'),
(44, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 15:25:50'),
(45, 1, 'CREATION_ROLE', 'Création du rôle : CHEF_EQUIPE', '::1', '2026-09-04 15:57:27'),
(46, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-04 16:34:43'),
(47, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-04 16:34:45'),
(48, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-08 08:41:46'),
(49, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-08 08:41:49'),
(50, 1, 'CONNEXION', 'Connexion réussie: directeur', '::1', '2026-09-08 09:24:57'),
(51, 1, 'CHOIX_MAGASIN', 'Magasin de suivi sélectionné: Magasin Principal', '::1', '2026-09-08 09:24:59'),
(52, 1, 'CREATION_ROLE', 'Création du rôle : CHEF_EQUIPE_USINE', '::1', '2026-09-08 09:32:25'),
(53, 1, 'MODIFICATION_ROLE', 'Modification du rôle : CHEF_EQUIPE', '::1', '2026-09-08 09:33:11');

-- --------------------------------------------------------

--
-- Structure de la table `magasins`
--

CREATE TABLE `magasins` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `type_magasin` enum('MAGASIN','USINE') NOT NULL DEFAULT 'MAGASIN',
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `nif` varchar(30) DEFAULT NULL,
  `rccm` varchar(30) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `magasins`
--

INSERT INTO `magasins` (`id`, `nom`, `type_magasin`, `adresse`, `code_postal`, `nif`, `rccm`, `actif`, `date_creation`) VALUES
(1, 'Magasin Principal', 'MAGASIN', 'Qt. Agoè, anomè-plateaux, derrière Queen store', 'Lomé', '1001701928', 'TG-LFW-01-2023-B13-01249', 1, '2026-07-02 22:15:22');

-- --------------------------------------------------------

--
-- Structure de la table `matieres_premieres`
--

CREATE TABLE `matieres_premieres` (
  `id` int(11) NOT NULL,
  `reference` varchar(30) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `unite_mesure` varchar(20) NOT NULL DEFAULT 'KG',
  `cout_reference` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `stock_minimum` int(11) NOT NULL DEFAULT 10,
  `fournisseur_id` int(11) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_matieres_premieres`
--

CREATE TABLE `mouvements_matieres_premieres` (
  `id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `type` enum('ENTREE_ACHAT','ENTREE_RETOUR','ENTREE_RECEPTION','SORTIE_PRODUCTION','SORTIE_PERTE','AJUSTEMENT_ENTREE','AJUSTEMENT_SORTIE') NOT NULL,
  `quantite` decimal(12,4) NOT NULL,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `cout_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `production_id` int(11) DEFAULT NULL,
  `reception_id` int(11) DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_produits_finis`
--

CREATE TABLE `mouvements_produits_finis` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `type` enum('PRODUCTION','TRANSFERT_SORTIE','RETOUR','AJUSTEMENT_ENTREE','AJUSTEMENT_SORTIE') NOT NULL,
  `quantite` int(11) NOT NULL,
  `production_id` int(11) DEFAULT NULL,
  `magasin_destination_id` int(11) DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `magasin_id` int(11) DEFAULT NULL,
  `type` enum('Entree','Sortie','Vente','Transfert','Ajustement','Retour_stock','RECEPTION','PERTE','PRODUCTION','Perte_production') NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `stock_avant` int(11) DEFAULT NULL,
  `stock_apres` int(11) DEFAULT NULL,
  `cout_unitaire` decimal(14,4) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT current_timestamp(),
  `motif` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id`, `article_id`, `utilisateur_id`, `magasin_id`, `type`, `quantite`, `date_mouvement`, `motif`) VALUES
(1, 1, 1, 1, 'Vente', 20, '2026-09-01 17:44:27', 'Vente facture FAC-20260901-0001 [Lot LOT-GENERAL: -20]');

-- --------------------------------------------------------

--
-- Structure de la table `paiements_facture`
--

CREATE TABLE `paiements_facture` (
  `id` int(11) NOT NULL,
  `facture_id` int(11) NOT NULL,
  `mode_paiement` enum('Especes','Mobile_Money','Carte_Bancaire','Virement','Autre') NOT NULL DEFAULT 'Especes',
  `montant` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(100) DEFAULT NULL,
  `date_paiement` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements_facture`
--

INSERT INTO `paiements_facture` (`id`, `facture_id`, `mode_paiement`, `montant`, `reference`, `date_paiement`) VALUES
(1, 1, 'Especes', 70000.00, NULL, '2026-09-01 17:44:27');

--
-- Déclencheurs `paiements_facture`
--
DELIMITER $$
CREATE TRIGGER `trg_paiements_immutable_delete` BEFORE DELETE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de paiement interdite';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_paiements_immutable_update` BEFORE UPDATE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les paiements sont immuables';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle` varchar(80) NOT NULL,
  `valeur` text DEFAULT NULL,
  `categorie` varchar(50) NOT NULL DEFAULT 'general',
  `ordre` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `date_modif` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `categorie`, `ordre`, `description`, `date_modif`) VALUES
(1, 'nom_boutique', 'OBS MIRA SARL U', 'boutique', 10, 'Nom affiché dans le titre, le ticket et le login', '2026-08-28 16:46:05'),
(2, 'adresse_boutique', 'Qt. Agoè, anomè-plateaux, derrière Queen store', 'boutique', 20, 'Adresse affichée sur le ticket de caisse', '2026-08-28 16:46:06'),
(3, 'telephone_boutique', '(+228) 90 99 26 68', 'boutique', 30, 'Téléphone affiché sur le ticket', '2026-08-28 16:46:06'),
(4, 'email_boutique', 'obssami@gmail.com', 'boutique', 40, 'E-mail de contact affiché sur le ticket', '2026-08-28 16:46:06'),
(5, 'site_boutique', 'www.estock-market.example', 'boutique', 50, 'Site web affiché sur le ticket', '2026-07-03 15:12:34'),
(6, 'slogan_boutique', 'Production et fourniture d’eau minérale et de jus de fruits, consommables informatiques, fournitures de bureau, Produits d’entretien et prestation de services', 'boutique', 60, 'Slogan affiché sur la page login', '2026-08-28 16:46:06'),
(7, 'devise_nom', 'Franc CFA BCEAO', 'financier', 10, 'Nom complet de la devise', '2026-07-03 15:14:15'),
(8, 'devise_symbole', 'Fcfa', 'financier', 20, 'Symbole affiché avec les montants', '2026-07-03 17:36:03'),
(9, 'devise_code', 'XOF', 'financier', 30, 'Code ISO 4217 de la devise', '2026-07-03 15:12:34'),
(10, 'devise_position', 'apres', 'financier', 35, 'Position du symbole : avant ou apres', '2026-07-03 15:12:34'),
(11, 'devise_decimales', '0', 'financier', 36, 'Nombre de décimales affichées', '2026-07-03 15:14:15'),
(12, 'separateur_decimal', ',', 'financier', 37, 'Séparateur décimal', '2026-08-27 15:48:20'),
(13, 'separateur_milliers', '', 'financier', 38, 'Séparateur des milliers', '2026-07-03 15:14:15'),
(14, 'tva_taux_defaut', '20.00', 'financier', 40, 'Taux de TVA par défaut en %', '2026-07-03 15:12:34'),
(15, 'tva_active', '1', 'financier', 50, 'TVA activée (1) ou désactivée (0)', '2026-07-03 15:12:34'),
(16, 'paiement_rapide_1', '2000.00', 'financier', 60, 'Premier montant rapide à la caisse', '2026-07-03 15:14:15'),
(17, 'paiement_rapide_2', '5000.00', 'financier', 70, 'Deuxième montant rapide à la caisse', '2026-07-03 15:14:15'),
(18, 'paiement_rapide_3', '10000.00', 'financier', 80, 'Troisième montant rapide à la caisse', '2026-07-03 15:14:15'),
(19, 'app_nom', 'OBS MIRA', 'apparence', 10, 'Nom court affiché dans la sidebar', '2026-08-28 16:46:06'),
(20, 'theme_couleur', 'rose', 'apparence', 20, 'Couleur d\'accent du thème', '2026-08-28 16:46:06'),
(21, 'langue', 'fr', 'apparence', 30, 'Langue de l\'interface', '2026-07-03 15:12:34'),
(22, 'ticket_entete', 'Merci de votre visite !', 'ticket', 10, 'Message de fin de ticket', '2026-07-03 15:12:34'),
(23, 'ticket_remarque', '', 'ticket', 20, 'Remarque additionnelle sur le ticket', '2026-07-03 15:12:34'),
(24, 'ticket_format', '80mm', 'ticket', 30, 'Format du ticket : 58mm ou 80mm', '2026-07-03 15:12:34'),
(25, 'pays', 'Togo', 'general', 10, 'Pays', '2026-07-03 15:12:34'),
(26, 'code_postal', 'Lomé', 'general', 20, 'Code postal ou ville', '2026-08-28 16:46:06'),
(105, 'facture_prefixe', 'FAC', 'financier', 90, 'Préfixe des numéros de facture', '2026-08-13 14:22:57'),
(106, 'facture_mentions_penalites', '1', 'boutique', 90, 'Afficher les mentions de pénalités de retard sur la facture PDF', '2026-08-13 14:22:57'),
(107, 'tva_intracom_boutique', '', 'boutique', 95, 'Numéro de TVA intracommunautaire de la boutique', '2026-08-13 14:22:57'),
(108, 'siret_boutique', '', 'boutique', 100, 'SIRET de la boutique (facturation B2B)', '2026-08-13 14:22:57'),
(109, 'archives_conservation_annees', '6', 'financier', 100, 'Durée de conservation des archives de caisse (années)', '2026-08-13 14:22:57'),
(110, 'rgpd_retention_clients_mois', '36', 'financier', 110, 'Durée de rétention des données clients sans activité (mois, puis anonymisation)', '2026-08-13 14:22:57'),
(111, 'rgpd_contact_email', '', 'financier', 111, 'Contact RGPD / délégué à la protection des données', '2026-08-13 14:22:57'),
(112, 'fidelite_actif', '1', 'financier', 120, 'Activer le programme de fidélité', '2026-08-29 12:38:30'),
(113, 'fidelite_points_par_devise', '100', 'financier', 121, 'Unités de devise dépensées pour gagner 1 point (ex : 100 = 1 pt / 100 FCFA)', '2026-08-13 15:18:26'),
(114, 'fidelite_valeur_point', '1', 'financier', 122, 'Valeur en devise d\'un point (1 point = montant × 100)', '2026-08-13 14:22:57'),
(115, 'fidelite_min_points_usage', '10', 'financier', 123, 'Seuil minimal de points pour utiliser une réduction fidélité', '2026-08-13 14:22:57'),
(116, 'valorisation_methode', 'CUMP', 'financier', 130, 'Méthode de valorisation du stock : CUMP ou FIFO', '2026-08-13 14:22:57'),
(165, 'emails_mode', 'file', 'general', 80, 'Mode d\'envoi des e-mails : file (worker asynchrone) ou direct', '2026-08-14 00:19:25'),
(166, 'emails_conservation_jours', '30', 'general', 81, 'Durée de conservation des e-mails traités avant purge (RGPD art. 5(1)(e))', '2026-08-14 00:19:25'),
(167, 'emails_desinscription_obligatoire', '1', 'general', 82, 'Ajouter la mention de désinscription dans les e-mails de prospection (art. 21 RGPD)', '2026-08-14 00:19:25'),
(168, 'emails_tentatives_max', '5', 'general', 83, 'Nombre maximal de tentatives d\'envoi avant échec définitif', '2026-08-14 00:19:25'),
(169, 'base_url_ext', '', 'general', 84, 'URL publique du site utilisée dans les e-mails (ex : https://magasin.tg)', '2026-08-14 00:19:25'),
(172, 'nif_boutique', '1001701928', 'boutique', 95, 'NIF (Numéro d\'Identification Fiscale) de la boutique — OTR', '2026-08-28 16:46:06'),
(173, 'rccm_boutique', 'TG-LFW-01-2023-B13-01249', 'boutique', 100, 'RCCM (Registre du Commerce et du Crédit Mobilier) de la boutique', '2026-08-28 16:46:06'),
(187, 'regime_fiscal', 'TVA', 'financier', 88, 'Régime fiscal : TVA (classique, 18 %) ou TPU (Taxe Professionnelle Unique — factures hors taxes)', '2026-08-28 16:46:06'),
(193, 'retention_clients_mois', '36', 'financier', 110, 'Durée de rétention des données clients sans activité (mois, puis anonymisation)', '2026-08-15 10:13:37'),
(194, 'contact_donnees_email', '', 'financier', 111, 'Contact dédié à la protection des données personnelles', '2026-08-15 10:13:37'),
(231, 'fuseau_horaire', 'UTC', 'general', 0, NULL, '2026-08-24 12:00:22'),
(233, 'smtp_from', 'noreply@estock.local', 'general', 0, NULL, '2026-08-24 12:00:22'),
(367, 'google_oauth_actif', '0', 'general', 0, NULL, '2026-08-28 16:46:06'),
(368, 'google_client_id', '', 'general', 0, NULL, '2026-08-28 16:46:06'),
(369, 'google_client_secret', '', 'general', 0, NULL, '2026-08-28 16:46:06');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `cle_permission` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `categorie` varchar(50) NOT NULL DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `cle_permission`, `description`, `categorie`) VALUES
(1, 'stock_consulter', 'Consulter l\'état du stock', 'stock'),
(2, 'stock_gerer', 'Gérer les entrées/sorties de stock', 'stock'),
(3, 'stock_transfert', 'Effectuer des transferts inter-magasins', 'stock'),
(4, 'articles_consulter', 'Consulter la liste des articles', 'articles'),
(5, 'articles_gerer', 'Créer/Modifier/Supprimer des articles', 'articles'),
(6, 'fournisseurs_consulter', 'Consulter les fournisseurs', 'fournisseurs'),
(7, 'fournisseurs_gerer', 'Gérer les fournisseurs', 'fournisseurs'),
(8, 'facturation_consulter', 'Consulter les factures', 'facturation'),
(9, 'facturation_gerer', 'Créer/Annuler des factures', 'facturation'),
(10, 'caisse_gerer', 'Utiliser la caisse (POS)', 'facturation'),
(11, 'utilisateurs_consulter', 'Consulter les utilisateurs', 'utilisateurs'),
(12, 'utilisateurs_gerer', 'Créer/Modifier les utilisateurs', 'utilisateurs'),
(13, 'magasins_consulter', 'Consulter les magasins', 'magasins'),
(14, 'magasins_gerer', 'Gérer les magasins', 'magasins'),
(15, 'depenses_consulter', 'Consulter les dépenses', 'depenses'),
(16, 'depenses_gerer', 'Saisir/Modifier les dépenses', 'depenses'),
(17, 'parametres_gerer', 'Modifier les paramètres de la boutique', 'config'),
(18, 'audit_consulter', 'Consulter le journal d\'audit', 'config'),
(19, 'statistiques_consulter', 'Consulter les statistiques', 'config'),
(20, 'cloture_gerer', 'Effectuer la clôture de caisse', 'caisse'),
(21, 'achats_gerer', 'Gérer les commandes fournisseurs (création, édition, réception)', 'Logistique'),
(22, 'achats_consulter', 'Consulter les commandes fournisseurs', 'Logistique'),
(23, 'retours_gerer', 'Créer et traiter des retours d\'articles et avoirs SAV', 'Ventes'),
(24, 'retours_consulter', 'Consulter l\'historique des retours d\'articles', 'Ventes'),
(25, 'promotions_gerer', 'Créer, modifier et désactiver des codes promo et promotions', 'Ventes'),
(26, 'promotions_consulter', 'Consulter les règles de promotions et codes promo', 'Ventes'),
(27, 'inventaire_gerer', 'Créer et valider des sessions d\'inventaire physique', 'Logistique'),
(28, 'inventaire_consulter', 'Consulter les sessions d\'inventaire physique', 'Logistique'),
(38, 'achats_valider', 'Valider ou rejeter les commandes fournisseurs soumises (approbation)', 'Logistique'),
(46, 'conformite_archives', 'Générer et consulter les archives de caisse (NF525)', 'config'),
(47, 'conformite_export_fec', 'Exporter les écritures comptables (FEC)', 'config'),
(48, 'clients_consulter', 'Consulter les clients (fidélité, RGPD)', 'ventes'),
(49, 'clients_gerer', 'Créer / modifier les clients et consentements', 'ventes'),
(112, 'conformite_export_syscohada', 'Exporter en format SYSCOHADA', 'Conformite'),
(113, 'articles_modifier', 'Modifier les articles via l\'API', 'Articles'),
(117, 'receptions_consulter', 'Consulter les receptions fournisseur', 'achats'),
(118, 'receptions_gerer', 'Creer et gerer les receptions fournisseur', 'achats'),
(119, 'pertes_consulter', 'Consulter les pertes fournisseur', 'achats'),
(120, 'pertes_gerer', 'Enregistrer les pertes fournisseur', 'achats'),
(121, 'tarification_consulter', 'Consulter les regles de tarification', 'tarification'),
(122, 'tarification_gerer', 'Gerer les regles de tarification', 'tarification'),
(123, 'prix_fournisseur_consulter', 'Historique des prix fournisseurs', 'achats'),
(124, 'prix_fournisseur_gerer', 'Modifier les prix fournisseurs', 'achats'),
(125, 'usine_consulter', 'Consulter le module usine', 'Usine'),
(126, 'usine_gerer', 'Gérer les matières premières et recettes', 'Usine'),
(127, 'production_consulter', 'Consulter les productions', 'Usine'),
(128, 'production_gerer', 'Créer et gérer les productions', 'Usine'),
(129, 'production_cloturer', 'Clôturer une production', 'Usine'),
(130, 'personnel_consulter', 'Consulter le personnel usine', 'Usine'),
(131, 'personnel_gerer', 'Gérer le personnel usine', 'Usine'),
(132, 'presence_consulter', 'Consulter les présences', 'Usine'),
(133, 'presence_gerer', 'Enregistrer et modifier les présences', 'Usine'),
(134, 'transfert_usine_gerer', 'Effectuer des transferts usine → magasin', 'Usine'),
(135, 'roles_consulter', 'Consulter les rôles', 'Administration'),
(136, 'roles_gerer', 'Créer/modifier/désactiver les rôles', 'Administration'),
(137, 'permissions_gerer', 'Gérer la matrice de permissions', 'Administration'),
(138, 'audit_gerer', 'Consulter l audit trail', 'Administration'),
(142, 'ventes_consulter', 'Consulter les ventes et factures', 'Ventes'),
(147, 'transferts_consulter', 'Consulter les transferts', 'Stock'),
(148, 'transferts_gerer', 'Effectuer les transferts inter-magasins', 'Stock'),
(149, 'exports_consulter', 'Consulter les exports', 'Rapports'),
(150, 'suggestions_consulter', 'Consulter les suggestions d achat', 'Achats'),
(151, 'impression_consulter', 'Consulter les impressions', 'Impression'),
(152, 'equipes_consulter', 'Consulter les équipes', 'Administration'),
(153, 'equipes_gerer', 'Gérer les équipes (CRUD + affectations)', 'Administration'),
(154, 'retards_consulter', 'Consulter les retards du personnel', 'Usine'),
(155, 'absences_consulter', 'Consulter les absences du personnel', 'Usine'),
(156, 'stock_usine_consulter', 'Consulter le stock usine', 'Usine'),
(157, 'transferts_magasins_gerer', 'Effectuer les transferts inter-magasins', 'Stock'),
(158, 'stock_usine_transfert', 'Transférer du stock usine vers magasin', 'Usine');

-- --------------------------------------------------------

--
-- Structure de la table `pertes_fournisseur`
--

CREATE TABLE `pertes_fournisseur` (
  `id` int(10) UNSIGNED NOT NULL,
  `reception_id` int(10) UNSIGNED DEFAULT NULL,
  `reception_ligne_id` int(10) UNSIGNED DEFAULT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `article_id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `motif` enum('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') NOT NULL DEFAULT 'autre',
  `commentaire` text DEFAULT NULL,
  `date_perte` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presences_employes`
--

CREATE TABLE `presences_employes` (
  `id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_presence` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `temps_travaille_minutes` int(11) DEFAULT NULL COMMENT 'Calculé automatiquement',
  `commentaire` varchar(255) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'Utilisateur ayant enregistré',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presences_employes_audit`
--

CREATE TABLE `presences_employes_audit` (
  `id` int(11) NOT NULL,
  `presence_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `ancienne_valeur` text DEFAULT NULL COMMENT 'JSON avant modification',
  `nouvelle_valeur` text DEFAULT NULL COMMENT 'JSON après modification',
  `utilisateur_id` int(11) DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `productions`
--

CREATE TABLE `productions` (
  `id` int(11) NOT NULL,
  `reference` varchar(40) NOT NULL COMMENT 'PROD-YYYY-NNNN',
  `article_id` int(11) NOT NULL COMMENT 'Produit fini à fabriquer',
  `recette_id` int(11) NOT NULL COMMENT 'Recette utilisée',
  `recette_version` int(11) NOT NULL DEFAULT 1,
  `quantite_prevue` int(11) NOT NULL DEFAULT 0,
  `quantite_produite` int(11) NOT NULL DEFAULT 0 COMMENT 'Produits conformes',
  `quantite_perdue` int(11) NOT NULL DEFAULT 0 COMMENT 'Produits non conformes',
  `cout_matieres` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Coût total matières consommées',
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Coût matière unitaire = cout_matieres / quantite_produite',
  `statut` enum('BROUILLON','PLANIFIEE','EN_COURS','TERMINEE','ANNULEE') NOT NULL DEFAULT 'BROUILLON',
  `date_prevue` date DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'Responsable de production',
  `notes` text DEFAULT NULL,
  `rendement_pct` decimal(8,4) DEFAULT NULL COMMENT 'Rendement basé sur les quantités',
  `rendement_cout` decimal(8,4) DEFAULT NULL COMMENT 'Rendement basé sur les coûts',
  `quantite_defectueuse` int(11) DEFAULT 0 COMMENT 'Produits défectueux (réparables)',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `production_employes`
--

CREATE TABLE `production_employes` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `production_lots`
--

CREATE TABLE `production_lots` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `numero_lot` varchar(100) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `date_fabrication` date NOT NULL,
  `date_peremption` date DEFAULT NULL,
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `magasin_id` int(11) NOT NULL COMMENT 'Magasin où le lot est stocké',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `production_matieres`
--

CREATE TABLE `production_matieres` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `quantite_prevue` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `quantite_reelle` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `numero_lot` varchar(100) DEFAULT NULL COMMENT 'Lot de matière première consommé',
  `lot_id` int(11) DEFAULT NULL COMMENT 'FK vers article_lots si applicable',
  `cout_unitaire` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Coût unitaire de référence au moment de la production',
  `cout_total` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'quantite_reelle × cout_unitaire'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `production_pertes`
--

CREATE TABLE `production_pertes` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `type_perte` enum('matiere_premiere','produit_non_conforme','casse','defaut_machine','erreur_operateur','rebut','autre') NOT NULL,
  `categorie_perte_id` int(11) DEFAULT NULL COMMENT 'FK vers categories_pertes_production',
  `article_id` int(11) NOT NULL COMMENT 'Article concerné (matière ou produit)',
  `quantite` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `motif` varchar(255) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `date_perte` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `production_produits`
--

CREATE TABLE `production_produits` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL COMMENT 'Produit fini',
  `quantite_produite` int(11) NOT NULL DEFAULT 0,
  `quantite_perdue` int(11) NOT NULL DEFAULT 0,
  `magasin_destination_id` int(11) DEFAULT NULL COMMENT 'Magasin cible (usine par défaut)',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `code_promo` varchar(40) DEFAULT NULL,
  `type_reduction` enum('pourcentage','montant_fixe') NOT NULL DEFAULT 'pourcentage',
  `valeur` decimal(10,2) NOT NULL DEFAULT 0.00,
  `article_id` int(11) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `montant_min_achat` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `limite_utilisations` int(11) DEFAULT NULL,
  `nb_utilisations` int(11) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `receptions`
--

CREATE TABLE `receptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(40) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `statut` enum('Brouillon','Validee','Annulee') NOT NULL DEFAULT 'Brouillon',
  `date_reception` datetime NOT NULL DEFAULT current_timestamp(),
  `commentaire` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `receptions`
--
DELIMITER $$
CREATE TRIGGER `trg_receptions_immutable_delete` BEFORE DELETE ON `receptions` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Les recussions ne peuvent pas etre supprimees.'; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_receptions_immutable_update` BEFORE UPDATE ON `receptions` FOR EACH ROW BEGIN
    IF OLD.statut = 'Validee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une réception validée ne peut pas être modifiée.';
    END IF;
    IF OLD.statut = 'Annulee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une réception annulée ne peut pas être réactivée.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `reception_lignes`
--

CREATE TABLE `reception_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `reception_id` int(10) UNSIGNED NOT NULL,
  `ligne_commande_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite_attendue` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `quantite_recue` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `quantite_acceptee` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `quantite_perdue` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `prix_achat_unitaire` decimal(12,2) NOT NULL DEFAULT 0.00,
  `numero_lot` varchar(100) DEFAULT NULL,
  `date_peremption` date DEFAULT NULL,
  `motif_perte` enum('endommage','manquant','expire','non_conforme','casse_livraison','erreur_fournisseur','autre') DEFAULT NULL,
  `commentaire_perte` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recettes`
--

CREATE TABLE `recettes` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `article_id` int(11) NOT NULL COMMENT 'Produit fini fabriqué',
  `quantite_produite` int(11) NOT NULL DEFAULT 100 COMMENT 'Quantité théorique produite par lot de recette',
  `unite_produit` varchar(20) NOT NULL DEFAULT 'UNITE',
  `version` int(11) NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recettes_lignes`
--

CREATE TABLE `recettes_lignes` (
  `id` int(11) NOT NULL,
  `recette_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL COMMENT 'Article matière première',
  `quantite_necessaire` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Quantité nécessaire pour quantite_produite',
  `unite` varchar(20) NOT NULL DEFAULT 'KG',
  `pertes_theoriques_pct` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Perte théorique en %',
  `ordre` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `regles_promotions`
--

CREATE TABLE `regles_promotions` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `condition_type` enum('PEREMPTION_PROCHE','SURSTOCK') NOT NULL,
  `jours_limite` int(11) DEFAULT NULL COMMENT 'Pour PEREMPTION_PROCHE : jours max avant DLC',
  `seuil_stock` int(11) DEFAULT NULL COMMENT 'Pour SURSTOCK : quantité minimum en stock',
  `pourcentage_remise` decimal(5,2) NOT NULL COMMENT 'Ex: 15.00 = 15%',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `retours_factures`
--

CREATE TABLE `retours_factures` (
  `id` int(11) NOT NULL,
  `numero_retour` varchar(40) NOT NULL,
  `facture_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL DEFAULT 1,
  `utilisateur_id` int(11) DEFAULT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `motif` varchar(255) DEFAULT NULL,
  `date_retour` datetime NOT NULL DEFAULT current_timestamp(),
  `statut` enum('Valide','Annule') NOT NULL DEFAULT 'Valide',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `code`, `nom`, `description`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'PROPRIETAIRE', 'Propriétaire', 'Accès total au système', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(2, 'ADMIN', 'Administrateur', 'Gère les paramètres et les utilisateurs', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(3, 'MAGASINIER', 'Magasinier', 'Gère le stock et les ventes en magasin', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(4, 'VENDEUR', 'Vendeur', 'Opère la caisse et les ventes', 1, '2026-09-04 14:52:47', '2026-09-04 14:52:47'),
(5, 'CHEF_EQUIPE', 'Chef d\'équipe boutique', 'Chef d\'équipe de la section boutique', 1, '2026-09-04 15:57:27', '2026-09-08 09:33:11'),
(7, 'CHEF_EQUIPE_USINE', 'Chef d\'équipe usine', 'Chef d\'équipe de la section usine', 1, '2026-09-08 09:32:25', '2026-09-08 09:32:25');

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_nom` varchar(50) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`role_nom`, `permission_id`) VALUES
('ADMIN', 1),
('ADMIN', 2),
('ADMIN', 3),
('ADMIN', 4),
('ADMIN', 5),
('ADMIN', 6),
('ADMIN', 7),
('ADMIN', 8),
('ADMIN', 9),
('ADMIN', 10),
('ADMIN', 11),
('ADMIN', 12),
('ADMIN', 13),
('ADMIN', 14),
('ADMIN', 15),
('ADMIN', 16),
('ADMIN', 17),
('ADMIN', 18),
('ADMIN', 19),
('ADMIN', 20),
('ADMIN', 21),
('ADMIN', 22),
('ADMIN', 23),
('ADMIN', 24),
('ADMIN', 25),
('ADMIN', 26),
('ADMIN', 27),
('ADMIN', 28),
('ADMIN', 38),
('ADMIN', 46),
('ADMIN', 47),
('ADMIN', 48),
('ADMIN', 49),
('ADMIN', 113),
('ADMIN', 117),
('ADMIN', 118),
('ADMIN', 119),
('ADMIN', 120),
('ADMIN', 121),
('ADMIN', 122),
('ADMIN', 123),
('ADMIN', 124),
('ADMIN', 125),
('ADMIN', 126),
('ADMIN', 127),
('ADMIN', 128),
('ADMIN', 129),
('ADMIN', 130),
('ADMIN', 131),
('ADMIN', 132),
('ADMIN', 133),
('ADMIN', 134),
('ADMIN', 135),
('ADMIN', 136),
('ADMIN', 137),
('ADMIN', 138),
('ADMIN', 142),
('ADMIN', 147),
('ADMIN', 148),
('ADMIN', 149),
('ADMIN', 150),
('ADMIN', 151),
('MAGASINIER', 1),
('MAGASINIER', 2),
('MAGASINIER', 3),
('MAGASINIER', 4),
('MAGASINIER', 5),
('MAGASINIER', 6),
('MAGASINIER', 7),
('MAGASINIER', 10),
('MAGASINIER', 13),
('MAGASINIER', 15),
('MAGASINIER', 16),
('MAGASINIER', 19),
('MAGASINIER', 20),
('MAGASINIER', 21),
('MAGASINIER', 22),
('MAGASINIER', 27),
('MAGASINIER', 28),
('MAGASINIER', 117),
('MAGASINIER', 119),
('MAGASINIER', 121),
('MAGASINIER', 123),
('MAGASINIER', 142),
('MAGASINIER', 147),
('MAGASINIER', 149),
('MAGASINIER', 150),
('MAGASINIER', 151),
('PROPRIETAIRE', 1),
('PROPRIETAIRE', 2),
('PROPRIETAIRE', 3),
('PROPRIETAIRE', 4),
('PROPRIETAIRE', 5),
('PROPRIETAIRE', 6),
('PROPRIETAIRE', 7),
('PROPRIETAIRE', 8),
('PROPRIETAIRE', 9),
('PROPRIETAIRE', 10),
('PROPRIETAIRE', 11),
('PROPRIETAIRE', 12),
('PROPRIETAIRE', 13),
('PROPRIETAIRE', 14),
('PROPRIETAIRE', 15),
('PROPRIETAIRE', 16),
('PROPRIETAIRE', 17),
('PROPRIETAIRE', 18),
('PROPRIETAIRE', 19),
('PROPRIETAIRE', 20),
('PROPRIETAIRE', 21),
('PROPRIETAIRE', 22),
('PROPRIETAIRE', 23),
('PROPRIETAIRE', 24),
('PROPRIETAIRE', 25),
('PROPRIETAIRE', 26),
('PROPRIETAIRE', 27),
('PROPRIETAIRE', 28),
('PROPRIETAIRE', 38),
('PROPRIETAIRE', 46),
('PROPRIETAIRE', 47),
('PROPRIETAIRE', 48),
('PROPRIETAIRE', 49),
('PROPRIETAIRE', 112),
('PROPRIETAIRE', 113),
('PROPRIETAIRE', 117),
('PROPRIETAIRE', 118),
('PROPRIETAIRE', 119),
('PROPRIETAIRE', 120),
('PROPRIETAIRE', 121),
('PROPRIETAIRE', 122),
('PROPRIETAIRE', 123),
('PROPRIETAIRE', 124),
('PROPRIETAIRE', 125),
('PROPRIETAIRE', 126),
('PROPRIETAIRE', 127),
('PROPRIETAIRE', 128),
('PROPRIETAIRE', 129),
('PROPRIETAIRE', 130),
('PROPRIETAIRE', 131),
('PROPRIETAIRE', 132),
('PROPRIETAIRE', 133),
('PROPRIETAIRE', 134),
('PROPRIETAIRE', 135),
('PROPRIETAIRE', 136),
('PROPRIETAIRE', 137),
('PROPRIETAIRE', 138),
('PROPRIETAIRE', 142),
('PROPRIETAIRE', 147),
('PROPRIETAIRE', 148),
('PROPRIETAIRE', 149),
('PROPRIETAIRE', 150),
('PROPRIETAIRE', 151),
('VENDEUR', 1),
('VENDEUR', 4),
('VENDEUR', 8),
('VENDEUR', 9),
('VENDEUR', 10),
('VENDEUR', 19),
('VENDEUR', 20),
('VENDEUR', 23),
('VENDEUR', 24),
('VENDEUR', 25),
('VENDEUR', 26),
('VENDEUR', 48),
('VENDEUR', 49),
('VENDEUR', 142);

-- --------------------------------------------------------

--
-- Structure de la table `sequences`
--

CREATE TABLE `sequences` (
  `cle` varchar(100) NOT NULL,
  `valeur` bigint(20) NOT NULL DEFAULT 0,
  `date_maj` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sequences`
--

INSERT INTO `sequences` (`cle`, `valeur`, `date_maj`) VALUES
('numero_facture:20260901', 1, '2026-09-01 17:44:26');

-- --------------------------------------------------------

--
-- Structure de la table `stock_magasins`
--

CREATE TABLE `stock_magasins` (
  `id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT 0.00,
  `stock_alerte` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_magasins`
--

INSERT INTO `stock_magasins` (`id`, `magasin_id`, `article_id`, `quantite`, `valeur_stock`, `stock_alerte`) VALUES
(1, 1, 1, 30, 0.00, 5);

-- --------------------------------------------------------

--
-- Structure de la table `stock_matieres_premieres`
--

CREATE TABLE `stock_matieres_premieres` (
  `id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `quantite` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `valeur_stock` decimal(14,2) NOT NULL DEFAULT 0.00,
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stock_produits_finis_usine`
--

CREATE TABLE `stock_produits_finis_usine` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `production_id` int(11) DEFAULT NULL,
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tranches_tarifaires`
--

CREATE TABLE `tranches_tarifaires` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `article_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = règle globale applicable à tous les articles',
  `categorie_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = pas de filtre catégorie',
  `qte_min` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `qte_max` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = pas de limite supérieure',
  `mode_calcul` enum('majoration_pct','marge_pct','prix_fixe') NOT NULL DEFAULT 'majoration_pct',
  `source_cout` enum('ACHAT_FOURNISSEUR','PRODUCTION_INTERNE') NOT NULL DEFAULT 'ACHAT_FOURNISSEUR',
  `valeur` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Taux ou montant fixe selon mode_calcul',
  `priorite` int(11) NOT NULL DEFAULT 0 COMMENT 'Plus élevé = prioritaire en cas de chevauchement',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tranches_tarifaires`
--

INSERT INTO `tranches_tarifaires` (`id`, `nom`, `article_id`, `categorie_id`, `qte_min`, `qte_max`, `mode_calcul`, `source_cout`, `valeur`, `priorite`, `actif`, `date_debut`, `date_fin`, `date_creation`, `date_modification`) VALUES
(3, 'Defaut 50-199 unites', NULL, NULL, 50, 199, 'majoration_pct', 'ACHAT_FOURNISSEUR', 30.0000, 0, 1, NULL, NULL, '2026-09-04 10:30:00', '2026-09-04 10:30:00'),
(4, 'Defaut 200-499 unites', NULL, NULL, 200, 499, 'majoration_pct', 'ACHAT_FOURNISSEUR', 25.0000, 0, 1, NULL, NULL, '2026-09-04 10:30:00', '2026-09-04 10:30:00'),
(5, 'Defaut 500+ unites', NULL, NULL, 500, NULL, 'majoration_pct', 'ACHAT_FOURNISSEUR', 20.0000, 0, 1, NULL, NULL, '2026-09-04 10:30:00', '2026-09-04 10:30:00');

-- --------------------------------------------------------

--
-- Structure de la table `transferts_stock`
--

CREATE TABLE `transferts_stock` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `magasin_source_id` int(11) NOT NULL,
  `magasin_destination_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_transfert` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `date_attribution` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `date_attribution`) VALUES
(1, 1, '2026-09-04 14:52:47'),
(3, 4, '2026-09-04 14:52:47'),
(7, 3, '2026-09-04 14:52:47'),
(10, 2, '2026-09-04 14:52:47');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `login` varchar(60) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `totp_secret` varchar(32) DEFAULT NULL,
  `totp_actif` tinyint(1) NOT NULL DEFAULT 0,
  `google_id` varchar(64) DEFAULT NULL,
  `google_email` varchar(255) DEFAULT NULL,
  `google_avatar` varchar(500) DEFAULT NULL,
  `magasin_id` int(11) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `login`, `mot_de_passe`, `role_id`, `totp_secret`, `totp_actif`, `google_id`, `google_email`, `google_avatar`, `magasin_id`, `actif`, `date_creation`) VALUES
(1, 'Directrice', 'directeur', '$2y$10$aZJiIFrCmjsIjXDJI9p2G.4R.gLH2ElDdsk/ZrgFkE7dXzny32TJC', 1, NULL, 0, NULL, NULL, NULL, 1, 1, '2026-07-01 16:51:22'),
(3, 'Vendeur Comptoir', 'vendeur', '$2y$10$fIRsNoDrYmOyvUYOovJ6peHj1c6kLDl6lrbgMTndFlT9mI78MaBLK', 4, NULL, 0, NULL, NULL, NULL, 1, 1, '2026-07-01 16:51:22'),
(7, 'Magasinier', 'magasinier', '$2y$10$OhLg67qKu4bbjAE0L36zQubBcb19RcBLYKQzREEUPVB9Zczg.TOnW', 3, NULL, 0, NULL, NULL, NULL, 1, 1, '2026-08-12 09:15:55'),
(10, 'Administrateur', 'admin', '$2y$10$4DPz6CSMQlFmS2TH3xS/ZO7DwhkMOfO4tN4mKGKZol7sLHqgijnj6', 2, NULL, 0, NULL, NULL, NULL, 1, 1, '2026-08-22 22:50:07');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `archives_caisse`
--
ALTER TABLE `archives_caisse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_arch_magasin` (`magasin_id`),
  ADD KEY `idx_arch_periode` (`periode_debut`,`periode_fin`),
  ADD KEY `idx_arch_statut` (`statut`);

--
-- Index pour la table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_barre` (`code_barre`),
  ADD KEY `idx_art_code` (`code_barre`),
  ADD KEY `idx_art_nom` (`nom`),
  ADD KEY `fk_art_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_art_low_stock` (`actif`,`quantite_stock`,`seuil_alerte`),
  ADD KEY `idx_art_fournisseur_prix_ref` (`fournisseur_prix_ref_id`),
  ADD KEY `idx_art_type` (`type_article`);

--
-- Index pour la table `article_couts`
--
ALTER TABLE `article_couts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_acc_article` (`article_id`,`magasin_id`),
  ADD KEY `idx_acc_date` (`date_entree`),
  ADD KEY `idx_ac_fifo` (`article_id`,`quantite`,`date_entree`);

--
-- Index pour la table `article_lots`
--
ALTER TABLE `article_lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_lot_article_magasin` (`article_id`,`magasin_id`,`numero_lot`),
  ADD KEY `idx_lot_fefo` (`article_id`,`magasin_id`,`date_peremption`,`quantite`),
  ADD KEY `idx_lot_magasin` (`magasin_id`,`date_peremption`),
  ADD KEY `idx_lot_article` (`article_id`,`magasin_id`),
  ADD KEY `idx_lot_alerte` (`date_peremption`,`quantite`,`magasin_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories_matieres_premieres`
--
ALTER TABLE `categories_matieres_premieres`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_client_code_fidelite` (`code_fidelite`),
  ADD KEY `idx_client_email` (`email`),
  ADD KEY `idx_client_nom` (`nom`),
  ADD KEY `idx_client_anonymise` (`anonymise`),
  ADD KEY `idx_client_dernier_achat` (`date_dernier_achat`);

--
-- Index pour la table `clotures_caisse`
--
ALTER TABLE `clotures_caisse`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cloture_jour` (`magasin_id`,`utilisateur_id`,`date_cloture`),
  ADD KEY `fk_cloture_user` (`utilisateur_id`),
  ADD KEY `idx_cloture_date` (`date_cloture`),
  ADD KEY `idx_cloture_magasin` (`magasin_id`);

--
-- Index pour la table `commandes_fournisseur`
--
ALTER TABLE `commandes_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cf_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_cf_magasin` (`magasin_id`),
  ADD KEY `idx_cf_user` (`utilisateur_id`),
  ADD KEY `idx_cf_statut` (`statut`),
  ADD KEY `idx_cf_date_commande` (`date_commande`);

--
-- Index pour la table `consentements_log`
--
ALTER TABLE `consentements_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cons_client` (`client_id`),
  ADD KEY `idx_cons_date` (`date_action`),
  ADD KEY `idx_cl_client_type` (`client_id`,`type_consentement`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dep_magasin` (`magasin_id`),
  ADD KEY `idx_dep_categorie` (`categorie`),
  ADD KEY `idx_dep_date` (`date_depense`),
  ADD KEY `fk_dep_user` (`utilisateur_id`);

--
-- Index pour la table `emails_consentements`
--
ALTER TABLE `emails_consentements`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `uq_ec_token` (`token_desinscription`),
  ADD KEY `idx_ec_email` (`email`);

--
-- Index pour la table `emails_queue`
--
ALTER TABLE `emails_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eq_statut` (`statut`,`prochaine_tentative`),
  ADD KEY `idx_eq_date` (`date_creation`);

--
-- Index pour la table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_employe_matricule` (`matricule`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_facture` (`numero_facture`),
  ADD UNIQUE KEY `idx_fact_client_sale` (`client_sale_id`),
  ADD KEY `idx_fact_num` (`numero_facture`),
  ADD KEY `idx_fact_date` (`date_facture`),
  ADD KEY `fk_fact_user` (`utilisateur_id`),
  ADD KEY `idx_fact_date_statut` (`date_facture`,`statut`),
  ADD KEY `idx_fact_magasin` (`magasin_id`),
  ADD KEY `idx_fact_cloture` (`cloture_id`),
  ADD KEY `idx_fact_client` (`client_id`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fournisseur_nom` (`nom`);

--
-- Index pour la table `fournisseur_prix_historique`
--
ALTER TABLE `fournisseur_prix_historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fph_article` (`article_id`),
  ADD KEY `idx_fph_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_fph_actif` (`article_id`,`fournisseur_id`,`est_actif`),
  ADD KEY `idx_fph_date` (`date_debut`);

--
-- Index pour la table `historique_points`
--
ALTER TABLE `historique_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_hp_client_facture_type` (`client_id`,`facture_id`,`type_operation`),
  ADD KEY `idx_hp_client` (`client_id`),
  ADD KEY `idx_hp_date` (`date_operation`),
  ADD KEY `idx_hp_type_operation` (`type_operation`);

--
-- Index pour la table `inventaires`
--
ALTER TABLE `inventaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inv_ref` (`reference`),
  ADD KEY `fk_inv_user` (`utilisateur_id`),
  ADD KEY `idx_inv_magasin` (`magasin_id`),
  ADD KEY `idx_inv_statut` (`statut`);

--
-- Index pour la table `inventaire_lignes`
--
ALTER TABLE `inventaire_lignes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_invl_art` (`inventaire_id`,`article_id`),
  ADD KEY `fk_invl_article` (`article_id`),
  ADD KEY `idx_invl_inventaire` (`inventaire_id`);

--
-- Index pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lcf_commande` (`commande_id`),
  ADD KEY `idx_lcf_article` (`article_id`);

--
-- Index pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lf_facture` (`facture_id`),
  ADD KEY `fk_lf_article` (`article_id`);

--
-- Index pour la table `lignes_retour`
--
ALTER TABLE `lignes_retour`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lret_lfact` (`ligne_facture_id`),
  ADD KEY `fk_lret_article` (`article_id`),
  ADD KEY `idx_lret_retour` (`retour_id`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_la_ip` (`ip_address`),
  ADD KEY `idx_la_date` (`attempted_at`);

--
-- Index pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_la_user` (`utilisateur_id`),
  ADD KEY `idx_la_action` (`action`),
  ADD KEY `idx_la_date` (`date_action`);

--
-- Index pour la table `magasins`
--
ALTER TABLE `magasins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mag_type` (`type_magasin`);

--
-- Index pour la table `matieres_premieres`
--
ALTER TABLE `matieres_premieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mp_ref` (`reference`);

--
-- Index pour la table `mouvements_matieres_premieres`
--
ALTER TABLE `mouvements_matieres_premieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mmp_matiere` (`matiere_id`),
  ADD KEY `idx_mmp_type` (`type`),
  ADD KEY `idx_mmp_date` (`date_mouvement`);

--
-- Index pour la table `mouvements_produits_finis`
--
ALTER TABLE `mouvements_produits_finis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mpf_article` (`article_id`),
  ADD KEY `idx_mpf_type` (`type`),
  ADD KEY `idx_mpf_date` (`date_mouvement`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mvt_art` (`article_id`),
  ADD KEY `idx_mvt_type` (`type`),
  ADD KEY `idx_mvt_date` (`date_mouvement`),
  ADD KEY `fk_mvt_user` (`utilisateur_id`),
  ADD KEY `idx_mvt_magasin` (`magasin_id`);

--
-- Index pour la table `paiements_facture`
--
ALTER TABLE `paiements_facture`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pf_facture` (`facture_id`),
  ADD KEY `idx_pf_mode` (`mode_paiement`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`),
  ADD KEY `idx_par_cat` (`categorie`),
  ADD KEY `idx_par_cle` (`cle`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle_permission` (`cle_permission`),
  ADD KEY `idx_perm_cle` (`cle_permission`),
  ADD KEY `idx_perm_cat` (`categorie`);

--
-- Index pour la table `pertes_fournisseur`
--
ALTER TABLE `pertes_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pf_reception` (`reception_id`),
  ADD KEY `idx_pf_article` (`article_id`),
  ADD KEY `idx_pf_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_pf_magasin` (`magasin_id`),
  ADD KEY `idx_pf_date` (`date_perte`);

--
-- Index pour la table `presences_employes`
--
ALTER TABLE `presences_employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_presence_employe_date` (`employe_id`,`date_presence`),
  ADD KEY `idx_pres_date` (`date_presence`);

--
-- Index pour la table `presences_employes_audit`
--
ALTER TABLE `presences_employes_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pa_presence` (`presence_id`),
  ADD KEY `idx_pa_employe` (`employe_id`);

--
-- Index pour la table `productions`
--
ALTER TABLE `productions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prod_ref` (`reference`),
  ADD KEY `idx_prod_article` (`article_id`),
  ADD KEY `idx_prod_recette` (`recette_id`),
  ADD KEY `idx_prod_statut` (`statut`),
  ADD KEY `idx_prod_date` (`date_prevue`);

--
-- Index pour la table `production_employes`
--
ALTER TABLE `production_employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prod_emp` (`production_id`,`employe_id`),
  ADD KEY `idx_pe_employe` (`employe_id`);

--
-- Index pour la table `production_lots`
--
ALTER TABLE `production_lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_plot_numero` (`numero_lot`),
  ADD KEY `idx_plot_production` (`production_id`),
  ADD KEY `idx_plot_article` (`article_id`),
  ADD KEY `idx_plot_magasin` (`magasin_id`);

--
-- Index pour la table `production_matieres`
--
ALTER TABLE `production_matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prod_matiere` (`production_id`,`matiere_id`),
  ADD KEY `idx_pm_production` (`production_id`),
  ADD KEY `idx_pm_matiere` (`matiere_id`);

--
-- Index pour la table `production_pertes`
--
ALTER TABLE `production_pertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ppert_production` (`production_id`),
  ADD KEY `idx_ppert_article` (`article_id`);

--
-- Index pour la table `production_produits`
--
ALTER TABLE `production_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pprod_production` (`production_id`),
  ADD KEY `idx_pprod_article` (`article_id`);

--
-- Index pour la table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_code_promo` (`code_promo`),
  ADD KEY `fk_promo_article` (`article_id`),
  ADD KEY `fk_promo_categorie` (`categorie_id`),
  ADD KEY `idx_promo_code` (`code_promo`),
  ADD KEY `idx_promo_dates` (`date_debut`,`date_fin`),
  ADD KEY `idx_promo_actif` (`actif`);

--
-- Index pour la table `receptions`
--
ALTER TABLE `receptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_reception_reference` (`reference`),
  ADD KEY `idx_reception_commande` (`commande_id`),
  ADD KEY `idx_reception_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_reception_magasin` (`magasin_id`),
  ADD KEY `idx_reception_statut` (`statut`);

--
-- Index pour la table `reception_lignes`
--
ALTER TABLE `reception_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rl_reception` (`reception_id`),
  ADD KEY `idx_rl_ligne_commande` (`ligne_commande_id`),
  ADD KEY `idx_rl_article` (`article_id`);

--
-- Index pour la table `recettes`
--
ALTER TABLE `recettes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_recette_article_version` (`article_id`,`version`),
  ADD KEY `idx_rec_article` (`article_id`),
  ADD KEY `idx_rec_actif` (`actif`);

--
-- Index pour la table `recettes_lignes`
--
ALTER TABLE `recettes_lignes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_recette_ligne` (`recette_id`,`matiere_id`),
  ADD KEY `idx_rl_recette` (`recette_id`),
  ADD KEY `idx_rl_matiere` (`matiere_id`);

--
-- Index pour la table `regles_promotions`
--
ALTER TABLE `regles_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promo_condition` (`condition_type`,`actif`);

--
-- Index pour la table `retours_factures`
--
ALTER TABLE `retours_factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ret_num` (`numero_retour`),
  ADD KEY `fk_ret_user` (`utilisateur_id`),
  ADD KEY `idx_ret_facture` (`facture_id`),
  ADD KEY `idx_ret_magasin` (`magasin_id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_code` (`code`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_nom`,`permission_id`),
  ADD KEY `fk_rp_permission` (`permission_id`);

--
-- Index pour la table `sequences`
--
ALTER TABLE `sequences`
  ADD PRIMARY KEY (`cle`);

--
-- Index pour la table `stock_magasins`
--
ALTER TABLE `stock_magasins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_stock_mag_art` (`magasin_id`,`article_id`),
  ADD KEY `idx_sm_magasin` (`magasin_id`),
  ADD KEY `idx_sm_article` (`article_id`);

--
-- Index pour la table `stock_matieres_premieres`
--
ALTER TABLE `stock_matieres_premieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_smp_matiere` (`matiere_id`);

--
-- Index pour la table `stock_produits_finis_usine`
--
ALTER TABLE `stock_produits_finis_usine`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_spfu_article` (`article_id`);

--
-- Index pour la table `tranches_tarifaires`
--
ALTER TABLE `tranches_tarifaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tt_article` (`article_id`),
  ADD KEY `idx_tt_categorie` (`categorie_id`),
  ADD KEY `idx_tt_actif` (`actif`),
  ADD KEY `idx_tt_qte` (`qte_min`,`qte_max`);

--
-- Index pour la table `transferts_stock`
--
ALTER TABLE `transferts_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tr_date` (`date_transfert`),
  ADD KEY `idx_tr_art` (`article_id`),
  ADD KEY `idx_tr_src` (`magasin_source_id`),
  ADD KEY `idx_tr_dst` (`magasin_destination_id`),
  ADD KEY `fk_tr_user` (`utilisateur_id`);

--
-- Index pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_ur_role` (`role_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `idx_user_google_email` (`google_email`),
  ADD KEY `idx_user_magasin` (`magasin_id`),
  ADD KEY `idx_user_google_id` (`google_id`),
  ADD KEY `fk_user_role_id` (`role_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `archives_caisse`
--
ALTER TABLE `archives_caisse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `article_couts`
--
ALTER TABLE `article_couts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `article_lots`
--
ALTER TABLE `article_lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `categories_matieres_premieres`
--
ALTER TABLE `categories_matieres_premieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `clotures_caisse`
--
ALTER TABLE `clotures_caisse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes_fournisseur`
--
ALTER TABLE `commandes_fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consentements_log`
--
ALTER TABLE `consentements_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `emails_queue`
--
ALTER TABLE `emails_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `employes`
--
ALTER TABLE `employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fournisseur_prix_historique`
--
ALTER TABLE `fournisseur_prix_historique`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_points`
--
ALTER TABLE `historique_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inventaires`
--
ALTER TABLE `inventaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `inventaire_lignes`
--
ALTER TABLE `inventaire_lignes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `lignes_retour`
--
ALTER TABLE `lignes_retour`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `magasins`
--
ALTER TABLE `magasins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `matieres_premieres`
--
ALTER TABLE `matieres_premieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_matieres_premieres`
--
ALTER TABLE `mouvements_matieres_premieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_produits_finis`
--
ALTER TABLE `mouvements_produits_finis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `paiements_facture`
--
ALTER TABLE `paiements_facture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT pour la table `pertes_fournisseur`
--
ALTER TABLE `pertes_fournisseur`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presences_employes`
--
ALTER TABLE `presences_employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presences_employes_audit`
--
ALTER TABLE `presences_employes_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `production_employes`
--
ALTER TABLE `production_employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `production_lots`
--
ALTER TABLE `production_lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `production_matieres`
--
ALTER TABLE `production_matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `production_pertes`
--
ALTER TABLE `production_pertes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `production_produits`
--
ALTER TABLE `production_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `receptions`
--
ALTER TABLE `receptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reception_lignes`
--
ALTER TABLE `reception_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `recettes`
--
ALTER TABLE `recettes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `recettes_lignes`
--
ALTER TABLE `recettes_lignes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `regles_promotions`
--
ALTER TABLE `regles_promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `retours_factures`
--
ALTER TABLE `retours_factures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `stock_magasins`
--
ALTER TABLE `stock_magasins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `stock_matieres_premieres`
--
ALTER TABLE `stock_matieres_premieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stock_produits_finis_usine`
--
ALTER TABLE `stock_produits_finis_usine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tranches_tarifaires`
--
ALTER TABLE `tranches_tarifaires`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `transferts_stock`
--
ALTER TABLE `transferts_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `fk_art_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `article_lots`
--
ALTER TABLE `article_lots`
  ADD CONSTRAINT `fk_lot_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lot_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `clotures_caisse`
--
ALTER TABLE `clotures_caisse`
  ADD CONSTRAINT `fk_cloture_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`),
  ADD CONSTRAINT `fk_cloture_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `fk_dep_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dep_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `fk_fact_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fact_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facture_cloture` FOREIGN KEY (`cloture_id`) REFERENCES `clotures_caisse` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `inventaires`
--
ALTER TABLE `inventaires`
  ADD CONSTRAINT `fk_inv_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`),
  ADD CONSTRAINT `fk_inv_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `inventaire_lignes`
--
ALTER TABLE `inventaire_lignes`
  ADD CONSTRAINT `fk_invl_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_invl_inventaire` FOREIGN KEY (`inventaire_id`) REFERENCES `inventaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD CONSTRAINT `fk_lcf_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseur` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  ADD CONSTRAINT `fk_lf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lf_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `lignes_retour`
--
ALTER TABLE `lignes_retour`
  ADD CONSTRAINT `fk_lret_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_lret_lfact` FOREIGN KEY (`ligne_facture_id`) REFERENCES `lignes_facture` (`id`),
  ADD CONSTRAINT `fk_lret_ret` FOREIGN KEY (`retour_id`) REFERENCES `retours_factures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `fk_la_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `mouvements_matieres_premieres`
--
ALTER TABLE `mouvements_matieres_premieres`
  ADD CONSTRAINT `fk_mmp_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `mouvements_produits_finis`
--
ALTER TABLE `mouvements_produits_finis`
  ADD CONSTRAINT `fk_mpf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `fk_mvt_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mvt_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mvt_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiements_facture`
--
ALTER TABLE `paiements_facture`
  ADD CONSTRAINT `fk_pf_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `pertes_fournisseur`
--
ALTER TABLE `pertes_fournisseur`
  ADD CONSTRAINT `fk_pf_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_pf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_pf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`);

--
-- Contraintes pour la table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `fk_promo_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_promo_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `receptions`
--
ALTER TABLE `receptions`
  ADD CONSTRAINT `fk_reception_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseur` (`id`),
  ADD CONSTRAINT `fk_reception_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_reception_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`);

--
-- Contraintes pour la table `reception_lignes`
--
ALTER TABLE `reception_lignes`
  ADD CONSTRAINT `fk_rl_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_rl_ligne_commande` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande_fournisseur` (`id`),
  ADD CONSTRAINT `fk_rl_reception` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `retours_factures`
--
ALTER TABLE `retours_factures`
  ADD CONSTRAINT `fk_ret_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`),
  ADD CONSTRAINT `fk_ret_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`),
  ADD CONSTRAINT `fk_ret_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `stock_magasins`
--
ALTER TABLE `stock_magasins`
  ADD CONSTRAINT `fk_sm_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `stock_matieres_premieres`
--
ALTER TABLE `stock_matieres_premieres`
  ADD CONSTRAINT `fk_smp_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_premieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `stock_produits_finis_usine`
--
ALTER TABLE `stock_produits_finis_usine`
  ADD CONSTRAINT `fk_spfu_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `transferts_stock`
--
ALTER TABLE `transferts_stock`
  ADD CONSTRAINT `fk_tr_art` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_dst` FOREIGN KEY (`magasin_destination_id`) REFERENCES `magasins` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_src` FOREIGN KEY (`magasin_source_id`) REFERENCES `magasins` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tr_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `equipes`
--
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_eq_chef` FOREIGN KEY (`chef_equipe_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `user_equipes`
--
ALTER TABLE `user_equipes`
  ADD CONSTRAINT `fk_ue_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ue_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `equipe_magasins`
--
ALTER TABLE `equipe_magasins`
  ADD CONSTRAINT `fk_em_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_em_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_role_code` FOREIGN KEY (`role_nom`) REFERENCES `roles` (`code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_user_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `production_pertes`
--
ALTER TABLE `production_pertes`
  ADD CONSTRAINT `fk_pp_categorie` FOREIGN KEY (`categorie_perte_id`) REFERENCES `categories_pertes_production` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Structure de la table `usines`
--

CREATE TABLE `usines` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_magasins`
--

CREATE TABLE `user_magasins` (
  `user_id` int(11) NOT NULL,
  `magasin_id` int(11) NOT NULL,
  `date_debut` datetime NOT NULL DEFAULT current_timestamp(),
  `date_fin` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipe_membres`
--

CREATE TABLE `equipe_membres` (
  `equipe_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_debut` datetime NOT NULL DEFAULT current_timestamp(),
  `date_fin` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `unites_mesure`
--

CREATE TABLE `unites_mesure` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('MASSIQUE','VOLUMIQUE','UNITE','LONGUEUR','AUTRE') NOT NULL DEFAULT 'UNITE',
  `facteur_conversion` decimal(14,6) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données de la table `usines`
--

INSERT INTO `usines` (`id`, `code`, `nom`, `description`, `adresse`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'USINE-1', 'Usine Principale', 'Migrated from magasins type USINE', NULL, 1, '2026-09-10 00:00:00', '2026-09-10 00:00:00');

--
-- Données de la table `unites_mesure`
--

INSERT INTO `unites_mesure` (`id`, `code`, `nom`, `categorie`, `facteur_conversion`, `actif`) VALUES
(1, 'KG', 'Kilogramme', 'MASSIQUE', 1.000000, 1),
(2, 'G', 'Gramme', 'MASSIQUE', 0.001000, 1),
(3, 'L', 'Litre', 'VOLUMIQUE', 1.000000, 1),
(4, 'ML', 'Millilitre', 'VOLUMIQUE', 0.001000, 1),
(5, 'UNITE', 'Unité', 'UNITE', 1.000000, 1),
(6, 'CARTON', 'Carton', 'UNITE', NULL, 1),
(7, 'SAC', 'Sac', 'UNITE', NULL, 1),
(8, 'BOTTE', 'Botte', 'UNITE', NULL, 1),
(9, 'CAISSE', 'Caisse', 'UNITE', NULL, 1);

--
-- Index pour les nouvelles tables
--

ALTER TABLE `usines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usine_code` (`code`);

ALTER TABLE `user_magasins`
  ADD PRIMARY KEY (`user_id`, `magasin_id`),
  ADD KEY `fk_um_magasin` (`magasin_id`);

ALTER TABLE `equipe_membres`
  ADD PRIMARY KEY (`equipe_id`, `user_id`),
  ADD KEY `fk_emb_user` (`user_id`);

ALTER TABLE `unites_mesure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_unite_code` (`code`);

--
-- Index supplémentaires pour mouvements_stock
--

ALTER TABLE `mouvements_stock`
  ADD KEY `idx_mvt_art_mag_date` (`article_id`, `magasin_id`, `date_mouvement`),
  ADD KEY `idx_mvt_ref` (`reference_type`, `reference_id`);

--
-- Contraintes pour les nouvelles tables
--

ALTER TABLE `user_magasins`
  ADD CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_um_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `equipe_membres`
  ADD CONSTRAINT `fk_emb_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_emb_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Contraintes pour la table `commandes_fournisseur`
--
ALTER TABLE `commandes_fournisseur`
  ADD CONSTRAINT `fk_cf_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cf_magasin` FOREIGN KEY (`magasin_id`) REFERENCES `magasins` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cf_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
