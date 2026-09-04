<?php
/**
 * MODULE USINE DE PRODUCTION — Architecture indépendante.
 * Les matières premières, catégories MP, stock MP et mouvements MP
 * utilisent leurs propres tables (pas de partage avec articles/stock_magasins).
 * Les produits finis restent dans `articles` avec origine_article = 'PRODUCTION_USINE'.
 */

// =====================================================================
// MATIÈRES PREMIÈRES — CRUD (table independante matieres_premieres)
// =====================================================================

function db_matiere_premiere_ref(PDO $pdo): string {
    $annee = date('Y');
    $seq = db_sequence_next($pdo, 'matiere_premiere_' . $annee, 1);
    return sprintf('MAT-%s-%03d', $annee, $seq);
}

function db_matiere_premiere_list(PDO $pdo, int $limit = 200): array {
    $st = $pdo->prepare(
        "SELECT mp.*, c.nom AS categorie_nom,
                COALESCE(smp.quantite, 0) AS stock_usine,
                mp.cout_reference * COALESCE(smp.quantite, 0) AS valeur_stock
         FROM matieres_premieres mp
         LEFT JOIN categories_matieres_premieres c ON c.id = mp.categorie_id
         LEFT JOIN stock_matieres_premieres smp ON smp.matiere_id = mp.id
         WHERE mp.actif = 1
         ORDER BY mp.nom
         LIMIT ?"
    );
    $st->execute([$limit]);
    return $st->fetchAll();
}

function db_matiere_premiere_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare(
        "SELECT mp.*, c.nom AS categorie_nom,
                COALESCE(smp.quantite, 0) AS stock_usine
         FROM matieres_premieres mp
         LEFT JOIN categories_matieres_premieres c ON c.id = mp.categorie_id
         LEFT JOIN stock_matieres_premieres smp ON smp.matiere_id = mp.id
         WHERE mp.id = ? AND mp.actif = 1"
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function db_matiere_premiere_insert(PDO $pdo, array $d): int {
    $ref = $d['reference'] ?? db_matiere_premiere_ref($pdo);
    $st = $pdo->prepare(
        "INSERT INTO matieres_premieres (reference, nom, categorie_id, unite_mesure,
                                         cout_reference, stock_minimum, fournisseur_id, actif, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([
        $ref, $d['nom'], $d['categorie_id'] ?? null,
        $d['unite_mesure'] ?? 'KG', $d['cout_reference'] ?? 0,
        $d['stock_minimum'] ?? 10, $d['fournisseur_id'] ?? null,
        $d['actif'] ?? 1, $d['notes'] ?? null
    ]);
    return (int)$pdo->lastInsertId();
}

function db_matiere_premiere_update(PDO $pdo, int $id, array $d): void {
    $st = $pdo->prepare(
        "UPDATE matieres_premieres SET nom = ?, categorie_id = ?, unite_mesure = ?,
                cout_reference = ?, stock_minimum = ?, fournisseur_id = ?,
                actif = ?, notes = ?
         WHERE id = ?"
    );
    $st->execute([
        $d['nom'], $d['categorie_id'] ?? null, $d['unite_mesure'] ?? 'KG',
        $d['cout_reference'] ?? 0, $d['stock_minimum'] ?? 10,
        $d['fournisseur_id'] ?? null, $d['actif'] ?? 1, $d['notes'] ?? null, $id
    ]);
}

// =====================================================================
// CATÉGORIES MATIÈRES PREMIÈRES — CRUD
// =====================================================================

function db_categories_mp_list(PDO $pdo): array {
    return $pdo->query("SELECT * FROM categories_matieres_premieres ORDER BY nom")->fetchAll();
}

function db_categorie_mp_insert(PDO $pdo, string $nom, ?string $description = null): int {
    $st = $pdo->prepare("INSERT INTO categories_matieres_premieres (nom, description) VALUES (?, ?)");
    $st->execute([$nom, $description]);
    return (int)$pdo->lastInsertId();
}

function db_categorie_mp_update(PDO $pdo, int $id, string $nom, ?string $description = null): void {
    $st = $pdo->prepare("UPDATE categories_matieres_premieres SET nom = ?, description = ? WHERE id = ?");
    $st->execute([$nom, $description, $id]);
}

function db_categorie_mp_delete(PDO $pdo, int $id): void {
    $pdo->prepare("DELETE FROM categories_matieres_premieres WHERE id = ?")->execute([$id]);
}

// =====================================================================
// STOCK MATIÈRES PREMIÈRES — Consultation
// =====================================================================

function db_stock_mp_list(PDO $pdo): array {
    $st = $pdo->prepare(
        "SELECT mp.id, mp.nom, mp.reference, mp.unite_mesure, mp.stock_minimum,
                mp.cout_reference,
                COALESCE(smp.quantite, 0) AS quantite,
                COALESCE(smp.valeur_stock, 0) AS valeur_stock
         FROM matieres_premieres mp
         LEFT JOIN stock_matieres_premieres smp ON smp.matiere_id = mp.id
         WHERE mp.actif = 1
         ORDER BY mp.nom"
    );
    $st->execute();
    return $st->fetchAll();
}

function db_stock_mp_matiere(PDO $pdo, int $matiere_id): ?array {
    $st = $pdo->prepare(
        "SELECT smp.*, mp.nom, mp.unite_mesure, mp.reference
         FROM stock_matieres_premieres smp
         JOIN matieres_premieres mp ON mp.id = smp.matiere_id
         WHERE smp.matiere_id = ?"
    );
    $st->execute([$matiere_id]);
    return $st->fetch() ?: null;
}

function db_stock_mp_suffisant(PDO $pdo, int $matiere_id, float $quantite_necessaire): bool {
    $stock = db_stock_mp_matiere($pdo, $matiere_id);
    return $stock && (float)$stock['quantite'] >= $quantite_necessaire;
}

// =====================================================================
// MOUVEMENTS MATIÈRES PREMIÈRES — Entrées / Sorties
// =====================================================================

function db_mouvement_mp_insert(PDO $pdo, int $matiere_id, ?int $user_id, string $type,
                                float $quantite, float $cout_unitaire = 0,
                                ?int $production_id = null, ?int $reception_id = null,
                                ?string $motif = null): int {
    $cout_total = $quantite * $cout_unitaire;
    $st = $pdo->prepare(
        "INSERT INTO mouvements_matieres_premieres
            (matiere_id, utilisateur_id, type, quantite, cout_unitaire, cout_total,
             production_id, reception_id, motif)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$matiere_id, $user_id, $type, $quantite, $cout_unitaire, $cout_total,
                  $production_id, $reception_id, $motif]);
    return (int)$pdo->lastInsertId();
}

function db_matiere_entree_usine(PDO $pdo, int $matiere_id, float $quantite, float $cout_unitaire,
                                ?string $motif = null, ?int $reception_id = null): void {
    $user_id = $_SESSION['user']['id'] ?? null;

    $pdo->beginTransaction();
    try {
        // Upsert stock
        $pdo->prepare(
            "INSERT INTO stock_matieres_premieres (matiere_id, quantite, valeur_stock)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantite = quantite + ?, valeur_stock = valeur_stock + ?"
        )->execute([$matiere_id, $quantite, $quantite * $cout_unitaire,
                    $quantite, $quantite * $cout_unitaire]);

        // Mouvement
        db_mouvement_mp_insert($pdo, $matiere_id, $user_id, 'ENTREE_ACHAT',
            $quantite, $cout_unitaire, null, $reception_id,
            $motif ?? 'Entrée matière première');

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_matiere_sortie_usine(PDO $pdo, int $matiere_id, float $quantite, ?string $motif = null,
                                ?int $production_id = null): void {
    $user_id = $_SESSION['user']['id'] ?? null;

    $stock = db_stock_mp_matiere($pdo, $matiere_id);
    if (!$stock || (float)$stock['quantite'] < $quantite) {
        throw new RuntimeException("Stock insuffisant pour la matière #$matiere_id.");
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "UPDATE stock_matieres_premieres SET quantite = quantite - ?,
                    valeur_stock = GREATEST(0, valeur_stock - ?)
             WHERE matiere_id = ? AND quantite >= ?"
        )->execute([$quantite, $quantite * ($stock['cout_reference'] ?? 0), $matiere_id, $quantite]);

        db_mouvement_mp_insert($pdo, $matiere_id, $user_id, 'SORTIE_PRODUCTION',
            $quantite, $stock['cout_reference'] ?? 0, $production_id, null,
            $motif ?? 'Sortie matière première');

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// =====================================================================
// RECETTES — CRUD (liaison matieres_premieres ↔ articles)
// =====================================================================

function db_recettes_list(PDO $pdo): array {
    $st = $pdo->query(
        "SELECT r.*, a.nom AS article_nom, a.code_barre,
                (SELECT COUNT(*) FROM recettes_lignes rl WHERE rl.recette_id = r.id) AS nb_matieres
         FROM recettes r
         JOIN articles a ON a.id = r.article_id
         ORDER BY a.nom, r.version DESC"
    );
    return $st->fetchAll();
}

function db_recette_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare(
        "SELECT r.*, a.nom AS article_nom, a.code_barre
         FROM recettes r
         JOIN articles a ON a.id = r.article_id
         WHERE r.id = ?"
    );
    $st->execute([$id]);
    $recette = $st->fetch();
    if (!$recette) return null;

    $st2 = $pdo->prepare(
        "SELECT rl.*, mp.nom AS matiere_nom, mp.unite_mesure, mp.cout_reference
         FROM recettes_lignes rl
         JOIN matieres_premieres mp ON mp.id = rl.matiere_id
         WHERE rl.recette_id = ?
         ORDER BY rl.ordre, rl.id"
    );
    $st2->execute([$id]);
    $recette['lignes'] = $st2->fetchAll();
    return $recette;
}

function db_recette_insert(PDO $pdo, array $d): int {
    $st = $pdo->prepare("SELECT COALESCE(MAX(version), 0) + 1 FROM recettes WHERE article_id = ?");
    $st->execute([$d['article_id']]);
    $version = (int)$st->fetchColumn();

    $st = $pdo->prepare(
        "INSERT INTO recettes (nom, article_id, quantite_produite, unite_produit, version, actif, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([
        $d['nom'], $d['article_id'], $d['quantite_produite'] ?? 100,
        $d['unite_produit'] ?? 'UNITE', $version, $d['actif'] ?? 1, $d['notes'] ?? null
    ]);
    $recette_id = (int)$pdo->lastInsertId();

    if (!empty($d['lignes']) && is_array($d['lignes'])) {
        $ordre = 0;
        foreach ($d['lignes'] as $ligne) {
            $ordre++;
            db_recette_ligne_insert($pdo, $recette_id, [
                'matiere_id' => $ligne['matiere_id'],
                'quantite_necessaire' => $ligne['quantite_necessaire'],
                'unite' => $ligne['unite'] ?? 'KG',
                'pertes_theoriques_pct' => $ligne['pertes_theoriques_pct'] ?? 0,
                'ordre' => $ordre,
            ]);
        }
    }

    return $recette_id;
}

function db_recette_ligne_insert(PDO $pdo, int $recette_id, array $d): int {
    $st = $pdo->prepare(
        "INSERT INTO recettes_lignes (recette_id, matiere_id, quantite_necessaire, unite, pertes_theoriques_pct, ordre)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $st->execute([
        $recette_id, $d['matiere_id'], $d['quantite_necessaire'],
        $d['unite'] ?? 'KG', $d['pertes_theoriques_pct'] ?? 0, $d['ordre'] ?? 0
    ]);
    return (int)$pdo->lastInsertId();
}

function db_recette_delete(PDO $pdo, int $id): void {
    $pdo->prepare("DELETE FROM recettes_lignes WHERE recette_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM recettes WHERE id = ?")->execute([$id]);
}

function db_recette_update(PDO $pdo, int $id, array $d): void {
    $st = $pdo->prepare(
        "UPDATE recettes SET nom = ?, quantite_produite = ?, unite_produit = ?,
                actif = ?, notes = ?
         WHERE id = ?"
    );
    $st->execute([
        $d['nom'], $d['quantite_produite'] ?? 100,
        $d['unite_produit'] ?? 'UNITE', $d['actif'] ?? 1, $d['notes'] ?? null, $id
    ]);

    $pdo->prepare("DELETE FROM recettes_lignes WHERE recette_id = ?")->execute([$id]);
    if (!empty($d['lignes']) && is_array($d['lignes'])) {
        $ordre = 0;
        foreach ($d['lignes'] as $ligne) {
            $ordre++;
            db_recette_ligne_insert($pdo, $id, [
                'matiere_id' => $ligne['matiere_id'],
                'quantite_necessaire' => $ligne['quantite_necessaire'],
                'unite' => $ligne['unite'] ?? 'KG',
                'pertes_theoriques_pct' => $ligne['pertes_theoriques_pct'] ?? 0,
                'ordre' => $ordre,
            ]);
        }
    }
}

// =====================================================================
// PRODUITS FINIS — Stock usine (table independante stock_produits_finis_usine)
// =====================================================================

function db_stock_pf_usine_list(PDO $pdo): array {
    $st = $pdo->prepare(
        "SELECT a.id, a.nom, a.code_barre, a.unite_mesure,
                COALESCE(spfu.quantite, 0) AS quantite
         FROM articles a
         LEFT JOIN stock_produits_finis_usine spfu ON spfu.article_id = a.id
         WHERE a.origine_article = 'PRODUCTION_USINE' AND a.actif = 1
         ORDER BY a.nom"
    );
    $st->execute();
    return $st->fetchAll();
}

function db_stock_pf_usine_article(PDO $pdo, int $article_id): ?array {
    $st = $pdo->prepare(
        "SELECT spfu.*, a.nom, a.unite_mesure, a.code_barre
         FROM stock_produits_finis_usine spfu
         JOIN articles a ON a.id = spfu.article_id
         WHERE spfu.article_id = ?"
    );
    $st->execute([$article_id]);
    return $st->fetch() ?: null;
}

// =====================================================================
// PRODUCTIONS — CRUD + Workflow
// =====================================================================

function db_productions_list(PDO $pdo, ?string $statut = null, int $limit = 100): array {
    $sql = "SELECT p.*, a.nom AS article_nom, r.nom AS recette_nom,
                   u.nom AS responsable_nom
            FROM productions p
            JOIN articles a ON a.id = p.article_id
            JOIN recettes r ON r.id = p.recette_id
            LEFT JOIN utilisateurs u ON u.id = p.utilisateur_id";
    $params = [];
    if ($statut) {
        $sql .= " WHERE p.statut = ?";
        $params[] = $statut;
    }
    $sql .= " ORDER BY p.date_creation DESC LIMIT ?";
    $params[] = $limit;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_production_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare(
        "SELECT p.*, a.nom AS article_nom, a.code_barre AS article_code_barre,
                r.nom AS recette_nom, r.quantite_produite AS recette_qte_produite,
                u.nom AS responsable_nom
         FROM productions p
         JOIN articles a ON a.id = p.article_id
         JOIN recettes r ON r.id = p.recette_id
         LEFT JOIN utilisateurs u ON u.id = p.utilisateur_id
         WHERE p.id = ?"
    );
    $st->execute([$id]);
    $prod = $st->fetch();
    if (!$prod) return null;

    // Matières (liaison via matieres_premieres)
    $st2 = $pdo->prepare(
        "SELECT pm.*, mp.nom AS matiere_nom, mp.unite_mesure, mp.cout_reference
         FROM production_matieres pm
         JOIN matieres_premieres mp ON mp.id = pm.matiere_id
         WHERE pm.production_id = ?"
    );
    $st2->execute([$id]);
    $prod['matieres'] = $st2->fetchAll();

    // Employés
    $st3 = $pdo->prepare(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.fonction
         FROM production_employes pe
         JOIN employes e ON e.id = pe.employe_id
         WHERE pe.production_id = ?"
    );
    $st3->execute([$id]);
    $prod['employes'] = $st3->fetchAll();

    // Pertes
    $st4 = $pdo->prepare(
        "SELECT pp.*, a.nom AS article_nom
         FROM production_pertes pp
         JOIN articles a ON a.id = pp.article_id
         WHERE pp.production_id = ?"
    );
    $st4->execute([$id]);
    $prod['pertes'] = $st4->fetchAll();

    // Lots générés
    $st5 = $pdo->prepare(
        "SELECT pl.*, a.nom AS article_nom
         FROM production_lots pl
         JOIN articles a ON a.id = pl.article_id
         WHERE pl.production_id = ?"
    );
    $st5->execute([$id]);
    $prod['lots'] = $st5->fetchAll();

    return $prod;
}

function db_production_reference(PDO $pdo): string {
    $annee = date('Y');
    $seq = db_sequence_next($pdo, 'production_' . $annee, 1);
    return sprintf('PROD-%s-%04d', $annee, $seq);
}

function db_production_insert(PDO $pdo, array $d): int {
    $reference = db_production_reference($pdo);
    $st = $pdo->prepare(
        "INSERT INTO productions (reference, article_id, recette_id, recette_version,
                                  quantite_prevue, statut, date_prevue, utilisateur_id, notes)
         VALUES (?, ?, ?, ?, ?, 'BROUILLON', ?, ?, ?)"
    );
    $st->execute([
        $reference, $d['article_id'], $d['recette_id'], $d['recette_version'] ?? 1,
        $d['quantite_prevue'], $d['date_prevue'] ?? null,
        $d['utilisateur_id'] ?? ($_SESSION['user']['id'] ?? null), $d['notes'] ?? null
    ]);
    $production_id = (int)$pdo->lastInsertId();

    // Pré-remplir les matières depuis la recette
    $recette = db_recette_get($pdo, $d['recette_id']);
    if ($recette && !empty($recette['lignes'])) {
        $ratio = (float)$d['quantite_prevue'] / max(1, (float)$recette['quantite_produite']);
        foreach ($recette['lignes'] as $ligne) {
            $qte_prevue = round((float)$ligne['quantite_necessaire'] * $ratio, 4);
            $pdo->prepare(
                "INSERT INTO production_matieres (production_id, matiere_id, quantite_prevue, unite, cout_unitaire)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([
                $production_id, $ligne['matiere_id'], $qte_prevue,
                $ligne['unite'], $ligne['cout_reference'] ?? 0
            ]);
        }
    }

    // Pré-remplir le produit fini
    $pdo->prepare(
        "INSERT INTO production_produits (production_id, article_id, quantite_produite)
         VALUES (?, ?, ?)"
    )->execute([$production_id, $d['article_id'], $d['quantite_prevue']]);

    return $production_id;
}

function db_production_demarrer(PDO $pdo, int $production_id): void {
    $prod = db_production_get($pdo, $production_id);
    if (!$prod) throw new RuntimeException("Production introuvable.");
    if ($prod['statut'] !== 'BROUILLON' && $prod['statut'] !== 'PLANIFIEE') {
        throw new RuntimeException("Seules les productions BROUILLON ou PLANIFIÉE peuvent être démarrées.");
    }
    $pdo->prepare("UPDATE productions SET statut = 'EN_COURS', date_debut = NOW() WHERE id = ?")
        ->execute([$production_id]);
}

function db_production_cloturer(PDO $pdo, int $production_id, array $matieres_reelles,
                               int $quantite_produite, int $quantite_perdue, array $pertes = []): void {
    $prod = db_production_get($pdo, $production_id);
    if (!$prod) throw new RuntimeException("Production introuvable.");
    if ($prod['statut'] !== 'EN_COURS') {
        throw new RuntimeException("Seule une production EN COURS peut être clôturée.");
    }

    $user_id = $_SESSION['user']['id'] ?? null;

    $total_produit = $quantite_produite + $quantite_perdue;
    if ($total_produit < 0) throw new RuntimeException("Quantités produites incohérentes.");

    $pdo->beginTransaction();
    try {
        $cout_total_matieres = 0;

        // 1. Consommer les matières (décrémenter stock_mp)
        foreach ($matieres_reelles as $m) {
            $matiere_id = (int)$m['matiere_id'];
            $quantite_reelle = (float)$m['quantite_reelle'];

            if ($quantite_reelle < 0) {
                throw new RuntimeException("Quantité réelle ne peut être négative pour la matière #$matiere_id.");
            }

            $stock = db_stock_mp_matiere($pdo, $matiere_id);
            if (!$stock || (float)$stock['quantite'] < $quantite_reelle) {
                throw new RuntimeException("Stock insuffisant pour la matière #$matiere_id (disponible: " . ($stock['quantite'] ?? 0) . ", requis: $quantite_reelle).");
            }

            // Mettre à jour la consommation réelle
            $cout_unit = (float)$m['cout_unitaire'] ?? (float)($stock['cout_reference'] ?? 0);
            $pdo->prepare(
                "UPDATE production_matieres SET quantite_reelle = ?, cout_unitaire = ?, cout_total = ? * ?
                 WHERE production_id = ? AND matiere_id = ?"
            )->execute([$quantite_reelle, $cout_unit, $quantite_reelle, $cout_unit, $production_id, $matiere_id]);

            // Décrémenter stock_mp
            db_matiere_sortie_usine($pdo, $matiere_id, $quantite_reelle,
                "Production " . $prod['reference'] . " — consommation", $production_id);

            $cout_total_matieres += $quantite_reelle * $cout_unit;
        }

        // 2. Enregistrer les produits finis (incrémenter stock_produits_finis_usine)
        $cout_unitaire = $quantite_produite > 0 ? $cout_total_matieres / $quantite_produite : 0;

        $pdo->prepare(
            "UPDATE production_produits SET quantite_produite = ?, quantite_perdue = ? WHERE production_id = ?"
        )->execute([$quantite_produite, $quantite_perdue, $production_id]);

        if ($quantite_produite > 0) {
            // Upsert stock produit fini usine
            $pdo->prepare(
                "INSERT INTO stock_produits_finis_usine (article_id, quantite, production_id)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantite = quantite + ?, production_id = ?"
            )->execute([
                $prod['article_id'], $quantite_produite, $production_id,
                $quantite_produite, $production_id
            ]);

            // Enregistrer lot de production
            $lot_numero = $prod['reference'];
            $pdo->prepare(
                "INSERT INTO production_lots (production_id, numero_lot, article_id, quantite,
                                              date_fabrication, cout_unitaire)
                 VALUES (?, ?, ?, ?, CURDATE(), ?)"
            )->execute([$production_id, $lot_numero, $prod['article_id'], $quantite_produite, $cout_unitaire]);

            // Mouvement produit fini
            db_mouvement_pf_insert($pdo, $prod['article_id'], $user_id, 'PRODUCTION',
                $quantite_produite, $production_id, null,
                "Production " . $prod['reference'] . " — $quantite_produite produits conformes");

            // Mettre à jour le coût de production de référence sur l'article
            $pdo->prepare("UPDATE articles SET cout_production_ref = ? WHERE id = ?")
                ->execute([$cout_unitaire, $prod['article_id']]);
        }

        // 3. Enregistrer les pertes
        if (!empty($pertes)) {
            foreach ($pertes as $perte) {
                $pdo->prepare(
                    "INSERT INTO production_pertes (production_id, type_perte, article_id, quantite, unite, motif, commentaire, utilisateur_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    $production_id, $perte['type_perte'], $perte['article_id'],
                    $perte['quantite'], $perte['unite'] ?? 'UNITE',
                    $perte['motif'] ?? null, $perte['commentaire'] ?? null, $user_id
                ]);

                if ((float)$perte['quantite'] > 0) {
                    db_mouvement_pf_insert($pdo, $perte['article_id'], $user_id, 'AJUSTEMENT_SORTIE',
                        (int)$perte['quantite'], $production_id, null,
                        "Production " . $prod['reference'] . " — perte: " . ($perte['motif'] ?? 'non conforme'));
                }
            }
        }

        // 4. Mettre à jour la production
        $pdo->prepare(
            "UPDATE productions SET statut = 'TERMINEE', date_fin = NOW(),
                    quantite_produite = ?, quantite_perdue = ?,
                    cout_matieres = ?, cout_unitaire = ?
             WHERE id = ?"
        )->execute([$quantite_produite, $quantite_perdue, $cout_total_matieres, $cout_unitaire, $production_id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_production_annuler(PDO $pdo, int $production_id, ?string $motif = null): void {
    $prod = db_production_get($pdo, $production_id);
    if (!$prod) throw new RuntimeException("Production introuvable.");
    if ($prod['statut'] === 'TERMINEE') {
        throw new RuntimeException("Une production terminée ne peut être annulée.");
    }
    $pdo->prepare("UPDATE productions SET statut = 'ANNULEE', date_fin = NOW() WHERE id = ?")
        ->execute([$production_id]);
}

// =====================================================================
// PRODUCTION — Affectation employés
// =====================================================================

function db_production_set_employes(PDO $pdo, int $production_id, array $employe_ids): void {
    $pdo->prepare("DELETE FROM production_employes WHERE production_id = ?")->execute([$production_id]);
    $st = $pdo->prepare("INSERT IGNORE INTO production_employes (production_id, employe_id) VALUES (?, ?)");
    foreach ($employe_ids as $eid) {
        $st->execute([$production_id, (int)$eid]);
    }
}

// =====================================================================
// MOUVEMENTS PRODUITS FINIS USINE
// =====================================================================

function db_mouvement_pf_insert(PDO $pdo, int $article_id, ?int $user_id, string $type,
                               int $quantite, ?int $production_id = null,
                               ?int $magasin_destination_id = null, ?string $motif = null): int {
    $st = $pdo->prepare(
        "INSERT INTO mouvements_produits_finis
            (article_id, utilisateur_id, type, quantite, production_id,
             magasin_destination_id, motif)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$article_id, $user_id, $type, $quantite, $production_id,
                  $magasin_destination_id, $motif]);
    return (int)$pdo->lastInsertId();
}

// =====================================================================
// EMPLOYÉS — CRUD
// =====================================================================

function db_employes_list(PDO $pdo, bool $actifs_only = true): array {
    $sql = "SELECT * FROM employes";
    if ($actifs_only) $sql .= " WHERE actif = 1";
    $sql .= " ORDER BY nom, prenom";
    return $pdo->query($sql)->fetchAll();
}

function db_employe_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM employes WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function db_employe_insert(PDO $pdo, array $d): int {
    $st = $pdo->prepare(
        "INSERT INTO employes (matricule, nom, prenom, fonction, telephone, actif)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $st->execute([
        $d['matricule'], $d['nom'], $d['prenom'] ?? '',
        $d['fonction'] ?? '', $d['telephone'] ?? null, $d['actif'] ?? 1
    ]);
    return (int)$pdo->lastInsertId();
}

function db_employe_update(PDO $pdo, int $id, array $d): void {
    $st = $pdo->prepare(
        "UPDATE employes SET matricule = ?, nom = ?, prenom = ?, fonction = ?, telephone = ?, actif = ?
         WHERE id = ?"
    );
    $st->execute([
        $d['matricule'], $d['nom'], $d['prenom'] ?? '',
        $d['fonction'] ?? '', $d['telephone'] ?? null, $d['actif'] ?? 1, $id
    ]);
}

// =====================================================================
// PRÉSENCES — CRUD + Audit
// =====================================================================

function db_presences_list_date(PDO $pdo, string $date): array {
    $st = $pdo->prepare(
        "SELECT pe.*, e.nom, e.prenom, e.matricule, e.fonction,
                u.nom AS enregistre_par
         FROM presences_employes pe
         JOIN employes e ON e.id = pe.employe_id
         LEFT JOIN utilisateurs u ON u.id = pe.utilisateur_id
         WHERE pe.date_presence = ?
         ORDER BY e.nom, e.prenom"
    );
    $st->execute([$date]);
    return $st->fetchAll();
}

function db_presence_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare(
        "SELECT pe.*, e.nom, e.prenom, e.matricule
         FROM presences_employes pe
         JOIN employes e ON e.id = pe.employe_id
         WHERE pe.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function db_presence_upsert(PDO $pdo, int $employe_id, string $date_presence,
                           ?string $heure_arrivee, ?string $heure_depart,
                           ?string $commentaire = null): int {
    $user_id = $_SESSION['user']['id'] ?? null;

    $temps_minutes = null;
    if ($heure_arrivee && $heure_depart) {
        $arr = strtotime($heure_arrivee);
        $dep = strtotime($heure_depart);
        if ($dep >= $arr) {
            $temps_minutes = (int)(($dep - $arr) / 60);
        }
    }

    $st = $pdo->prepare("SELECT * FROM presences_employes WHERE employe_id = ? AND date_presence = ?");
    $st->execute([$employe_id, $date_presence]);
    $existing = $st->fetch();

    if ($existing) {
        $ancienne = json_encode([
            'heure_arrivee' => $existing['heure_arrivee'],
            'heure_depart' => $existing['heure_depart'],
            'temps_travaille_minutes' => $existing['temps_travaille_minutes'],
            'commentaire' => $existing['commentaire'],
        ]);

        $st2 = $pdo->prepare(
            "UPDATE presences_employes SET heure_arrivee = ?, heure_depart = ?,
                    temps_travaille_minutes = ?, commentaire = ?, utilisateur_id = ?
             WHERE id = ?"
        );
        $st2->execute([$heure_arrivee, $heure_depart, $temps_minutes, $commentaire, $user_id, $existing['id']]);

        $nouvelle = json_encode([
            'heure_arrivee' => $heure_arrivee,
            'heure_depart' => $heure_depart,
            'temps_travaille_minutes' => $temps_minutes,
            'commentaire' => $commentaire,
        ]);

        $pdo->prepare(
            "INSERT INTO presences_employes_audit (presence_id, employe_id, ancienne_valeur, nouvelle_valeur, utilisateur_id, motif)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$existing['id'], $employe_id, $ancienne, $nouvelle, $user_id, 'Modification présence']);

        return (int)$existing['id'];
    } else {
        $st2 = $pdo->prepare(
            "INSERT INTO presences_employes (employe_id, date_presence, heure_arrivee, heure_depart, temps_travaille_minutes, commentaire, utilisateur_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st2->execute([$employe_id, $date_presence, $heure_arrivee, $heure_depart, $temps_minutes, $commentaire, $user_id]);
        return (int)$pdo->lastInsertId();
    }
}

// =====================================================================
// TABLEAU DE BORD USINE
// =====================================================================

function db_usine_dashboard(PDO $pdo): array {
    $today = date('Y-m-d');

    // Productions du jour
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS nb, COALESCE(SUM(quantite_produite), 0) AS produits,
                COALESCE(SUM(quantite_perdue), 0) AS pertes
         FROM productions WHERE DATE(date_creation) = ? AND statut != 'ANNULEE'"
    );
    $st->execute([$today]);
    $productions_jour = $st->fetch();

    // Matières sous seuil
    $st = $pdo->query(
        "SELECT COUNT(*) AS nb
         FROM matieres_premieres mp
         LEFT JOIN stock_matieres_premieres smp ON smp.matiere_id = mp.id
         WHERE mp.actif = 1
           AND COALESCE(smp.quantite, 0) <= mp.stock_minimum"
    );
    $matieres_alerte = (int)$st->fetchColumn();

    // Employés présents aujourd'hui
    $st = $pdo->prepare(
        "SELECT COUNT(DISTINCT employe_id) AS presents
         FROM presences_employes WHERE date_presence = ? AND heure_arrivee IS NOT NULL"
    );
    $st->execute([$today]);
    $presents = (int)$st->fetchColumn();

    $total_employes = (int)$pdo->query("SELECT COUNT(*) FROM employes WHERE actif = 1")->fetchColumn();

    // Stock produits finis en usine
    $st = $pdo->query(
        "SELECT a.nom, COALESCE(spfu.quantite, 0) AS quantite
         FROM articles a
         LEFT JOIN stock_produits_finis_usine spfu ON spfu.article_id = a.id
         WHERE a.origine_article = 'PRODUCTION_USINE' AND a.actif = 1
         ORDER BY a.nom"
    );
    $produits_finis = $st->fetchAll();

    // Stock matières premières
    $st = $pdo->query(
        "SELECT mp.nom, mp.unite_mesure, COALESCE(smp.quantite, 0) AS quantite, mp.stock_minimum
         FROM matieres_premieres mp
         LEFT JOIN stock_matieres_premieres smp ON smp.matiere_id = mp.id
         WHERE mp.actif = 1
         ORDER BY mp.nom"
    );
    $matieres_stock = $st->fetchAll();

    return [
        'productions_jour' => $productions_jour,
        'matieres_alerte' => $matieres_alerte,
        'employes_presents' => $presents,
        'total_employes' => $total_employes,
        'produits_finis' => $produits_finis,
        'matieres_stock' => $matieres_stock,
    ];
}

// =====================================================================
// TRANSFERT USINE → MAGASIN (produits finis uniquement)
// =====================================================================

function db_transfert_usine_vers_magasin(PDO $pdo, int $article_id, int $magasin_destination_id,
                                         int $quantite, ?string $motif = null): void {
    $user_id = $_SESSION['user']['id'] ?? null;

    if ($quantite <= 0) {
        throw new RuntimeException("La quantité doit être supérieure à zéro.");
    }

    $pdo->beginTransaction();
    try {
        // Lock stock produit fini usine
        $st = $pdo->prepare(
            "SELECT spfu.quantite FROM stock_produits_finis_usine spfu
             WHERE spfu.article_id = ? FOR UPDATE"
        );
        $st->execute([$article_id]);
        $stock = $st->fetch();
        if (!$stock || (int)$stock['quantite'] < $quantite) {
            throw new RuntimeException("Stock insuffisant de produits finis en usine.");
        }

        // Décrémenter usine
        $pdo->prepare(
            "UPDATE stock_produits_finis_usine SET quantite = quantite - ? WHERE article_id = ?"
        )->execute([$quantite, $article_id]);

        // Incrémenter magasin destination (via stock_magasins normal)
        $pdo->prepare(
            "INSERT INTO stock_magasins (magasin_id, article_id, quantite, valeur_stock, stock_alerte)
             VALUES (?, ?, ?, 0, 5)
             ON DUPLICATE KEY UPDATE quantite = quantite + ?"
        )->execute([$magasin_destination_id, $article_id, $quantite, $quantite]);

        // Mouvements
        db_mouvement_pf_insert($pdo, $article_id, $user_id, 'TRANSFERT_SORTIE', $quantite,
            null, $magasin_destination_id, $motif ?? 'Transfert usine → magasin');
        db_mouvement_insert($pdo, $article_id, $user_id, 'Transfert', $quantite,
            $motif ?? 'Transfert depuis usine', $magasin_destination_id);

        // Transfert record
        $pdo->prepare(
            "INSERT INTO transferts_stock (article_id, magasin_source_id, magasin_destination_id, quantite, utilisateur_id, motif)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$article_id, 0, $magasin_destination_id, $quantite, $user_id,
                    $motif ?? 'Transfert usine → magasin']);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// =====================================================================
// RAPPORT DE PRODUCTION
// =====================================================================

function db_production_rapport(PDO $pdo, string $date_debut, string $date_fin, ?int $article_id = null): array {
    $sql = "SELECT p.*, a.nom AS article_nom, r.nom AS recette_nom,
                   u.nom AS responsable_nom
            FROM productions p
            JOIN articles a ON a.id = p.article_id
            JOIN recettes r ON r.id = p.recette_id
            LEFT JOIN utilisateurs u ON u.id = p.utilisateur_id
            WHERE p.date_creation BETWEEN ? AND ?";
    $params = [$date_debut . ' 00:00:00', $date_fin . ' 23:59:59'];

    if ($article_id) {
        $sql .= " AND p.article_id = ?";
        $params[] = $article_id;
    }
    $sql .= " ORDER BY p.date_creation DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_production_rapport_detail(PDO $pdo, int $production_id): array {
    return db_production_get($pdo, $production_id);
}
