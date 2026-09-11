-- Migration : Ajouter les permissions credit manquantes
-- A executer une seule fois sur la base de donnees

-- 1. Inserer les permissions credit (ignore si deja present)
INSERT IGNORE INTO `permissions` (`cle_permission`, `description`, `categorie`) VALUES
  ('credit_consulter', 'Consulter les creances et soldes clients', 'Credit'),
  ('credit_creer', 'Creer une vente a credit', 'Credit'),
  ('credit_paiement_creer', 'Enregistrer un remboursement sur creance', 'Credit'),
  ('credit_paiement_consulter', 'Consulter l''historique des remboursements', 'Credit'),
  ('credit_modifier', 'Modifier les conditions de credit (limite, echeance)', 'Credit'),
  ('credit_annuler', 'Annuler une creance', 'Credit'),
  ('credit_rapport', 'Consulter les rapports de creances', 'Credit'),
  ('credit_override_limit', 'Depasser la limite de credit autorisee', 'Credit');

-- 2. Affecter les permissions credit a tous les roles qui en ont besoin
-- PROPRIETAIRE : toutes les permissions
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'PROPRIETAIRE', id FROM `permissions` WHERE `cle_permission` LIKE 'credit%';

-- ADMIN
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'ADMIN', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_modifier','credit_rapport','credit_override_limit');

-- VENDEUR
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'VENDEUR', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_creer','credit_paiement_creer');

-- CHEF_EQUIPE
INSERT IGNORE INTO `role_permissions` (`role_nom`, `permission_id`)
SELECT 'CHEF_EQUIPE', id FROM `permissions` WHERE `cle_permission` IN
  ('credit_consulter','credit_creer','credit_paiement_creer');
