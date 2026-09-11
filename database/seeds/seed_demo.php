<?php
/**
 * seed_demo.php — Seed de données démo complètes pour eStock v2.7.0
 *
 * Usage : php database/seeds/seed_demo.php
 *
 * - Re-runnable (idempotent) : DELETE puis INSERT
 * - Préfixe [DEMO] sur tous les noms
 * - Résolution dynamique des IDs via lastInsertId() / requêtes SELECT
 * - Gestion propre des triggers immuables (DROP / CREATE)
 * - Respect de l'ordre FK avec foreign_key_checks = 0
 */

// ─── Chargement de la connexion PDO ────────────────────────────
require_once __DIR__ . '/../../config/connexion.php';

// ─── Helpers ───────────────────────────────────────────────────

/**
 * Exécute un INSERT simple avec paramètres nommés.
 */
function seedInsert(PDO $pdo, string $sql, array $data = []): void {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
}

/**
 * Exécute un INSERT et retourne lastInsertId().
 */
function seedInsertId(PDO $pdo, string $sql, array $data = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return (int) $pdo->lastInsertId();
}

/**
 * INSERT IGNORE pour les tables à clé composite.
 */
function seedInsertIgnore(PDO $pdo, string $sql, array $data = []): void {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
}

/**
 * Récupère un ID par lookup.
 */
function seedLookupId(PDO $pdo, string $sql, array $data = []): ?int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

/**
 * Récupère une colonne par lookup.
 */
function seedLookup(PDO $pdo, string $sql, array $data = []): ?string {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string) $val : null;
}

/**
 * Exécute un DROP TRIGGER IF EXISTS.
 */
function seedDropTrigger(PDO $pdo, string $name): void {
    $pdo->exec("DROP TRIGGER IF EXISTS `{$name}`");
}

/**
 * Exécute CREATE TRIGGER (DELIMITER géré via exec direct).
 */
function seedCreateTrigger(PDO $pdo, string $name, string $body): void {
    $pdo->exec("DROP TRIGGER IF EXISTS `{$name}`");
    $pdo->exec("CREATE TRIGGER `{$name}` {$body}");
}

// ─── Début du script ──────────────────────────────────────────

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== seed_demo.php — Démarrage ===\n";

// ─── 0. Désactiver FK + supprimer les 10 triggers ─────────────
$pdo->exec("SET foreign_key_checks = 0");

$triggers = [
    'trg_clotures_immutable_delete',
    'trg_clotures_immutable_update',
    'trg_factures_immutable_delete',
    'trg_factures_immutable_update',
    'trg_lignes_facture_immutable_delete',
    'trg_lignes_facture_immutable_update',
    'trg_paiements_immutable_delete',
    'trg_paiements_immutable_update',
    'trg_receptions_immutable_delete',
    'trg_receptions_immutable_update',
];
foreach ($triggers as $t) {
    seedDropTrigger($pdo, $t);
}
echo "[OK] Triggers supprimés, FK désactivées.\n";

// ─── 1. NETTOYAGE (ordre inverse des dépendances FK) ──────────

// Sous-arbre créances/paiements credit
$pdo->exec("DELETE FROM `paiements_credit` WHERE `creance_id` IN (SELECT id FROM `creances_clients` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%'))");
$pdo->exec("DELETE FROM `creances_clients` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%')");

// Sous-arbre retours
$pdo->exec("DELETE FROM `lignes_retour` WHERE `retour_id` IN (SELECT id FROM `retours_factures` WHERE numero_retour LIKE 'DEMO-RET%')");
$pdo->exec("DELETE FROM `retours_factures` WHERE numero_retour LIKE 'DEMO-RET%'");

// Sous-arbre factures (triggers déjà supprimés)
$pdo->exec("DELETE FROM `paiements_facture` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%')");
$pdo->exec("DELETE FROM `lignes_facture` WHERE `facture_id` IN (SELECT id FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%')");
$pdo->exec("DELETE FROM `factures` WHERE numero_facture LIKE 'DEMO-FACT%'");

// Sous-arbre production
$pdo->exec("DELETE FROM `production_pertes` WHERE `production_id` IN (SELECT id FROM `productions` WHERE reference LIKE 'DEMO-PROD%')");
$pdo->exec("DELETE FROM `production_matieres` WHERE `production_id` IN (SELECT id FROM `productions` WHERE reference LIKE 'DEMO-PROD%')");
$pdo->exec("DELETE FROM `productions` WHERE reference LIKE 'DEMO-PROD%'");

// Sous-arbre recettes
$pdo->exec("DELETE FROM `recettes_lignes` WHERE `recette_id` IN (SELECT id FROM `recettes` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `recettes` WHERE nom LIKE '[DEMO]%'");

// Sous-arbre réceptions
$pdo->exec("DELETE FROM `pertes_fournisseur` WHERE `commentaire` LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `reception_lignes` WHERE `reception_id` IN (SELECT id FROM `receptions` WHERE reference LIKE 'DEMO-REC%')");
$pdo->exec("DELETE FROM `receptions` WHERE reference LIKE 'DEMO-REC%'");

// Sous-arbre commandes
$pdo->exec("DELETE FROM `lignes_commande_fournisseur` WHERE `commande_id` IN (SELECT id FROM `commandes_fournisseur` WHERE notes LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `commandes_fournisseur` WHERE notes LIKE '[DEMO]%'");

// Sous-arbre transferts
$pdo->exec("DELETE FROM `transferts_stock` WHERE motif LIKE '[DEMO]%'");

// Sous-arbre mouvements
$pdo->exec("DELETE FROM `mouvements_stock` WHERE motif LIKE '[DEMO]%' OR motif LIKE 'Production DEMO%' OR motif LIKE 'Reception commande DEMO%' OR motif LIKE 'Vente facture DEMO%' OR motif LIKE 'Perte fournisseur DEMO%'");

// Sous-arbre article_couts / lots
$pdo->exec("DELETE FROM `article_couts` WHERE `reference` LIKE 'DEMO-CMD%'");
$pdo->exec("DELETE FROM `article_lots` WHERE `numero_lot` LIKE 'LOT-DEMO%'");
$pdo->exec("DELETE FROM `fournisseur_prix_historique` WHERE `source` = 'manuelle' AND `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `fournisseur_prix_historique` WHERE `source` = 'commande' AND `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%')");

// Sous-arbre stock
$pdo->exec("DELETE FROM `stock_magasins` WHERE `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `stock_produits_finis_usine` WHERE `article_id` IN (SELECT id FROM `articles` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `stock_matieres_premieres` WHERE `matiere_id` IN (SELECT id FROM `matieres_premieres` WHERE reference LIKE 'DEMP-%')");

// Articles
$pdo->exec("DELETE FROM `articles` WHERE nom LIKE '[DEMO]%'");

// Matières premières
$pdo->exec("DELETE FROM `matieres_premieres` WHERE reference LIKE 'DEMP-%'");

// Autres tables
$pdo->exec("DELETE FROM `machines` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `machine_etats` WHERE `machine_id` NOT IN (SELECT id FROM `machines`)");
$pdo->exec("DELETE FROM `presences_employes` WHERE `employe_id` IN (SELECT id FROM `employes` WHERE matricule LIKE 'DEMP-EMP%')");
$pdo->exec("DELETE FROM `employes` WHERE matricule LIKE 'DEMP-EMP%'");
$pdo->exec("DELETE FROM `depenses` WHERE titre LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `logs_activite` WHERE action = 'SEED_DEMO'");
$pdo->exec("DELETE FROM `clotures_caisse` WHERE `utilisateur_id` = 3 AND `date_cloture` >= '2026-09-01'");
$pdo->exec("DELETE FROM `notifications` WHERE message LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `equipe_magasins` WHERE `equipe_id` IN (SELECT id FROM `equipes` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `equipe_membres` WHERE `equipe_id` IN (SELECT id FROM `equipes` WHERE nom LIKE '[DEMO]%')");
$pdo->exec("DELETE FROM `equipes` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `user_magasins` WHERE `user_id` IN (1,3,7,10) AND `date_debut` > '2026-08-01'");
$pdo->exec("DELETE FROM `promotions` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `regles_promotions` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `usines` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `clients` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `fournisseurs` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `categories` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `horaires_travail` WHERE nom LIKE '[DEMO]%'");
$pdo->exec("DELETE FROM `sequences` WHERE cle IN ('facture_numero','commande_fournisseur','reception_reference','retour_numero','inventaire_reference','production_reference')");
$pdo->exec("DELETE FROM `parametres` WHERE cle = 'DEMO_MODE'");

echo "[OK] Nettoyage terminé.\n";

// ─── 2. INSERTION DES DONNÉES ─────────────────────────────────

// ============================================================
// PHASE 1 : ENTITÉS DE BASE
// ============================================================

// ── Categories ───────────────────────────────────────────────
$categories = [
    ['[DEMO] Boissons',         'Eaux, jus, sodas, boissons energisantes'],
    ['[DEMO] Alimentaire sec',  'Riz, pates, farines, huiles, epices'],
    ['[DEMO] Hygiène',          'Savons, shampoings, detergents'],
    ['[DEMO] Électronique',     'Telephones, accessoires, cables'],
    ['[DEMO] Textile',          'Vetements, tissus, chaussures'],
    ['[DEMO] Entretien',        'Produits menagers, detergents'],
    ['[DEMO] Tabac',            'Cigarettes, allumettes'],
    ['[DEMO] Bricolage',        'Outillage, peinture, visserie'],
];
$catIds = [];
foreach ($categories as $c) {
    $catIds[$c[0]] = seedInsertId($pdo,
        "INSERT INTO `categories` (`nom`,`description`,`actif`) VALUES (:nom, :desc, 1)",
        [':nom' => $c[0], ':desc' => $c[1]]
    );
}
echo "[OK] Categories ({$catIds['[DEMO] Bricolage']}) insérées.\n";

// ── Fournisseurs ─────────────────────────────────────────────
$fournisseurs = [
    ['[DEMO] Sen Distribution',  'Mamadou Diallo',   '+221 77 123 4567', 'XOF'],
    ['[DEMO] Palm CI',           'Ibrahim Kouassi',  '+225 07 08 09 10', 'XOF'],
    ['[DEMO] Agro-Alim SA',      'Fatou Sow',        '+221 78 234 5678', 'XOF'],
    ['[DEMO] TechImport',        'Jean-Pierre Mensah','+228 90 12 34 56', 'XOF'],
    ['[DEMO] TextPro',           'Aissatou Ba',      '+221 76 345 6789', 'XOF'],
    ['[DEMO] Cosmétique Plus',   'Oumar Ndiaye',     '+221 70 456 7890', 'XOF'],
];
$fournisseurIds = [];
foreach ($fournisseurs as $f) {
    $fournisseurIds[$f[0]] = seedInsertId($pdo,
        "INSERT INTO `fournisseurs` (`nom`,`contact`,`telephone`,`devise`) VALUES (:nom,:contact,:tel,:devise)",
        [':nom' => $f[0], ':contact' => $f[1], ':tel' => $f[2], ':devise' => $f[3]]
    );
}
echo "[OK] Fournisseurs insérés.\n";

// ── Clients ──────────────────────────────────────────────────
$clients = [
    ['[DEMO] Aminata Toure',        null, null, null, null, 'Rue 12, Cocody', '+221 77 111 2233', 'aminata.demo@test.com', 'DEM-CLI-001', 250, 1, 'boutique', 0, 0.00, '2026-09-10 14:30:00'],
    ['[DEMO] Moussa Konate',        'Konate & Fils SARL', 'NIF-98765', 'RCCM-1234', null, 'Bd de la Republique, Abidjan', '+222 33 445 566', 'moussa.demo@test.com', 'DEM-CLI-002', 80, 1, 'boutique', 1, 500000.00, '2026-09-09 10:15:00'],
    ['[DEMO] Fatima Sy',            null, null, null, null, 'Quartier HLM, Dakar', '+221 78 777 888', null, 'DEM-CLI-003', 0, 0, null, 0, 0.00, null],
    ['[DEMO] Societe Com-Plus',     'Com-Plus SARL', 'NIF-54321', 'RCCM-5678', 'SIRET-67890', 'Zone Industrielle, Bamako', '+223 76 901 234', 'contact@complus-demo.com', 'DEM-CLI-004', 1200, 1, 'web', 1, 2000000.00, '2026-09-11 08:00:00'],
    ['[DEMO] Ibrahim Bamba',        null, null, null, null, 'Rue Felix Faure', '+221 70 222 333', null, 'DEM-CLI-005', 45, 1, 'caisse', 0, 0.00, '2026-09-08 16:45:00'],
    ['[DEMO] COGITEC SARL',         'COGITEC', 'NIF-11111', 'RCCM-2222', null, 'Plateau, Abidjan', '+222 21 333 444', 'cogitec.demo@test.ci', 'DEM-CLI-006', 500, 1, 'web', 1, 3500000.00, '2026-09-07 11:20:00'],
    ['[DEMO] Awa Diop',             null, null, null, null, 'Medina, Dakar', '+221 76 444 555', null, 'DEM-CLI-007', 15, 0, null, 0, 0.00, '2026-09-10 09:00:00'],
    ['[DEMO] Boulangerie Doree',    'Doree SARL', 'NIF-66666', 'RCCM-7777', null, 'Rue de Commerce', '+221 77 888 999', 'doree.demo@test.com', 'DEM-CLI-008', 320, 1, 'boutique', 1, 750000.00, '2026-09-11 07:30:00'],
    ['[DEMO] Ousmane Fall',         null, null, null, null, 'Grand Yoff', '+221 78 000 111', null, 'DEM-CLI-009', 0, 0, null, 0, 0.00, null],
    ['[DEMO] Trans Sahel',          'Trans Sahel Transport', 'NIF-88888', 'RCCM-9999', null, 'Route Nationale 1', '+223 79 222 333', 'transsahel.demo@test.ml', 'DEM-CLI-010', 890, 1, 'web', 1, 5000000.00, '2026-09-06 15:00:00'],
    ['[DEMO] Khady Niang',          null, null, null, null, 'Parcelles Assainies', '+221 70 555 666', null, 'DEM-CLI-011', 60, 1, 'boutique', 0, 0.00, '2026-09-09 13:10:00'],
    ['[DEMO] Menuiserie Bois Prestige', 'Bois Prestige SARL', 'NIF-44444', 'RCCM-3333', null, 'Zone Artisanat', '+221 76 777 888', 'boisprestige.demo@test.com', 'DEM-CLI-012', 200, 1, 'boutique', 1, 1200000.00, '2026-09-10 16:20:00'],
];
$clientIds = [];
foreach ($clients as $c) {
    $clientIds[$c[0]] = seedInsertId($pdo,
        "INSERT INTO `clients` (`nom`,`raison_sociale`,`nif`,`rccm`,`siret`,`adresse`,`telephone`,`email`,`code_fidelite`,`points_fidelite`,`consentement_fidelite`,`source_consentement`,`credit_autorise`,`limite_credit`,`date_dernier_achat`)
         VALUES (:nom,:rs,:nif,:rccm,:siret,:adr,:tel,:email,:code_fidelite,:pts,:consentement,:src_consent,:credit_autorise,:limite_credit,:date_dernier_achat)",
        [
            ':nom' => $c[0], ':rs' => $c[1], ':nif' => $c[2], ':rccm' => $c[3], ':siret' => $c[4],
            ':adr' => $c[5], ':tel' => $c[6], ':email' => $c[7], ':code_fidelite' => $c[8],
            ':pts' => $c[9], ':consentement' => $c[10], ':src_consent' => $c[11],
            ':credit_autorise' => $c[12], ':limite_credit' => $c[13], ':date_dernier_achat' => $c[14],
        ]
    );
}
echo "[OK] Clients ({$clientIds['[DEMO] Menuiserie Bois Prestige']}) insérés.\n";

// ── Usines ───────────────────────────────────────────────────
$usines = [
    ['DEM-USINE-01', '[DEMO] Usine Yopougon', 'Unite de production principale - plasturgie et agroalimentaire', 'Zone Industrielle, Yopougon, Abidjan'],
    ['DEM-USINE-02', '[DEMO] Usine Abobo',    'Unite secondaire - conditionnement et emballage', 'Quartier Industriel, Abobo, Abidjan'],
];
$usineIds = [];
foreach ($usines as $u) {
    $usineIds[$u[1]] = seedInsertId($pdo,
        "INSERT INTO `usines` (`code`,`nom`,`description`,`adresse`,`actif`) VALUES (:code,:nom,:desc,:adr,1)",
        [':code' => $u[0], ':nom' => $u[1], ':desc' => $u[2], ':adr' => $u[3]]
    );
}
echo "[OK] Usines insérées.\n";

// ── Matières premières ───────────────────────────────────────
// categorie_id → categories_matieres_premieres
$mpCategories = [
    1 => 'Matieres plastiques',
    2 => 'Additifs',
    3 => 'Emballages',
    4 => 'Produits chimiques',
    5 => 'Consommables usine',
    6 => 'Matières organiques',
    7 => 'Métaux',
];
$mpCatIds = [];
foreach ($mpCategories as $k => $v) {
    $mpCatIds[$k] = seedLookupId($pdo, "SELECT id FROM `categories_matieres_premieres` WHERE `nom` = :nom", [':nom' => $v]);
}

$matieres = [
    ['DEMP-001', '[DEMO] Granule PEHD 001',    1, 'KG',  850.0000, 500, 2, 'Polyethylene haute densite'],
    ['DEMP-002', '[DEMO] Granule PP 002',       1, 'KG',  780.0000, 500, 2, 'Polypropylene - granules blancs'],
    ['DEMP-003', '[DEMO] Colorant Noir',         2, 'KG', 3200.0000,  50, 6, 'Colorant noir concentre'],
    ['DEMP-004', '[DEMO] Colorant Bleu',         2, 'KG', 3500.0000,  50, 6, 'Colorant bleu concentre'],
    ['DEMP-005', '[DEMO] Stabilisant UV',        2, 'KG', 5600.0000,  20, 6, 'Stabilisant anti-UV'],
    ['DEMP-006', '[DEMO] Film etirable 30µ',     3, 'KG', 1200.0000, 200, 1, 'Film etirable pour emballage palette'],
    ['DEMP-007', '[DEMO] Carton ondule 700g',    3, 'KG',  650.0000, 300, 1, 'Carton ondule grands formats'],
    ['DEMP-008', '[DEMO] Colle neoprene',        4, 'L',  4500.0000,  30, 3, 'Colle neoprene industrielle'],
    ['DEMP-009', '[DEMO] Huile moteur 15W40',   5, 'L',  2800.0000,  50, 3, 'Huile moteur synthetique'],
    ['DEMP-010', '[DEMO] Sirop Baobab 5L',      6, 'L',  3500.0000, 100, 3, 'Sirop de baobab concentre'],
    ['DEMP-011', '[DEMO] Pate tomate 2.5kg',     6, 'KG', 1800.0000, 150, 3, 'Concentre de tomate'],
    ['DEMP-012', '[DEMO] Sel iode 1kg',          6, 'KG',  500.0000, 200, 3, 'Sel de cuisine iode'],
    ['DEMP-013', '[DEMO] Cafe en grains 1kg',    6, 'KG', 6500.0000,  80, 3, 'Cafe arabica torrefie'],
    ['DEMP-014', '[DEMO] Feuille alu 250g',      7, 'KG', 4800.0000,  60, 1, 'Papier aluminium culinaire'],
    ['DEMP-015', '[DEMO] Mastic silicone',        8, 'L',  7200.0000,  25, 4, 'Mastic silicone sanitaire'],
];
$mpIds = [];
foreach ($matieres as $m) {
    $catId = $mpCatIds[$m[2]] ?? null;
    $mpIds[$m[0]] = seedInsertId($pdo,
        "INSERT INTO `matieres_premieres` (`reference`,`nom`,`categorie_id`,`unite_mesure`,`cout_reference`,`stock_minimum`,`fournisseur_id`,`actif`,`notes`)
         VALUES (:ref,:nom,:cat_id,:unite,:cout,:stock_min,:four_id,1,:notes)",
        [
            ':ref' => $m[0], ':nom' => $m[1], ':cat_id' => $catId, ':unite' => $m[3],
            ':cout' => $m[4], ':stock_min' => $m[5], ':four_id' => $fournisseurIds[$fournisseurs[$m[6] - 1][0]] ?? null,
            ':notes' => $m[7],
        ]
    );
}
echo "[OK] Matières premières insérées.\n";

// ── Stock matières premières ──────────────────────────────────
$stockMpData = [
    ['DEMP-001', 2500.0000, 2125000.00],
    ['DEMP-002', 1800.0000, 1404000.00],
    ['DEMP-003',    45.0000,  144000.00],
    ['DEMP-004',    30.0000,  105000.00],
    ['DEMP-005',    15.0000,   84000.00],
    ['DEMP-006',   180.0000,  216000.00],
    ['DEMP-007',   220.0000,  143000.00],
    ['DEMP-008',    12.0000,   54000.00],
    ['DEMP-009',    25.0000,   70000.00],
    ['DEMP-010',    80.0000,  280000.00],
    ['DEMP-011',   100.0000,  180000.00],
    ['DEMP-012',   150.0000,   75000.00],
    ['DEMP-013',    40.0000,  260000.00],
    ['DEMP-014',    30.0000,  144000.00],
    ['DEMP-015',    10.0000,   72000.00],
];
foreach ($stockMpData as $s) {
    seedInsert($pdo,
        "INSERT INTO `stock_matieres_premieres` (`matiere_id`,`quantite`,`valeur_stock`) VALUES (:mp_id,:qte,:val)",
        [':mp_id' => $mpIds[$s[0]], ':qte' => $s[1], ':val' => $s[2]]
    );
}
echo "[OK] Stock matières premières inséré.\n";

// ── Machines ─────────────────────────────────────────────────
$machines = [
    ['DEM-MACH-01', '[DEMO] Injecteuse Engel 80T', 'Injecteur plastique', 'Injecteuse 80 tonnes - moules seaux et bacs', 'EN_FONCTIONNEMENT'],
    ['DEM-MACH-02', '[DEMO] Souffleuse Bekum 50L', 'Souffleuse',          'Souffleuse 50L - bouteilles et bidons', 'EN_FONCTIONNEMENT'],
    ['DEM-MACH-03', '[DEMO] Extrudeuse Cincinnati', 'Extrudeuse',          'Extrudeuse a film - production de sachets', 'EN_MAINTENANCE'],
    ['DEM-MACH-04', '[DEMO] Groupe froid Carrier',  'Refrigeration',       'Groupe froid frigorifique pour stockage matieres', 'ARRETEE'],
    ['DEM-MACH-05', '[DEMO] Pont roulant 5T',       'Manutention',         'Pont roulant 5 tonnes - atelier assemblage', 'EN_FONCTIONNEMENT'],
];
foreach ($machines as $m) {
    seedInsert($pdo,
        "INSERT INTO `machines` (`reference`,`nom`,`type`,`description`,`etat`,`actif`) VALUES (:ref,:nom,:type,:desc,:etat,1)",
        [':ref' => $m[0], ':nom' => $m[1], ':type' => $m[2], ':desc' => $m[3], ':etat' => $m[4]]
    );
}
echo "[OK] Machines insérées.\n";

// ── Employés ─────────────────────────────────────────────────
$employes = [
    ['DEMP-EMP-01', 'Diallo',     'Moussa',      'Operateur machine',     '+221 77 101 0001'],
    ['DEMP-EMP-02', 'Kone',       'Awa',         'Soudeuse plastique',    '+221 77 101 0002'],
    ['DEMP-EMP-03', 'Ouedraogo',  'Ibrahim',     'Chef atelier',          '+221 77 101 0003'],
    ['DEMP-EMP-04', 'Sow',        'Fatoumata',   'Controle qualite',      '+221 77 101 0004'],
    ['DEMP-EMP-05', 'Toure',      'Amadou',      'Magasinier usine',      '+221 77 101 0005'],
    ['DEMP-EMP-06', 'Bamba',      'Ousmane',     'Cariste',               '+221 77 101 0006'],
    ['DEMP-EMP-07', 'Ndiaye',     'Khady',       "Agent d'entretien",     '+221 77 101 0007'],
    ['DEMP-EMP-08', 'Fall',       'Cheikh',      'Technicien maintenance','+221 77 101 0008'],
    ['DEMP-EMP-09', 'Ba',         'Mariama',     'Receptionniste',        '+221 77 101 0009'],
    ['DEMP-EMP-10', 'Sy',         'Boubacar',    'Manutentionnaire',      '+221 77 101 0010'],
];
$empIds = [];
foreach ($employes as $e) {
    $empIds[$e[0]] = seedInsertId($pdo,
        "INSERT INTO `employes` (`matricule`,`nom`,`prenom`,`fonction`,`telephone`,`actif`) VALUES (:mat,:nom,:prenom,:fonction,:tel,1)",
        [':mat' => $e[0], ':nom' => $e[1], ':prenom' => $e[2], ':fonction' => $e[3], ':tel' => $e[4]]
    );
}
echo "[OK] Employés insérés.\n";

// ============================================================
// PHASE 2 : ARTICLES
// ============================================================

$articleIds = [];

// ── PRODUIT_FINI (5) ─────────────────────────────────────────
$pfArticles = [
    ['DEM-ART-001', '[DEMO] Seau plastique 20L',       'SKU-SEAU-20L', 1800.00, 3500.00, 250, 450000.00,  20, '[DEMO] Bricolage',    'PRODUIT_FINI'],
    ['DEM-ART-002', '[DEMO] Bac plastique 5L',          'SKU-BAC-5L',    850.00, 1700.00, 180, 153000.00,  15, '[DEMO] Bricolage',    'PRODUIT_FINI'],
    ['DEM-ART-003', '[DEMO] Bouteille PEHD 1L',         'SKU-BTL-1L',    320.00,  650.00, 500, 160000.00,  50, '[DEMO] Boissons',     'PRODUIT_FINI'],
    ['DEM-ART-004', '[DEMO] Bidon 10L',                 'SKU-BID-10L',  1200.00, 2400.00, 120, 144000.00,  10, '[DEMO] Boissons',     'PRODUIT_FINI'],
    ['DEM-ART-005', '[DEMO] Sachet alimentaire 500g',   'SKU-SAC-500',    45.00,  100.00, 2000, 90000.00, 200, '[DEMO] Alimentaire sec', 'PRODUIT_FINI'],
];
foreach ($pfArticles as $a) {
    $articleIds[$a[0]] = seedInsertId($pdo,
        "INSERT INTO `articles` (`code_barre`,`nom`,`sku`,`prix_achat`,`cump`,`prix_vente`,`unite_mesure`,`taux_tva`,`quantite_stock`,`valeur_stock`,`seuil_alerte`,`categorie_id`,`type_article`,`origine_article`,`actif`)
         VALUES (:code,:nom,:sku,:pa,:cump,:pv,'UNITE',NULL,:qte,:val_stock,:seuil,:cat_id,:type,'PRODUCTION_USINE',1)",
        [
            ':code' => $a[0], ':nom' => $a[1], ':sku' => $a[2], ':pa' => $a[3],
            ':cump' => $a[3], ':pv' => $a[4], ':qte' => $a[5], ':val_stock' => $a[6],
            ':seuil' => $a[7], ':cat_id' => $catIds[$a[8]], ':type' => $a[9],
        ]
    );
}

// ── MATIERE_PREMIERE (4) ─────────────────────────────────────
$mpArticles = [
    ['DEM-ART-006', '[DEMO] Granule PEHD 25kg sac',  'SKU-MP-PEHD',  21250.00, 25500.00, 40, 850000.00, 5, '[DEMO] Palm CI',          '[DEMO] Boissons',       'SAC'],
    ['DEM-ART-007', '[DEMO] Granule PP 25kg sac',     'SKU-MP-PP',    19500.00, 23400.00, 35, 682500.00, 5, '[DEMO] Palm CI',          '[DEMO] Boissons',       'SAC'],
    ['DEM-ART-008', '[DEMO] Colorant 5kg',            'SKU-MP-COL',   16000.00, 19200.00, 12, 192000.00, 3, '[DEMO] Cosmétique Plus',  '[DEMO] Hygiène',        'UNITE'],
    ['DEM-ART-009', '[DEMO] Colle neoprene 5L',       'SKU-MP-COLLE', 22500.00, 27000.00,  8, 180000.00, 2, '[DEMO] Agro-Alim SA',     '[DEMO] Entretien',      'UNITE'],
];
foreach ($mpArticles as $a) {
    $articleIds[$a[0]] = seedInsertId($pdo,
        "INSERT INTO `articles` (`code_barre`,`nom`,`sku`,`prix_achat`,`cump`,`prix_vente`,`unite_mesure`,`vente_au_poids`,`taux_tva`,`quantite_stock`,`valeur_stock`,`seuil_alerte`,`fournisseur_id`,`categorie_id`,`type_article`,`origine_article`,`actif`)
         VALUES (:code,:nom,:sku,:pa,:cump,:pv,:unite,0,NULL,:qte,:val_stock,:seuil,:four_id,:cat_id,:type,'ACHAT_FOURNISSEUR',1)",
        [
            ':code' => $a[0], ':nom' => $a[1], ':sku' => $a[2], ':pa' => $a[3],
            ':cump' => $a[3], ':pv' => $a[4], ':unite' => $a[10],
            ':qte' => $a[5], ':val_stock' => $a[6], ':seuil' => $a[7],
            ':four_id' => $fournisseurIds[$a[8]], ':cat_id' => $catIds[$a[9]],
            ':type' => 'MATIERE_PREMIERE',
        ]
    );
}

// ── ARTICLE_COMMERCIAL (13) ──────────────────────────────────
$acArticles = [
    ['DEM-ART-010', '[DEMO] Eau minerale 1.5L x6',     'SKU-BOI-001', 2400.00, 3600.00, 100, 240000.00, 15, '[DEMO] Sen Distribution',  '[DEMO] Boissons'],
    ['DEM-ART-011', '[DEMO] Jus de baobab 1L',           'SKU-BOI-002', 1200.00, 2200.00,  80,  96000.00, 10, '[DEMO] Agro-Alim SA',      '[DEMO] Boissons'],
    ['DEM-ART-012', '[DEMO] Riz 5kg',                    'SKU-ALI-001', 3500.00, 5000.00,  60, 210000.00, 10, '[DEMO] Agro-Alim SA',      '[DEMO] Alimentaire sec'],
    ['DEM-ART-013', '[DEMO] Huile vegetale 5L',          'SKU-ALI-002', 4000.00, 5500.00,  45, 180000.00,  8, '[DEMO] Agro-Alim SA',      '[DEMO] Alimentaire sec'],
    ['DEM-ART-014', '[DEMO] Savon noir 200g x3',         'SKU-HYG-001',  800.00, 1500.00, 150, 120000.00, 20, '[DEMO] Cosmétique Plus',   '[DEMO] Hygiène'],
    ['DEM-ART-015', '[DEMO] Detergent 2L',               'SKU-HYG-002', 1500.00, 2800.00,  70, 105000.00, 10, '[DEMO] Cosmétique Plus',   '[DEMO] Entretien'],
    ['DEM-ART-016', '[DEMO] Telephone dual SIM',         'SKU-TEC-001',35000.00,55000.00,  25, 875000.00,  5, '[DEMO] TechImport',        '[DEMO] Électronique'],
    ['DEM-ART-017', '[DEMO] Chargeur USB-C',             'SKU-TEC-002', 3500.00, 7000.00,  40, 140000.00,  8, '[DEMO] TechImport',        '[DEMO] Électronique'],
    ['DEM-ART-018', '[DEMO] T-Shirt coton M',            'SKU-TXT-001', 2500.00, 5000.00,  60, 150000.00, 10, '[DEMO] TextPro',           '[DEMO] Textile'],
    ['DEM-ART-019', '[DEMO] Cigarettes pack 10',         'SKU-TAB-001', 1500.00, 2500.00, 200, 300000.00, 30, '[DEMO] Sen Distribution',  '[DEMO] Tabac'],
    ['DEM-ART-020', '[DEMO] Ampoule LED 9W',             'SKU-BRI-001',  800.00, 1800.00,  80,  64000.00, 15, '[DEMO] TechImport',        '[DEMO] Bricolage'],
    ['DEM-ART-021', '[DEMO] Pate tomate 400g x12',       'SKU-ALI-003', 9600.00,14000.00,  30, 288000.00,  5, '[DEMO] Agro-Alim SA',      '[DEMO] Alimentaire sec'],
    ['DEM-ART-022', '[DEMO] Cafe moulu 250g',            'SKU-ALI-004', 2000.00, 3500.00,  50, 100000.00, 10, '[DEMO] Agro-Alim SA',      '[DEMO] Alimentaire sec'],
];
foreach ($acArticles as $a) {
    $articleIds[$a[0]] = seedInsertId($pdo,
        "INSERT INTO `articles` (`code_barre`,`nom`,`sku`,`prix_achat`,`cump`,`prix_vente`,`unite_mesure`,`taux_tva`,`quantite_stock`,`valeur_stock`,`seuil_alerte`,`fournisseur_id`,`categorie_id`,`type_article`,`origine_article`,`actif`)
         VALUES (:code,:nom,:sku,:pa,:cump,:pv,'UNITE',NULL,:qte,:val_stock,:seuil,:four_id,:cat_id,'ARTICLE_COMMERCIAL','ACHAT_FOURNISSEUR',1)",
        [
            ':code' => $a[0], ':nom' => $a[1], ':sku' => $a[2], ':pa' => $a[3],
            ':cump' => $a[3], ':pv' => $a[4], ':qte' => $a[5], ':val_stock' => $a[6],
            ':seuil' => $a[7], ':four_id' => $fournisseurIds[$a[8]], ':cat_id' => $catIds[$a[9]],
        ]
    );
}

// ── CONSOMMABLE (4) ──────────────────────────────────────────
$conArticles = [
    ['DEM-ART-023', '[DEMO] Papier aluminium 300m',    'SKU-ALI-005', 3000.00, 5000.00,  35, 105000.00,  8, '[DEMO] Sen Distribution',  '[DEMO] Alimentaire sec'],
    ['DEM-ART-024', '[DEMO] Ruban adhesif 48mm',       'SKU-CON-001',  600.00, 1200.00, 100,  60000.00, 20, '[DEMO] Sen Distribution',  '[DEMO] Entretien'],
    ['DEM-ART-025', '[DEMO] Gants latex M x100',       'SKU-CON-002', 4500.00, 7500.00,  50, 225000.00, 10, '[DEMO] TechImport',        '[DEMO] Hygiène'],
    ['DEM-ART-026', '[DEMO] Sachet poubelle 60L x50',  'SKU-CON-003', 3000.00, 5000.00,  40, 120000.00,  8, '[DEMO] Sen Distribution',  '[DEMO] Entretien'],
];
foreach ($conArticles as $a) {
    $articleIds[$a[0]] = seedInsertId($pdo,
        "INSERT INTO `articles` (`code_barre`,`nom`,`sku`,`prix_achat`,`cump`,`prix_vente`,`unite_mesure`,`taux_tva`,`quantite_stock`,`valeur_stock`,`seuil_alerte`,`fournisseur_id`,`categorie_id`,`type_article`,`origine_article`,`actif`)
         VALUES (:code,:nom,:sku,:pa,:cump,:pv,'UNITE',NULL,:qte,:val_stock,:seuil,:four_id,:cat_id,'CONSOMMABLE','ACHAT_FOURNISSEUR',1)",
        [
            ':code' => $a[0], ':nom' => $a[1], ':sku' => $a[2], ':pa' => $a[3],
            ':cump' => $a[3], ':pv' => $a[4], ':qte' => $a[5], ':val_stock' => $a[6],
            ':seuil' => $a[7], ':four_id' => $fournisseurIds[$a[8]], ':cat_id' => $catIds[$a[9]],
        ]
    );
}
echo "[OK] Articles (26) insérés.\n";

// ── Stock produits finis usine ────────────────────────────────
$spfuData = [
    ['DEM-ART-001', 250], ['DEM-ART-002', 180], ['DEM-ART-003', 500],
    ['DEM-ART-004', 120], ['DEM-ART-005', 2000],
];
foreach ($spfuData as $s) {
    seedInsert($pdo,
        "INSERT INTO `stock_produits_finis_usine` (`article_id`,`quantite`) VALUES (:art_id,:qte)",
        [':art_id' => $articleIds[$s[0]], ':qte' => $s[1]]
    );
}
echo "[OK] Stock produits finis usine inséré.\n";

// ============================================================
// PHASE 3 : STOCK MAGASINS
// ============================================================

$stockMagData = [
    [1, 'DEM-ART-006', 20, 425000.00, 5],
    [1, 'DEM-ART-007', 18, 351000.00, 5],
    [1, 'DEM-ART-008',  6,  96000.00, 3],
    [1, 'DEM-ART-009',  4,  90000.00, 2],
    [1, 'DEM-ART-010', 60, 144000.00, 15],
    [1, 'DEM-ART-011', 40,  48000.00, 10],
    [1, 'DEM-ART-012', 30, 105000.00, 10],
    [1, 'DEM-ART-013', 25, 100000.00, 8],
    [1, 'DEM-ART-014', 80,  64000.00, 20],
    [1, 'DEM-ART-015', 40,  60000.00, 10],
    [1, 'DEM-ART-016', 15, 525000.00, 5],
    [1, 'DEM-ART-017', 25,  87500.00, 8],
    [1, 'DEM-ART-018', 40, 100000.00, 10],
    [1, 'DEM-ART-019', 120, 180000.00, 30],
    [1, 'DEM-ART-020', 50,  40000.00, 15],
    [1, 'DEM-ART-021', 20, 192000.00, 5],
    [1, 'DEM-ART-022', 30,  60000.00, 10],
    [1, 'DEM-ART-023', 20,  60000.00, 8],
    [1, 'DEM-ART-024', 60,  36000.00, 20],
    [1, 'DEM-ART-025', 30, 135000.00, 10],
    [1, 'DEM-ART-026', 25,  75000.00, 8],
    [2, 'DEM-ART-006', 20, 425000.00, 5],
    [2, 'DEM-ART-007', 17, 331500.00, 5],
    [2, 'DEM-ART-010', 40,  96000.00, 10],
    [2, 'DEM-ART-012', 30, 105000.00, 10],
    [2, 'DEM-ART-014', 70,  56000.00, 15],
    [2, 'DEM-ART-015', 30,  45000.00, 10],
    [2, 'DEM-ART-018', 20,  50000.00, 10],
    [2, 'DEM-ART-019', 80, 120000.00, 30],
    [2, 'DEM-ART-020', 30,  24000.00, 10],
    [2, 'DEM-ART-024', 40,  24000.00, 15],
];
foreach ($stockMagData as $s) {
    seedInsert($pdo,
        "INSERT INTO `stock_magasins` (`magasin_id`,`article_id`,`quantite`,`valeur_stock`,`stock_alerte`)
         VALUES (:mag_id,:art_id,:qte,:val,:alerte)",
        [':mag_id' => $s[0], ':art_id' => $articleIds[$s[1]], ':qte' => $s[2], ':val' => $s[3], ':alerte' => $s[4]]
    );
}
echo "[OK] Stock magasins (32 lignes) inséré.\n";

// ── Article lots ─────────────────────────────────────────────
$lotsData = [
    ['DEM-ART-010', 1, 'LOT-DEMO-EAU-260901',     60, '2027-03-01'],
    ['DEM-ART-012', 1, 'LOT-DEMO-RIZ-260902',     30, '2027-06-15'],
    ['DEM-ART-014', 1, 'LOT-DEMO-SAVON-260901',    80, '2027-12-31'],
    ['DEM-ART-019', 1, 'LOT-DEMO-CIG-260901',     120, '2027-09-01'],
    ['DEM-ART-010', 2, 'LOT-DEMO-EAU-260901D',     40, '2027-03-01'],
    ['DEM-ART-019', 2, 'LOT-DEMO-CIG-260901D',     80, '2027-09-01'],
];
foreach ($lotsData as $l) {
    seedInsert($pdo,
        "INSERT INTO `article_lots` (`article_id`,`magasin_id`,`numero_lot`,`quantite`,`date_peremption`)
         VALUES (:art_id,:mag_id,:lot,:qte,:peremp)",
        [':art_id' => $articleIds[$l[0]], ':mag_id' => $l[1], ':lot' => $l[2], ':qte' => $l[3], ':peremp' => $l[4]]
    );
}
echo "[OK] Article lots insérés.\n";

// ============================================================
// PHASE 4 : ÉQUIPES
// ============================================================

$equipes = [
    ['[DEMO] Équipe Vente A',      'Équipe de vente du magasin principal - matin', 'BOUTIQUE'],
    ['[DEMO] Équipe Vente B',      'Équipe de vente du depot - matin', 'BOUTIQUE'],
    ['[DEMO] Équipe Production 1', 'Équipe de production usine Yopougon -shift jour', 'USINE'],
    ['[DEMO] Équipe Logistique',   'Équipe logistique et livraison', 'LIVRAISON'],
    ['[DEMO] Équipe Achats',       'Équipe achats et approvisionnement', 'ACHATS'],
    ['[DEMO] Équipe Caisse',       'Équipe gestion caisse et encaissements', 'CAISSE'],
];
$equipeIds = [];
foreach ($equipes as $e) {
    $equipeIds[$e[0]] = seedInsertId($pdo,
        "INSERT INTO `equipes` (`nom`,`description`,`type`,`actif`) VALUES (:nom,:desc,:type,1)",
        [':nom' => $e[0], ':desc' => $e[1], ':type' => $e[2]]
    );
}

// equipe_membres
$emData = [
    ['[DEMO] Équipe Vente A',   'vendeur'],
    ['[DEMO] Équipe Vente A',   'magasinier'],
    ['[DEMO] Équipe Vente B',   'vendeur'],
    ['[DEMO] Équipe Caisse',    'admin'],
];
foreach ($emData as $em) {
    $uid = seedLookupId($pdo, "SELECT id FROM `utilisateurs` WHERE `login` = :login", [':login' => $em[1]]);
    if ($uid !== null && isset($equipeIds[$em[0]])) {
        seedInsertIgnore($pdo,
            "INSERT IGNORE INTO `equipe_membres` (`equipe_id`,`user_id`,`actif`) VALUES (:eq_id,:uid,1)",
            [':eq_id' => $equipeIds[$em[0]], ':uid' => $uid]
        );
    }
}

// equipe_magasins
$eqMagData = [
    ['[DEMO] Équipe Vente A', 1],
    ['[DEMO] Équipe Vente B', 2],
    ['[DEMO] Équipe Logistique', 1],
    ['[DEMO] Équipe Caisse', 1],
];
foreach ($eqMagData as $em) {
    if (isset($equipeIds[$em[0]])) {
        seedInsertIgnore($pdo,
            "INSERT IGNORE INTO `equipe_magasins` (`equipe_id`,`magasin_id`) VALUES (:eq_id,:mag_id)",
            [':eq_id' => $equipeIds[$em[0]], ':mag_id' => $em[1]]
        );
    }
}

// user_magasins
$userMagData = [
    ['directeur', [1, 2]],
    ['vendeur',   [1]],
    ['magasinier', [1, 2]],
    ['admin',     [1]],
];
foreach ($userMagData as $um) {
    $uid = seedLookupId($pdo, "SELECT id FROM `utilisateurs` WHERE `login` = :login", [':login' => $um[0]]);
    if ($uid !== null) {
        foreach ($um[1] as $mid) {
            seedInsertIgnore($pdo,
                "INSERT IGNORE INTO `user_magasins` (`user_id`,`magasin_id`,`actif`) VALUES (:uid,:mid,1)",
                [':uid' => $uid, ':mid' => $mid]
            );
        }
    }
}
echo "[OK] Équipes et memberships insérés.\n";

// ============================================================
// PHASE 5 : RECETTES → PRODUCTIONS
// ============================================================

// ── Recettes ─────────────────────────────────────────────────
$recetteData = [
    ['[DEMO] Recette Seau 20L',    'DEM-ART-001', 100, 'Production de 100 seaux de 20L'],
    ['[DEMO] Recette Bac 5L',      'DEM-ART-002', 150, 'Production de 150 bacs de 5L'],
    ['[DEMO] Recette Bouteille 1L','DEM-ART-003', 500, 'Production de 500 bouteilles PEHD 1L'],
    ['[DEMO] Recette Bidon 10L',   'DEM-ART-004',  80, 'Production de 80 bidons 10L'],
];
$recetteIds = [];
foreach ($recetteData as $r) {
    $recetteIds[$r[0]] = seedInsertId($pdo,
        "INSERT INTO `recettes` (`nom`,`article_id`,`quantite_produite`,`unite_produit`,`version`,`actif`,`notes`)
         VALUES (:nom,:art_id,:qte,'UNITE',1,1,:notes)",
        [':nom' => $r[0], ':art_id' => $articleIds[$r[1]], ':qte' => $r[2], ':notes' => $r[3]]
    );
}

// ── Recettes lignes ──────────────────────────────────────────
$rlData = [
    ['[DEMO] Recette Seau 20L',    'DEMP-001', 250.0000, 'KG', 3.00, 1],
    ['[DEMO] Recette Seau 20L',    'DEMP-003',   5.0000, 'KG', 0.00, 2],
    ['[DEMO] Recette Seau 20L',    'DEMP-005',   2.0000, 'KG', 0.00, 3],
    ['[DEMO] Recette Bac 5L',      'DEMP-001', 100.0000, 'KG', 2.50, 1],
    ['[DEMO] Recette Bac 5L',      'DEMP-003',   2.0000, 'KG', 0.00, 2],
    ['[DEMO] Recette Bouteille 1L','DEMP-002',  30.0000, 'KG', 4.00, 1],
    ['[DEMO] Recette Bouteille 1L','DEMP-004',   1.0000, 'KG', 0.00, 2],
    ['[DEMO] Recette Bouteille 1L','DEMP-005',   1.0000, 'KG', 0.00, 3],
    ['[DEMO] Recette Bidon 10L',   'DEMP-001', 350.0000, 'KG', 3.00, 1],
    ['[DEMO] Recette Bidon 10L',   'DEMP-003',   8.0000, 'KG', 0.00, 2],
    ['[DEMO] Recette Bidon 10L',   'DEMP-005',   3.0000, 'KG', 0.00, 3],
];
foreach ($rlData as $rl) {
    seedInsert($pdo,
        "INSERT INTO `recettes_lignes` (`recette_id`,`matiere_id`,`quantite_necessaire`,`unite`,`pertes_theoriques_pct`,`ordre`)
         VALUES (:rec_id,:mp_id,:qte,:unite,:pertes,:ordre)",
        [
            ':rec_id' => $recetteIds[$rl[0]], ':mp_id' => $mpIds[$rl[1]],
            ':qte' => $rl[2], ':unite' => $rl[3], ':pertes' => $rl[4], ':ordre' => $rl[5],
        ]
    );
}
echo "[OK] Recettes et lignes insérées.\n";

// ── Productions ──────────────────────────────────────────────
$prodData = [
    ['DEMO-PROD-001', 'DEM-ART-001', '[DEMO] Recette Seau 20L',    100, 97,  3, 26500.00, 273.1959, 97.0000, 'TERMINEE',  '2026-09-10', '2026-09-10 06:00:00', '2026-09-10 13:30:00', 'Production seaux'],
    ['DEMO-PROD-002', 'DEM-ART-002', '[DEMO] Recette Bac 5L',      150, 148,  2, 15200.00, 102.7027, 98.6667, 'TERMINEE',  '2026-09-10', '2026-09-10 06:15:00', '2026-09-10 12:00:00', 'Production bacs'],
    ['DEMO-PROD-003', 'DEM-ART-003', '[DEMO] Recette Bouteille 1L',500, 490, 10, 16500.00,  33.6735, 98.0000, 'TERMINEE',  '2026-09-11', '2026-09-11 06:30:00', '2026-09-11 11:00:00', 'Production bouteilles'],
    ['DEMO-PROD-004', 'DEM-ART-004', '[DEMO] Recette Bidon 10L',    80,   0,  0, 30000.00, 375.0000, null,     'PLANIFIEE', '2026-09-12', null,                   null,                   'Production bidons planifiee'],
];
$prodIds = [];
foreach ($prodData as $p) {
    $prodIds[$p[0]] = seedInsertId($pdo,
        "INSERT INTO `productions` (`reference`,`article_id`,`recette_id`,`recette_version`,`quantite_prevue`,`quantite_produite`,`quantite_perdue`,`cout_matieres`,`cout_unitaire`,`rendement_pct`,`statut`,`date_prevue`,`date_debut`,`date_fin`,`utilisateur_id`,`usine_id`,`notes`)
         VALUES (:ref,:art_id,:rec_id,1,:qte_prev,:qte_prod,:qte_perd,:cout_mat,:cout_unit,:rendement,:statut,:date_prev,:date_debut,:date_fin,10,:usine_id,:notes)",
        [
            ':ref' => $p[0], ':art_id' => $articleIds[$p[1]], ':rec_id' => $recetteIds[$p[2]],
            ':qte_prev' => $p[3], ':qte_prod' => $p[4], ':qte_perd' => $p[5],
            ':cout_mat' => $p[6], ':cout_unit' => $p[7], ':rendement' => $p[8],
            ':statut' => $p[9], ':date_prev' => $p[10], ':date_debut' => $p[11], ':date_fin' => $p[12],
            ':usine_id' => $usineIds['[DEMO] Usine Yopougon'], ':notes' => $p[13],
        ]
    );
}

// ── Production matières ──────────────────────────────────────
$pmData = [
    ['DEMO-PROD-001', 'DEMP-001', 25.0000, 25.3500, 'KG', 'LOT-DEMO-MP-001', 850.0000, 21547.50],
    ['DEMO-PROD-001', 'DEMP-003',  0.5000,  0.5100, 'KG', 'LOT-DEMO-MP-002', 3200.0000, 1632.00],
    ['DEMO-PROD-001', 'DEMP-005',  0.2000,  0.2050, 'KG', 'LOT-DEMO-MP-003', 5600.0000, 1148.00],
    ['DEMO-PROD-002', 'DEMP-001', 15.0000, 15.2200, 'KG', 'LOT-DEMO-MP-004', 850.0000, 12937.00],
    ['DEMO-PROD-002', 'DEMP-003',  0.3000,  0.3050, 'KG', 'LOT-DEMO-MP-005', 3200.0000, 976.00],
    ['DEMO-PROD-003', 'DEMP-002', 15.0000, 15.6000, 'KG', 'LOT-DEMO-MP-006', 780.0000, 12168.00],
    ['DEMO-PROD-003', 'DEMP-004',  0.5000,  0.5200, 'KG', 'LOT-DEMO-MP-007', 3500.0000, 1820.00],
    ['DEMO-PROD-003', 'DEMP-005',  0.5000,  0.5150, 'KG', 'LOT-DEMO-MP-008', 5600.0000, 2884.00],
];
foreach ($pmData as $pm) {
    seedInsert($pdo,
        "INSERT INTO `production_matieres` (`production_id`,`matiere_id`,`quantite_prevue`,`quantite_reelle`,`unite`,`numero_lot`,`cout_unitaire`,`cout_total`)
         VALUES (:prod_id,:mp_id,:qte_prev,:qte_reel,:unite,:lot,:cout_unit,:cout_total)",
        [
            ':prod_id' => $prodIds[$pm[0]], ':mp_id' => $mpIds[$pm[1]],
            ':qte_prev' => $pm[2], ':qte_reel' => $pm[3], ':unite' => $pm[4],
            ':lot' => $pm[5], ':cout_unit' => $pm[6], ':cout_total' => $pm[7],
        ]
    );
}

// ── Production pertes ────────────────────────────────────────
$ppData = [
    ['DEMO-PROD-001', 'rebut',            'DEM-ART-001', 3.0000, 'UNITE', 'Defaut esthetique'],
    ['DEMO-PROD-002', 'casse',            'DEM-ART-002', 2.0000, 'UNITE', 'Bac fissure demoulage'],
    ['DEMO-PROD-003', 'matiere_premiere', 'DEM-ART-003', 10.0000, 'UNITE', 'Bouteilles deformees'],
];
foreach ($ppData as $pp) {
    seedInsert($pdo,
        "INSERT INTO `production_pertes` (`production_id`,`type_perte`,`article_id`,`quantite`,`unite`,`motif`,`utilisateur_id`)
         VALUES (:prod_id,:type,:art_id,:qte,:unite,:motif,10)",
        [
            ':prod_id' => $prodIds[$pp[0]], ':type' => $pp[1], ':art_id' => $articleIds[$pp[2]],
            ':qte' => $pp[3], ':unite' => $pp[4], ':motif' => $pp[5],
        ]
    );
}
echo "[OK] Productions, matières et pertes insérées.\n";

// ============================================================
// PHASE 6 : COMMANDES → RÉCEPTIONS
// ============================================================

// ── Commandes fournisseur ────────────────────────────────────
$cmdData = [
    ['[DEMO] Palm CI',          'Recue',    '2026-09-05 09:00:00', '2026-09-08', '[DEMO] Commande granules plastiques'],
    ['[DEMO] Agro-Alim SA',     'Envoyee',  '2026-09-09 10:30:00', '2026-09-13', '[DEMO] Commande matieres alimentaires'],
    ['[DEMO] Sen Distribution', 'En_Attente','2026-09-11 08:00:00', '2026-09-15', '[DEMO] Commande emballages divers'],
];
$cmdIds = [];
foreach ($cmdData as $c) {
    $cmdIds[$c[4]] = seedInsertId($pdo,
        "INSERT INTO `commandes_fournisseur` (`fournisseur_id`,`magasin_id`,`utilisateur_id`,`statut`,`date_commande`,`devise`,`taux_change`,`date_reception_prevue`,`notes`)
         VALUES (:four_id,1,10,:statut,:date_cmd,'XOF',1.000000,:date_prev,:notes)",
        [
            ':four_id' => $fournisseurIds[$c[0]], ':statut' => $c[1], ':date_cmd' => $c[2],
            ':date_prev' => $c[3], ':notes' => $c[4],
        ]
    );
}

// ── Lignes commande ──────────────────────────────────────────
$lcfData = [
    ['[DEMO] Commande granules plastiques',    'DEM-ART-006', 40, 40, 40, 0, 21250.00],
    ['[DEMO] Commande granules plastiques',    'DEM-ART-007', 35, 35, 35, 0, 19500.00],
    ['[DEMO] Commande matieres alimentaires',  'DEM-ART-012', 60,  0,  0, 0,  3500.00],
    ['[DEMO] Commande matieres alimentaires',  'DEM-ART-013', 50,  0,  0, 0,  4000.00],
    ['[DEMO] Commande matieres alimentaires',  'DEM-ART-010', 100, 0,  0, 0,  2400.00],
    ['[DEMO] Commande emballages divers',      'DEM-ART-024', 200, 0,  0, 0,   600.00],
    ['[DEMO] Commande emballages divers',      'DEM-ART-026', 100, 0,  0, 0,  3000.00],
];
$lcfIds = [];
foreach ($lcfData as $lc) {
    $lcfIds[$lc[0] . '_' . $lc[1]] = seedInsertId($pdo,
        "INSERT INTO `lignes_commande_fournisseur` (`commande_id`,`article_id`,`quantite_commandee`,`quantite_recue`,`quantite_receptionnee`,`quantite_perdue`,`prix_achat_unitaire`)
         VALUES (:cmd_id,:art_id,:qte_cmd,:qte_rec,:qte_recep,:qte_perdue,:prix)",
        [
            ':cmd_id' => $cmdIds[$lc[0]], ':art_id' => $articleIds[$lc[1]],
            ':qte_cmd' => $lc[2], ':qte_rec' => $lc[3], ':qte_recep' => $lc[4],
            ':qte_perdue' => $lc[5], ':prix' => $lc[6],
        ]
    );
}

// ── Réceptions ───────────────────────────────────────────────
$recIds = [];
$recIds['DEMO-REC-001'] = seedInsertId($pdo,
    "INSERT INTO `receptions` (`reference`,`commande_id`,`fournisseur_id`,`magasin_id`,`utilisateur_id`,`statut`,`date_reception`,`commentaire`)
     VALUES ('DEMO-REC-001',:cmd_id,:four_id,1,10,'Validee','2026-09-08 14:00:00','[DEMO] Reception granules - conforme')",
    [':cmd_id' => $cmdIds['[DEMO] Commande granules plastiques'], ':four_id' => $fournisseurIds['[DEMO] Palm CI']]
);

$recIds['DEMO-REC-002'] = seedInsertId($pdo,
    "INSERT INTO `receptions` (`reference`,`commande_id`,`fournisseur_id`,`magasin_id`,`utilisateur_id`,`statut`,`date_reception`,`commentaire`)
     VALUES ('DEMO-REC-002',:cmd_id,:four_id,1,10,'Validee','2026-09-08 14:30:00','[DEMO] Reception emballages - conforme')",
    [':cmd_id' => $cmdIds['[DEMO] Commande emballages divers'], ':four_id' => $fournisseurIds['[DEMO] Sen Distribution']]
);

// ── Réception lignes ─────────────────────────────────────────
$rlRecData = [
    ['DEMO-REC-001', 'DEM-ART-006', 'LOT-DEMO-REC-PEHD-0908', '2027-09-08'],
    ['DEMO-REC-001', 'DEM-ART-007', 'LOT-DEMO-REC-PP-0908',   '2027-09-08'],
    ['DEMO-REC-002', 'DEM-ART-024', null, null],
    ['DEMO-REC-002', 'DEM-ART-026', null, null],
];
foreach ($rlRecData as $rl) {
    $lcfKey = ($rl[0] === 'DEMO-REC-001'
        ? '[DEMO] Commande granules plastiques'
        : '[DEMO] Commande emballages divers') . '_' . $rl[1];
    seedInsert($pdo,
        "INSERT INTO `reception_lignes` (`reception_id`,`ligne_commande_id`,`article_id`,`quantite_attendue`,`quantite_recue`,`quantite_acceptee`,`quantite_perdue`,`prix_achat_unitaire`,`numero_lot`,`date_peremption`)
         VALUES (:rec_id,:lcf_id,:art_id,0,0,0,0,0.00,:lot,:peremp)",
        [
            ':rec_id' => $recIds[$rl[0]], ':lcf_id' => $lcfIds[$lcfKey] ?? 0,
            ':art_id' => $articleIds[$rl[1]], ':lot' => $rl[2], ':peremp' => $rl[3],
        ]
    );
}

// ── Pertes fournisseur ───────────────────────────────────────
$rlRec2Key = '[DEMO] Commande emballages divers_DEM-ART-024';
seedInsert($pdo,
    "INSERT INTO `pertes_fournisseur` (`reception_id`,`reception_ligne_id`,`commande_id`,`article_id`,`fournisseur_id`,`magasin_id`,`utilisateur_id`,`quantite`,`motif`,`commentaire`)
     VALUES (:rec_id,:rl_id,:cmd_id,:art_id,:four_id,1,10,5,'endommage','[DEMO] 5 rouleaux ruban adhesif endommages')",
    [
        ':rec_id' => $recIds['DEMO-REC-002'],
        ':rl_id' => seedLookupId($pdo, "SELECT id FROM `reception_lignes` WHERE reception_id = :rec_id AND article_id = :art_id LIMIT 1", [':rec_id' => $recIds['DEMO-REC-002'], ':art_id' => $articleIds['DEM-ART-024']]) ?? 0,
        ':cmd_id' => $cmdIds['[DEMO] Commande emballages divers'],
        ':art_id' => $articleIds['DEM-ART-024'],
        ':four_id' => $fournisseurIds['[DEMO] Sen Distribution'],
    ]
);

// ── Prix historique fournisseur ──────────────────────────────
$fphData = [
    ['DEM-ART-006', '[DEMO] Palm CI',          21250.00],
    ['DEM-ART-007', '[DEMO] Palm CI',          19500.00],
    ['DEM-ART-010', '[DEMO] Agro-Alim SA',      2400.00],
    ['DEM-ART-012', '[DEMO] Agro-Alim SA',      3500.00],
    ['DEM-ART-016', '[DEMO] TechImport',        35000.00],
    ['DEM-ART-014', '[DEMO] Cosmétique Plus',    800.00],
];
foreach ($fphData as $fph) {
    seedInsert($pdo,
        "INSERT INTO `fournisseur_prix_historique` (`article_id`,`fournisseur_id`,`prix_achat`,`devise`,`est_actif`,`source`,`date_debut`)
         VALUES (:art_id,:four_id,:prix,'XOF',1,'manuelle','2026-09-01 00:00:00')",
        [':art_id' => $articleIds[$fph[0]], ':four_id' => $fournisseurIds[$fph[1]], ':prix' => $fph[2]]
    );
}
echo "[OK] Commandes, réceptions et pertes fournisseur insérées.\n";

// ============================================================
// PHASE 7 : FACTURES → PAIEMENTS → CRÉANCES
// ============================================================

// ── Factures ─────────────────────────────────────────────────
$factData = [
    ['DEMO-FACT-001', '2026-09-01 10:30:00', 1, 3600.00,   3600.00, 3600.00, 'Payee', '[DEMO] Aminata Toure'],
    ['DEMO-FACT-002', '2026-09-02 14:15:00', 1, 5000.00,   5000.00, 5000.00, 'Payee', '[DEMO] Moussa Konate'],
    ['DEMO-FACT-003', '2026-09-03 09:00:00', 1, 12000.00, 12000.00, 8000.00, 'Payee', '[DEMO] Societe Com-Plus'],
    ['DEMO-FACT-004', '2026-09-04 11:45:00', 1, 2500.00,   2500.00, 2500.00, 'Payee', '[DEMO] Ibrahim Bamba'],
    ['DEMO-FACT-005', '2026-09-05 16:00:00', 1, 55000.00, 55000.00,     0.00, 'Payee', '[DEMO] COGITEC SARL'],
    ['DEMO-FACT-006', '2026-09-06 10:00:00', 1, 3500.00,   3500.00, 3500.00, 'Payee', '[DEMO] Boulangerie Doree'],
    ['DEMO-FACT-007', '2026-09-07 13:30:00', 1, 7200.00,   7200.00, 5000.00, 'Payee', '[DEMO] Trans Sahel'],
    ['DEMO-FACT-008', '2026-09-10 09:15:00', 2, 5500.00,   5500.00, 5500.00, 'Payee', '[DEMO] Aminata Toure'],
    ['DEMO-FACT-009', '2026-09-11 08:00:00', 1, 1500.00,   1500.00,     0.00, 'Payee', '[DEMO] Khady Niang'],
    ['DEMO-FACT-010', '2026-09-11 08:30:00', 1, 21000.00, 21000.00, 21000.00, 'Payee', '[DEMO] Menuiserie Bois Prestige'],
];
$factIds = [];
foreach ($factData as $f) {
    $factIds[$f[0]] = seedInsertId($pdo,
        "INSERT INTO `factures` (`numero_facture`,`date_facture`,`utilisateur_id`,`magasin_id`,`total_ht`,`tva_taux`,`total_ttc`,`montant_paye`,`monnaie_rendue`,`statut`,`statut_transmission`,`client_id`,`client_nom`)
         VALUES (:num,:date_fact,3,:mag_id,:total_ht,0.00,:total_ttc,:montant_paye,0.00,'Payee','non_transmise',:client_id,:client_nom)",
        [
            ':num' => $f[0], ':date_fact' => $f[1], ':mag_id' => $f[2],
            ':total_ht' => $f[3], ':total_ttc' => $f[4], ':montant_paye' => $f[5],
            ':client_id' => $clientIds[$f[7]], ':client_nom' => $f[7],
        ]
    );
}

// ── Lignes facture ───────────────────────────────────────────
$lfData = [
    ['DEMO-FACT-001', 'DEM-ART-010', 2, 1800.00],
    ['DEMO-FACT-002', 'DEM-ART-012', 1, 5000.00],
    ['DEMO-FACT-003', 'DEM-ART-016', 1, 55000.00],
    ['DEMO-FACT-004', 'DEM-ART-014', 1, 1500.00],
    ['DEMO-FACT-005', 'DEM-ART-016', 1, 55000.00],
    ['DEMO-FACT-006', 'DEM-ART-022', 1, 3500.00],
    ['DEMO-FACT-007', 'DEM-ART-010', 2, 3600.00],
    ['DEMO-FACT-007', 'DEM-ART-011', 2, 2200.00],
    ['DEMO-FACT-008', 'DEM-ART-013', 1, 5500.00],
    ['DEMO-FACT-009', 'DEM-ART-014', 1, 1500.00],
    ['DEMO-FACT-010', 'DEM-ART-021', 3, 14000.00],
    ['DEMO-FACT-010', 'DEM-ART-022', 2, 3500.00],
];
foreach ($lfData as $lf) {
    seedInsert($pdo,
        "INSERT INTO `lignes_facture` (`facture_id`,`article_id`,`quantite`,`prix_unitaire`,`taux_tva`)
         VALUES (:fact_id,:art_id,:qte,:prix,NULL)",
        [':fact_id' => $factIds[$lf[0]], ':art_id' => $articleIds[$lf[1]], ':qte' => $lf[2], ':prix' => $lf[3]]
    );
}

// ── Paiements facture ────────────────────────────────────────
$pfData = [
    ['DEMO-FACT-001', 'Especes',        3600.00, null,              '2026-09-01 10:30:00'],
    ['DEMO-FACT-002', 'Mobile_Money',   5000.00, 'OM-DEMO-001',     '2026-09-02 14:15:00'],
    ['DEMO-FACT-003', 'Especes',        5000.00, null,              '2026-09-03 09:00:00'],
    ['DEMO-FACT-003', 'Mobile_Money',   3000.00, 'WV-DEMO-002',     '2026-09-03 09:05:00'],
    ['DEMO-FACT-004', 'Especes',        2500.00, null,              '2026-09-04 11:45:00'],
    ['DEMO-FACT-006', 'Carte_Bancaire', 3500.00, 'CB-DEMO-003',     '2026-09-06 10:00:00'],
    ['DEMO-FACT-007', 'Especes',        5000.00, null,              '2026-09-07 13:30:00'],
    ['DEMO-FACT-008', 'Mobile_Money',   5500.00, 'OM-DEMO-003',     '2026-09-10 09:15:00'],
    ['DEMO-FACT-010', 'Especes',       21000.00, null,              '2026-09-11 08:30:00'],
];
foreach ($pfData as $pf) {
    seedInsert($pdo,
        "INSERT INTO `paiements_facture` (`facture_id`,`mode_paiement`,`montant`,`reference`,`date_paiement`)
         VALUES (:fact_id,:mode,:montant,:ref,:date_paie)",
        [':fact_id' => $factIds[$pf[0]], ':mode' => $pf[1], ':montant' => $pf[2], ':ref' => $pf[3], ':date_paie' => $pf[4]]
    );
}

// ── Créances ─────────────────────────────────────────────────
$creanceData = [
    ['DEMO-FACT-003', 'Partiellement_Payee', '2026-10-03'],
    ['DEMO-FACT-005', 'En_Cours',            '2026-10-05'],
    ['DEMO-FACT-007', 'Partiellement_Payee', '2026-10-07'],
];
$creanceIds = [];
foreach ($creanceData as $cr) {
    $fId = $factIds[$cr[0]];
    // Récupérer client_id et montants depuis la facture
    $fRow = $pdo->prepare("SELECT client_id, total_ttc, montant_paye FROM factures WHERE id = :fid");
    $fRow->execute([':fid' => $fId]);
    $fInfo = $fRow->fetch(PDO::FETCH_ASSOC);
    $reste = (float)$fInfo['total_ttc'] - (float)$fInfo['montant_paye'];
    $creanceIds[$cr[0]] = seedInsertId($pdo,
        "INSERT INTO `creances_clients` (`facture_id`,`client_id`,`montant_total`,`montant_paye`,`reste_a_payer`,`statut`,`date_echeance`)
         VALUES (:fact_id,:client_id,:montant_total,:montant_paye,:reste,:statut,:echeance)",
        [
            ':fact_id' => $fId, ':client_id' => $fInfo['client_id'],
            ':montant_total' => $fInfo['total_ttc'], ':montant_paye' => $fInfo['montant_paye'],
            ':reste' => $reste, ':statut' => $cr[1], ':echeance' => $cr[2],
        ]
    );
}
echo "[OK] Factures, paiements et créances insérés.\n";

// ── Paiements crédit ─────────────────────────────────────────
if (isset($creanceIds['DEMO-FACT-003'])) {
    seedInsert($pdo,
        "INSERT INTO `paiements_credit` (`creance_id`,`montant`,`mode_paiement`,`reference`,`date_paiement`,`utilisateur_id`)
         VALUES (:creance_id,8000.00,'Especes',NULL,'2026-09-03 09:05:00',3)",
        [':creance_id' => $creanceIds['DEMO-FACT-003']]
    );
}
if (isset($creanceIds['DEMO-FACT-007'])) {
    seedInsert($pdo,
        "INSERT INTO `paiements_credit` (`creance_id`,`montant`,`mode_paiement`,`reference`,`date_paiement`,`utilisateur_id`)
         VALUES (:creance_id,5000.00,'Especes',NULL,'2026-09-07 13:35:00',3)",
        [':creance_id' => $creanceIds['DEMO-FACT-007']]
    );
}

// ============================================================
// PHASE 8 : TRANSFERTS, DÉPENSES, RETOURS
// ============================================================

// ── Transferts stock ─────────────────────────────────────────
$trData = [
    ['DEM-ART-010', 40, '[DEMO] Transfert eaux vers depot',       '2026-09-05 10:00:00'],
    ['DEM-ART-014', 70, '[DEMO] Transfert savons vers depot',      '2026-09-06 09:00:00'],
    ['DEM-ART-019', 80, '[DEMO] Transfert tabac vers depot',       '2026-09-07 11:00:00'],
    ['DEM-ART-018', 20, '[DEMO] Retour t-shirts du depot',         '2026-09-09 14:00:00'],
];
foreach ($trData as $t) {
    seedInsert($pdo,
        "INSERT INTO `transferts_stock` (`article_id`,`magasin_source_id`,`magasin_destination_id`,`quantite`,`utilisateur_id`,`motif`,`date_transfert`)
         VALUES (:art_id,1,2,:qte,10,:motif,:date_tr)",
        [':art_id' => $articleIds[$t[0]], ':qte' => $t[1], ':motif' => $t[2], ':date_tr' => $t[3]]
    );
}

// ── Dépenses ─────────────────────────────────────────────────
$depenses = [
    [1, 10, '[DEMO] Électricite septembre',  'Loyer & charges', 85000.00, '2026-09-01', 'Facture electricite EDF - magasin principal'],
    [1, 10, '[DEMO] Entretien climatisation','Maintenance',     25000.00, '2026-09-05', 'Recharge gaz climatiseur'],
    [2, 10, '[DEMO] Assurance vehicule',     'Transport',       45000.00, '2026-09-03', 'Prime assurance camion livraison'],
    [1, 10, '[DEMO] Fournitures bureau',     'Fournitures',      8000.00, '2026-09-07', 'Papier, stylos, encre'],
    [null, 10, '[DEMO] Internet & telephone','Services',        35000.00, '2026-09-01', 'Abonnement internet + forfait mobile'],
];
foreach ($depenses as $d) {
    seedInsert($pdo,
        "INSERT INTO `depenses` (`magasin_id`,`utilisateur_id`,`titre`,`categorie`,`montant`,`date_depense`,`description`)
         VALUES (:mag_id,10,:titre,:categorie,:montant,:date_dep,:desc)",
        [':mag_id' => $d[0], ':titre' => $d[2], ':categorie' => $d[3], ':montant' => $d[4], ':date_dep' => $d[5], ':desc' => $d[6]]
    );
}

// ── Clôtures caisse ──────────────────────────────────────────
$clotures = [
    [1, 3, '2026-09-01', 3600.00, 3600.00,  0.00, '2026-09-01 18:10:00'],
    [1, 3, '2026-09-02', 5000.00, 5000.00,  0.00, '2026-09-02 18:10:00'],
    [1, 3, '2026-09-03', 8000.00, 8100.00, 100.00, '2026-09-03 18:10:00'],
    [1, 3, '2026-09-04', 2500.00, 2500.00,  0.00, '2026-09-04 18:10:00'],
    [1, 3, '2026-09-05',    0.00,    0.00,  0.00, '2026-09-05 18:10:00'],
    [1, 3, '2026-09-06', 3500.00, 3500.00,  0.00, '2026-09-06 18:10:00'],
    [1, 3, '2026-09-07', 5000.00, 5000.00,  0.00, '2026-09-07 18:10:00'],
    [2, 3, '2026-09-10', 5500.00, 5450.00, -50.00, '2026-09-10 18:10:00'],
    [1, 3, '2026-09-11',    0.00,    0.00,  0.00, '2026-09-11 18:10:00'],
];
foreach ($clotures as $cl) {
    seedInsert($pdo,
        "INSERT INTO `clotures_caisse` (`magasin_id`,`utilisateur_id`,`date_cloture`,`montant_attendu`,`montant_reel`,`ecart`,`statut`,`date_creation`)
         VALUES (:mag_id,3,:date_clot,:montant_att,:montant_reel,:ecart,'VALIDE',:date_cre)",
        [
            ':mag_id' => $cl[0], ':date_clot' => $cl[2], ':montant_att' => $cl[3],
            ':montant_reel' => $cl[4], ':ecart' => $cl[5], ':date_cre' => $cl[6],
        ]
    );
}

// ── Retours facture ──────────────────────────────────────────
$retourIds = [];
$retourIds['DEMO-RET-001'] = seedInsertId($pdo,
    "INSERT INTO `retours_factures` (`numero_retour`,`facture_id`,`magasin_id`,`utilisateur_id`,`montant_total`,`motif`,`statut`)
     VALUES ('DEMO-RET-001',:fact_id,1,3,1500.00,'[DEMO] Client insatisfait - savon defectueux','Valide')",
    [':fact_id' => $factIds['DEMO-FACT-004']]
);

$retourIds['DEMO-RET-002'] = seedInsertId($pdo,
    "INSERT INTO `retours_factures` (`numero_retour`,`facture_id`,`magasin_id`,`utilisateur_id`,`montant_total`,`motif`,`statut`)
     VALUES ('DEMO-RET-002',:fact_id,2,3,5500.00,'[DEMO] Huile perimee - retour fournisseur','Valide')",
    [':fact_id' => $factIds['DEMO-FACT-008']]
);

// ── Lignes retour ────────────────────────────────────────────
// Ligne retour 001 : article savon sur facture 004
$lfSavonId = seedLookupId($pdo,
    "SELECT lf.id FROM lignes_facture lf WHERE lf.facture_id = :fact_id AND lf.article_id = :art_id LIMIT 1",
    [':fact_id' => $factIds['DEMO-FACT-004'], ':art_id' => $articleIds['DEM-ART-014']]
);
if ($lfSavonId !== null) {
    seedInsert($pdo,
        "INSERT INTO `lignes_retour` (`retour_id`,`ligne_facture_id`,`article_id`,`quantite`,`prix_unitaire`)
         VALUES (:ret_id,:lf_id,:art_id,1,1500.00)",
        [':ret_id' => $retourIds['DEMO-RET-001'], ':lf_id' => $lfSavonId, ':art_id' => $articleIds['DEM-ART-014']]
    );
}

// Ligne retour 002 : article huile sur facture 008
$lfHuileId = seedLookupId($pdo,
    "SELECT lf.id FROM lignes_facture lf WHERE lf.facture_id = :fact_id AND lf.article_id = :art_id LIMIT 1",
    [':fact_id' => $factIds['DEMO-FACT-008'], ':art_id' => $articleIds['DEM-ART-013']]
);
if ($lfHuileId !== null) {
    seedInsert($pdo,
        "INSERT INTO `lignes_retour` (`retour_id`,`ligne_facture_id`,`article_id`,`quantite`,`prix_unitaire`)
         VALUES (:ret_id,:lf_id,:art_id,1,5500.00)",
        [':ret_id' => $retourIds['DEMO-RET-002'], ':lf_id' => $lfHuileId, ':art_id' => $articleIds['DEM-ART-013']]
    );
}
echo "[OK] Transferts, dépenses, clôtures et retours insérés.\n";

// ============================================================
// PHASE 9 : DONNÉES DE SUPPORT
// ============================================================

// ── Logs activité ────────────────────────────────────────────
seedInsert($pdo,
    "INSERT INTO `logs_activite` (`utilisateur_id`,`action`,`details`,`ip_address`,`date_action`) VALUES
     (1,'CONNEXION','Connexion reussie - role PROPRIETAIRE','127.0.0.1','2026-09-11 08:00:00'),
     (10,'SEED_DEMO','Import donnees demo - seed_demo.php','127.0.0.1',NOW())"
);

// ── Notifications ────────────────────────────────────────────
seedInsert($pdo,
    "INSERT INTO `notifications` (`type`,`titre`,`type_notif`,`message`,`cible_role`,`lu`,`statut`) VALUES
     ('STOCK','Stock bas detecte','ALERTE_STOCK','[DEMO] Stock bas pour article DEM-ART-020 (Ampoule LED 9W)','MAGASINIER',0,'EN_ATTENTE'),
     ('PRODUCTION','Production planifiee','INFO_PRODUCTION','[DEMO] Production DEMO-PROD-004 planifiee pour demain','CHEF_EQUIPE_USINE',0,'EN_ATTENTE'),
     ('CREDIT','Creance en souffrance','ALERTE_CREDIT','[DEMO] Creance COGITEC - 55 000 XOF impayes','VENDEUR',0,'EN_ATTENTE'),
     ('COMMANDE','Commande en cours','INFO_COMMANDE','[DEMO] Commande emballages en cours de livraison','ADMIN',1,'LUE')"
);

// ── Présences employés ───────────────────────────────────────
$presences = [
    ['DEMP-EMP-01', '2026-09-11', '06:00:00', null, null, 'PRESENT'],
    ['DEMP-EMP-02', '2026-09-11', '06:02:00', null, null, 'PRESENT'],
    ['DEMP-EMP-03', '2026-09-11', '05:55:00', null, null, 'PRESENT'],
    ['DEMP-EMP-04', '2026-09-11', '07:15:00', null, null, 'RETARD'],
    ['DEMP-EMP-06', '2026-09-11', null,       null, null, 'ABSENT'],
];
foreach ($presences as $p) {
    seedInsert($pdo,
        "INSERT INTO `presences_employes` (`employe_id`,`date_presence`,`heure_arrivee`,`heure_depart`,`temps_travaille_minutes`,`statut`,`utilisateur_id`)
         VALUES (:emp_id,:date_pres,:heure_arr,:heure_dep,:temps_trav,:statut,10)",
        [':emp_id' => $empIds[$p[0]], ':date_pres' => $p[1], ':heure_arr' => $p[2], ':heure_dep' => $p[3], ':temps_trav' => $p[4], ':statut' => $p[5]]
    );
}

// ── Horaires travail ─────────────────────────────────────────
$horaires = [];
foreach (['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI'] as $j) {
    $horaires[] = ['[DEMO] Horaire Usine',    $j, '06:00:00', '14:00:00', 10];
}
$horaires[] = ['[DEMO] Horaire Usine', 'SAMEDI', '06:00:00', '12:00:00', 10];
foreach (['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI'] as $j) {
    $horaires[] = ['[DEMO] Horaire Boutique', $j, '08:00:00', '18:00:00', 5];
}
$horaires[] = ['[DEMO] Horaire Boutique', 'SAMEDI', '08:00:00', '13:00:00', 5];

foreach ($horaires as $h) {
    seedInsert($pdo,
        "INSERT INTO `horaires_travail` (`nom`,`jour`,`heure_debut`,`heure_fin`,`tolerance_retard_minutes`,`actif`)
         VALUES (:nom,:jour,:h_debut,:h_fin,:tol,1)",
        [':nom' => $h[0], ':jour' => $h[1], ':h_debut' => $h[2], ':h_fin' => $h[3], ':tol' => $h[4]]
    );
}

// ── Promotions ───────────────────────────────────────────────
$promoIds = [];
$promoIds['DEMO-SOLDE10'] = seedInsertId($pdo,
    "INSERT INTO `promotions` (`nom`,`code_promo`,`type_reduction`,`valeur`,`article_id`,`categorie_id`,`montant_min_achat`,`date_debut`,`date_fin`,`limite_utilisations`,`nb_utilisations`,`actif`)
     VALUES ('[DEMO] Solde fin saison','DEMO-SOLDE10','pourcentage',10.00,NULL,NULL,0.00,'2026-09-01 00:00:00','2026-09-30 23:59:59',200,45,1)"
);

$promoIds['DEMO-TEL500'] = seedInsertId($pdo,
    "INSERT INTO `promotions` (`nom`,`code_promo`,`type_reduction`,`valeur`,`article_id`,`categorie_id`,`montant_min_achat`,`date_debut`,`date_fin`,`limite_utilisations`,`nb_utilisations`,`actif`)
     VALUES ('[DEMO] -500F sur telephone','DEMO-TEL500','montant_fixe',500.00,:art_id,NULL,50000.00,'2026-09-01 00:00:00','2026-09-30 23:59:59',50,12,1)",
    [':art_id' => $articleIds['DEM-ART-016']]
);

$promoIds['DEMO-HYG20'] = seedInsertId($pdo,
    "INSERT INTO `promotions` (`nom`,`code_promo`,`type_reduction`,`valeur`,`article_id`,`categorie_id`,`montant_min_achat`,`date_debut`,`date_fin`,`limite_utilisations`,`nb_utilisations`,`actif`)
     VALUES ('[DEMO] 20% produits hygiene','DEMO-HYG20','pourcentage',20.00,NULL,:cat_id,0.00,'2026-09-01 00:00:00','2026-09-30 23:59:59',NULL,30,1)",
    [':cat_id' => $catIds['[DEMO] Hygiène']]
);

// ── Règles promotions ────────────────────────────────────────
seedInsert($pdo,
    "INSERT INTO `regles_promotions` (`nom`,`condition_type`,`jours_limite`,`seuil_stock`,`pourcentage_remise`,`actif`) VALUES
     ('[DEMO] Peremption 30j','PEREMPTION_PROCHE',30,NULL,20.00,1),
     ('[DEMO] Surstock >500','SURSTOCK',NULL,500,15.00,1),
     ('[DEMO] Peremption 15j urgente','PEREMPTION_PROCHE',15,NULL,40.00,1)"
);

// ── Paramètres ───────────────────────────────────────────────
seedInsert($pdo,
    "INSERT INTO `parametres` (`cle`,`valeur`,`categorie`,`description`) VALUES ('DEMO_MODE','1','general','[DEMO] Mode demo active - donnees fictives')
     ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`)"
);

// ── Séquences ────────────────────────────────────────────────
$seqData = [
    ['facture_numero', 10],
    ['commande_fournisseur', 3],
    ['reception_reference', 2],
    ['retour_numero', 2],
    ['inventaire_reference', 0],
    ['production_reference', 4],
];
foreach ($seqData as $s) {
    seedInsert($pdo,
        "INSERT INTO `sequences` (`cle`,`valeur`) VALUES (:cle,:val) ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`)",
        [':cle' => $s[0], ':val' => $s[1]]
    );
}
echo "[OK] Données de support insérées.\n";

// ============================================================
// 3. RESTAURATION DES 10 TRIGGERS
// ============================================================

// trg_paiements_immutable_delete
seedCreateTrigger($pdo, 'trg_paiements_immutable_delete',
    "BEFORE DELETE ON `paiements_facture` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de paiement interdite';
    END");

// trg_paiements_immutable_update
seedCreateTrigger($pdo, 'trg_paiements_immutable_update',
    "BEFORE UPDATE ON `paiements_facture` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les paiements sont immuables';
    END");

// trg_lignes_facture_immutable_delete
seedCreateTrigger($pdo, 'trg_lignes_facture_immutable_delete',
    "BEFORE DELETE ON `lignes_facture` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de ligne de facture interdite';
    END");

// trg_lignes_facture_immutable_update
seedCreateTrigger($pdo, 'trg_lignes_facture_immutable_update',
    "BEFORE UPDATE ON `lignes_facture` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les lignes de facture sont immuables';
    END");

// trg_factures_immutable_delete
seedCreateTrigger($pdo, 'trg_factures_immutable_delete',
    "BEFORE DELETE ON `factures` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de facture interdite';
    END");

// trg_factures_immutable_update
seedCreateTrigger($pdo, 'trg_factures_immutable_update',
    "BEFORE UPDATE ON `factures` FOR EACH ROW BEGIN
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
    END");

// trg_receptions_immutable_delete
seedCreateTrigger($pdo, 'trg_receptions_immutable_delete',
    "BEFORE DELETE ON `receptions` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Les recussions ne peuvent pas etre supprimees.';
    END");

// trg_receptions_immutable_update
seedCreateTrigger($pdo, 'trg_receptions_immutable_update',
    "BEFORE UPDATE ON `receptions` FOR EACH ROW BEGIN
        IF OLD.statut = 'Validee' AND NEW.statut != 'Annulee' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Une reception validee ne peut pas etre modifiee.';
        END IF;
        IF OLD.statut = 'Annulee' AND NEW.statut != 'Annulee' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Une reception annulee ne peut pas etre reactivee.';
        END IF;
    END");

// trg_clotures_immutable_delete
seedCreateTrigger($pdo, 'trg_clotures_immutable_delete',
    "BEFORE DELETE ON `clotures_caisse` FOR EACH ROW BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: suppression physique de cloture interdite';
    END");

// trg_clotures_immutable_update
seedCreateTrigger($pdo, 'trg_clotures_immutable_update',
    "BEFORE UPDATE ON `clotures_caisse` FOR EACH ROW BEGIN
        IF OLD.montant_attendu <> NEW.montant_attendu OR OLD.montant_reel <> NEW.montant_reel
           OR OLD.ecart <> NEW.ecart THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: les montants d une cloture sont immuables';
        END IF;
        IF OLD.hash_chaine IS NOT NULL AND OLD.hash_chaine <> NEW.hash_chaine THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'INTEGRITE: le hash de chaine d une cloture est immuable';
        END IF;
    END");

echo "[OK] 10 triggers restaurés.\n";

// ─── Réactiver FK ────────────────────────────────────────────
$pdo->exec("SET foreign_key_checks = 1");

echo "=== seed_demo.php — Terminé avec succès ===\n";
