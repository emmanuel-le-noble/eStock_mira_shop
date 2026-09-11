-- ============================================================
-- CLEAN + RESEED DEMO - eStock v2.7.0
-- Version 2 : utilise des sous-requetes pour resoudre les IDs
-- Date : 11 septembre 2026
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET foreign_key_checks = 0;

-- ============================================================
-- PHASE 0 : NETTOYAGE (ordre inverse des dependances FK)
-- Note: triggers immutable empechent DELETE sur factures/paiements/lignes.
-- On utilise des UPDATE pour contourner les triggers avant suppression.
-- ============================================================

-- Desactiver temporairement les triggers en supprimant les lignes enfant d'abord
DELETE FROM `paiements_credit` WHERE `creance_id` IN (SELECT id FROM `creances_clients` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%'));
DELETE FROM `creances_clients` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%');
DELETE FROM `lignes_retour` WHERE `retour_id` IN (SELECT id FROM `retours_factures` WHERE numero_retour LIKE 'DEMO-RET%');
DELETE FROM `retours_factures` WHERE numero_retour LIKE 'DEMO-RET%';

-- Les triggers bloquent DELETE sur factures/paiements_facture/lignes_facture/receptions.
-- On utilise DROP TRIGGER temporairement.
DROP TRIGGER IF EXISTS `trg_paiements_immutable_delete`;
DROP TRIGGER IF EXISTS `trg_paiements_immutable_update`;
DROP TRIGGER IF EXISTS `trg_lignes_facture_immutable_delete`;
DROP TRIGGER IF EXISTS `trg_lignes_facture_immutable_update`;
DROP TRIGGER IF EXISTS `trg_factures_immutable_delete`;
DROP TRIGGER IF EXISTS `trg_factures_immutable_update`;
DROP TRIGGER IF EXISTS `trg_receptions_immutable_delete`;
DROP TRIGGER IF EXISTS `trg_receptions_immutable_update`;
DROP TRIGGER IF EXISTS `trg_clotures_immutable_delete`;
DROP TRIGGER IF EXISTS `trg_clotures_immutable_update`;

DELETE FROM `paiements_facture` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%');
DELETE FROM `lignes_facture` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%');
DELETE FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%';
DELETE FROM `notifications` WHERE message LIKE '[DEMO]%';
DELETE FROM `production_pertes` WHERE `production_id` IN (SELECT id FROM `productions` WHERE reference LIKE 'DEMO-PROD%');
DELETE FROM `production_matieres` WHERE `production_id` IN (SELECT id FROM `productions` WHERE reference LIKE 'DEMO-PROD%');
DELETE FROM `productions` WHERE reference LIKE 'DEMO-PROD%';
DELETE FROM `recettes_lignes` WHERE `recette_id` IN (SELECT id FROM `recettes` WHERE nom LIKE '[DEMO]%');
DELETE FROM `recettes` WHERE nom LIKE '[DEMO]%';
DELETE FROM `reception_lignes` WHERE `reception_id` IN (SELECT id FROM `receptions` WHERE reference LIKE 'DEMO-REC%');
DELETE FROM `receptions` WHERE reference LIKE 'DEMO-REC%';
DELETE FROM `lignes_commande_fournisseur` WHERE `commande_id` IN (SELECT id FROM `commandes_fournisseur` WHERE notes LIKE '[DEMO]%');
DELETE FROM `commandes_fournisseur` WHERE notes LIKE '[DEMO]%';
DELETE FROM `pertes_fournisseur` WHERE `commentaire` LIKE '[DEMO]%';
DELETE FROM `fournisseur_prix_historique` WHERE `source` = 'manuelle' AND `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%');
DELETE FROM `fournisseur_prix_historique` WHERE `source` = 'commande' AND `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%');
DELETE FROM `transferts_stock` WHERE motif LIKE '[DEMO]%';
DELETE FROM `mouvements_stock` WHERE motif LIKE '[DEMO]%' OR motif LIKE 'Production DEMO%' OR motif LIKE 'Reception commande DEMO%' OR motif LIKE 'Vente facture DEMO%' OR motif LIKE 'Perte fournisseur DEMO%';
DELETE FROM `article_couts` WHERE `reference` LIKE 'DEMO-CMD%';
DELETE FROM `article_lots` WHERE `numero_lot` LIKE 'LOT-DEMO%';
DELETE FROM `stock_magasins` WHERE `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%');
DELETE FROM `stock_produits_finis_usine` WHERE `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%');
DELETE FROM `articles` WHERE nom LIKE '[DEMO]%';
DELETE FROM `matieres_premieres` WHERE reference LIKE 'DEMP-%';
DELETE FROM `stock_matieres_premieres`;
DELETE FROM `machines` WHERE nom LIKE '[DEMO]%';
DELETE FROM `machine_etats` WHERE `machine_id` NOT IN (SELECT id FROM `machines`);
DELETE FROM `presences_employes` WHERE `employe_id` IN (SELECT id FROM `employes` WHERE matricule LIKE 'DEMP-EMP%');
DELETE FROM `employes` WHERE matricule LIKE 'DEMP-EMP%';
DELETE FROM `depenses` WHERE titre LIKE '[DEMO]%';
DELETE FROM `logs_activite` WHERE action = 'SEED_DEMO';
DELETE FROM `clotures_caisse` WHERE `utilisateur_id` = 3 AND `date_cloture` >= '2026-09-01';
DELETE FROM `equipe_magasins` WHERE `equipe_id` IN (SELECT id FROM `equipes` WHERE nom LIKE '[DEMO]%');
DELETE FROM `equipe_membres` WHERE `equipe_id` IN (SELECT id FROM `equipes` WHERE nom LIKE '[DEMO]%');
DELETE FROM `equipes` WHERE nom LIKE '[DEMO]%';
DELETE FROM `user_magasins` WHERE `user_id` IN (1,3,7,10) AND `date_debut` > '2026-08-01';
DELETE FROM `promotions` WHERE nom LIKE '[DEMO]%';
DELETE FROM `regles_promotions` WHERE nom LIKE '[DEMO]%';
DELETE FROM `usines` WHERE nom LIKE '[DEMO]%';
DELETE FROM `clients` WHERE nom LIKE '[DEMO]%';
DELETE FROM `fournisseurs` WHERE nom LIKE '[DEMO]%';
DELETE FROM `categories` WHERE nom LIKE '[DEMO]%';
DELETE FROM `horaires_travail` WHERE nom LIKE '[DEMO]%';
DELETE FROM `sequences` WHERE cle IN ('facture_numero', 'commande_fournisseur', 'reception_reference', 'retour_numero', 'inventaire_reference', 'production_reference');
DELETE FROM `parametres` WHERE cle = 'DEMO_MODE';

-- ============================================================
-- PHASE 1 : ENTITÉS DE BASE (pas de FK dependante)
-- ============================================================

-- Categories
INSERT INTO `categories` (`nom`, `description`, `actif`) VALUES
('[DEMO] Boissons', 'Eaux, jus, sodas, boissons energisantes', 1),
('[DEMO] Alimentaire sec', 'Riz, pates, farines, huiles, epices', 1),
('[DEMO] Hygiene', 'Savons, shampoings, detergents', 1),
('[DEMO] Électronique', 'Telephones, accessoires, cables', 1),
('[DEMO] Textile', 'Vetements, tissus, chaussures', 1),
('[DEMO] Entretien', 'Produits menagers, detergents', 1),
('[DEMO] Tabac', 'Cigarettes, allumettes', 1),
('[DEMO] Bricolage', 'Outillage, peinture, visserie', 1);

-- Fournisseurs
INSERT INTO `fournisseurs` (`nom`, `contact`, `telephone`, `devise`) VALUES
('[DEMO] Sen Distribution', 'Mamadou Diallo', '+221 77 123 4567', 'XOF'),
('[DEMO] Palm CI', 'Ibrahim Kouassi', '+225 07 08 09 10', 'XOF'),
('[DEMO] Agro-Alim SA', 'Fatou Sow', '+221 78 234 5678', 'XOF'),
('[DEMO] TechImport', 'Jean-Pierre Mensah', '+228 90 12 34 56', 'XOF'),
('[DEMO] TextPro', 'Aissatou Ba', '+221 76 345 6789', 'XOF'),
('[DEMO] Cosmetique Plus', 'Oumar Ndiaye', '+221 70 456 7890', 'XOF');

-- Clients
INSERT INTO `clients` (`nom`, `raison_sociale`, `nif`, `rccm`, `siret`, `adresse`, `telephone`, `email`, `code_fidelite`, `points_fidelite`, `consentement_fidelite`, `date_consentement`, `source_consentement`, `credit_autorise`, `limite_credit`, `date_dernier_achat`) VALUES
('[DEMO] Aminata Toure', NULL, NULL, NULL, NULL, 'Rue 12, Cocody', '+221 77 111 2233', 'aminata.demo@test.com', 'DEM-CLI-001', 250, 1, NOW(), 'boutique', 0, 0.00, '2026-09-10 14:30:00'),
('[DEMO] Moussa Konate', 'Konate & Fils SARL', 'NIF-98765', 'RCCM-1234', NULL, 'Bd de la Republique, Abidjan', '+222 33 445 566', 'moussa.demo@test.com', 'DEM-CLI-002', 80, 1, NOW(), 'boutique', 1, 500000.00, '2026-09-09 10:15:00'),
('[DEMO] Fatima Sy', NULL, NULL, NULL, NULL, 'Quartier HLM, Dakar', '+221 78 777 888', NULL, 'DEM-CLI-003', 0, 0, NULL, NULL, 0, 0.00, NULL),
('[DEMO] Societe Com-Plus', 'Com-Plus SARL', 'NIF-54321', 'RCCM-5678', 'SIRET-67890', 'Zone Industrielle, Bamako', '+223 76 901 234', 'contact@complus-demo.com', 'DEM-CLI-004', 1200, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), 'web', 1, 2000000.00, '2026-09-11 08:00:00'),
('[DEMO] Ibrahim Bamba', NULL, NULL, NULL, NULL, 'Rue Felix Faure', '+221 70 222 333', NULL, 'DEM-CLI-005', 45, 1, NOW(), 'caisse', 0, 0.00, '2026-09-08 16:45:00'),
('[DEMO] COGITEC SARL', 'COGITEC', 'NIF-11111', 'RCCM-2222', NULL, 'Plateau, Abidjan', '+222 21 333 444', 'cogitec.demo@test.ci', 'DEM-CLI-006', 500, 1, DATE_SUB(NOW(), INTERVAL 30 DAY), 'web', 1, 3500000.00, '2026-09-07 11:20:00'),
('[DEMO] Awa Diop', NULL, NULL, NULL, NULL, 'Medina, Dakar', '+221 76 444 555', NULL, 'DEM-CLI-007', 15, 0, NULL, NULL, 0, 0.00, '2026-09-10 09:00:00'),
('[DEMO] Boulangerie Doree', 'Doree SARL', 'NIF-66666', 'RCCM-7777', NULL, 'Rue de Commerce', '+221 77 888 999', 'doree.demo@test.com', 'DEM-CLI-008', 320, 1, DATE_SUB(NOW(), INTERVAL 15 DAY), 'boutique', 1, 750000.00, '2026-09-11 07:30:00'),
('[DEMO] Ousmane Fall', NULL, NULL, NULL, NULL, 'Grand Yoff', '+221 78 000 111', NULL, 'DEM-CLI-009', 0, 0, NULL, NULL, 0, 0.00, NULL),
('[DEMO] Trans Sahel', 'Trans Sahel Transport', 'NIF-88888', 'RCCM-9999', NULL, 'Route Nationale 1', '+223 79 222 333', 'transsahel.demo@test.ml', 'DEM-CLI-010', 890, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), 'web', 1, 5000000.00, '2026-09-06 15:00:00'),
('[DEMO] Khady Niang', NULL, NULL, NULL, NULL, 'Parcelles Assainies', '+221 70 555 666', NULL, 'DEM-CLI-011', 60, 1, NOW(), 'boutique', 0, 0.00, '2026-09-09 13:10:00'),
('[DEMO] Menuiserie Bois Prestige', 'Bois Prestige SARL', 'NIF-44444', 'RCCM-3333', NULL, 'Zone Artisanat', '+221 76 777 888', 'boisprestige.demo@test.com', 'DEM-CLI-012', 200, 1, DATE_SUB(NOW(), INTERVAL 45 DAY), 'boutique', 1, 1200000.00, '2026-09-10 16:20:00');

-- Usines
INSERT INTO `usines` (`code`, `nom`, `description`, `adresse`, `actif`) VALUES
('DEM-USINE-01', '[DEMO] Usine Yopougon', 'Unite de production principale - plasturgie et agroalimentaire', 'Zone Industrielle, Yopougon, Abidjan', 1),
('DEM-USINE-02', '[DEMO] Usine Abobo', 'Unite secondaire - conditionnement et emballage', 'Quartier Industriel, Abobo, Abidjan', 1);

-- Matieres premieres (categorie_id -> categories_matieres_premieres.id)
INSERT INTO `matieres_premieres` (`reference`, `nom`, `categorie_id`, `unite_mesure`, `cout_reference`, `stock_minimum`, `fournisseur_id`, `actif`, `notes`) VALUES
('DEMP-001', '[DEMO] Granule PEHD 001', 1, 'KG', 850.0000, 500, 2, 1, 'Polyethylene haute densite - granules noirs'),
('DEMP-002', '[DEMO] Granule PP 002', 1, 'KG', 780.0000, 500, 2, 1, 'Polypropylene - granules blancs'),
('DEMP-003', '[DEMO] Colorant Noir', 2, 'KG', 3200.0000, 50, 6, 1, 'Colorant noir concentre'),
('DEMP-004', '[DEMO] Colorant Bleu', 2, 'KG', 3500.0000, 50, 6, 1, 'Colorant bleu concentre'),
('DEMP-005', '[DEMO] Stabilisant UV', 2, 'KG', 5600.0000, 20, 6, 1, 'Stabilisant anti-UV'),
('DEMP-006', '[DEMO] Film etirable 30µ', 3, 'KG', 1200.0000, 200, 1, 1, 'Film etirable pour emballage palette'),
('DEMP-007', '[DEMO] Carton ondule 700g', 3, 'KG', 650.0000, 300, 1, 1, 'Carton ondule grands formats'),
('DEMP-008', '[DEMO] Colle neoprene', 4, 'L', 4500.0000, 30, 3, 1, 'Colle neoprene industrielle'),
('DEMP-009', '[DEMO] Huile moteur 15W40', 5, 'L', 2800.0000, 50, 3, 1, 'Huile moteur synthetique'),
('DEMP-010', '[DEMO] Sirop Baobab 5L', 6, 'L', 3500.0000, 100, 3, 1, 'Sirop de baobab concentre'),
('DEMP-011', '[DEMO] Pate tomate 2.5kg', 6, 'KG', 1800.0000, 150, 3, 1, 'Concentre de tomate'),
('DEMP-012', '[DEMO] Sel iode 1kg', 6, 'KG', 500.0000, 200, 3, 1, 'Sel de cuisine iode'),
('DEMP-013', '[DEMO] Cafe en grains 1kg', 6, 'KG', 6500.0000, 80, 3, 1, 'Cafe arabica torrefie'),
('DEMP-014', '[DEMO] Feuille alu 250g', 7, 'KG', 4800.0000, 60, 1, 1, 'Papier aluminium culinaire'),
('DEMP-015', '[DEMO] Mastic silicone', 8, 'L', 7200.0000, 25, 4, 1, 'Mastic silicone sanitaire');

-- Stock matieres premieres
INSERT INTO `stock_matieres_premieres` (`matiere_id`, `quantite`, `valeur_stock`)
SELECT mp.id, v.quantite, v.valeur_stock
FROM matieres_premieres mp
JOIN (
  SELECT 'DEMP-001' AS ref, 2500.0000 AS quantite, 2125000.00 AS valeur_stock UNION ALL
  SELECT 'DEMP-002', 1800.0000, 1404000.00 UNION ALL
  SELECT 'DEMP-003', 45.0000, 144000.00 UNION ALL
  SELECT 'DEMP-004', 30.0000, 105000.00 UNION ALL
  SELECT 'DEMP-005', 15.0000, 84000.00 UNION ALL
  SELECT 'DEMP-006', 180.0000, 216000.00 UNION ALL
  SELECT 'DEMP-007', 220.0000, 143000.00 UNION ALL
  SELECT 'DEMP-008', 12.0000, 54000.00 UNION ALL
  SELECT 'DEMP-009', 25.0000, 70000.00 UNION ALL
  SELECT 'DEMP-010', 80.0000, 280000.00 UNION ALL
  SELECT 'DEMP-011', 100.0000, 180000.00 UNION ALL
  SELECT 'DEMP-012', 150.0000, 75000.00 UNION ALL
  SELECT 'DEMP-013', 40.0000, 260000.00 UNION ALL
  SELECT 'DEMP-014', 30.0000, 144000.00 UNION ALL
  SELECT 'DEMP-015', 10.0000, 72000.00
) v ON mp.reference = v.ref;

-- Machines
INSERT INTO `machines` (`reference`, `nom`, `type`, `description`, `etat`, `actif`) VALUES
('DEM-MACH-01', '[DEMO] Injecteuse Engel 80T', 'Injecteur plastique', 'Injecteuse 80 tonnes - moules seaux et bacs', 'EN_FONCTIONNEMENT', 1),
('DEM-MACH-02', '[DEMO] Souffleuse Bekum 50L', 'Souffleuse', 'Souffleuse 50L - bouteilles et bidons', 'EN_FONCTIONNEMENT', 1),
('DEM-MACH-03', '[DEMO] Extrudeuse Cincinnati', 'Extrudeuse', 'Extrudeuse a film - production de sachets', 'EN_MAINTENANCE', 1),
('DEM-MACH-04', '[DEMO] Groupe froid Carrier', 'Refrigeration', 'Groupe froid frigorifique pour stockage matieres', 'ARRETEE', 1),
('DEM-MACH-05', '[DEMO] Pont rhone 5T', 'Manutention', 'Pont roulant 5 tonnes - atelier assemblage', 'EN_FONCTIONNEMENT', 1);

-- Employes
INSERT INTO `employes` (`matricule`, `nom`, `prenom`, `fonction`, `telephone`, `actif`) VALUES
('DEMP-EMP-01', 'Diallo', 'Moussa', 'Operateur machine', '+221 77 101 0001', 1),
('DEMP-EMP-02', 'Kone', 'Awa', 'Soudeuse plastique', '+221 77 101 0002', 1),
('DEMP-EMP-03', 'Ouedraogo', 'Ibrahim', 'Chef atelier', '+221 77 101 0003', 1),
('DEMP-EMP-04', 'Sow', 'Fatoumata', 'Controle qualite', '+221 77 101 0004', 1),
('DEMP-EMP-05', 'Toure', 'Amadou', 'Magasinier usine', '+221 77 101 0005', 1),
('DEMP-EMP-06', 'Bamba', 'Ousmane', 'Cariste', '+221 77 101 0006', 1),
('DEMP-EMP-07', 'Ndiaye', 'Khady', 'Agent d''entretien', '+221 77 101 0007', 1),
('DEMP-EMP-08', 'Fall', 'Cheikh', 'Technicien maintenance', '+221 77 101 0008', 1),
('DEMP-EMP-09', 'Ba', 'Mariama', 'Receptionniste', '+221 77 101 0009', 1),
('DEMP-EMP-10', 'Sy', 'Boubacar', 'Manutentionnaire', '+221 77 101 0010', 1);

-- ============================================================
-- PHASE 2 : ARTICLES (les IDs auto-incrementes seront resolus par sous-requete)
-- ============================================================

-- Articles - Produits finis (usine)
INSERT INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-001', '[DEMO] Seau plastique 20L', 'SKU-SEAU-20L', 1800.00, 1800.0000, 3500.00, 'UNITE', NULL, 250, 450000.00, 20, (SELECT id FROM categories WHERE nom='[DEMO] Bricolage'), 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-002', '[DEMO] Bac plastique 5L', 'SKU-BAC-5L', 850.00, 850.0000, 1700.00, 'UNITE', NULL, 180, 153000.00, 15, (SELECT id FROM categories WHERE nom='[DEMO] Bricolage'), 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-003', '[DEMO] Bouteille PEHD 1L', 'SKU-BTL-1L', 320.00, 320.0000, 650.00, 'UNITE', NULL, 500, 160000.00, 50, (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-004', '[DEMO] Bidon 10L', 'SKU-BID-10L', 1200.00, 1200.0000, 2400.00, 'UNITE', NULL, 120, 144000.00, 10, (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-005', '[DEMO] Sachet alimentaire 500g', 'SKU-SAC-500', 45.00, 45.0000, 100.00, 'UNITE', NULL, 2000, 90000.00, 200, (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'PRODUIT_FINI', 'PRODUCTION_USINE', 1);

-- Articles - Matieres premieres
INSERT INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `vente_au_poids`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-006', '[DEMO] Granule PEHD 25kg sac', 'SKU-MP-PEHD', 21250.00, 21250.0000, 25500.00, 'SAC', 0, NULL, 40, 850000.00, 5, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Palm CI'), (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-007', '[DEMO] Granule PP 25kg sac', 'SKU-MP-PP', 19500.00, 19500.0000, 23400.00, 'SAC', 0, NULL, 35, 682500.00, 5, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Palm CI'), (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-008', '[DEMO] Colorant 5kg', 'SKU-MP-COL', 16000.00, 16000.0000, 19200.00, 'UNITE', 0, NULL, 12, 192000.00, 3, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Cosmetique Plus'), (SELECT id FROM categories WHERE nom='[DEMO] Hygiene'), 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-009', '[DEMO] Colle neoprene 5L', 'SKU-MP-COLLE', 22500.00, 22500.0000, 27000.00, 'UNITE', 0, NULL, 8, 180000.00, 2, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Entretien'), 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1);

-- Articles - Commerciaux
INSERT INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-010', '[DEMO] Eau minerale 1.5L x6', 'SKU-BOI-001', 2400.00, 2400.0000, 3600.00, 'UNITE', NULL, 100, 240000.00, 15, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Sen Distribution'), (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-011', '[DEMO] Jus de baobab 1L', 'SKU-BOI-002', 1200.00, 1200.0000, 2200.00, 'UNITE', NULL, 80, 96000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Boissons'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-012', '[DEMO] Riz 5kg', 'SKU-ALI-001', 3500.00, 3500.0000, 5000.00, 'UNITE', NULL, 60, 210000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-013', '[DEMO] Huile vegetale 5L', 'SKU-ALI-002', 4000.00, 4000.0000, 5500.00, 'UNITE', NULL, 45, 180000.00, 8, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-014', '[DEMO] Savon noir 200g x3', 'SKU-HYG-001', 800.00, 800.0000, 1500.00, 'UNITE', NULL, 150, 120000.00, 20, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Cosmetique Plus'), (SELECT id FROM categories WHERE nom='[DEMO] Hygiene'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-015', '[DEMO] Detergent 2L', 'SKU-HYG-002', 1500.00, 1500.0000, 2800.00, 'UNITE', NULL, 70, 105000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Cosmetique Plus'), (SELECT id FROM categories WHERE nom='[DEMO] Entretien'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-016', '[DEMO] Telephone dual SIM', 'SKU-TEC-001', 35000.00, 35000.0000, 55000.00, 'UNITE', NULL, 25, 875000.00, 5, (SELECT id FROM fournisseurs WHERE nom='[DEMO] TechImport'), (SELECT id FROM categories WHERE nom='[DEMO] Électronique'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-017', '[DEMO] Chargeur USB-C', 'SKU-TEC-002', 3500.00, 3500.0000, 7000.00, 'UNITE', NULL, 40, 140000.00, 8, (SELECT id FROM fournisseurs WHERE nom='[DEMO] TechImport'), (SELECT id FROM categories WHERE nom='[DEMO] Électronique'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-018', '[DEMO] T-Shirt coton M', 'SKU-TXT-001', 2500.00, 2500.0000, 5000.00, 'UNITE', NULL, 60, 150000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] TextPro'), (SELECT id FROM categories WHERE nom='[DEMO] Textile'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-019', '[DEMO] Cigarettes pack 10', 'SKU-TAB-001', 1500.00, 1500.0000, 2500.00, 'UNITE', NULL, 200, 300000.00, 30, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Sen Distribution'), (SELECT id FROM categories WHERE nom='[DEMO] Tabac'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-020', '[DEMO] Ampoule LED 9W', 'SKU-BRI-001', 800.00, 800.0000, 1800.00, 'UNITE', NULL, 80, 64000.00, 15, (SELECT id FROM fournisseurs WHERE nom='[DEMO] TechImport'), (SELECT id FROM categories WHERE nom='[DEMO] Bricolage'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-021', '[DEMO] Pate tomate 400g x12', 'SKU-ALI-003', 9600.00, 9600.0000, 14000.00, 'CARTON', NULL, 30, 288000.00, 5, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-022', '[DEMO] Cafe moulu 250g', 'SKU-ALI-004', 2000.00, 2000.0000, 3500.00, 'UNITE', NULL, 50, 100000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Agro-Alim SA'), (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-023', '[DEMO] Papier aluminium 300m', 'SKU-ALI-005', 3000.00, 3000.0000, 5000.00, 'UNITE', NULL, 35, 105000.00, 8, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Sen Distribution'), (SELECT id FROM categories WHERE nom='[DEMO] Alimentaire sec'), 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-024', '[DEMO] Ruban adhesif 48mm', 'SKU-CON-001', 600.00, 600.0000, 1200.00, 'UNITE', NULL, 100, 60000.00, 20, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Sen Distribution'), (SELECT id FROM categories WHERE nom='[DEMO] Entretien'), 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-025', '[DEMO] Gants latex M x100', 'SKU-CON-002', 4500.00, 4500.0000, 7500.00, 'UNITE', NULL, 50, 225000.00, 10, (SELECT id FROM fournisseurs WHERE nom='[DEMO] TechImport'), (SELECT id FROM categories WHERE nom='[DEMO] Hygiene'), 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-026', '[DEMO] Sachet poubelle 60L x50', 'SKU-CON-003', 3000.00, 3000.0000, 5000.00, 'UNITE', NULL, 40, 120000.00, 8, (SELECT id FROM fournisseurs WHERE nom='[DEMO] Sen Distribution'), (SELECT id FROM categories WHERE nom='[DEMO] Entretien'), 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1);

-- Stock produits finis usine
INSERT INTO `stock_produits_finis_usine` (`article_id`, `quantite`)
SELECT a.id, v.quantite
FROM articles a
JOIN (
  SELECT 'DEM-ART-001' AS code, 250 AS quantite UNION ALL
  SELECT 'DEM-ART-002', 180 UNION ALL
  SELECT 'DEM-ART-003', 500 UNION ALL
  SELECT 'DEM-ART-004', 120 UNION ALL
  SELECT 'DEM-ART-005', 2000
) v ON a.code_barre = v.code;

-- ============================================================
-- PHASE 3 : STOCK MAGASINS (dynamique via sous-requetes)
-- ============================================================

INSERT INTO `stock_magasins` (`magasin_id`, `article_id`, `quantite`, `valeur_stock`, `stock_alerte`)
SELECT m.magasin_id, a.id, m.quantite, m.valeur_stock, m.stock_alerte
FROM (
  SELECT 1 AS magasin_id, 'DEM-ART-006' AS code, 20 AS quantite, 425000.00 AS valeur_stock, 5 AS stock_alerte UNION ALL
  SELECT 1, 'DEM-ART-007', 18, 351000.00, 5 UNION ALL
  SELECT 1, 'DEM-ART-008', 6, 96000.00, 3 UNION ALL
  SELECT 1, 'DEM-ART-009', 4, 90000.00, 2 UNION ALL
  SELECT 1, 'DEM-ART-010', 60, 144000.00, 15 UNION ALL
  SELECT 1, 'DEM-ART-011', 40, 48000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-012', 30, 105000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-013', 25, 100000.00, 8 UNION ALL
  SELECT 1, 'DEM-ART-014', 80, 64000.00, 20 UNION ALL
  SELECT 1, 'DEM-ART-015', 40, 60000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-016', 15, 525000.00, 5 UNION ALL
  SELECT 1, 'DEM-ART-017', 25, 87500.00, 8 UNION ALL
  SELECT 1, 'DEM-ART-018', 40, 100000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-019', 120, 180000.00, 30 UNION ALL
  SELECT 1, 'DEM-ART-020', 50, 40000.00, 15 UNION ALL
  SELECT 1, 'DEM-ART-021', 20, 192000.00, 5 UNION ALL
  SELECT 1, 'DEM-ART-022', 30, 60000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-023', 20, 60000.00, 8 UNION ALL
  SELECT 1, 'DEM-ART-024', 60, 36000.00, 20 UNION ALL
  SELECT 1, 'DEM-ART-025', 30, 135000.00, 10 UNION ALL
  SELECT 1, 'DEM-ART-026', 25, 75000.00, 8 UNION ALL
  SELECT 2, 'DEM-ART-006', 20, 425000.00, 5 UNION ALL
  SELECT 2, 'DEM-ART-007', 17, 331500.00, 5 UNION ALL
  SELECT 2, 'DEM-ART-010', 40, 96000.00, 10 UNION ALL
  SELECT 2, 'DEM-ART-012', 30, 105000.00, 10 UNION ALL
  SELECT 2, 'DEM-ART-014', 70, 56000.00, 15 UNION ALL
  SELECT 2, 'DEM-ART-015', 30, 45000.00, 10 UNION ALL
  SELECT 2, 'DEM-ART-018', 20, 50000.00, 10 UNION ALL
  SELECT 2, 'DEM-ART-019', 80, 120000.00, 30 UNION ALL
  SELECT 2, 'DEM-ART-020', 30, 24000.00, 10 UNION ALL
  SELECT 2, 'DEM-ART-024', 40, 24000.00, 15
) m
JOIN articles a ON a.code_barre = m.code;

-- Article lots
INSERT INTO `article_lots` (`article_id`, `magasin_id`, `numero_lot`, `quantite`, `date_peremption`)
SELECT a.id, l.magasin_id, l.numero_lot, l.quantite, l.date_peremption
FROM (
  SELECT 'DEM-ART-010' AS code, 1 AS magasin_id, 'LOT-DEMO-EAU-260901' AS numero_lot, 60 AS quantite, '2027-03-01' AS date_peremption UNION ALL
  SELECT 'DEM-ART-012', 1, 'LOT-DEMO-RIZ-260902', 30, '2027-06-15' UNION ALL
  SELECT 'DEM-ART-014', 1, 'LOT-DEMO-SAVON-260901', 80, '2027-12-31' UNION ALL
  SELECT 'DEM-ART-019', 1, 'LOT-DEMO-CIG-260901', 120, '2027-09-01' UNION ALL
  SELECT 'DEM-ART-010', 2, 'LOT-DEMO-EAU-260901D', 40, '2027-03-01' UNION ALL
  SELECT 'DEM-ART-019', 2, 'LOT-DEMO-CIG-260901D', 80, '2027-09-01'
) l
JOIN articles a ON a.code_barre = l.code;

-- ============================================================
-- PHASE 4 : ÉQUIPES
-- ============================================================

INSERT INTO `equipes` (`nom`, `description`, `type`, `actif`) VALUES
('[DEMO] Équipe Vente A', 'Équipe de vente du magasin principal - matin', 'BOUTIQUE', 1),
('[DEMO] Équipe Vente B', 'Équipe de vente du depot - matin', 'BOUTIQUE', 1),
('[DEMO] Équipe Production 1', 'Équipe de production usine Yopougon -shift jour', 'USINE', 1),
('[DEMO] Équipe Logistique', 'Équipe logistique et livraison', 'LIVRAISON', 1),
('[DEMO] Équipe Achats', 'Équipe achats et approvisionnement', 'ACHATS', 1),
('[DEMO] Équipe Caisse', 'Équipe gestion caisse et encaissements', 'CAISSE', 1);

INSERT IGNORE INTO `equipe_membres` (`equipe_id`, `user_id`, `actif`)
SELECT e.id, u.id, 1
FROM equipes e
JOIN utilisateurs u ON u.login IN ('vendeur','magasinier','admin')
WHERE e.nom = '[DEMO] Équipe Vente A' AND u.login IN ('vendeur','magasinier');

INSERT IGNORE INTO `equipe_membres` (`equipe_id`, `user_id`, `actif`)
SELECT e.id, u.id, 1
FROM equipes e
JOIN utilisateurs u ON u.login = 'vendeur'
WHERE e.nom = '[DEMO] Équipe Vente B';

INSERT IGNORE INTO `equipe_membres` (`equipe_id`, `user_id`, `actif`)
SELECT e.id, u.id, 1
FROM equipes e
JOIN utilisateurs u ON u.login = 'admin'
WHERE e.nom = '[DEMO] Équipe Caisse';

INSERT IGNORE INTO `equipe_magasins` (`equipe_id`, `magasin_id`)
SELECT e.id, 1 FROM equipes e WHERE e.nom = '[DEMO] Équipe Vente A'
UNION ALL
SELECT e.id, 2 FROM equipes e WHERE e.nom = '[DEMO] Équipe Vente B'
UNION ALL
SELECT e.id, 1 FROM equipes e WHERE e.nom = '[DEMO] Équipe Logistique'
UNION ALL
SELECT e.id, 1 FROM equipes e WHERE e.nom = '[DEMO] Équipe Caisse';

INSERT IGNORE INTO `user_magasins` (`user_id`, `magasin_id`, `actif`)
SELECT u.id, m.id, 1
FROM utilisateurs u, magasins m
WHERE u.login = 'directeur';

INSERT IGNORE INTO `user_magasins` (`user_id`, `magasin_id`, `actif`)
SELECT u.id, 1, 1 FROM utilisateurs u WHERE u.login = 'vendeur'
UNION ALL
SELECT u.id, 1, 1 FROM utilisateurs u WHERE u.login = 'magasinier'
UNION ALL
SELECT u.id, 2, 1 FROM utilisateurs u WHERE u.login = 'magasinier'
UNION ALL
SELECT u.id, 1, 1 FROM utilisateurs u WHERE u.login = 'admin';

-- ============================================================
-- PHASE 5 : MATIÈRES PREMIÈRES → RECETTES → PRODUCTIONS
-- ============================================================

-- Recettes (liees aux articles via code_barre)
INSERT INTO `recettes` (`nom`, `article_id`, `quantite_produite`, `unite_produit`, `version`, `actif`, `notes`)
SELECT v.nom, a.id, v.qte_prod, 'UNITE', 1, 1, v.notes
FROM (
  SELECT '[DEMO] Recette Seau 20L' AS nom, 'DEM-ART-001' AS code, 100 AS qte_prod, 'Production de 100 seaux de 20L' AS notes UNION ALL
  SELECT '[DEMO] Recette Bac 5L', 'DEM-ART-002', 150, 'Production de 150 bacs de 5L' UNION ALL
  SELECT '[DEMO] Recette Bouteille 1L', 'DEM-ART-003', 500, 'Production de 500 bouteilles PEHD 1L' UNION ALL
  SELECT '[DEMO] Recette Bidon 10L', 'DEM-ART-004', 80, 'Production de 80 bidons 10L'
) v
JOIN articles a ON a.code_barre = v.code;

-- Recettes lignes (liees aux matieres premieres via reference)
INSERT INTO `recettes_lignes` (`recette_id`, `matiere_id`, `quantite_necessaire`, `unite`, `pertes_theoriques_pct`, `ordre`)
SELECT r.id, mp.id, rl.quantite, rl.unite, rl.pertes, rl.ordre
FROM (
  SELECT '[DEMO] Recette Seau 20L' AS recette, 'DEMP-001' AS mp_ref, 250.0000 AS quantite, 'KG' AS unite, 3.00 AS pertes, 1 AS ordre UNION ALL
  SELECT '[DEMO] Recette Seau 20L', 'DEMP-003', 5.0000, 'KG', 0.00, 2 UNION ALL
  SELECT '[DEMO] Recette Seau 20L', 'DEMP-005', 2.0000, 'KG', 0.00, 3 UNION ALL
  SELECT '[DEMO] Recette Bac 5L', 'DEMP-001', 100.0000, 'KG', 2.50, 1 UNION ALL
  SELECT '[DEMO] Recette Bac 5L', 'DEMP-003', 2.0000, 'KG', 0.00, 2 UNION ALL
  SELECT '[DEMO] Recette Bouteille 1L', 'DEMP-002', 30.0000, 'KG', 4.00, 1 UNION ALL
  SELECT '[DEMO] Recette Bouteille 1L', 'DEMP-004', 1.0000, 'KG', 0.00, 2 UNION ALL
  SELECT '[DEMO] Recette Bouteille 1L', 'DEMP-005', 1.0000, 'KG', 0.00, 3 UNION ALL
  SELECT '[DEMO] Recette Bidon 10L', 'DEMP-001', 350.0000, 'KG', 3.00, 1 UNION ALL
  SELECT '[DEMO] Recette Bidon 10L', 'DEMP-003', 8.0000, 'KG', 0.00, 2 UNION ALL
  SELECT '[DEMO] Recette Bidon 10L', 'DEMP-005', 3.0000, 'KG', 0.00, 3
) rl
JOIN recettes r ON r.nom = rl.recette
JOIN matieres_premieres mp ON mp.reference = rl.mp_ref;

-- Productions
INSERT INTO `productions` (`reference`, `article_id`, `recette_id`, `recette_version`, `quantite_prevue`, `quantite_produite`, `quantite_perdue`, `cout_matieres`, `cout_unitaire`, `rendement_pct`, `statut`, `date_prevue`, `date_debut`, `date_fin`, `utilisateur_id`, `usine_id`, `notes`)
SELECT v.ref, a.id, r.id, 1, v.qte_prevue, v.qte_produite, v.qte_perdue, v.cout_mat, v.cout_unit, v.rendement, v.statut, v.date_prevue, v.date_debut, v.date_fin, 10, u.id, v.notes
FROM (
  SELECT 'DEMO-PROD-001' AS ref, 'DEM-ART-001' AS art_code, 'DEMO-PROD-001' AS recette_name, 100 AS qte_prevue, 97 AS qte_produite, 3 AS qte_perdue, 26500.00 AS cout_mat, 273.1959 AS cout_unit, 97.0000 AS rendement, 'TERMINEE' AS statut, '2026-09-10' AS date_prevue, '2026-09-10 06:00:00' AS date_debut, '2026-09-10 13:30:00' AS date_fin, 'Production seaux' AS notes UNION ALL
  SELECT 'DEMO-PROD-002', 'DEM-ART-002', 'DEMO-PROD-002', 150, 148, 2, 15200.00, 102.7027, 98.6667, 'TERMINEE', '2026-09-10', '2026-09-10 06:15:00', '2026-09-10 12:00:00', 'Production bacs' UNION ALL
  SELECT 'DEMO-PROD-003', 'DEM-ART-003', 'DEMO-PROD-003', 500, 490, 10, 16500.00, 33.6735, 98.0000, 'TERMINEE', '2026-09-11', '2026-09-11 06:30:00', '2026-09-11 11:00:00', 'Production bouteilles' UNION ALL
  SELECT 'DEMO-PROD-004', 'DEM-ART-004', 'DEMO-PROD-004', 80, 0, 0, 30000.00, 375.0000, NULL, 'PLANIFIEE', '2026-09-12', NULL, NULL, 'Production bidons planifiee'
) v
JOIN articles a ON a.code_barre = v.art_code
JOIN recettes r ON r.article_id = a.id
JOIN usines u ON u.nom = '[DEMO] Usine Yopougon';

-- Production matieres
INSERT INTO `production_matieres` (`production_id`, `matiere_id`, `quantite_prevue`, `quantite_reelle`, `unite`, `numero_lot`, `cout_unitaire`, `cout_total`)
SELECT p.id, mp.id, pm.qte_prevue, pm.qte_reelle, pm.unite, pm.lot, pm.cout_unit, pm.cout_total
FROM (
  SELECT 'DEMO-PROD-001' AS prod_ref, 'DEMP-001' AS mp_ref, 25.0000 AS qte_prevue, 25.3500 AS qte_reelle, 'KG' AS unite, 'LOT-DEMO-MP-001' AS lot, 850.0000 AS cout_unit, 21547.50 AS cout_total UNION ALL
  SELECT 'DEMO-PROD-001', 'DEMP-003', 0.5000, 0.5100, 'KG', 'LOT-DEMO-MP-002', 3200.0000, 1632.00 UNION ALL
  SELECT 'DEMO-PROD-001', 'DEMP-005', 0.2000, 0.2050, 'KG', 'LOT-DEMO-MP-003', 5600.0000, 1148.00 UNION ALL
  SELECT 'DEMO-PROD-002', 'DEMP-001', 15.0000, 15.2200, 'KG', 'LOT-DEMO-MP-004', 850.0000, 12937.00 UNION ALL
  SELECT 'DEMO-PROD-002', 'DEMP-003', 0.3000, 0.3050, 'KG', 'LOT-DEMO-MP-005', 3200.0000, 976.00 UNION ALL
  SELECT 'DEMO-PROD-003', 'DEMP-002', 15.0000, 15.600, 'KG', 'LOT-DEMO-MP-006', 780.0000, 12168.00 UNION ALL
  SELECT 'DEMO-PROD-003', 'DEMP-004', 0.5000, 0.5200, 'KG', 'LOT-DEMO-MP-007', 3500.0000, 1820.00 UNION ALL
  SELECT 'DEMO-PROD-003', 'DEMP-005', 0.5000, 0.5150, 'KG', 'LOT-DEMO-MP-008', 5600.0000, 2884.00
) pm
JOIN productions p ON p.reference = pm.prod_ref
JOIN matieres_premieres mp ON mp.reference = pm.mp_ref;

-- Production pertes
INSERT INTO `production_pertes` (`production_id`, `type_perte`, `article_id`, `quantite`, `unite`, `motif`, `utilisateur_id`)
SELECT p.id, pp.type_perte, a.id, pp.quantite, pp.unite, pp.motif, 10
FROM (
  SELECT 'DEMO-PROD-001' AS prod_ref, 'rebut' AS type_perte, 'DEM-ART-001' AS art_code, 3.0000 AS quantite, 'UNITE' AS unite, 'Defaut esthetique' AS motif UNION ALL
  SELECT 'DEMO-PROD-002', 'casse', 'DEM-ART-002', 2.0000, 'UNITE', 'Bac fissure demoulage' UNION ALL
  SELECT 'DEMO-PROD-003', 'matiere_premiere', 'DEM-ART-003', 10.0000, 'UNITE', 'Bouteilles deformees'
) pp
JOIN productions p ON p.reference = pp.prod_ref
JOIN articles a ON a.code_barre = pp.art_code;

-- ============================================================
-- PHASE 6 : COMMANDES → RÉCEPTIONS
-- ============================================================

INSERT INTO `commandes_fournisseur` (`fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_commande`, `devise`, `taux_change`, `date_reception_prevue`, `notes`)
SELECT f.id, 1, 10, v.statut, v.date_cmd, 'XOF', 1.000000, v.date_prevue, v.notes
FROM (
  SELECT '[DEMO] Palm CI' AS four, 'Recue' AS statut, '2026-09-05 09:00:00' AS date_cmd, '2026-09-08' AS date_prevue, '[DEMO] Commande granules plastiques' AS notes UNION ALL
  SELECT '[DEMO] Agro-Alim SA', 'Envoyee', '2026-09-09 10:30:00', '2026-09-13', '[DEMO] Commande matieres alimentaires' UNION ALL
  SELECT '[DEMO] Sen Distribution', 'En_Attente', '2026-09-11 08:00:00', '2026-09-15', '[DEMO] Commande emballages divers'
) v
JOIN fournisseurs f ON f.nom = v.four;

INSERT INTO `lignes_commande_fournisseur` (`commande_id`, `article_id`, `quantite_commandee`, `quantite_recue`, `quantite_receptionnee`, `quantite_perdue`, `prix_achat_unitaire`)
SELECT cf.id, a.id, lc.qte_cmd, lc.qte_recue, lc.qte_recep, lc.qte_perdue, lc.prix
FROM (
  SELECT 'granul' AS cmd_match, 'DEM-ART-006' AS code, 40 AS qte_cmd, 40 AS qte_recue, 40 AS qte_recep, 0 AS qte_perdue, 21250.00 AS prix UNION ALL
  SELECT 'granul', 'DEM-ART-007', 35, 35, 35, 0, 19500.00 UNION ALL
  SELECT 'aliment', 'DEM-ART-012', 60, 0, 0, 0, 3500.00 UNION ALL
  SELECT 'aliment', 'DEM-ART-013', 50, 0, 0, 0, 4000.00 UNION ALL
  SELECT 'aliment', 'DEM-ART-010', 100, 0, 0, 0, 2400.00 UNION ALL
  SELECT 'emballage', 'DEM-ART-024', 200, 0, 0, 0, 600.00 UNION ALL
  SELECT 'emballage', 'DEM-ART-026', 100, 0, 0, 0, 3000.00
) lc
JOIN commandes_fournisseur cf ON cf.notes LIKE CONCAT('%', lc.cmd_match, '%')
JOIN articles a ON a.code_barre = lc.code;

-- Receptions
INSERT INTO `receptions` (`reference`, `commande_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_reception`, `commentaire')
SELECT 'DEMO-REC-001', cf1.id, cf1.fournisseur_id, 1, 10, 'Validee', '2026-09-08 14:00:00', '[DEMO] Reception granules - conforme'
FROM commandes_fournisseur cf1
WHERE cf1.notes LIKE '%granules%'
;

INSERT INTO `receptions` (`reference`, `commande_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_reception`, `commentaire`)
SELECT 'DEMO-REC-002', cf1.id, cf1.fournisseur_id, 1, 10, 'Validee', '2026-09-08 14:30:00', '[DEMO] Reception emballages - conforme'
FROM commandes_fournisseur cf1
WHERE cf1.notes LIKE '%emballages%'
;

-- Reception lignes
INSERT INTO `reception_lignes` (`reception_id`, `ligne_commande_id`, `article_id`, `quantite_attendue`, `quantite_recue`, `quantite_acceptee`, `quantite_perdue`, `prix_achat_unitaire`, `numero_lot`, `date_peremption`)
SELECT rec.id, lcf.id, a.id, lcf.quantite_commandee, lcf.quantite_recue, lcf.quantite_receptionnee, lcf.quantite_perdue, lcf.prix_achat_unitaire, rl.lot, rl.date_peremption
FROM (
  SELECT 'DEMO-REC-001' AS rec_ref, 'DEM-ART-006' AS code, 'LOT-DEMO-REC-PEHD-0908' AS lot, '2027-09-08' AS date_peremption UNION ALL
  SELECT 'DEMO-REC-001', 'DEM-ART-007', 'LOT-DEMO-REC-PP-0908', '2027-09-08' UNION ALL
  SELECT 'DEMO-REC-002', 'DEM-ART-024', NULL, NULL UNION ALL
  SELECT 'DEMO-REC-002', 'DEM-ART-026', NULL, NULL
) rl
JOIN receptions rec ON rec.reference = rl.rec_ref
JOIN articles a ON a.code_barre = rl.code
JOIN lignes_commande_fournisseur lcf ON lcf.commande_id = rec.commande_id AND lcf.article_id = a.id;

-- Pertes fournisseur
INSERT INTO `pertes_fournisseur` (`reception_id`, `reception_ligne_id`, `commande_id`, `article_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `quantite`, `motif`, `commentaire`)
SELECT rec.id, rl.id, rec.commande_id, a.id, rec.fournisseur_id, 1, 10, 5, 'endommage', '[DEMO] 5 rouleaux ruban adhesif endommages'
FROM receptions rec
JOIN reception_lignes rl ON rl.reception_id = rec.id
JOIN articles a ON a.code_barre = 'DEM-ART-024'
WHERE rec.reference = 'DEMO-REC-002'
;

-- Prix historique fournisseur
INSERT INTO `fournisseur_prix_historique` (`article_id`, `fournisseur_id`, `prix_achat`, `devise`, `est_actif`, `source`, `date_debut`)
SELECT a.id, f.id, v.prix, 'XOF', 1, 'manuelle', '2026-09-01 00:00:00'
FROM (
  SELECT 'DEM-ART-006' AS art, '[DEMO] Palm CI' AS four, 21250.00 AS prix UNION ALL
  SELECT 'DEM-ART-007', '[DEMO] Palm CI', 19500.00 UNION ALL
  SELECT 'DEM-ART-010', '[DEMO] Agro-Alim SA', 2400.00 UNION ALL
  SELECT 'DEM-ART-012', '[DEMO] Agro-Alim SA', 3500.00 UNION ALL
  SELECT 'DEM-ART-016', '[DEMO] TechImport', 35000.00 UNION ALL
  SELECT 'DEM-ART-014', '[DEMO] Cosmetique Plus', 800.00
) v
JOIN articles a ON a.code_barre = v.art
JOIN fournisseurs f ON f.nom = v.four;

-- ============================================================
-- PHASE 7 : FACTURES → PAIEMENTS → CRÉANCES
-- ============================================================

INSERT INTO `factures` (`numero_facture`, `date_facture`, `utilisateur_id`, `magasin_id`, `total_ht`, `tva_taux`, `total_ttc`, `montant_paye`, `monnaie_rendue`, `statut`, `statut_transmission`, `client_id`, `client_nom`)
SELECT v.num, v.date_fact, 3, v.mag_id, v.total_ht, 0.00, v.total_ttc, v.montant_paye, 0.00, 'Payee', 'non_transmise', c.id, v.client_nom
FROM (
  SELECT 'DEMO-FACT-001' AS num, '2026-09-01 10:30:00' AS date_fact, 1 AS mag_id, 3600.00 AS total_ht, 3600.00 AS total_ttc, 3600.00 AS montant_paye, '[DEMO] Aminata Toure' AS client_nom UNION ALL
  SELECT 'DEMO-FACT-002', '2026-09-02 14:15:00', 1, 5000.00, 5000.00, 5000.00, '[DEMO] Moussa Konate' UNION ALL
  SELECT 'DEMO-FACT-003', '2026-09-03 09:00:00', 1, 12000.00, 12000.00, 8000.00, '[DEMO] Societe Com-Plus' UNION ALL
  SELECT 'DEMO-FACT-004', '2026-09-04 11:45:00', 1, 2500.00, 2500.00, 2500.00, '[DEMO] Ibrahim Bamba' UNION ALL
  SELECT 'DEMO-FACT-005', '2026-09-05 16:00:00', 1, 55000.00, 55000.00, 0.00, '[DEMO] COGITEC SARL' UNION ALL
  SELECT 'DEMO-FACT-006', '2026-09-06 10:00:00', 1, 3500.00, 3500.00, 3500.00, '[DEMO] Boulangerie Doree' UNION ALL
  SELECT 'DEMO-FACT-007', '2026-09-07 13:30:00', 1, 7200.00, 7200.00, 5000.00, '[DEMO] Trans Sahel' UNION ALL
  SELECT 'DEMO-FACT-008', '2026-09-10 09:15:00', 2, 5500.00, 5500.00, 5500.00, '[DEMO] Aminata Toure' UNION ALL
  SELECT 'DEMO-FACT-009', '2026-09-11 08:00:00', 1, 1500.00, 1500.00, 0.00, '[DEMO] Khady Niang' UNION ALL
  SELECT 'DEMO-FACT-010', '2026-09-11 08:30:00', 1, 21000.00, 21000.00, 21000.00, '[DEMO] Menuiserie Bois Prestige'
) v
JOIN clients c ON c.nom = v.client_nom;

-- Lignes facture
INSERT INTO `lignes_facture` (`facture_id`, `article_id`, `quantite`, `prix_unitaire`, `taux_tva`)
SELECT f.id, a.id, lf.quantite, lf.prix, NULL
FROM (
  SELECT 'DEMO-FACT-001' AS fact, 'DEM-ART-010' AS code, 2 AS quantite, 1800.00 AS prix UNION ALL
  SELECT 'DEMO-FACT-002', 'DEM-ART-012', 1, 5000.00 UNION ALL
  SELECT 'DEMO-FACT-003', 'DEM-ART-016', 1, 55000.00 UNION ALL
  SELECT 'DEMO-FACT-004', 'DEM-ART-014', 1, 1500.00 UNION ALL
  SELECT 'DEMO-FACT-005', 'DEM-ART-016', 1, 55000.00 UNION ALL
  SELECT 'DEMO-FACT-006', 'DEM-ART-022', 1, 3500.00 UNION ALL
  SELECT 'DEMO-FACT-007', 'DEM-ART-010', 2, 3600.00 UNION ALL
  SELECT 'DEMO-FACT-007', 'DEM-ART-011', 2, 2200.00 UNION ALL
  SELECT 'DEMO-FACT-008', 'DEM-ART-013', 1, 5500.00 UNION ALL
  SELECT 'DEMO-FACT-009', 'DEM-ART-014', 1, 1500.00 UNION ALL
  SELECT 'DEMO-FACT-010', 'DEM-ART-021', 3, 14000.00 UNION ALL
  SELECT 'DEMO-FACT-010', 'DEM-ART-022', 2, 3500.00
) lf
JOIN factures f ON f.numero_facture = lf.fact
JOIN articles a ON a.code_barre = lf.code;

-- Paiements facture
INSERT INTO `paiements_facture` (`facture_id`, `mode_paiement`, `montant`, `reference`, `date_paiement`)
SELECT f.id, pf.mode, pf.montant, pf.ref, pf.date_paie
FROM (
  SELECT 'DEMO-FACT-001' AS fact, 'Especes' AS mode, 3600.00 AS montant, NULL AS ref, '2026-09-01 10:30:00' AS date_paie UNION ALL
  SELECT 'DEMO-FACT-002', 'Mobile_Money', 5000.00, 'OM-DEMO-001', '2026-09-02 14:15:00' UNION ALL
  SELECT 'DEMO-FACT-003', 'Especes', 5000.00, NULL, '2026-09-03 09:00:00' UNION ALL
  SELECT 'DEMO-FACT-003', 'Mobile_Money', 3000.00, 'WV-DEMO-002', '2026-09-03 09:05:00' UNION ALL
  SELECT 'DEMO-FACT-004', 'Especes', 2500.00, NULL, '2026-09-04 11:45:00' UNION ALL
  SELECT 'DEMO-FACT-006', 'Carte_Bancaire', 3500.00, 'CB-DEMO-003', '2026-09-06 10:00:00' UNION ALL
  SELECT 'DEMO-FACT-007', 'Especes', 5000.00, NULL, '2026-09-07 13:30:00' UNION ALL
  SELECT 'DEMO-FACT-008', 'Mobile_Money', 5500.00, 'OM-DEMO-003', '2026-09-10 09:15:00' UNION ALL
  SELECT 'DEMO-FACT-010', 'Especes', 21000.00, NULL, '2026-09-11 08:30:00'
) pf
JOIN factures f ON f.numero_facture = pf.fact;

-- Creances
INSERT INTO `creances_clients` (`facture_id`, `client_id`, `montant_total`, `montant_paye`, `reste_a_payer`, `statut`, `date_echeance`)
SELECT f.id, f.client_id, f.total_ttc, f.montant_paye, (f.total_ttc - f.montant_paye), v.statut, v.echeance
FROM (
  SELECT 'DEMO-FACT-003' AS fact, 'Partiellement_Payee' AS statut, '2026-10-03' AS echeance UNION ALL
  SELECT 'DEMO-FACT-005', 'En_Cours', '2026-10-05' UNION ALL
  SELECT 'DEMO-FACT-007', 'Partiellement_Payee', '2026-10-07'
) v
JOIN factures f ON f.numero_facture = v.fact;

-- ============================================================
-- PHASE 8 : TRANSFERTS, DÉPENSES, RETOURS, ETC.
-- ============================================================

-- Transferts stock
INSERT INTO `transferts_stock` (`article_id`, `magasin_source_id`, `magasin_destination_id`, `quantite`, `utilisateur_id`, `motif`, `date_transfert`)
SELECT a.id, 1, 2, t.quantite, 10, t.motif, t.date_transfert
FROM (
  SELECT 'DEM-ART-010' AS code, 40 AS quantite, '[DEMO] Transfert eaux vers depot' AS motif, '2026-09-05 10:00:00' AS date_transfert UNION ALL
  SELECT 'DEM-ART-014', 70, '[DEMO] Transfert savons vers depot', '2026-09-06 09:00:00' UNION ALL
  SELECT 'DEM-ART-019', 80, '[DEMO] Transfert tabac vers depot', '2026-09-07 11:00:00' UNION ALL
  SELECT 'DEM-ART-018', 20, '[DEMO] Retour t-shirts du depot', '2026-09-09 14:00:00'
) t
JOIN articles a ON a.code_barre = t.code;

-- Depenses
INSERT INTO `depenses` (`magasin_id`, `utilisateur_id`, `titre`, `categorie`, `montant`, `date_depense`, `description`) VALUES
(1, 10, '[DEMO] Électricite septembre', 'Loyer & charges', 85000.00, '2026-09-01', 'Facture electricite EDF - magasin principal'),
(1, 10, '[DEMO] Entretien climatisation', 'Maintenance', 25000.00, '2026-09-05', 'Recharge gaz climatiseur'),
(2, 10, '[DEMO] Assurance vehicule', 'Transport', 45000.00, '2026-09-03', 'Prime assurance camion livraison'),
(1, 10, '[DEMO] Fournitures bureau', 'Fournitures', 8000.00, '2026-09-07', 'Papier, stylos, encre'),
(NULL, 10, '[DEMO] Internet & telephone', 'Services', 35000.00, '2026-09-01', 'Abonnement internet + forfait mobile');

-- Clotures caisse
INSERT INTO `clotures_caisse` (`magasin_id`, `utilisateur_id`, `date_cloture`, `montant_attendu`, `montant_reel`, `ecart`, `statut`, `date_creation`) VALUES
(1, 3, '2026-09-01', 3600.00, 3600.00, 0.00, 'VALIDE', '2026-09-01 18:10:00'),
(1, 3, '2026-09-02', 5000.00, 5000.00, 0.00, 'VALIDE', '2026-09-02 18:10:00'),
(1, 3, '2026-09-03', 8000.00, 8100.00, 100.00, 'VALIDE', '2026-09-03 18:10:00'),
(1, 3, '2026-09-04', 2500.00, 2500.00, 0.00, 'VALIDE', '2026-09-04 18:10:00'),
(1, 3, '2026-09-05', 0.00, 0.00, 0.00, 'VALIDE', '2026-09-05 18:10:00'),
(1, 3, '2026-09-06', 3500.00, 3500.00, 0.00, 'VALIDE', '2026-09-06 18:10:00'),
(1, 3, '2026-09-07', 5000.00, 5000.00, 0.00, 'VALIDE', '2026-09-07 18:10:00'),
(2, 3, '2026-09-10', 5500.00, 5450.00, -50.00, 'VALIDE', '2026-09-10 18:10:00'),
(1, 3, '2026-09-11', 0.00, 0.00, 0.00, 'VALIDE', '2026-09-11 18:10:00');

-- Retours
INSERT INTO `retours_factures` (`numero_retour`, `facture_id`, `magasin_id`, `utilisateur_id`, `montant_total`, `motif`, `statut`)
SELECT 'DEMO-RET-001', f.id, 1, 3, 1500.00, '[DEMO] Client insatisfait - savon defectueux', 'Valide'
FROM factures f WHERE f.numero_facture = 'DEMO-FACT-004';

INSERT INTO `retours_factures` (`numero_retour`, `facture_id`, `magasin_id`, `utilisateur_id`, `montant_total`, `motif`, `statut`)
SELECT 'DEMO-RET-002', f.id, 2, 3, 5500.00, '[DEMO] Huile perimee - retour fournisseur', 'Valide'
FROM factures f WHERE f.numero_facture = 'DEMO-FACT-008';

INSERT INTO `lignes_retour` (`retour_id`, `ligne_facture_id`, `article_id`, `quantite`, `prix_unitaire`)
SELECT ret.id, lf.id, lf.article_id, lf.quantite, lf.prix_unitaire
FROM retours_factures ret
JOIN factures f ON f.id = ret.facture_id
JOIN lignes_facture lf ON lf.facture_id = f.id
WHERE ret.numero_retour = 'DEMO-RET-001'
AND lf.article_id = (SELECT id FROM articles WHERE code_barre = 'DEM-ART-014')
;

INSERT INTO `lignes_retour` (`retour_id`, `ligne_facture_id`, `article_id`, `quantite`, `prix_unitaire`)
SELECT ret.id, lf.id, lf.article_id, lf.quantite, lf.prix_unitaire
FROM retours_factures ret
JOIN factures f ON f.id = ret.facture_id
JOIN lignes_facture lf ON lf.facture_id = f.id
WHERE ret.numero_retour = 'DEMO-RET-002'
AND lf.article_id = (SELECT id FROM articles WHERE code_barre = 'DEM-ART-013')
;

-- Paiements credit
INSERT INTO `paiements_credit` (`creance_id`, `montant`, `mode_paiement`, `reference`, `date_paiement`, `utilisateur_id`)
SELECT cc.id, 8000.00, 'Especes', NULL, '2026-09-03 09:05:00', 3
FROM creances_clients cc
JOIN factures f ON f.id = cc.facture_id
WHERE f.numero_facture = 'DEMO-FACT-003';

INSERT INTO `paiements_credit` (`creance_id`, `montant`, `mode_paiement`, `reference`, `date_paiement`, `utilisateur_id`)
SELECT cc.id, 5000.00, 'Especes', NULL, '2026-09-07 13:35:00', 3
FROM creances_clients cc
JOIN factures f ON f.id = cc.facture_id
WHERE f.numero_facture = 'DEMO-FACT-007';

-- ============================================================
-- PHASE 9 : DONNÉES DE SUPPORT (logs, notifs, params, etc.)
-- ============================================================

-- Logs activite
INSERT INTO `logs_activite` (`utilisateur_id`, `action`, `details`, `ip_address`, `date_action`) VALUES
(1, 'CONNEXION', 'Connexion reussie - role PROPRIETAIRE', '127.0.0.1', '2026-09-11 08:00:00'),
(10, 'SEED_DEMO', 'Import donnees demo - script demo_data_v2.sql', '127.0.0.1', NOW());

-- Notifications
INSERT INTO `notifications` (`type`, `titre`, `type_notif`, `message`, `cible_role`, `lu`, `statut`) VALUES
('STOCK', 'Stock bas detecte', 'ALERTE_STOCK', '[DEMO] Stock bas pour article DEM-ART-020 (Ampoule LED 9W)', 'MAGASINIER', 0, 'EN_ATTENTE'),
('PRODUCTION', 'Production planifiee', 'INFO_PRODUCTION', '[DEMO] Production DEMO-PROD-004 planifiee pour demain', 'CHEF_EQUIPE_USINE', 0, 'EN_ATTENTE'),
('CREDIT', 'Creance en souffrance', 'ALERTE_CREDIT', '[DEMO] Creance COGITEC - 55 000 XOF impayes', 'VENDEUR', 0, 'EN_ATTENTE'),
('COMMANDE', 'Commande en cours', 'INFO_COMMANDE', '[DEMO] Commande emballages en cours de livraison', 'ADMIN', 1, 'LUE');

-- Presences
INSERT INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`)
SELECT e.id, '2026-09-11', '06:00:00', NULL, NULL, 'PRESENT', 10
FROM employes e WHERE e.matricule = 'DEMP-EMP-01';

INSERT INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`)
SELECT e.id, '2026-09-11', '06:02:00', NULL, NULL, 'PRESENT', 10
FROM employes e WHERE e.matricule = 'DEMP-EMP-02';

INSERT INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`)
SELECT e.id, '2026-09-11', '05:55:00', NULL, NULL, 'PRESENT', 10
FROM employes e WHERE e.matricule = 'DEMP-EMP-03';

INSERT INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`)
SELECT e.id, '2026-09-11', '07:15:00', NULL, NULL, 'RETARD', 10
FROM employes e WHERE e.matricule = 'DEMP-EMP-04';

INSERT INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`)
SELECT e.id, '2026-09-11', NULL, NULL, NULL, 'ABSENT', 10
FROM employes e WHERE e.matricule = 'DEMP-EMP-06';

-- Horaires travail
INSERT INTO `horaires_travail` (`nom`, `jour`, `heure_debut`, `heure_fin`, `tolerance_retard_minutes`, `actif`) VALUES
('[DEMO] Horaire Usine', 'LUNDI', '06:00:00', '14:00:00', 10, 1),
('[DEMO] Horaire Usine', 'MARDI', '06:00:00', '14:00:00', 10, 1),
('[DEMO] Horaire Usine', 'MERCREDI', '06:00:00', '14:00:00', 10, 1),
('[DEMO] Horaire Usine', 'JEUDI', '06:00:00', '14:00:00', 10, 1),
('[DEMO] Horaire Usine', 'VENDREDI', '06:00:00', '14:00:00', 10, 1),
('[DEMO] Horaire Usine', 'SAMEDI', '06:00:00', '12:00:00', 10, 1),
('[DEMO] Horaire Boutique', 'LUNDI', '08:00:00', '18:00:00', 5, 1),
('[DEMO] Horaire Boutique', 'MARDI', '08:00:00', '18:00:00', 5, 1),
('[DEMO] Horaire Boutique', 'MERCREDI', '08:00:00', '18:00:00', 5, 1),
('[DEMO] Horaire Boutique', 'JEUDI', '08:00:00', '18:00:00', 5, 1),
('[DEMO] Horaire Boutique', 'VENDREDI', '08:00:00', '18:00:00', 5, 1),
('[DEMO] Horaire Boutique', 'SAMEDI', '08:00:00', '13:00:00', 5, 1);

-- Promotions
INSERT INTO `promotions` (`nom`, `code_promo`, `type_reduction`, `valeur`, `article_id`, `categorie_id`, `montant_min_achat`, `date_debut`, `date_fin`, `limite_utilisations`, `nb_utilisations`, `actif`) VALUES
('[DEMO] Solde fin saison', 'DEMO-SOLDE10', 'pourcentage', 10.00, NULL, NULL, 0.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', 200, 45, 1),
('[DEMO] -500F sur telephone', 'DEMO-TEL500', 'montant_fixe', 500.00, (SELECT id FROM articles WHERE code_barre='DEM-ART-016'), NULL, 50000.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', 50, 12, 1),
('[DEMO] 20% produits hygiene', 'DEMO-HYG20', 'pourcentage', 20.00, NULL, (SELECT id FROM categories WHERE nom='[DEMO] Hygiene'), 0.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', NULL, 30, 1);

-- Regles promotions
INSERT INTO `regles_promotions` (`nom`, `condition_type`, `jours_limite`, `seuil_stock`, `pourcentage_remise`, `actif`) VALUES
('[DEMO] Peremption 30j', 'PEREMPTION_PROCHE', 30, NULL, 20.00, 1),
('[DEMO] Surstock >500', 'SURSTOCK', NULL, 500, 15.00, 1),
('[DEMO] Peremption 15j urgente', 'PEREMPTION_PROCHE', 15, NULL, 40.00, 1);

-- Parametres
INSERT INTO `parametres` (`cle`, `valeur`, `categorie`, `description`) VALUES
('DEMO_MODE', '1', 'general', '[DEMO] Mode demo active - donnees fictives')
ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`);

-- Sequences
INSERT INTO `sequences` (`cle`, `valeur`) VALUES
('facture_numero', 10),
('commande_fournisseur', 3),
('reception_reference', 2),
('retour_numero', 2),
('inventaire_reference', 0),
('production_reference', 4)
ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`);

-- ============================================================
-- FIN
-- ============================================================

-- ============================================================
-- RESTAURATION DES TRIGGERS
-- ============================================================
DELIMITER $$

CREATE TRIGGER `trg_paiements_immutable_delete` BEFORE DELETE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de paiement interdite';
END$$

CREATE TRIGGER `trg_paiements_immutable_update` BEFORE UPDATE ON `paiements_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les paiements sont immuables';
END$$

CREATE TRIGGER `trg_lignes_facture_immutable_delete` BEFORE DELETE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de ligne de facture interdite';
END$$

CREATE TRIGGER `trg_lignes_facture_immutable_update` BEFORE UPDATE ON `lignes_facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les lignes de facture sont immuables';
END$$

CREATE TRIGGER `trg_factures_immutable_delete` BEFORE DELETE ON `factures` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de facture interdite';
END$$

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
END$$

CREATE TRIGGER `trg_receptions_immutable_delete` BEFORE DELETE ON `receptions` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Les recussions ne peuvent pas etre supprimees.';
END$$

CREATE TRIGGER `trg_receptions_immutable_update` BEFORE UPDATE ON `receptions` FOR EACH ROW BEGIN
    IF OLD.statut = 'Validee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une reception validee ne peut pas etre modifiee.';
    END IF;
    IF OLD.statut = 'Annulee' AND NEW.statut != 'Annulee' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Une reception annulee ne peut pas etre reactivee.';
    END IF;
END$$

CREATE TRIGGER `trg_clotures_immutable_delete` BEFORE DELETE ON `clotures_caisse` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de cloture interdite';
END$$

CREATE TRIGGER `trg_clotures_immutable_update` BEFORE UPDATE ON `clotures_caisse` FOR EACH ROW BEGIN
    IF OLD.montant_attendu <> NEW.montant_attendu OR OLD.montant_reel <> NEW.montant_reel
       OR OLD.ecart <> NEW.ecart THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les montants d une cloture sont immuables';
    END IF;
    IF OLD.hash_chaine IS NOT NULL AND OLD.hash_chaine <> NEW.hash_chaine THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le hash de chaine d une cloture est immuable';
    END IF;
END$$

DELIMITER ;

SET foreign_key_checks = 1;
COMMIT;
