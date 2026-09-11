-- ============================================================
-- SEED DEMO COMPLET — eStock v2.7.0
-- Date : 11 septembre 2026
-- Objectif : données réalistes couvrant tous les workflows
-- Marqueur : [DEMO] dans les noms pour repérage rapide
-- ============================================================
-- ATTENTION : Ce script est TRANSACTIONNEL.
-- En cas d'erreur, toute la transaction est annulée.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET foreign_key_checks = 0;

START TRANSACTION;

-- ============================================================
-- 1. CATÉGORIES D'ARTICLES
-- ============================================================
INSERT IGNORE INTO `categories` (`nom`, `description`, `actif`) VALUES
('[DEMO] Boissons', 'Eaux, jus, sodas, boissons énergisantes', 1),
('[DEMO] Alimentaire sec', 'Riz, pâtes, farines, huiles, épices', 1),
('[DEMO] Hygiène', 'Savons, shampoings, détergents', 1),
('[DEMO] Électronique', 'Téléphones, accessoires, câbles', 1),
('[DEMO] Textile', 'Vêtements, tissus, chaussures', 1),
('[DEMO] Entretien', 'Produits ménagers, détergents', 1),
('[DEMO] Tabac', 'Cigarettes, allumettes', 1),
('[DEMO] Bricolage', 'Outillage, peinture, visserie', 1);

-- ============================================================
-- 2. FOURNISSEURS
-- ============================================================
INSERT IGNORE INTO `fournisseurs` (`nom`, `contact`, `telephone`, `devise`) VALUES
('[DEMO] Sen Distribution', 'Mamadou Diallo', '+221 77 123 4567', 'XOF'),
('[DEMO] Palm CI', 'Ibrahim Kouassi', '+225 07 08 09 10', 'XOF'),
('[DEMO] Agro-Alim SA', 'Fatou Sow', '+221 78 234 5678', 'XOF'),
('[DEMO] TechImport', 'Jean-Pierre Mensah', '+228 90 12 34 56', 'XOF'),
('[DEMO] TextPro', 'Aissatou Ba', '+221 76 345 6789', 'XOF'),
('[DEMO] Cosmétique Plus', 'Oumar Ndiaye', '+221 70 456 7890', 'XOF');

-- ============================================================
-- 3. CLIENTS (avec fidélité, crédit, consentements)
-- ============================================================
INSERT IGNORE INTO `clients` (`nom`, `raison_sociale`, `nif`, `rccm`, `siret`, `adresse`, `telephone`, `email`, `code_fidelite`, `points_fidelite`, `consentement_fidelite`, `date_consentement`, `source_consentement`, `credit_autorise`, `limite_credit`, `date_dernier_achat`) VALUES
('[DEMO] Aminata Touré', NULL, NULL, NULL, NULL, 'Rue 12, Cocody', '+221 77 111 2233', 'aminata.demo@test.com', 'DEM-CLI-001', 250, 1, NOW(), 'boutique', 0, 0.00, '2026-09-10 14:30:00'),
('[DEMO] Moussa Konaté', 'Konaté & Fils SARL', 'NIF-98765', 'RCCM-1234', NULL, 'Bd de la République, Abidjan', '+222 33 445 566', 'moussa.demo@test.com', 'DEM-CLI-002', 80, 1, NOW(), 'boutique', 1, 500000.00, '2026-09-09 10:15:00'),
('[DEMO] Fatima Sy', NULL, NULL, NULL, NULL, 'Quartier HLM, Dakar', '+221 78 777 888', NULL, 'DEM-CLI-003', 0, 0, NULL, NULL, 0, 0.00, NULL),
('[DEMO] Société Com-Plus', 'Com-Plus SARL', 'NIF-54321', 'RCCM-5678', 'SIRET-67890', 'Zone Industrielle, Bamako', '+223 76 901 234', 'contact@complus-demo.com', 'DEM-CLI-004', 1200, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), 'web', 1, 2000000.00, '2026-09-11 08:00:00'),
('[DEMO] Ibrahim Bamba', NULL, NULL, NULL, NULL, 'Rue Félix Faure', '+221 70 222 333', NULL, 'DEM-CLI-005', 45, 1, NOW(), 'caisse', 0, 0.00, '2026-09-08 16:45:00'),
('[DEMO] COGITEC SARL', 'COGITEC', 'NIF-11111', 'RCCM-2222', NULL, 'Plateau, Abidjan', '+222 21 333 444', 'cogitec.demo@test.ci', 'DEM-CLI-006', 500, 1, DATE_SUB(NOW(), INTERVAL 30 DAY), 'web', 1, 3500000.00, '2026-09-07 11:20:00'),
('[DEMO] Awa Diop', NULL, NULL, NULL, NULL, 'Medina, Dakar', '+221 76 444 555', NULL, 'DEM-CLI-007', 15, 0, NULL, NULL, 0, 0.00, '2026-09-10 09:00:00'),
('[DEMO] Boulangerie Dorée', 'Dorée SARL', 'NIF-66666', 'RCCM-7777', NULL, 'Rue de Commerce', '+221 77 888 999', 'doree.demo@test.com', 'DEM-CLI-008', 320, 1, DATE_SUB(NOW(), INTERVAL 15 DAY), 'boutique', 1, 750000.00, '2026-09-11 07:30:00'),
('[DEMO] Ousmane Fall', NULL, NULL, NULL, NULL, 'Grand Yoff', '+221 78 000 111', NULL, 'DEM-CLI-009', 0, 0, NULL, NULL, 0, 0.00, NULL),
('[DEMO] Trans Sahel', 'Trans Sahel Transport', 'NIF-88888', 'RCCM-9999', NULL, 'Route Nationale 1', '+223 79 222 333', 'transsahel.demo@test.ml', 'DEM-CLI-010', 890, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), 'web', 1, 5000000.00, '2026-09-06 15:00:00'),
('[DEMO] Khady Niang', NULL, NULL, NULL, NULL, 'Parcelles Assainies', '+221 70 555 666', NULL, 'DEM-CLI-011', 60, 1, NOW(), 'boutique', 0, 0.00, '2026-09-09 13:10:00'),
('[DEMO] Menuiserie Bois Prestige', 'Bois Prestige SARL', 'NIF-44444', 'RCCM-3333', NULL, 'Zone Artisanat', '+221 76 777 888', 'boisprestige.demo@test.com', 'DEM-CLI-012', 200, 1, DATE_SUB(NOW(), INTERVAL 45 DAY), 'boutique', 1, 1200000.00, '2026-09-10 16:20:00');

-- ============================================================
-- 4. USINES
-- ============================================================
INSERT IGNORE INTO `usines` (`code`, `nom`, `description`, `adresse`, `actif`) VALUES
('DEM-USINE-01', '[DEMO] Usine Yopougon', 'Unité de production principale — plasturgie et agroalimentaire', 'Zone Industrielle, Yopougon, Abidjan', 1),
('DEM-USINE-02', '[DEMO] Usine Abobo', 'Unité secondaire — conditionnement et emballage', 'Quartier Industriel, Abobo, Abidjan', 1);

-- ============================================================
-- 5. ÉQUIPES
-- ============================================================
INSERT IGNORE INTO `equipes` (`nom`, `description`, `type`, `chef_equipe_id`, `actif`) VALUES
('[DEMO] Équipe Vente A', 'Équipe de vente du magasin principal — matin', 'BOUTIQUE', NULL, 1),
('[DEMO] Équipe Vente B', 'Équipe de vente du dépôt — matin', 'BOUTIQUE', NULL, 1),
('[DEMO] Équipe Production 1', 'Équipe de production usine Yopougon —shift jour', 'USINE', NULL, 1),
('[DEMO] Équipe Logistique', 'Équipe logistique et livraison', 'LIVRAISON', NULL, 1),
('[DEMO] Équipe Achats', 'Équipe achats et approvisionnement', 'ACHATS', NULL, 1),
('[DEMO] Équipe Caisse', 'Équipe gestion caisse et encaissements', 'CAISSE', NULL, 1);

-- ============================================================
-- 6. USER ↔ MAGASIN & ÉQUIPE MAPPINGS
-- ============================================================
INSERT IGNORE INTO `user_magasins` (`user_id`, `magasin_id`, `actif`) VALUES
(1, 1, 1), (1, 2, 1),
(3, 1, 1),
(7, 1, 1), (7, 2, 1),
(10, 1, 1);

INSERT IGNORE INTO `equipe_membres` (`equipe_id`, `user_id`, `actif`) VALUES
(1, 3, 1), (1, 7, 1),
(2, 3, 1),
(6, 10, 1);

INSERT IGNORE INTO `equipe_magasins` (`equipe_id`, `magasin_id`) VALUES
(1, 1), (2, 2), (4, 1), (6, 1);

-- ============================================================
-- 7. ARTICLES — MATIÈRES PREMIÈRES
-- ============================================================
INSERT IGNORE INTO `matieres_premieres` (`reference`, `nom`, `categorie_id`, `unite_mesure`, `cout_reference`, `stock_minimum`, `fournisseur_id`, `actif`, `notes`) VALUES
('DEMP-001', '[DEMO] Granulé PEHD 001', 1, 'KG', 850.0000, 500, 2, 1, 'Polyéthylène haute densité — granulés noirs'),
('DEMP-002', '[DEMO] Granulé PP 002', 1, 'KG', 780.0000, 500, 2, 1, 'Polypropylène — granulés blancs'),
('DEMP-003', '[DEMO] Colorant Noir', 2, 'KG', 3200.0000, 50, 6, 1, 'Colorant noir concentré'),
('DEMP-004', '[DEMO] Colorant Bleu', 2, 'KG', 3500.0000, 50, 6, 1, 'Colorant bleu concentré'),
('DEMP-005', '[DEMO] Stabilisant UV', 2, 'KG', 5600.0000, 20, 6, 1, 'Stabilisant anti-UV'),
('DEMP-006', '[DEMO] Film étirable 30µ', 3, 'KG', 1200.0000, 200, 1, 1, 'Film étirable pour emballage palette'),
('DEMP-007', '[DEMO] Carton ondulé 700g', 3, 'KG', 650.0000, 300, 1, 1, 'Carton ondulé grands formats'),
('DEMP-008', '[DEMO] Colle néoprène', 4, 'L', 4500.0000, 30, 3, 1, 'Colle néoprène industrielle'),
('DEMP-009', '[DEMO] Huile moteur 15W40', 5, 'L', 2800.0000, 50, 3, 1, 'Huile moteur synthétique'),
('DEMP-010', '[DEMO] Sirop Baobab 5L', 6, 'L', 3500.0000, 100, 3, 1, 'Sirop de baobab concentré'),
('DEMP-011', '[DEMO] Pâte tomate 2.5kg', 6, 'KG', 1800.0000, 150, 3, 1, 'Concentré de tomate'),
('DEMP-012', '[DEMO] Sel iodé 1kg', 6, 'KG', 500.0000, 200, 3, 1, 'Sel de cuisine iodé'),
('DEMP-013', '[DEMO] Café en grains 1kg', 6, 'KG', 6500.0000, 80, 3, 1, 'Café arabica torréfié'),
('DEMP-014', '[DEMO] Feuille alu 250g', 7, 'KG', 4800.0000, 60, 1, 1, 'Papier aluminium culinaire'),
('DEMP-015', '[DEMO] Mastic silicone', 8, 'L', 7200.0000, 25, 4, 1, 'Mastic silicone sanitaire');

-- ============================================================
-- 8. ARTICLES — PRODUITS FINIS (usine)
-- ============================================================
INSERT IGNORE INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-001', '[DEMO] Seau plastique 20L', 'SKU-SEAU-20L', 1800.00, 1800.0000, 3500.00, 'UNITE', NULL, 250, 450000.00, 20, NULL, 8, 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-002', '[DEMO] Bac plastique 5L', 'SKU-BAC-5L', 850.00, 850.0000, 1700.00, 'UNITE', NULL, 180, 153000.00, 15, NULL, 8, 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-003', '[DEMO] Bouteille PEHD 1L', 'SKU-BTL-1L', 320.00, 320.0000, 650.00, 'UNITE', NULL, 500, 160000.00, 50, NULL, 1, 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-004', '[DEMO] Bidon 10L', 'SKU-BID-10L', 1200.00, 1200.0000, 2400.00, 'UNITE', NULL, 120, 144000.00, 10, NULL, 1, 'PRODUIT_FINI', 'PRODUCTION_USINE', 1),
('DEM-ART-005', '[DEMO] Sachet alimentaire 500g', 'SKU-SAC-500', 45.00, 45.0000, 100.00, 'UNITE', NULL, 2000, 90000.00, 200, NULL, 2, 'PRODUIT_FINI', 'PRODUCTION_USINE', 1);

-- ============================================================
-- 9. ARTICLES — MATIÈRES PREMIÈRES EN TANT QU'ARTICLES (pour stock magasin)
-- ============================================================
INSERT IGNORE INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `vente_au_poids`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-006', '[DEMO] Granulé PEHD 25kg sac', 'SKU-MP-PEHD', 21250.00, 21250.0000, 25500.00, 'SAC', 0, NULL, 40, 850000.00, 5, 2, 1, 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-007', '[DEMO] Granulé PP 25kg sac', 'SKU-MP-PP', 19500.00, 19500.0000, 23400.00, 'SAC', 0, NULL, 35, 682500.00, 5, 2, 1, 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-008', '[DEMO] Colorant 5kg', 'SKU-MP-COL', 16000.00, 16000.0000, 19200.00, 'UNITE', 0, NULL, 12, 192000.00, 3, 6, 2, 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-009', '[DEMO] Colle néoprène 5L', 'SKU-MP-COLLE', 22500.00, 22500.0000, 27000.00, 'UNITE', 0, NULL, 8, 180000.00, 2, 3, 4, 'MATIERE_PREMIERE', 'ACHAT_FOURNISSEUR', 1);

-- ============================================================
-- 10. ARTICLES — ARTICLES COMMERCIAUX (achat-revente)
-- ============================================================
INSERT IGNORE INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-010', '[DEMO] Eau minérale 1.5L x6', 'SKU-BOI-001', 2400.00, 2400.0000, 3600.00, 'UNITE', NULL, 100, 240000.00, 15, 1, 1, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-011', '[DEMO] Jus de baobab 1L', 'SKU-BOI-002', 1200.00, 1200.0000, 2200.00, 'UNITE', NULL, 80, 96000.00, 10, 3, 1, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-012', '[DEMO] Riz 5kg', 'SKU-ALI-001', 3500.00, 3500.0000, 5000.00, 'UNITE', NULL, 60, 210000.00, 10, 3, 2, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-013', '[DEMO] Huile végétale 5L', 'SKU-ALI-002', 4000.00, 4000.0000, 5500.00, 'UNITE', NULL, 45, 180000.00, 8, 3, 2, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-014', '[DEMO] Savon noir 200g x3', 'SKU-HYG-001', 800.00, 800.0000, 1500.00, 'UNITE', NULL, 150, 120000.00, 20, 6, 3, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-015', '[DEMO] Détergent 2L', 'SKU-HYG-002', 1500.00, 1500.0000, 2800.00, 'UNITE', NULL, 70, 105000.00, 10, 6, 6, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-016', '[DEMO] Téléphone dual SIM', 'SKU-TEC-001', 35000.00, 35000.0000, 55000.00, 'UNITE', NULL, 25, 875000.00, 5, 4, 4, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-017', '[DEMO] Chargeur USB-C', 'SKU-TEC-002', 3500.00, 3500.0000, 7000.00, 'UNITE', NULL, 40, 140000.00, 8, 4, 4, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-018', '[DEMO] T-Shirt coton M', 'SKU-TXT-001', 2500.00, 2500.0000, 5000.00, 'UNITE', NULL, 60, 150000.00, 10, 5, 5, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-019', '[DEMO] Cigarettes pack 10', 'SKU-TAB-001', 1500.00, 1500.0000, 2500.00, 'UNITE', NULL, 200, 300000.00, 30, 1, 7, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-020', '[DEMO] Ampoule LED 9W', 'SKU-BRI-001', 800.00, 800.0000, 1800.00, 'UNITE', NULL, 80, 64000.00, 15, 4, 8, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-021', '[DEMO] Pâte tomate 400g x12', 'SKU-ALI-003', 9600.00, 9600.0000, 14000.00, 'CARTON', NULL, 30, 288000.00, 5, 3, 2, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-022', '[DEMO] Café moulu 250g', 'SKU-ALI-004', 2000.00, 2000.0000, 3500.00, 'UNITE', NULL, 50, 100000.00, 10, 3, 2, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-023', '[DEMO] Papier aluminium 300m', 'SKU-ALI-005', 3000.00, 3000.0000, 5000.00, 'UNITE', NULL, 35, 105000.00, 8, 1, 2, 'ARTICLE_COMMERCIAL', 'ACHAT_FOURNISSEUR', 1);

-- ============================================================
-- 11. ARTICLES — CONSOMMABLES
-- ============================================================
INSERT IGNORE INTO `articles` (`code_barre`, `nom`, `sku`, `prix_achat`, `cump`, `prix_vente`, `unite_mesure`, `taux_tva`, `quantite_stock`, `valeur_stock`, `seuil_alerte`, `fournisseur_id`, `categorie_id`, `type_article`, `origine_article`, `actif`) VALUES
('DEM-ART-024', '[DEMO] Ruban adhésif 48mm', 'SKU-CON-001', 600.00, 600.0000, 1200.00, 'UNITE', NULL, 100, 60000.00, 20, 1, 6, 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-025', '[DEMO] Gants latex M x100', 'SKU-CON-002', 4500.00, 4500.0000, 7500.00, 'UNITE', NULL, 50, 225000.00, 10, 4, 3, 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1),
('DEM-ART-026', '[DEMO] Sachet poubelle 60L x50', 'SKU-CON-003', 3000.00, 3000.0000, 5000.00, 'UNITE', NULL, 40, 120000.00, 8, 1, 6, 'CONSOMMABLE', 'ACHAT_FOURNISSEUR', 1);

-- ============================================================
-- 12. STOCK MATIÈRES PREMIÈRES
-- ============================================================
INSERT IGNORE INTO `stock_matieres_premieres` (`matiere_id`, `quantite`, `valeur_stock`) VALUES
(1, 2500.0000, 2125000.00),
(2, 1800.0000, 1404000.00),
(3, 45.0000, 144000.00),
(4, 30.0000, 105000.00),
(5, 15.0000, 84000.00),
(6, 180.0000, 216000.00),
(7, 220.0000, 143000.00),
(8, 12.0000, 54000.00),
(9, 25.0000, 70000.00),
(10, 80.0000, 280000.00),
(11, 100.0000, 180000.00),
(12, 150.0000, 75000.00),
(13, 40.0000, 260000.00),
(14, 30.0000, 144000.00),
(15, 10.0000, 72000.00);

-- ============================================================
-- 13. STOCK PRODUITS FINIS USINE
-- ============================================================
INSERT IGNORE INTO `stock_produits_finis_usine` (`article_id`, `quantite`) VALUES
(1, 250), (2, 180), (3, 500), (4, 120), (5, 2000);

-- ============================================================
-- 14. STOCK MAGASINS (articles MP + commerciaux + consommables)
-- ============================================================
INSERT IGNORE INTO `stock_magasins` (`magasin_id`, `article_id`, `quantite`, `valeur_stock`, `stock_alerte`) VALUES
-- Magasin Principal (id=1)
(1, 6, 20, 425000.00, 5),
(1, 7, 18, 351000.00, 5),
(1, 8, 6, 96000.00, 3),
(1, 9, 4, 90000.00, 2),
(1, 10, 60, 144000.00, 15),
(1, 11, 40, 48000.00, 10),
(1, 12, 30, 105000.00, 10),
(1, 13, 25, 100000.00, 8),
(1, 14, 80, 64000.00, 20),
(1, 15, 40, 60000.00, 10),
(1, 16, 15, 525000.00, 5),
(1, 17, 25, 87500.00, 8),
(1, 18, 40, 100000.00, 10),
(1, 19, 120, 180000.00, 30),
(1, 20, 50, 40000.00, 15),
(1, 21, 20, 192000.00, 5),
(1, 22, 30, 60000.00, 10),
(1, 23, 20, 60000.00, 8),
(1, 24, 60, 36000.00, 20),
(1, 25, 30, 135000.00, 10),
(1, 26, 25, 75000.00, 8),
-- Dépôt (id=2)
(2, 6, 20, 425000.00, 5),
(2, 7, 17, 331500.00, 5),
(2, 10, 40, 96000.00, 10),
(2, 12, 30, 105000.00, 10),
(2, 14, 70, 56000.00, 15),
(2, 15, 30, 45000.00, 10),
(2, 18, 20, 50000.00, 10),
(2, 19, 80, 120000.00, 30),
(2, 20, 30, 24000.00, 10),
(2, 24, 40, 24000.00, 15);

-- ============================================================
-- 15. ARTICLE COUTS (historique des coûts)
-- ============================================================
INSERT IGNORE INTO `article_couts` (`article_id`, `magasin_id`, `quantite`, `cout_unitaire`, `reference`) VALUES
(6, 1, 40, 21250.0000, 'DEMO-CMD-001'),
(7, 1, 35, 19500.0000, 'DEMO-CMD-002'),
(10, 1, 100, 2400.0000, 'DEMO-CMD-003'),
(12, 1, 60, 3500.0000, 'DEMO-CMD-004'),
(14, 1, 150, 800.0000, 'DEMO-CMD-005'),
(16, 1, 25, 35000.0000, 'DEMO-CMD-006'),
(18, 1, 60, 2500.0000, 'DEMO-CMD-007'),
(19, 1, 200, 1500.0000, 'DEMO-CMD-008');

-- ============================================================
-- 16. ARTICLE LOTS
-- ============================================================
INSERT IGNORE INTO `article_lots` (`article_id`, `magasin_id`, `numero_lot`, `quantite`, `date_peremption`) VALUES
(10, 1, 'LOT-DEMO-EAU-260901', 60, '2027-03-01'),
(12, 1, 'LOT-DEMO-RIZ-260902', 30, '2027-06-15'),
(14, 1, 'LOT-DEMO-SAVON-260901', 80, '2027-12-31'),
(19, 1, 'LOT-DEMO-CIG-260901', 120, '2027-09-01'),
(10, 2, 'LOT-DEMO-EAU-260901D', 40, '2027-03-01'),
(19, 2, 'LOT-DEMO-CIG-260901D', 80, '2027-09-01');

-- ============================================================
-- 17. MACHINES
-- ============================================================
INSERT IGNORE INTO `machines` (`reference`, `nom`, `type`, `description`, `etat`, `actif`) VALUES
('DEM-MACH-01', '[DEMO] Injecteuse Engel 80T', 'Injecteur plastique', 'Injecteuse 80 tonnes — moules seaux et bacs', 'EN_FONCTIONNEMENT', 1),
('DEM-MACH-02', '[DEMO] Souffleuse Bekum 50L', 'Souffleuse', 'Souffleuse 50L — bouteilles et bidons', 'EN_FONCTIONNEMENT', 1),
('DEM-MACH-03', '[DEMO] Extrudeuse Cincinnati', 'Extrudeuse', 'Extrudeuse à film — production de sachets', 'EN_MAINTENANCE', 1),
('DEM-MACH-04', '[DEMO] Groupe froid Carrier', 'Réfrigération', 'Groupe froid frigorifique pour stockage matières', 'ARRETEE', 1),
('DEM-MACH-05', '[DEMO] Pont rhone 5T', 'Manutention', 'Pont roulant 5 tonnes — atelier assemblage', 'EN_FONCTIONNEMENT', 1);

-- ============================================================
-- 18. MACHINE ÉTATS (historique)
-- ============================================================
INSERT IGNORE INTO `machine_etats` (`machine_id`, `production_id`, `etat`, `heure_debut`, `heure_fin`, `duree_minutes`, `motif`, `utilisateur_id`) VALUES
(1, NULL, 'EN_FONCTIONNEMENT', '2026-09-11 06:00:00', NULL, NULL, 'Début de production journée', 10),
(2, NULL, 'EN_FONCTIONNEMENT', '2026-09-11 06:15:00', NULL, NULL, 'Début production bouteilles', 10),
(3, NULL, 'EN_MAINTENANCE', '2026-09-10 14:00:00', '2026-09-10 17:00:00', 180, 'Remplacement vis extrudeuse', 10),
(5, NULL, 'EN_FONCTIONNEMENT', '2026-09-11 06:00:00', NULL, NULL, 'Pont roulant actif', 10);

-- ============================================================
-- 19. RÈGLES PROMOTIONS
-- ============================================================
INSERT IGNORE INTO `regles_promotions` (`nom`, `condition_type`, `jours_limite`, `seuil_stock`, `pourcentage_remise`, `actif`) VALUES
('[DEMO] Péremption 30j', 'PEREMPTION_PROCHE', 30, NULL, 20.00, 1),
('[DEMO] Surstock >500', 'SURSTOCK', NULL, 500, 15.00, 1),
('[DEMO] Péremption 15j urgente', 'PEREMPTION_PROCHE', 15, NULL, 40.00, 1);

-- ============================================================
-- 20. PROMOTIONS
-- ============================================================
INSERT IGNORE INTO `promotions` (`nom`, `code_promo`, `type_reduction`, `valeur`, `article_id`, `categorie_id`, `montant_min_achat`, `date_debut`, `date_fin`, `limite_utilisations`, `nb_utilisations`, `actif`) VALUES
('[DEMO] Solde fin saison', 'DEMO-SOLDE10', 'pourcentage', 10.00, NULL, NULL, 0.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', 200, 45, 1),
('[DEMO] -500F sur téléphone', 'DEMO-TEL500', 'montant_fixe', 500.00, 16, NULL, 50000.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', 50, 12, 1),
('[DEMO] 20% produits hygiène', 'DEMO-HYG20', 'pourcentage', 20.00, NULL, 3, 0.00, '2026-09-01 00:00:00', '2026-09-30 23:59:59', NULL, 30, 1);

-- ============================================================
-- 21. EMPLOYÉS
-- ============================================================
INSERT IGNORE INTO `employes` (`matricule`, `nom`, `prenom`, `fonction`, `telephone`, `actif`) VALUES
('DEMP-EMP-01', 'Diallo', 'Moussa', 'Opérateur machine', '+221 77 101 0001', 1),
('DEMP-EMP-02', 'Koné', 'Awa', 'Soudeuse plastique', '+221 77 101 0002', 1),
('DEMP-EMP-03', 'Ouédraogo', 'Ibrahim', 'Chef atelier', '+221 77 101 0003', 1),
('DEMP-EMP-04', 'Sow', 'Fatoumata', 'Contrôle qualité', '+221 77 101 0004', 1),
('DEMP-EMP-05', 'Touré', 'Amadou', 'Magasinier usine', '+221 77 101 0005', 1),
('DEMP-EMP-06', 'Bamba', 'Ousmane', 'Cariste', '+221 77 101 0006', 1),
('DEMP-EMP-07', 'Ndiaye', 'Khady', 'Agent d''entretien', '+221 77 101 0007', 1),
('DEMP-EMP-08', 'Fall', 'Cheikh', 'Technicien maintenance', '+221 77 101 0008', 1),
('DEMP-EMP-09', 'Ba', 'Mariama', ' Réceptionniste', '+221 77 101 0009', 1),
('DEMP-EMP-10', 'Sy', 'Boubacar', 'Manutentionnaire', '+221 77 101 0010', 1);

-- ============================================================
-- 22. PRÉSENCES EMPLOYÉS
-- ============================================================
INSERT IGNORE INTO `presences_employes` (`employe_id`, `date_presence`, `heure_arrivee`, `heure_depart`, `temps_travaille_minutes`, `statut`, `utilisateur_id`) VALUES
(1, '2026-09-11', '06:00:00', NULL, NULL, 'PRESENT', 10),
(2, '2026-09-11', '06:02:00', NULL, NULL, 'PRESENT', 10),
(3, '2026-09-11', '05:55:00', NULL, NULL, 'PRESENT', 10),
(4, '2026-09-11', '07:15:00', NULL, NULL, 'RETARD', 10),
(5, '2026-09-11', '06:10:00', NULL, NULL, 'PRESENT', 10),
(6, '2026-09-11', NULL, NULL, NULL, 'ABSENT', 10),
(7, '2026-09-11', '06:00:00', NULL, NULL, 'PRESENT', 10),
(8, '2026-09-11', '06:05:00', NULL, NULL, 'PRESENT', 10),
(9, '2026-09-11', '06:30:00', NULL, NULL, 'PRESENT', 10),
(10, '2026-09-11', NULL, NULL, NULL, 'ABSENT', 10),
-- Historique jours précédents
(1, '2026-09-10', '06:00:00', '14:00:00', 480, 'PRESENT', 10),
(2, '2026-09-10', '06:05:00', '14:05:00', 480, 'PRESENT', 10),
(3, '2026-09-10', '05:50:00', '13:50:00', 480, 'PRESENT', 10),
(4, '2026-09-10', '06:00:00', '14:00:00', 480, 'PRESENT', 10),
(6, '2026-09-10', '06:10:00', '14:00:00', 470, 'PRESENT', 10),
(1, '2026-09-09', '06:00:00', '14:00:00', 480, 'PRESENT', 10),
(2, '2026-09-09', NULL, NULL, NULL, 'CONGE', 10),
(5, '2026-09-09', '06:00:00', '14:00:00', 480, 'PRESENT', 10);

-- ============================================================
-- 23. HORAIRES DE TRAVAIL
-- ============================================================
INSERT IGNORE INTO `horaires_travail` (`nom`, `jour`, `heure_debut`, `heure_fin`, `tolerance_retard_minutes`, `actif`) VALUES
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

-- ============================================================
-- 24. RECETTES
-- ============================================================
INSERT IGNORE INTO `recettes` (`nom`, `article_id`, `quantite_produite`, `unite_produit`, `version`, `actif`, `notes`) VALUES
('[DEMO] Recette Seau 20L', 1, 100, 'UNITE', 1, 1, 'Production de 100 seaux de 20L — formule standard'),
('[DEMO] Recette Bac 5L', 2, 150, 'UNITE', 1, 1, 'Production de 150 bacs de 5L'),
('[DEMO] Recette Bouteille 1L', 3, 500, 'UNITE', 1, 1, 'Production de 500 bouteilles PEHD 1L'),
('[DEMO] Recette Bidon 10L', 4, 80, 'UNITE', 1, 1, 'Production de 80 bidons 10L');

-- ============================================================
-- 25. RECETTES LIGNES
-- ============================================================
INSERT IGNORE INTO `recettes_lignes` (`recette_id`, `matiere_id`, `quantite_necessaire`, `unite`, `pertes_theoriques_pct`, `ordre`) VALUES
-- Recette Seau 20L : 250g PEHD + 5g colorant + 2g stabilisant
(1, 1, 250.0000, 'KG', 3.00, 1),
(1, 3, 5.0000, 'KG', 0.00, 2),
(1, 5, 2.0000, 'KG', 0.00, 3),
-- Recette Bac 5L : 100g PEHD + 2g colorant
(2, 1, 100.0000, 'KG', 2.50, 1),
(2, 3, 2.0000, 'KG', 0.00, 2),
-- Recette Bouteille 1L : 30g PP + 1g colorant + 1g stabilisant
(3, 2, 30.0000, 'KG', 4.00, 1),
(3, 4, 1.0000, 'KG', 0.00, 2),
(3, 5, 1.0000, 'KG', 0.00, 3),
-- Recette Bidon 10L : 350g PEHD + 8g colorant + 3g stabilisant
(4, 1, 350.0000, 'KG', 3.00, 1),
(4, 3, 8.0000, 'KG', 0.00, 2),
(4, 5, 3.0000, 'KG', 0.00, 3);

-- ============================================================
-- 26. PRODUCTIONS
-- ============================================================
INSERT IGNORE INTO `productions` (`reference`, `article_id`, `recette_id`, `recette_version`, `quantite_prevue`, `quantite_produite`, `quantite_perdue`, `cout_matieres`, `cout_unitaire`, `rendement_pct`, `statut`, `date_prevue`, `date_debut`, `date_fin`, `utilisateur_id`, `usine_id`, `notes`) VALUES
('DEMO-PROD-001', 1, 1, 1, 100, 97, 3, 26500.00, 273.1959, 97.0000, 'TERMINEE', '2026-09-10', '2026-09-10 06:00:00', '2026-09-10 13:30:00', 10, 1, 'Production seaux — 3 rebuts'),
('DEMO-PROD-002', 2, 2, 1, 150, 148, 2, 15200.00, 102.7027, 98.6667, 'TERMINEE', '2026-09-10', '2026-09-10 06:15:00', '2026-09-10 12:00:00', 10, 1, 'Production bacs — 2 rebuts'),
('DEMO-PROD-003', 3, 3, 1, 500, 490, 10, 16500.00, 33.6735, 98.0000, 'TERMINEE', '2026-09-11', '2026-09-11 06:30:00', '2026-09-11 11:00:00', 10, 1, 'Production bouteilles — en cours'),
('DEMO-PROD-004', 4, 4, 1, 80, 0, 0, 30000.00, 375.0000, NULL, 'PLANIFIEE', '2026-09-12', NULL, NULL, 10, 1, 'Production bidons — planifiée demain');

-- ============================================================
-- 27. PRODUCTION MATIÈRES
-- ============================================================
INSERT IGNORE INTO `production_matieres` (`production_id`, `matiere_id`, `quantite_prevue`, `quantite_reelle`, `unite`, `numero_lot`, `cout_unitaire`, `cout_total`) VALUES
-- Prod 001 (Seau 20L x97)
(1, 1, 25.0000, 25.3500, 'KG', 'LOT-DEMO-MP-001', 850.0000, 21547.50),
(1, 3, 0.5000, 0.5100, 'KG', 'LOT-DEMO-MP-002', 3200.0000, 1632.00),
(1, 5, 0.2000, 0.2050, 'KG', 'LOT-DEMO-MP-003', 5600.0000, 1148.00),
-- Prod 002 (Bac 5L x148)
(2, 1, 15.0000, 15.2200, 'KG', 'LOT-DEMO-MP-004', 850.0000, 12937.00),
(2, 3, 0.3000, 0.3050, 'KG', 'LOT-DEMO-MP-005', 3200.0000, 976.00),
-- Prod 003 (Bouteille 1L x490)
(3, 2, 15.0000, 15.6000, 'KG', 'LOT-DEMO-MP-006', 780.0000, 12168.00),
(3, 4, 0.5000, 0.5200, 'KG', 'LOT-DEMO-MP-007', 3500.0000, 1820.00),
(3, 5, 0.5000, 0.5150, 'KG', 'LOT-DEMO-MP-008', 5600.0000, 2884.00);

-- ============================================================
-- 28. PRODUCTION PERTES
-- ============================================================
INSERT IGNORE INTO `production_pertes` (`production_id`, `type_perte`, `article_id`, `quantite`, `unite`, `motif`, `utilisateur_id`) VALUES
(1, 'rebut', 1, 3.0000, 'UNITE', 'Défaut esthétique — rayures sur fond de seau', 10),
(2, 'casse', 2, 2.0000, 'UNITE', 'Bac fissuré démoulage', 10),
(3, 'matiere_premiere', 3, 10.0000, 'UNITE', 'Bouteilles déformées — température extrudeuse', 10);

-- ============================================================
-- 29. COMMANDES FOURNISSEUR
-- ============================================================
INSERT IGNORE INTO `commandes_fournisseur` (`fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_commande`, `devise`, `taux_change`, `date_reception_prevue`, `notes`) VALUES
(2, 1, 10, 'Recue', '2026-09-05 09:00:00', 'XOF', 1.000000, '2026-09-08', '[DEMO] Commande granulés plastiques — PAIEMENT EFFECTUÉ'),
(3, 1, 10, 'Envoyee', '2026-09-09 10:30:00', 'XOF', 1.000000, '2026-09-13', '[DEMO] Commande matières alimentaires — EN COURS DE LIVRAISON'),
(1, 2, 10, 'En_Attente', '2026-09-11 08:00:00', 'XOF', 1.000000, '2026-09-15', '[DEMO] Commande emballages divers — EN ATTENTE VALIDATION');

-- ============================================================
-- 30. LIGNES COMMANDE FOURNISSEUR
-- ============================================================
INSERT IGNORE INTO `lignes_commande_fournisseur` (`commande_id`, `article_id`, `quantite_commandee`, `quantite_recue`, `quantite_receptionnee`, `quantite_perdue`, `prix_achat_unitaire`) VALUES
-- Cmd 1 (Recue — granulés)
(1, 6, 40, 40, 40, 0, 21250.00),
(1, 7, 35, 35, 35, 0, 19500.00),
-- Cmd 2 (Envoyée — alimentaire)
(2, 12, 60, 0, 0, 0, 3500.00),
(2, 13, 50, 0, 0, 0, 4000.00),
(2, 10, 100, 0, 0, 0, 2400.00),
-- Cmd 3 (En attente — emballages)
(3, 24, 200, 0, 0, 0, 600.00),
(3, 26, 100, 0, 0, 0, 3000.00);

-- ============================================================
-- 31. RÉCEPTIONS
-- ============================================================
INSERT IGNORE INTO `receptions` (`reference`, `commande_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_reception`, `commentaire`) VALUES
('DEMO-REC-001', 1, 2, 1, 10, 'Validee', '2026-09-08 14:00:00', '[DEMO] Réception granulés — conforme'),
('DEMO-REC-002', 1, 2, 1, 10, 'Validee', '2026-09-08 14:30:00', '[DEMO] Réception carton — conforme');

-- ============================================================
-- 32. RÉCEPTION LIGNES
-- ============================================================
INSERT IGNORE INTO `reception_lignes` (`reception_id`, `ligne_commande_id`, `article_id`, `quantite_attendue`, `quantite_recue`, `quantite_acceptee`, `quantite_perdue`, `prix_achat_unitaire`, `numero_lot`, `date_peremption`) VALUES
(1, 1, 6, 40, 40, 40, 0, 21250.00, 'LOT-DEMO-REC-PEHD-0908', '2027-09-08'),
(1, 2, 7, 35, 35, 35, 0, 19500.00, 'LOT-DEMO-REC-PP-0908', '2027-09-08'),
(2, 1, 24, 200, 200, 200, 0, 600.00, NULL, NULL),
(2, 2, 26, 100, 100, 100, 0, 3000.00, NULL, NULL);

-- ============================================================
-- 33. PERTES FOURNISSEUR
-- ============================================================
INSERT IGNORE INTO `pertes_fournisseur` (`reception_id`, `reception_ligne_id`, `commande_id`, `article_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `quantite`, `motif`, `commentaire`) VALUES
(2, 3, 1, 24, 2, 1, 10, 5, 'endommage', '[DEMO] 5 rouleaux ruban adhésif endommagés lors du transport'),
(1, 2, 1, 7, 2, 1, 10, 2, 'manquant', '[DEMO] 2 sacs carton manquants à la livraison');

-- ============================================================
-- 34. HISTORIQUE PRIX FOURNISSEUR
-- ============================================================
INSERT IGNORE INTO `fournisseur_prix_historique` (`article_id`, `fournisseur_id`, `prix_achat`, `devise`, `est_actif`, `source`, `date_debut`) VALUES
(6, 2, 21250.00, 'XOF', 1, 'commande', '2026-09-01 00:00:00'),
(7, 2, 19500.00, 'XOF', 1, 'commande', '2026-09-01 00:00:00'),
(10, 3, 2400.00, 'XOF', 1, 'commande', '2026-09-01 00:00:00'),
(12, 3, 3500.00, 'XOF', 1, 'commande', '2026-09-01 00:00:00'),
(16, 4, 35000.00, 'XOF', 1, 'manuelle', '2026-09-01 00:00:00'),
(14, 6, 800.00, 'XOF', 1, 'commande', '2026-09-01 00:00:00');

-- ============================================================
-- 35. FACTURES
-- ============================================================
INSERT IGNORE INTO `factures` (`numero_facture`, `date_facture`, `utilisateur_id`, `magasin_id`, `total_ht`, `tva_taux`, `total_ttc`, `montant_paye`, `monnaie_rendue`, `statut`, `statut_transmission`, `client_id`, `client_nom`) VALUES
('DEMO-FACT-001', '2026-09-01 10:30:00', 3, 1, 3600.00, 0.00, 3600.00, 3600.00, 0.00, 'Payee', 'non_transmise', 1, '[DEMO] Aminata Touré'),
('DEMO-FACT-002', '2026-09-02 14:15:00', 3, 1, 5000.00, 0.00, 5000.00, 5000.00, 0.00, 'Payee', 'non_transmise', 2, '[DEMO] Moussa Konaté'),
('DEMO-FACT-003', '2026-09-03 09:00:00', 3, 1, 12000.00, 0.00, 12000.00, 8000.00, 0.00, 'Payee', 'non_transmise', 4, '[DEMO] Société Com-Plus'),
('DEMO-FACT-004', '2026-09-04 11:45:00', 3, 1, 2500.00, 0.00, 2500.00, 2500.00, 0.00, 'Payee', 'non_transmise', 5, '[DEMO] Ibrahim Bamba'),
('DEMO-FACT-005', '2026-09-05 16:00:00', 3, 1, 55000.00, 0.00, 55000.00, 0.00, 0.00, 'Payee', 'non_transmise', 6, '[DEMO] COGITEC SARL'),
('DEMO-FACT-006', '2026-09-06 10:00:00', 3, 1, 3500.00, 0.00, 3500.00, 3500.00, 0.00, 'Payee', 'non_transmise', 8, '[DEMO] Boulangerie Dorée'),
('DEMO-FACT-007', '2026-09-07 13:30:00', 3, 1, 7200.00, 0.00, 7200.00, 5000.00, 0.00, 'Payee', 'non_transmise', 10, '[DEMO] Trans Sahel'),
('DEMO-FACT-008', '2026-09-10 09:15:00', 3, 2, 5500.00, 0.00, 5500.00, 5500.00, 0.00, 'Payee', 'non_transmise', 1, '[DEMO] Aminata Touré'),
('DEMO-FACT-009', '2026-09-11 08:00:00', 3, 1, 1500.00, 0.00, 1500.00, 0.00, 0.00, 'Payee', 'non_transmise', 11, '[DEMO] Khady Niang'),
('DEMO-FACT-010', '2026-09-11 08:30:00', 3, 1, 21000.00, 0.00, 21000.00, 21000.00, 0.00, 'Payee', 'non_transmise', 12, '[DEMO] Menuiserie Bois Prestige');

-- ============================================================
-- 36. LIGNES FACTURE
-- ============================================================
INSERT IGNORE INTO `lignes_facture` (`facture_id`, `article_id`, `quantite`, `prix_unitaire`, `taux_tva`) VALUES
-- Fact 001 : 2 eaux
(1, 10, 2, 1800.00, NULL),
-- Fact 002 : 2 riz
(2, 12, 1, 5000.00, NULL),
-- Fact 003 : 2 téléphones
(3, 16, 1, 55000.00, NULL),
-- Fact 004 : 1 savon
(4, 14, 1, 1500.00, NULL),
-- Fact 005 : 1 téléphone
(5, 16, 1, 55000.00, NULL),
-- Fact 006 : 1 café
(6, 22, 1, 3500.00, NULL),
-- Fact 007 : 3 eaux
(7, 10, 2, 3600.00, NULL),
(7, 11, 2, 2200.00, NULL),
-- Fact 008 : 1 huile + 1 détergent
(8, 13, 1, 5500.00, NULL),
-- Fact 009 : 1 savon
(9, 14, 1, 1500.00, NULL),
-- Fact 010 : 3 pâtes tomates + 2 café
(10, 21, 3, 14000.00, NULL),
(10, 22, 2, 3500.00, NULL);

-- ============================================================
-- 37. PAIEMENTS FACTURE
-- ============================================================
INSERT IGNORE INTO `paiements_facture` (`facture_id`, `mode_paiement`, `montant`, `reference`, `date_paiement`) VALUES
(1, 'Especes', 3600.00, NULL, '2026-09-01 10:30:00'),
(2, 'Mobile_Money', 5000.00, 'OM-DEMO-001', '2026-09-02 14:15:00'),
(3, 'Especes', 5000.00, NULL, '2026-09-03 09:00:00'),
(3, 'Mobile_Money', 3000.00, 'WV-DEMO-002', '2026-09-03 09:05:00'),
(4, 'Especes', 2500.00, NULL, '2026-09-04 11:45:00'),
(5, 'Virement', 0.00, 'VIR-DEMO-001', '2026-09-05 16:00:00'),
(6, 'Carte_Bancaire', 3500.00, 'CB-DEMO-003', '2026-09-06 10:00:00'),
(7, 'Especes', 5000.00, NULL, '2026-09-07 13:30:00'),
(8, 'Mobile_Money', 5500.00, 'OM-DEMO-003', '2026-09-10 09:15:00'),
(10, 'Especes', 21000.00, NULL, '2026-09-11 08:30:00');

-- ============================================================
-- 38. CRÉANCES CLIENTS
-- ============================================================
INSERT IGNORE INTO `creances_clients` (`facture_id`, `client_id`, `montant_total`, `montant_paye`, `reste_a_payer`, `statut`, `date_echeance`) VALUES
(3, 4, 12000.00, 8000.00, 4000.00, 'Partiellement_Payee', '2026-10-03'),
(5, 6, 55000.00, 0.00, 55000.00, 'En_Cours', '2026-10-05'),
(7, 10, 7200.00, 5000.00, 2200.00, 'Partiellement_Payee', '2026-10-07');

-- ============================================================
-- 39. PAIEMENTS CRÉDIT
-- ============================================================
INSERT IGNORE INTO `paiements_credit` (`creance_id`, `montant`, `mode_paiement`, `reference`, `date_paiement`, `utilisateur_id`) VALUES
(1, 8000.00, 'Especes', NULL, '2026-09-03 09:05:00', 3),
(1, 3000.00, 'Mobile_Money', 'WV-CRED-001', '2026-09-08 10:00:00', 3),
(2, 0.00, 'Virement', NULL, '2026-09-05 16:00:00', 3),
(3, 5000.00, 'Especes', NULL, '2026-09-07 13:35:00', 3);

-- ============================================================
-- 40. TRANSFERTS STOCK
-- ============================================================
INSERT IGNORE INTO `transferts_stock` (`article_id`, `magasin_source_id`, `magasin_destination_id`, `quantite`, `utilisateur_id`, `motif`, `date_transfert`) VALUES
(10, 1, 2, 40, 10, '[DEMO] Transfert eaux vers dépôt pour réapprovisionnement', '2026-09-05 10:00:00'),
(14, 1, 2, 70, 10, '[DEMO] Transfert savons vers dépôt', '2026-09-06 09:00:00'),
(19, 1, 2, 80, 10, '[DEMO] Transfert tabac vers dépôt', '2026-09-07 11:00:00'),
(18, 2, 1, 20, 10, '[DEMO] Retour t-shirts du dépôt', '2026-09-09 14:00:00');

-- ============================================================
-- 41. MOUVEMENTS DE STOCK
-- ============================================================
INSERT IGNORE INTO `mouvements_stock` (`article_id`, `utilisateur_id`, `magasin_id`, `type`, `quantite`, `stock_avant`, `stock_apres`, `cout_unitaire`, `reference_type`, `reference_id`, `date_mouvement`, `motif`) VALUES
-- Entrées réception
(6, 10, 1, 'RECEPTION', 40, 0, 40, 21250.00, 'RECEPTION', 1, '2026-09-08 14:00:00', 'Réception commande DEMO-REC-001'),
(7, 10, 1, 'RECEPTION', 35, 0, 35, 19500.00, 'RECEPTION', 1, '2026-09-08 14:00:00', 'Réception commande DEMO-REC-001'),
(24, 10, 1, 'RECEPTION', 200, 0, 200, 600.00, 'RECEPTION', 2, '2026-09-08 14:30:00', 'Réception commande DEMO-REC-002'),
(26, 10, 1, 'RECEPTION', 100, 0, 100, 3000.00, 'RECEPTION', 2, '2026-09-08 14:30:00', 'Réception commande DEMO-REC-002'),
-- Sorties ventes
(10, 3, 1, 'VENTE', 2, 62, 60, 2400.00, 'FACTURE', 1, '2026-09-01 10:30:00', 'Vente facture DEMO-FACT-001'),
(12, 3, 1, 'VENTE', 1, 31, 30, 3500.00, 'FACTURE', 2, '2026-09-02 14:15:00', 'Vente facture DEMO-FACT-002'),
(16, 3, 1, 'VENTE', 1, 16, 15, 35000.00, 'FACTURE', 3, '2026-09-03 09:00:00', 'Vente facture DEMO-FACT-003'),
(14, 3, 1, 'VENTE', 1, 81, 80, 800.00, 'FACTURE', 4, '2026-09-04 11:45:00', 'Vente facture DEMO-FACT-004'),
(16, 3, 1, 'VENTE', 1, 15, 14, 35000.00, 'FACTURE', 5, '2026-09-05 16:00:00', 'Vente facture DEMO-FACT-005'),
(22, 3, 1, 'VENTE', 1, 31, 30, 2000.00, 'FACTURE', 6, '2026-09-06 10:00:00', 'Vente facture DEMO-FACT-006'),
(10, 3, 1, 'VENTE', 2, 62, 60, 2400.00, 'FACTURE', 7, '2026-09-07 13:30:00', 'Vente facture DEMO-FACT-007'),
(13, 3, 1, 'VENTE', 1, 26, 25, 4000.00, 'FACTURE', 8, '2026-09-10 09:15:00', 'Vente facture DEMO-FACT-008'),
(14, 3, 1, 'VENTE', 1, 81, 80, 800.00, 'FACTURE', 9, '2026-09-11 08:00:00', 'Vente facture DEMO-FACT-009'),
(21, 3, 1, 'VENTE', 3, 23, 20, 9600.00, 'FACTURE', 10, '2026-09-11 08:30:00', 'Vente facture DEMO-FACT-010'),
(22, 3, 1, 'VENTE', 2, 32, 30, 2000.00, 'FACTURE', 10, '2026-09-11 08:30:00', 'Vente facture DEMO-FACT-010'),
-- Transferts
(10, 10, 1, 'TRANSFERT_SORTIE', 40, 100, 60, 2400.00, 'TRANSFERT', 1, '2026-09-05 10:00:00', 'Transfert vers dépôt'),
(14, 10, 1, 'TRANSFERT_SORTIE', 70, 150, 80, 800.00, 'TRANSFERT', 2, '2026-09-06 09:00:00', 'Transfert vers dépôt'),
(19, 10, 1, 'TRANSFERT_SORTIE', 80, 200, 120, 1500.00, 'TRANSFERT', 3, '2026-09-07 11:00:00', 'Transfert vers dépôt'),
-- Productions
(1, 10, 1, 'PRODUCTION', 97, 0, 97, 1800.00, 'PRODUCTION', 1, '2026-09-10 13:30:00', 'Production DEMO-PROD-001'),
(2, 10, 1, 'PRODUCTION', 148, 0, 148, 850.00, 'PRODUCTION', 2, '2026-09-10 12:00:00', 'Production DEMO-PROD-002'),
(3, 10, 1, 'PRODUCTION', 490, 0, 490, 320.00, 'PRODUCTION', 3, '2026-09-11 11:00:00', 'Production DEMO-PROD-003'),
-- Pertes
(24, 10, 1, 'PERTE', 5, 200, 195, 600.00, 'PERTE_FOURNISSEUR', 1, '2026-09-08 15:00:00', 'Perte fournisseur DEMO-PF-001');

-- ============================================================
-- 42. CLÔTURES CAISSE
-- ============================================================
INSERT IGNORE INTO `clotures_caisse` (`magasin_id`, `utilisateur_id`, `date_cloture`, `montant_attendu`, `montant_reel`, `ecart`, `statut`, `date_creation`) VALUES
(1, 3, '2026-09-01', 3600.00, 3600.00, 0.00, 'VALIDE', '2026-09-01 18:10:00'),
(1, 3, '2026-09-02', 5000.00, 5000.00, 0.00, 'VALIDE', '2026-09-02 18:10:00'),
(1, 3, '2026-09-03', 8000.00, 8100.00, 100.00, 'VALIDE', '2026-09-03 18:10:00'),
(1, 3, '2026-09-04', 2500.00, 2500.00, 0.00, 'VALIDE', '2026-09-04 18:10:00'),
(1, 3, '2026-09-05', 0.00, 0.00, 0.00, 'VALIDE', '2026-09-05 18:10:00'),
(1, 3, '2026-09-06', 3500.00, 3500.00, 0.00, 'VALIDE', '2026-09-06 18:10:00'),
(1, 3, '2026-09-07', 5000.00, 5000.00, 0.00, 'VALIDE', '2026-09-07 18:10:00'),
(2, 3, '2026-09-10', 5500.00, 5450.00, -50.00, 'VALIDE', '2026-09-10 18:10:00'),
(1, 3, '2026-09-11', 0.00, 0.00, 0.00, 'VALIDE', '2026-09-11 18:10:00');

-- ============================================================
-- 43. DÉPENSES
-- ============================================================
INSERT IGNORE INTO `depenses` (`magasin_id`, `utilisateur_id`, `titre`, `categorie`, `montant`, `date_depense`, `description`) VALUES
(1, 10, '[DEMO] Électricité septembre', 'Loyer & charges', 85000.00, '2026-09-01', 'Facture électricité EDF — magasin principal'),
(1, 10, '[DEMO] Entretien climatisation', 'Maintenance', 25000.00, '2026-09-05', 'Recharge gaz climatiseur'),
(2, 10, '[DEMO] Assurance véhicule', 'Transport', 45000.00, '2026-09-03', 'Prime assurance camion livraison'),
(1, 10, '[DEMO] Fournitures bureau', 'Fournitures', 8000.00, '2026-09-07', 'Papier, stylos, encre'),
(NULL, 10, '[DEMO] Internet & téléphone', 'Services', 35000.00, '2026-09-01', 'Abonnement internet + forfait mobile');

-- ============================================================
-- 44. RETOURS FACTURES
-- ============================================================
INSERT IGNORE INTO `retours_factures` (`numero_retour`, `facture_id`, `magasin_id`, `utilisateur_id`, `montant_total`, `motif`, `statut`) VALUES
('DEMO-RET-001', 4, 1, 3, 1500.00, '[DEMO] Client insatisfait — savon défectueux', 'Valide'),
('DEMO-RET-002', 8, 2, 3, 5500.00, '[DEMO] Huile périmée — retour fournisseur', 'Valide');

-- ============================================================
-- 45. LIGNES RETOUR
-- ============================================================
INSERT IGNORE INTO `lignes_retour` (`retour_id`, `ligne_facture_id`, `article_id`, `quantite`, `prix_unitaire`) VALUES
(1, 4, 14, 1, 1500.00),
(2, 9, 13, 1, 5500.00);

-- ============================================================
-- 46. LOGS ACTIVITÉ
-- ============================================================
INSERT IGNORE INTO `logs_activite` (`utilisateur_id`, `action`, `details`, `ip_address`, `date_action`) VALUES
(1, 'CONNEXION', 'Connexion réussie — rôle PROPRIETAIRE', '127.0.0.1', '2026-09-11 08:00:00'),
(10, 'SEED_DEMO', 'Import données démo — script demo_data.sql', '127.0.0.1', '2026-09-11 09:00:00'),
(3, 'VENTE', 'Création facture DEMO-FACT-001 — 3 600 XOF', '192.168.1.10', '2026-09-01 10:30:00'),
(3, 'VENTE', 'Création facture DEMO-FACT-002 — 5 000 XOF', '192.168.1.10', '2026-09-02 14:15:00'),
(10, 'COMMANDE', 'Création commande DEMO-CMD-001 fournisseur Palm CI', '127.0.0.1', '2026-09-05 09:00:00'),
(10, 'RECEPTION', 'Réception DEMO-REC-001 — 2 lignes validées', '127.0.0.1', '2026-09-08 14:00:00'),
(10, 'TRANSFERT', 'Transfert eaux Magasin→Dépôt — 40 unités', '127.0.0.1', '2026-09-05 10:00:00'),
(10, 'PRODUCTION', 'Production DEMO-PROD-001 terminée — 97 seaux', '192.168.2.5', '2026-09-10 13:30:00');

-- ============================================================
-- 47. NOTIFICATIONS
-- ============================================================
INSERT IGNORE INTO `notifications` (`type`, `titre`, `type_notif`, `message`, `cible_role`, `cible_utilisateur_id`, `lu`, `statut`) VALUES
('STOCK', 'Stock bas détecté', 'ALERTE_STOCK', '[DEMO] Stock bas pour article DEM-ART-020 (Ampoule LED 9W) — 50 unités restantes', 'MAGASINIER', NULL, 0, 'EN_ATTENTE'),
('PRODUCTION', 'Production planifiée', 'INFO_PRODUCTION', '[DEMO] Production DEMO-PROD-004 (Bidon 10L) planifiée pour demain', 'CHEF_EQUIPE_USINE', NULL, 0, 'EN_ATTENTE'),
('CREDIT', 'Créance en souffrance', 'ALERTE_CREDIT', '[DEMO] Créance COGITEC SARL — 55 000 XOF impayés depuis 6 jours', 'VENDEUR', 3, 0, 'EN_ATTENTE'),
('COMMANDE', 'Commande en cours', 'INFO_COMMANDE', '[DEMO] Commande DEMO-CMD-002 en cours de livraison — arrivée prévue le 13/09', 'ADMIN', 10, 1, 'LUE');

-- ============================================================
-- 48. SÉQUENCES (compteurs)
-- ============================================================
INSERT IGNORE INTO `sequences` (`cle`, `valeur`) VALUES
('facture_numero', 10),
('commande_fournisseur', 3),
('reception_reference', 2),
('retour_numero', 2),
('inventaire_reference', 0),
('production_reference', 4);

-- ============================================================
-- 49. PARAMÈTRES
-- ============================================================
INSERT IGNORE INTO `parametres` (`cle`, `valeur`, `categorie`, `description`) VALUES
('DEMO_MODE', '1', 'general', '[DEMO] Mode démo activé — données fictives'),
('devise_defaut', 'XOF', 'general', 'Devise par défaut du système'),
('taux_tva_defaut', '0', 'facturation', 'Taux TVA par défaut'),
('seuil_alerte_stock', '5', 'stock', 'Seuil d alerte stock bas par défaut'),
('delai_paiement_defaut', '30', 'credit', 'Délai de paiement crédit par défaut (jours)')
ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`);

-- ============================================================
-- FIN DU SEED
-- ============================================================

SET foreign_key_checks = 1;

COMMIT;

-- ============================================================
-- VÉRIFICATION RAPIDE
-- ============================================================
-- Nombre de lignes insérées par table (debug)
-- SELECT 'categories' AS tbl, COUNT(*) AS nb FROM categories WHERE nom LIKE '[DEMO]%'
-- UNION ALL SELECT 'clients', COUNT(*) FROM clients WHERE nom LIKE '[DEMO]%'
-- UNION ALL SELECT 'fournisseurs', COUNT(*) FROM fournisseurs WHERE nom LIKE '[DEMO]%'
-- UNION ALL SELECT 'articles', COUNT(*) FROM articles WHERE nom LIKE '[DEMO]%'
-- UNION ALL SELECT 'matieres_premieres', COUNT(*) FROM matieres_premieres WHERE reference LIKE 'DEMP-%'
-- UNION ALL SELECT 'productions', COUNT(*) FROM productions WHERE reference LIKE 'DEMO-PROD%'
-- UNION ALL SELECT 'factures', COUNT(*) FROM factures WHERE numero_facture LIKE 'DEMO-FACT%'
-- UNION ALL SELECT 'mouvements_stock', COUNT(*) FROM mouvements_stock WHERE motif LIKE '[DEMO]%';
