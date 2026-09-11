<?php
/**
 * MODULE USINE DE PRODUCTION — Architecture indépendante.
 * Les matières premières, catégories MP et stock MP
 * utilisent leurs propres tables (pas de partage avec articles/stock_magasins).
 * Les produits finis restent dans `articles` avec origine_article = 'PRODUCTION_USINE'.
 *
 * Note : Les tables `mouvements_matieres_premieres`, `mouvements_produits_finis`,
 *        `production_lots`, `production_produits`, `production_employes` et
 *        `presences_employes_audit` ont été supprimées (migration 2026-09-10).
 *        Le suivi des mouvements est centralisé dans `mouvements_stock` et `logs_activite`.
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
    // Table `mouvements_matieres_premieres` supprimée (migration 2026-09-10).
    // Le stock MP est suivi directement dans stock_matieres_premieres.
    return 0;
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

    $inTransaction = $pdo->inTransaction();
    if (!$inTransaction) $pdo->beginTransaction();
    try {
        // Coût moyen pondéré (évite la dérive de valorisation)
        $cout_moyen = max(0, (float)$stock['quantite']) > 0
            ? (float)$stock['valeur_stock'] / (float)$stock['quantite']
            : (float)($stock['cout_reference'] ?? 0);
        $valeur_sortie = $quantite * $cout_moyen;

        $pdo->prepare(
            "UPDATE stock_matieres_premieres SET quantite = quantite - ?,
                    valeur_stock = valeur_stock - ?
             WHERE matiere_id = ? AND quantite >= ?"
        )->execute([$quantite, $valeur_sortie, $matiere_id, $quantite]);

        db_mouvement_mp_insert($pdo, $matiere_id, $user_id, 'SORTIE_PRODUCTION',
            $quantite, $cout_moyen, $production_id, null,
            $motif ?? 'Sortie matière première');

        if (!$inTransaction) $pdo->commit();
    } catch (Throwable $e) {
        if (!$inTransaction) $pdo->rollBack();
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

    // Employés et lots
    $prod['employes'] = [];
    $prod['lots'] = [];

    // Pertes
    $st4 = $pdo->prepare(
        "SELECT pp.*, a.nom AS article_nom
         FROM production_pertes pp
         JOIN articles a ON a.id = pp.article_id
         WHERE pp.production_id = ?"
    );
    $st4->execute([$id]);
    $prod['pertes'] = $st4->fetchAll();

    return $prod;
}

function db_production_reference(PDO $pdo): string {
    $annee = date('Y');
    $seq = db_sequence_next($pdo, 'production_' . $annee, 1);
    return sprintf('PROD-%s-%04d', $annee, $seq);
}

function db_production_insert(PDO $pdo, array $d): int {
    // Valider que la recette appartient à l'article sélectionné et récupérer la version
    $recette_check = $pdo->prepare("SELECT article_id, version FROM recettes WHERE id = ?");
    $recette_check->execute([$d['recette_id']]);
    $recette_row = $recette_check->fetch();
    if (!$recette_row || (int)$recette_row['article_id'] !== (int)$d['article_id']) {
        throw new RuntimeException("La recette sélectionnée ne correspond pas au produit fini choisi.");
    }

    // Utiliser la version réelle de la recette (pas la valeur par défaut)
    $recette_version = (int)($d['recette_version'] ?? $recette_row['version'] ?? 1);

    $reference = db_production_reference($pdo);
    $st = $pdo->prepare(
        "INSERT INTO productions (reference, article_id, recette_id, recette_version,
                                  quantite_prevue, statut, date_prevue, utilisateur_id, notes)
         VALUES (?, ?, ?, ?, ?, 'BROUILLON', ?, ?, ?)"
    );
    $st->execute([
        $reference, $d['article_id'], $d['recette_id'], $recette_version,
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
        $total_matieres = 0;

        // 1. Consommer les matières (décrémenter stock_mp)
        foreach ($matieres_reelles as $m) {
            $matiere_id = (int)$m['matiere_id'];
            $quantite_reelle = (float)$m['quantite_reelle'];
            $total_matieres += $quantite_reelle;

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

            // Mettre à jour le coût de production de référence sur l'article
            $pdo->prepare("UPDATE articles SET cout_production_ref = ? WHERE id = ?")
                ->execute([$cout_unitaire, $prod['article_id']]);
        }

        // 3. Enregistrer les pertes
        if (!empty($pertes)) {
            foreach ($pertes as $perte) {
                $categorie_perte_id = $perte['categorie_perte_id'] ?? null;
                $pdo->prepare(
                    "INSERT INTO production_pertes (production_id, type_perte, categorie_perte_id, article_id, quantite, unite, motif, commentaire, utilisateur_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    $production_id, $perte['type_perte'], $categorie_perte_id,
                    $perte['article_id'] ?? null,
                    $perte['quantite'], $perte['unite'] ?? 'UNITE',
                    $perte['motif'] ?? null, $perte['commentaire'] ?? null, $user_id
                ]);

                if ((float)$perte['quantite'] > 0) {
                    // Les mouvements de pertes sont tracés dans mouvements_stock via
                    // db_mouvement_insert si nécessaire (mouvements_produits_finis supprimé).
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

        // 5. Calculer et sauvegarder le rendement
        if ($total_produit > 0) {
            // Inclure toutes les pertes : quantite_perdue + pertes additionnelles
            $total_pertes = $quantite_perdue;
            if (!empty($pertes)) {
                foreach ($pertes as $perte) {
                    $total_pertes += (float)($perte['quantite'] ?? 0);
                }
            }

            // Rendement basé sur les quantités (formule unifiée)
            $rendement_pct = ($quantite_produite + $total_pertes) > 0
                ? round(($quantite_produite / ($quantite_produite + $total_pertes)) * 100, 2)
                : 0;

            // Rendement basé sur les coûts (pour information)
            $cout_pertes = $total_matieres > 0 ? ($total_pertes / max(1, $quantite_produite + $total_pertes)) * $cout_total_matieres : 0;
            $rendement_cout = ($cout_total_matieres + $cout_pertes) > 0
                ? round(($cout_total_matieres / ($cout_total_matieres + $cout_pertes)) * 100, 2)
                : 0;

            $pdo->prepare("UPDATE productions SET rendement_pct = ?, rendement_cout = ? WHERE id = ?")
                ->execute([$rendement_pct, $rendement_cout, $production_id]);

            // Notification si pertes élevées (> 15%)
            $taux_perte_pct = ($quantite_produite + $total_pertes) > 0
                ? round(($total_pertes / ($quantite_produite + $total_pertes)) * 100, 2)
                : 0;
            if ($taux_perte_pct > 15) {
                try {
                    db_notif_perte_elevee($pdo, $prod['reference'], $taux_perte_pct);
                } catch (Throwable $ignored) {}
            }

            // Notification si rendement bas (< 85%)
            if ($rendement_pct < 85 && $cout_total_matieres > 0) {
                try {
                    db_notif_rendement_bas($pdo, $prod['reference'], $rendement_pct);
                } catch (Throwable $ignored) {}
            }
        }

        // 6. Notification de production terminée
        try {
            db_notif_production($pdo, 'terminée', $prod['reference'],
                "{$quantite_produite} produits conformes, {$quantite_perdue} perdus.");
        } catch (Throwable $ignored) {}

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
    // Affectation simplifiée — non utilisée dans l'interface actuelle
}

function db_mouvement_pf_insert(PDO $pdo, int $article_id, ?int $user_id, string $type,
                               int $quantite, ?int $production_id = null,
                               ?int $magasin_destination_id = null, ?string $motif = null): int {
    // Table `mouvements_produits_finis` supprimée (migration 2026-09-10).
    // Les mouvements sont tracés dans mouvements_stock via db_mouvement_insert.
    return 0;
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

    // Calculer le retard avant l'upsert
    $retard_info = db_calculer_retard($pdo, $employe_id, $date_presence, $heure_arrivee);

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

        if (function_exists('suivre_activite')) {
            suivre_activite('MODIF_PRESENCE', "Pointage modifié pour l'employé #$employe_id");
        }

        $presence_id = (int)$existing['id'];
    } else {
        $st2 = $pdo->prepare(
            "INSERT INTO presences_employes (employe_id, date_presence, heure_arrivee, heure_depart, temps_travaille_minutes, commentaire, utilisateur_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st2->execute([$employe_id, $date_presence, $heure_arrivee, $heure_depart, $temps_minutes, $commentaire, $user_id]);
        $presence_id = (int)$pdo->lastInsertId();
    }

    // Créer notification si retard
    if ($retard_info['statut'] === 'EN_RETARD' && $retard_info['retard_minutes'] > 0) {
        $employe = db_employe_get($pdo, $employe_id);
        if ($employe) {
            $nom_complet = trim($employe['prenom'] . ' ' . $employe['nom']);
            try {
                db_notif_retard_employe($pdo, $nom_complet, $heure_arrivee, $retard_info['heure_prevue'], $retard_info['retard_minutes']);
            } catch (Throwable $ignored) {}
        }
    }

    return $presence_id;
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

        // Maintenir le stock global (articles.quantite_stock) synchronisé
        $pdo->prepare("UPDATE articles SET quantite_stock = quantite_stock + ? WHERE id = ?")
            ->execute([$quantite, $article_id]);

        // Mouvement de stock principal
        db_mouvement_insert($pdo, $article_id, $user_id, 'TRANSFERT', $quantite,
            $motif ?? 'Transfert depuis usine', $magasin_destination_id);

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

// =====================================================================
// MACHINES — CRUD + États (démarrage / arrêt)
// =====================================================================

function db_machine_ref(PDO $pdo): string {
    $annee = date('Y');
    $seq = db_sequence_next($pdo, 'machine_' . $annee, 1);
    return sprintf('MAC-%s-%03d', $annee, $seq);
}

function db_machines_list(PDO $pdo, bool $actifs_only = true): array {
    $sql = "SELECT m.*,
                   (SELECT me.etat FROM machine_etats me WHERE me.machine_id = m.id ORDER BY me.id DESC LIMIT 1) AS etat_actuel,
                   (SELECT me.heure_debut FROM machine_etats me WHERE me.machine_id = m.id AND me.heure_fin IS NULL ORDER BY me.id DESC LIMIT 1) AS derniere_action
            FROM machines m";
    if ($actifs_only) $sql .= " WHERE m.actif = 1";
    $sql .= " ORDER BY m.nom";
    return $pdo->query($sql)->fetchAll();
}

function db_machine_get(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM machines WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function db_machine_insert(PDO $pdo, array $d): int {
    $ref = $d['reference'] ?? db_machine_ref($pdo);
    $st = $pdo->prepare(
        "INSERT INTO machines (reference, nom, type, description, etat, actif)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $st->execute([
        $ref, $d['nom'], $d['type'] ?? null, $d['description'] ?? null,
        $d['etat'] ?? 'ARRETEE', $d['actif'] ?? 1
    ]);
    return (int)$pdo->lastInsertId();
}

function db_machine_update(PDO $pdo, int $id, array $d): void {
    $st = $pdo->prepare(
        "UPDATE machines SET nom = ?, type = ?, description = ?, actif = ?
         WHERE id = ?"
    );
    $st->execute([
        $d['nom'], $d['type'] ?? null, $d['description'] ?? null,
        $d['actif'] ?? 1, $id
    ]);
}

function db_machine_demarrer(PDO $pdo, int $machine_id, ?int $production_id = null, ?int $utilisateur_id = null): int {
    $machine = db_machine_get($pdo, $machine_id);
    if (!$machine) throw new RuntimeException("Machine introuvable.");

    $user_id = $utilisateur_id ?? ($_SESSION['user']['id'] ?? null);

    $pdo->beginTransaction();
    try {
        // Fermer tout état ouvert précédent
        $pdo->prepare(
            "UPDATE machine_etats SET heure_fin = NOW(),
                    duree_minutes = TIMESTAMPDIFF(MINUTE, heure_debut, NOW())
             WHERE machine_id = ? AND heure_fin IS NULL"
        )->execute([$machine_id]);

        // Enregistrer le démarrage
        $st = $pdo->prepare(
            "INSERT INTO machine_etats (machine_id, production_id, etat, heure_debut, utilisateur_id)
             VALUES (?, ?, 'EN_FONCTIONNEMENT', NOW(), ?)"
        );
        $st->execute([$machine_id, $production_id, $user_id]);
        $etat_id = (int)$pdo->lastInsertId();

        // Mettre à jour l'état de la machine
        $pdo->prepare("UPDATE machines SET etat = 'EN_FONCTIONNEMENT' WHERE id = ?")
            ->execute([$machine_id]);

        $pdo->commit();
        return $etat_id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_machine_arreter(PDO $pdo, int $machine_id, ?string $motif = null, ?int $utilisateur_id = null): void {
    $machine = db_machine_get($pdo, $machine_id);
    if (!$machine) throw new RuntimeException("Machine introuvable.");

    $user_id = $utilisateur_id ?? ($_SESSION['user']['id'] ?? null);

    // Mapper le motif vers l'état correct
    $etat = match(true) {
        stripos($motif ?? '', 'panne') !== false => 'EN_PANNE',
        stripos($motif ?? '', 'maintenance') !== false => 'EN_MAINTENANCE',
        default => 'ARRETEE',
    };

    $pdo->beginTransaction();
    try {
        // Fermer l'état ouvert
        $pdo->prepare(
            "UPDATE machine_etats SET heure_fin = NOW(),
                    duree_minutes = TIMESTAMPDIFF(MINUTE, heure_debut, NOW()),
                    motif = ?, etat = ?
             WHERE machine_id = ? AND heure_fin IS NULL"
        )->execute([$motif, $etat, $machine_id]);

        // Mettre à jour l'état de la machine
        $pdo->prepare("UPDATE machines SET etat = ? WHERE id = ?")
            ->execute([$etat, $machine_id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_machine_set_etat(PDO $pdo, int $machine_id, string $etat, ?string $motif = null, ?int $utilisateur_id = null): void {
    $machine = db_machine_get($pdo, $machine_id);
    if (!$machine) throw new RuntimeException("Machine introuvable.");

    $user_id = $utilisateur_id ?? ($_SESSION['user']['id'] ?? null);

    $pdo->beginTransaction();
    try {
        // Fermer l'état ouvert précédent
        $pdo->prepare(
            "UPDATE machine_etats SET heure_fin = NOW(),
                    duree_minutes = TIMESTAMPDIFF(MINUTE, heure_debut, NOW())
             WHERE machine_id = ? AND heure_fin IS NULL"
        )->execute([$machine_id]);

        // Enregistrer le nouvel état
        $pdo->prepare(
            "INSERT INTO machine_etats (machine_id, etat, heure_debut, motif, utilisateur_id)
             VALUES (?, ?, NOW(), ?, ?)"
        )->execute([$machine_id, $etat, $motif, $user_id]);

        // Mettre à jour la machine
        $pdo->prepare("UPDATE machines SET etat = ? WHERE id = ?")
            ->execute([$etat, $machine_id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_machine_historique(PDO $pdo, int $machine_id, int $limit = 50): array {
    $st = $pdo->prepare(
        "SELECT me.*, u.nom AS utilisateur_nom
         FROM machine_etats me
         LEFT JOIN utilisateurs u ON u.id = me.utilisateur_id
         WHERE me.machine_id = ?
         ORDER BY me.heure_debut DESC
         LIMIT ?"
    );
    $st->execute([$machine_id, $limit]);
    return $st->fetchAll();
}

function db_machines_en_fonctionnement(PDO $pdo): array {
    $st = $pdo->prepare(
        "SELECT m.*, me.heure_debut, me.production_id,
                p.reference AS production_reference
         FROM machines m
         JOIN machine_etats me ON me.machine_id = m.id AND me.heure_fin IS NULL
         LEFT JOIN productions p ON p.id = me.production_id
         WHERE m.actif = 1"
    );
    $st->execute();
    return $st->fetchAll();
}

function db_production_machines(PDO $pdo, int $production_id): array {
    $st = $pdo->prepare(
        "SELECT me.*, m.nom AS machine_nom, m.reference AS machine_reference, u.nom AS utilisateur_nom
         FROM machine_etats me
         JOIN machines m ON m.id = me.machine_id
         LEFT JOIN utilisateurs u ON u.id = me.utilisateur_id
         WHERE me.production_id = ?
         ORDER BY me.heure_debut"
    );
    $st->execute([$production_id]);
    return $st->fetchAll();
}

// =====================================================================
// NOTIFICATIONS — CRUD + Helpers
// =====================================================================

function db_notifications_list(PDO $pdo, ?string $type = null, ?string $role = null,
                               ?int $utilisateur_id = null, bool $non_lues_seulement = false,
                               int $limit = 50): array {
    $sql = "SELECT * FROM notifications WHERE 1=1";
    $params = [];
    if ($type) { $sql .= " AND type = ?"; $params[] = $type; }
    if ($role) { $sql .= " AND (cible_role IS NULL OR cible_role = ?)"; $params[] = $role; }
    if ($utilisateur_id) { $sql .= " AND (cible_utilisateur_id IS NULL OR cible_utilisateur_id = ?)"; $params[] = $utilisateur_id; }
    if ($non_lues_seulement) { $sql .= " AND lu = 0"; }
    $sql .= " ORDER BY date_creation DESC LIMIT ?";
    $params[] = $limit;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_notifications_nb_non_lues(PDO $pdo, ?string $role = null, ?int $utilisateur_id = null): int {
    $sql = "SELECT COUNT(*) FROM notifications WHERE lu = 0";
    $params = [];
    if ($role) { $sql .= " AND (cible_role IS NULL OR cible_role = ?)"; $params[] = $role; }
    if ($utilisateur_id) { $sql .= " AND (cible_utilisateur_id IS NULL OR cible_utilisateur_id = ?)"; $params[] = $utilisateur_id; }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

function db_notification_insert(PDO $pdo, string $type, string $titre, string $message,
                                ?string $cible_role = null, ?int $cible_utilisateur_id = null,
                                ?int $source_id = null, ?string $source_type = null): int {
    $st = $pdo->prepare(
        "INSERT INTO notifications (type, titre, message, cible_role, cible_utilisateur_id, source_id, source_type)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$type, $titre, $message, $cible_role, $cible_utilisateur_id, $source_id, $source_type]);
    return (int)$pdo->lastInsertId();
}

function db_notification_marquer_lue(PDO $pdo, int $notification_id): void {
    $pdo->prepare("UPDATE notifications SET lu = 1, date_lecture = NOW() WHERE id = ?")
        ->execute([$notification_id]);
}

function db_notification_tout_lu(PDO $pdo, ?string $role = null, ?int $utilisateur_id = null): void {
    $sql = "UPDATE notifications SET lu = 1, date_lecture = NOW() WHERE lu = 0";
    $params = [];
    if ($role) { $sql .= " AND (cible_role IS NULL OR cible_role = ?)"; $params[] = $role; }
    if ($utilisateur_id) { $sql .= " AND (cible_utilisateur_id IS NULL OR cible_utilisateur_id = ?)"; $params[] = $utilisateur_id; }
    $st = $pdo->prepare($sql);
    $st->execute($params);
}

function db_notification_supprimer(PDO $pdo, int $notification_id): void {
    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notification_id]);
}

// Helpers de création rapide
function db_notif_retard_employe(PDO $pdo, string $nom_employe, string $heure_arrivee,
                                 string $heure_prevue, int $retard_minutes): int {
    $titre = "Retard employé : {$nom_employe}";
    $message = "{$nom_employe} est arrivé(e) à {$heure_arrivee}. Heure prévue : {$heure_prevue}. Retard : {$retard_minutes} minutes.";
    return db_notification_insert($pdo, 'retard_employe', $titre, $message);
}

function db_notif_absence_employe(PDO $pdo, string $nom_employe, string $heure_prevue): int {
    $titre = "Absence employé : {$nom_employe}";
    $message = "{$nom_employe} n'est pas encore arrivé(e). Heure prévue : {$heure_prevue}.";
    return db_notification_insert($pdo, 'absence_employe', $titre, $message);
}

function db_notif_machine(PDO $pdo, string $type_machine, string $machine_nom, string $action, ?string $details = null): int {
    $titre = "Machine {$action} : {$machine_nom}";
    $message = "La machine {$machine_nom} ({$type_machine}) a été {$action}.";
    if ($details) $message .= " {$details}";
    $notif_type = match($action) {
        'démarrée' => 'machine_demarree',
        'arrêtée' => 'machine_arretee',
        'en panne' => 'machine_panee',
        default => 'machine_info',
    };
    // Envoyer aux utilisateurs configurés
    $user_ids = usine_notif_machines_get($pdo);
    if (!empty($user_ids)) {
        $last_id = 0;
        foreach ($user_ids as $uid) {
            $last_id = db_notification_insert($pdo, $notif_type, $titre, $message, null, (int)$uid);
        }
        return $last_id;
    }
    return db_notification_insert($pdo, $notif_type, $titre, $message);
}

// =====================================================================
// CONFIG NOTIFICATIONS MACHINES — quels utilisateurs reçoivent les alertes
// =====================================================================

function usine_notif_machines_get(PDO $pdo): array {
    $valeur = param('usine_notif_machines_utilisateurs', '');
    if ($valeur === '') return [];
    return array_map('intval', explode(',', $valeur));
}

function usine_notif_machines_set(PDO $pdo, array $user_ids): void {
    $clean = array_filter(array_map('intval', $user_ids));
    param_save('usine_notif_machines_utilisateurs', implode(',', $clean));
}

function db_notif_perte_elevee(PDO $pdo, string $production_ref, float $pct_perte): int {
    $titre = "Pertes élevées : {$production_ref}";
    $message = "La production {$production_ref} présente un taux de perte de {$pct_perte}%. Vérification recommandée.";
    return db_notification_insert($pdo, 'perte_elevee', $titre, $message);
}

function db_notif_stock_faible_matiere(PDO $pdo, string $matiere_nom, float $stock_actuel, float $stock_minimum): int {
    $titre = "Stock faible : {$matiere_nom}";
    $message = "Le stock de {$matiere_nom} est de {$stock_actuel} (seuil minimum : {$stock_minimum}).";
    return db_notification_insert($pdo, 'stock_faible_matiere', $titre, $message);
}

function db_notif_rendement_bas(PDO $pdo, string $production_ref, float $rendement_pct): int {
    $titre = "Rendement bas : {$production_ref}";
    $message = "Le rendement matière de la production {$production_ref} est de {$rendement_pct}%. Seuil recommandé : 85%.";
    return db_notification_insert($pdo, 'rendement_bas', $titre, $message);
}

function db_notif_production(PDO $pdo, string $type_event, string $production_ref, ?string $details = null): int {
    $titre = "Production {$type_event} : {$production_ref}";
    $message = "La production {$production_ref} a été {$type_event}.";
    if ($details) $message .= " {$details}";
    return db_notification_insert($pdo, 'production_' . strtolower(str_replace(' ', '_', $type_event)), $titre, $message);
}

// =====================================================================
// HORAIRES DE TRAVAIL — CRUD
// =====================================================================

function db_horaires_list(PDO $pdo): array {
    return $pdo->query("SELECT * FROM horaires_travail ORDER BY FIELD(jour, 'LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'), heure_debut")->fetchAll();
}

function db_horaires_by_name(PDO $pdo, string $nom): array {
    $st = $pdo->prepare("SELECT * FROM horaires_travail WHERE nom = ? ORDER BY FIELD(jour, 'LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'), heure_debut");
    $st->execute([$nom]);
    return $st->fetchAll();
}

function db_horaire_insert(PDO $pdo, string $nom, string $jour, string $heure_debut, string $heure_fin, int $tolerance = 5): int {
    $st = $pdo->prepare(
        "INSERT INTO horaires_travail (nom, jour, heure_debut, heure_fin, tolerance_retard_minutes)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE heure_debut = VALUES(heure_debut), heure_fin = VALUES(heure_fin), tolerance_retard_minutes = VALUES(tolerance_retard_minutes)"
    );
    $st->execute([$nom, $jour, $heure_debut, $heure_fin, $tolerance]);
    return (int)$pdo->lastInsertId();
}

function db_horaire_delete(PDO $pdo, string $nom, ?string $jour = null): void {
    if ($jour) {
        $pdo->prepare("DELETE FROM horaires_travail WHERE nom = ? AND jour = ?")->execute([$nom, $jour]);
    } else {
        $pdo->prepare("DELETE FROM horaires_travail WHERE nom = ?")->execute([$nom]);
    }
}

function db_horaire_get_tolerance(PDO $pdo, string $nom, string $jour): int {
    $st = $pdo->prepare("SELECT tolerance_retard_minutes FROM horaires_travail WHERE nom = ? AND jour = ?");
    $st->execute([$nom, $jour]);
    $tol = $st->fetchColumn();
    return $tol !== false ? (int)$tol : 5;
}

// =====================================================================
// CATEGORIES PERTES PRODUCTION — CRUD
// =====================================================================

function db_categories_pertes_list(PDO $pdo): array {
    return $pdo->query("SELECT * FROM categories_pertes_production WHERE actif = 1 ORDER BY nom")->fetchAll();
}

function db_categorie_perte_insert(PDO $pdo, string $nom, ?string $description = null): int {
    $st = $pdo->prepare("INSERT INTO categories_pertes_production (nom, description) VALUES (?, ?)");
    $st->execute([$nom, $description]);
    return (int)$pdo->lastInsertId();
}

function db_categorie_perte_update(PDO $pdo, int $id, string $nom, ?string $description = null, bool $actif = true): void {
    $st = $pdo->prepare("UPDATE categories_pertes_production SET nom = ?, description = ?, actif = ? WHERE id = ?");
    $st->execute([$nom, $description, $actif ? 1 : 0, $id]);
}

function db_categorie_perte_delete(PDO $pdo, int $id): void {
    $pdo->prepare("UPDATE categories_pertes_production SET actif = 0 WHERE id = ?")->execute([$id]);
}

// =====================================================================
// RETARD / ABSENCE — Détection automatique
// =====================================================================

function db_calculer_retard(PDO $pdo, int $employe_id, string $date_presence, ?string $heure_arrivee): array {
    if (!$heure_arrivee) {
        return ['statut' => 'ABSENT', 'retard_minutes' => 0, 'heure_prevue' => null, 'tolerance' => 0];
    }

    $jour = strtoupper(date('l', strtotime($date_presence)));
    $jour_map = ['MONDAY' => 'LUNDI', 'TUESDAY' => 'MARDI', 'WEDNESDAY' => 'MERCREDI',
                 'THURSDAY' => 'JEUDI', 'FRIDAY' => 'VENDREDI', 'SATURDAY' => 'SAMEDI', 'SUNDAY' => 'DIMANCHE'];
    $jour_fr = $jour_map[$jour] ?? $jour;

    // Chercher horaire (nom par défaut : 'Usine')
    $st = $pdo->prepare(
        "SELECT heure_debut, tolerance_retard_minutes FROM horaires_travail
         WHERE nom = 'Usine' AND jour = ? AND actif = 1 LIMIT 1"
    );
    $st->execute([$jour_fr]);
    $horaire = $st->fetch();

    if (!$horaire) {
        return ['statut' => 'A_L_HEURE', 'retard_minutes' => 0, 'heure_prevue' => null, 'tolerance' => 0];
    }

    $heure_prevue = $horaire['heure_debut'];
    $tolerance = (int)$horaire['tolerance_retard_minutes'];

    $ts_prevu = strtotime($heure_prevue);
    $ts_reel = strtotime($heure_arrivee);

    if ($ts_reel <= $ts_prevu + ($tolerance * 60)) {
        return ['statut' => 'A_L_HEURE', 'retard_minutes' => 0, 'heure_prevue' => $heure_prevue, 'tolerance' => $tolerance];
    }

    $retard = (int)(($ts_reel - $ts_prevu) / 60);
    return ['statut' => 'EN_RETARD', 'retard_minutes' => $retard, 'heure_prevue' => $heure_prevue, 'tolerance' => $tolerance];
}

function db_presences_avec_retards(PDO $pdo, string $date): array {
    $presences = db_presences_list_date($pdo, $date);
    $result = [];
    foreach ($presences as $p) {
        $retard_info = db_calculer_retard($pdo, $p['employe_id'], $date, $p['heure_arrivee']);
        $p['statut_presence'] = $retard_info['statut'];
        $p['retard_minutes'] = $retard_info['retard_minutes'];
        $p['heure_prevue'] = $retard_info['heure_prevue'];
        $result[] = $p;
    }
    return $result;
}

function db_employes_absents(PDO $pdo, string $date): array {
    $jour = strtoupper(date('l', strtotime($date)));
    $jour_map = ['MONDAY' => 'LUNDI', 'TUESDAY' => 'MARDI', 'WEDNESDAY' => 'MERCREDI',
                 'THURSDAY' => 'JEUDI', 'FRIDAY' => 'VENDREDI', 'SATURDAY' => 'SAMEDI', 'SUNDAY' => 'DIMANCHE'];
    $jour_fr = $jour_map[$jour] ?? $jour;

    $st = $pdo->prepare(
        "SELECT h.heure_debut
         FROM horaires_travail h
         WHERE h.nom = 'Usine' AND h.jour = ? AND h.actif = 1 LIMIT 1"
    );
    $st->execute([$jour_fr]);
    $heure_prevue = $st->fetchColumn();

    if (!$heure_prevue) return [];

    $st2 = $pdo->prepare(
        "SELECT e.* FROM employes e
         WHERE e.actif = 1
           AND e.id NOT IN (SELECT pe.employe_id FROM presences_employes pe WHERE pe.date_presence = ?)
         ORDER BY e.nom"
    );
    $st2->execute([$date]);
    $absents = $st2->fetchAll();

    foreach ($absents as &$abs) {
        $abs['heure_prevue'] = $heure_prevue;
    }
    return $absents;
}

// =====================================================================
// RENDEMENT — Calculs automatiques
// =====================================================================

function db_rendement_production(PDO $pdo, int $production_id): ?array {
    $prod = db_production_get($pdo, $production_id);
    if (!$prod) return null;

    $total_matieres_consommees = 0;
    $total_matieres_prevues = 0;
    $details_matieres = [];

    if (!empty($prod['matieres'])) {
        foreach ($prod['matieres'] as $m) {
            $qte_reelle = (float)$m['quantite_reelle'];
            $qte_prevue = (float)$m['quantite_prevue'];
            $total_matieres_consommees += $qte_reelle;
            $total_matieres_prevues += $qte_prevue;
            $ecart = $qte_reelle - $qte_prevue;
            $pct_ecart = $qte_prevue > 0 ? round(($ecart / $qte_prevue) * 100, 2) : 0;
            $details_matieres[] = [
                'matiere_id' => $m['matiere_id'],
                'matiere_nom' => $m['matiere_nom'],
                'unite' => $m['unite_mesure'],
                'quantite_prevue' => $qte_prevue,
                'quantite_reelle' => $qte_reelle,
                'ecart' => $ecart,
                'pct_ecart' => $pct_ecart,
                'cout_unitaire' => (float)$m['cout_unitaire'],
                'cout_total' => $qte_reelle * (float)$m['cout_unitaire'],
            ];
        }
    }

    $total_pertes = 0;
    if (!empty($prod['pertes'])) {
        foreach ($prod['pertes'] as $p) {
            $total_pertes += (float)$p['quantite'];
        }
    }

    $quantite_produite = (int)$prod['quantite_produite'];
    $quantite_perdue = (int)$prod['quantite_perdue'];
    $total_produit = $quantite_produite + $quantite_perdue;

    // Rendement basé sur les quantités (formule unifiée : conforme / (conforme + pertes))
    $total_cout_matieres = 0;
    foreach ($details_matieres as $dm) {
        $total_cout_matieres += $dm['cout_total'];
    }
    $rendement_pct = $total_produit > 0
        ? round(($quantite_produite / $total_produit) * 100, 2)
        : 0;

    $taux_perte_pct = $total_produit > 0
        ? round(($total_pertes / $total_produit) * 100, 2)
        : 0;

    return [
        'production_id' => $production_id,
        'reference' => $prod['reference'],
        'article_nom' => $prod['article_nom'],
        'total_matieres_consommees' => $total_matieres_consommees,
        'total_matieres_prevues' => $total_matieres_prevues,
        'quantite_produite' => $quantite_produite,
        'quantite_perdue' => $quantite_perdue,
        'total_pertes' => $total_pertes,
        'rendement_pct' => $rendement_pct,
        'taux_perte_pct' => $taux_perte_pct,
        'details_matieres' => $details_matieres,
        'cout_matieres' => (float)$prod['cout_matieres'],
        'cout_unitaire' => (float)$prod['cout_unitaire'],
    ];
}

function db_rendement_par_categorie(PDO $pdo, int $production_id): array {
    $prod = db_production_get($pdo, $production_id);
    if (!$prod || empty($prod['matieres'])) return [];

    $total = 0;
    $par_categorie = [];
    foreach ($prod['matieres'] as $m) {
        $qte = (float)$m['quantite_reelle'];
        $total += $qte;
        $cat_nom = $m['matiere_nom'] ?? 'Autre';
        // Tenter de récupérer la catégorie
        $st = $pdo->prepare(
            "SELECT c.nom FROM matieres_premieres mp
             JOIN categories_matieres_premieres c ON c.id = mp.categorie_id
             WHERE mp.id = ?"
        );
        $st->execute([$m['matiere_id']]);
        $cat = $st->fetchColumn();
        $cat_key = $cat ?: 'Non catégorisé';
        if (!isset($par_categorie[$cat_key])) {
            $par_categorie[$cat_key] = ['quantite' => 0, 'pct' => 0];
        }
        $par_categorie[$cat_key]['quantite'] += $qte;
    }

    foreach ($par_categorie as &$cat) {
        $cat['pct'] = $total > 0 ? round(($cat['quantite'] / $total) * 100, 2) : 0;
    }

    return $par_categorie;
}

function db_rapport_matiere_production(PDO $pdo, string $date_debut, string $date_fin): array {
    $st = $pdo->prepare(
        "SELECT mp.nom AS matiere_nom, mp.unite_mesure, c.nom AS categorie_nom,
                SUM(pm.quantite_reelle) AS total_consomme,
                SUM(pm.cout_total) AS cout_total
         FROM production_matieres pm
         JOIN matieres_premieres mp ON mp.id = pm.matiere_id
         LEFT JOIN categories_matieres_premieres c ON c.id = mp.categorie_id
         JOIN productions p ON p.id = pm.production_id
         WHERE p.date_creation BETWEEN ? AND ? AND p.statut = 'TERMINEE'
         GROUP BY mp.id, mp.nom, mp.unite_mesure, c.nom
         ORDER BY total_consomme DESC"
    );
    $st->execute([$date_debut . ' 00:00:00', $date_fin . ' 23:59:59']);
    $matieres = $st->fetchAll();

    $st2 = $pdo->prepare(
        "SELECT a.nom AS article_nom, SUM(p.quantite_produite) AS total_produit,
                SUM(p.quantite_perdue) AS total_perdu
         FROM productions p
         JOIN articles a ON a.id = p.article_id
         WHERE p.date_creation BETWEEN ? AND ? AND p.statut = 'TERMINEE'
         GROUP BY a.id, a.nom
         ORDER BY total_produit DESC"
    );
    $st2->execute([$date_debut . ' 00:00:00', $date_fin . ' 23:59:59']);
    $produits = $st2->fetchAll();

    $total_matieres = array_sum(array_column($matieres, 'total_consomme'));
    $total_produit = array_sum(array_column($produits, 'total_produit'));
    $total_perdu = array_sum(array_column($produits, 'total_perdu'));
    $total_cout = array_sum(array_column($matieres, 'cout_total'));

    return [
        'periode' => ['debut' => $date_debut, 'fin' => $date_fin],
        'matieres' => $matieres,
        'produits' => $produits,
        'total_matieres' => $total_matieres,
        'total_produit' => $total_produit,
        'total_perdu' => $total_perdu,
        'total_cout_matieres' => $total_cout,
        'rendement_global' => $total_matieres > 0 ? round(($total_produit / $total_matieres) * 100, 2) : 0,
    ];
}

// =====================================================================
// TABLEAU DE BORD DIRECTRICE — Vue enrichie
// =====================================================================

function db_usine_dashboard_enrichi(PDO $pdo): array {
    $base = db_usine_dashboard($pdo);
    $today = date('Y-m-d');
    $month_start = date('Y-m-01');

    // Productions du mois
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS nb, COALESCE(SUM(quantite_produite), 0) AS produits,
                COALESCE(SUM(quantite_perdue), 0) AS pertes
         FROM productions WHERE date_creation BETWEEN ? AND ? AND statut != 'ANNULEE'"
    );
    $st->execute([$month_start . ' 00:00:00', $today . ' 23:59:59']);
    $productions_mois = $st->fetch();

    // Employés en retard aujourd'hui
    $retards = [];
    $presences = db_presences_avec_retards($pdo, $today);
    foreach ($presences as $p) {
        if ($p['statut_presence'] === 'EN_RETARD') {
            $retards[] = $p;
        }
    }

    // Absents aujourd'hui
    $absents = db_employes_absents($pdo, $today);

    // Machines en fonctionnement
    $machines_en_cours = db_machines_en_fonctionnement($pdo);
    $total_machines = (int)$pdo->query("SELECT COUNT(*) FROM machines WHERE actif = 1")->fetchColumn();

    // Matières consommées aujourd'hui
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(pm.quantite_reelle), 0) AS total_kg
         FROM production_matieres pm
         JOIN productions p ON p.id = pm.production_id
         WHERE DATE(p.date_creation) = ? AND p.statut = 'TERMINEE'"
    );
    $st->execute([$today]);
    $matieres_consommees_jour = (float)$st->fetchColumn();

    // Rendement moyen du jour
    $st = $pdo->prepare(
        "SELECT AVG(rendement_pct) AS rendement_moyen
         FROM productions
         WHERE DATE(date_creation) = ? AND statut = 'TERMINEE' AND rendement_pct IS NOT NULL"
    );
    $st->execute([$today]);
    $rendement_moyen_jour = (float)$st->fetchColumn();

    // Notifications non lues
    $role = user_role();
    $user_id = $_SESSION['user']['id'] ?? null;
    $nb_notifs = db_notifications_nb_non_lues($pdo, $role, $user_id);

    return array_merge($base, [
        'productions_mois' => $productions_mois,
        'retards' => $retards,
        'absents' => $absents,
        'machines_en_cours' => $machines_en_cours,
        'total_machines' => $total_machines,
        'matieres_consommees_jour' => $matieres_consommees_jour,
        'rendement_moyen_jour' => $rendement_moyen_jour,
        'nb_notifications' => $nb_notifs,
    ]);
}

// =====================================================================
// PRODUCTION — Mise à jour rendement à la clôture
// =====================================================================

function db_production_update_rendement(PDO $pdo, int $production_id): void {
    $rendement = db_rendement_production($pdo, $production_id);
    if ($rendement) {
        $pdo->prepare("UPDATE productions SET rendement_pct = ? WHERE id = ?")
            ->execute([$rendement['rendement_pct'], $production_id]);
    }
}

// =====================================================================
// ÉQUIPES — Gestion des équipes de travail
// =====================================================================

function db_equipes_list(PDO $pdo, ?string $type = null): array {
    $sql = "SELECT e.*, u.nom AS chef_nom,
                   (SELECT COUNT(*) FROM equipe_membres ue WHERE ue.equipe_id = e.id AND ue.actif = 1) AS nb_membres
            FROM equipes e
            LEFT JOIN utilisateurs u ON u.id = e.chef_equipe_id
            WHERE e.actif = 1";
    $params = [];
    if ($type) {
        $sql .= " AND e.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY e.nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_equipe_get(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT e.*, u.nom AS chef_nom
         FROM equipes e
         LEFT JOIN utilisateurs u ON u.id = e.chef_equipe_id
         WHERE e.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function db_equipe_insert(PDO $pdo, array $d): int {
    $stmt = $pdo->prepare(
        "INSERT INTO equipes (nom, description, type, chef_equipe_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([
        $d['nom'], $d['description'] ?? null, $d['type'] ?? 'BOUTIQUE',
        $d['chef_equipe_id'] ?? null
    ]);
    return (int)$pdo->lastInsertId();
}

function db_equipe_update(PDO $pdo, int $id, array $d): void {
    $stmt = $pdo->prepare(
        "UPDATE equipes SET nom = ?, description = ?, type = ?, chef_equipe_id = ?,
                date_modification = NOW()
         WHERE id = ?"
    );
    $stmt->execute([
        $d['nom'], $d['description'] ?? null, $d['type'] ?? 'BOUTIQUE',
        $d['chef_equipe_id'] ?? null, $id
    ]);
}

function db_equipe_delete(PDO $pdo, int $id): void {
    $pdo->prepare("UPDATE equipes SET actif = 0, date_modification = NOW() WHERE id = ?")
        ->execute([$id]);
}

function db_equipe_set_membres(PDO $pdo, int $equipe_id, array $user_ids): void {
    $pdo->prepare("UPDATE equipe_membres SET actif = 0, date_fin = NOW() WHERE equipe_id = ? AND actif = 1")->execute([$equipe_id]);
    $stmt = $pdo->prepare("INSERT INTO equipe_membres (equipe_id, user_id, date_debut, actif) VALUES (?, ?, NOW(), 1)
        ON DUPLICATE KEY UPDATE actif = 1, date_debut = NOW(), date_fin = NULL");
    foreach ($user_ids as $uid) {
        $stmt->execute([$equipe_id, (int)$uid]);
    }
}

function db_equipe_membres(PDO $pdo, int $equipe_id): array {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.nom, u.login, r.code AS role_code, r.nom AS role_nom
         FROM equipe_membres ue
         JOIN utilisateurs u ON u.id = ue.user_id
         LEFT JOIN user_roles ur ON ur.user_id = u.id
         LEFT JOIN roles r ON r.id = ur.role_id
         WHERE ue.equipe_id = ? AND ue.actif = 1
         ORDER BY u.nom"
    );
    $stmt->execute([$equipe_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_equipe_set_magasins(PDO $pdo, int $equipe_id, array $magasin_ids): void {
    $pdo->prepare("DELETE FROM equipe_magasins WHERE equipe_id = ?")->execute([$equipe_id]);
    $stmt = $pdo->prepare("INSERT INTO equipe_magasins (equipe_id, magasin_id) VALUES (?, ?)");
    foreach ($magasin_ids as $mid) {
        $stmt->execute([$equipe_id, (int)$mid]);
    }
}

function db_equipe_magasins(PDO $pdo, int $equipe_id): array {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nom
         FROM equipe_magasins em
         JOIN magasins m ON m.id = em.magasin_id
         WHERE em.equipe_id = ?
         ORDER BY m.nom"
    );
    $stmt->execute([$equipe_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_user_equipes(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare(
        "SELECT e.id, e.nom, e.type
         FROM equipe_membres ue
         JOIN equipes e ON e.id = ue.equipe_id
         WHERE ue.user_id = ? AND ue.actif = 1
         ORDER BY e.nom"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
