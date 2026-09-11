-- Migration credit complète : tables + permissions + colonnes clients
-- Exécuter ce fichier sur estock_db si les tables credit n'existent pas

-- 1. Tables
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

-- 2. FK
SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'creances_clients' AND CONSTRAINT_NAME = 'fk_creance_facture');
SET @sql = IF(@fk = 0, "ALTER TABLE creances_clients ADD CONSTRAINT fk_creance_facture FOREIGN KEY (facture_id) REFERENCES factures (id) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'creances_clients' AND CONSTRAINT_NAME = 'fk_creance_client');
SET @sql = IF(@fk = 0, "ALTER TABLE creances_clients ADD CONSTRAINT fk_creance_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paiements_credit' AND CONSTRAINT_NAME = 'fk_pc_creance');
SET @sql = IF(@fk = 0, "ALTER TABLE paiements_credit ADD CONSTRAINT fk_pc_creance FOREIGN KEY (creance_id) REFERENCES creances_clients (id) ON DELETE RESTRICT", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3. Colonnes credit sur table clients (si absentes)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'credit_autorise');
SET @sql = IF(@col = 0, "ALTER TABLE clients ADD COLUMN credit_autorise TINYINT(1) NOT NULL DEFAULT 0 AFTER consentement_fidelite", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'limite_credit');
SET @sql = IF(@col = 0, "ALTER TABLE clients ADD COLUMN limite_credit decimal(12,2) NOT NULL DEFAULT 0.00 AFTER credit_autorise", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 4. Permissions
INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('credit_consulter', 'Consulter les creances et soldes clients', 'Credit'),
  ('credit_creer', 'Creer une vente a credit', 'Credit'),
  ('credit_paiement_creer', 'Enregistrer un remboursement sur creance', 'Credit'),
  ('credit_paiement_consulter', 'Consulter l''historique des remboursements', 'Credit'),
  ('credit_modifier', 'Modifier les conditions de credit (limite, echeance)', 'Credit'),
  ('credit_annuler', 'Annuler une creance', 'Credit'),
  ('credit_rapport', 'Consulter les rapports de creances', 'Credit'),
  ('credit_override_limit', 'Depasser la limite de credit autorisee', 'Credit');

-- 5. Role permissions
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'PROPRIETAIRE', id FROM `permissions`;

INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'ADMIN', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_modifier','credit_rapport','credit_override_limit');

INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'VENDEUR', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_creer','credit_paiement_creer');

INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_creer','credit_paiement_creer');

INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'MAGASINIER', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_creer','credit_paiement_creer');
