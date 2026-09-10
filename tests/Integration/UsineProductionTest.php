<?php
/**
 * Tests d'intégration — Module Usine de Production (architecture indépendante).
 *
 * Couvre :
 *   * Matières premières CRUD (table `matieres_premieres`)
 *   * Catégories MP CRUD
 *   * Recettes CRUD (liaison matieres_premieres ↔ articles)
 *   * Création, démarrage, clôture de production
 *   * Consommation matières (stock_mp) + produits finis (stock_produits_finis_usine)
 *   * Calcul des coûts de production
 *   * Transfert usine → magasin
 *   * Personnel et présences
 *
 * Note : Les tables `production_lots`, `production_employes`, `production_produits`,
 *        `presences_employes_audit`, `mouvements_produits_finis` et
 *        `mouvements_matieres_premieres` ont été supprimées lors du nettoyage
 *        ciblé du schéma (migration 2026-09-10 — Option B).
 */

final class UsineProductionTest extends PHPUnit\Framework\TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        test_db_schema();
        self::$pdo = test_db();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        self::$pdo->exec('DELETE FROM production_pertes');
        self::$pdo->exec('DELETE FROM production_matieres');
        self::$pdo->exec('DELETE FROM productions');
        self::$pdo->exec('DELETE FROM presences_employes');
        self::$pdo->exec('DELETE FROM employes');
        self::$pdo->exec('DELETE FROM recettes_lignes');
        self::$pdo->exec('DELETE FROM recettes');
        self::$pdo->exec('DELETE FROM stock_matieres_premieres');
        self::$pdo->exec('DELETE FROM stock_produits_finis_usine');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function creerMatierePremiere(string $nom, string $ref, float $cout = 800): int {
        $id = db_matiere_premiere_insert(self::$pdo, [
            'nom' => $nom,
            'reference' => $ref,
            'unite_mesure' => 'KG',
            'cout_reference' => $cout,
            'stock_minimum' => 10,
        ]);
        self::assertGreaterThan(0, $id);
        return $id;
    }

    private function creerProduitFini(string $nom, string $code): int {
        $id = db_article_insert(self::$pdo, [
            'nom' => $nom,
            'code_barre' => $code,
            'type_article' => 'PRODUIT_FINI',
            'origine_article' => 'PRODUCTION_USINE',
            'prix_achat' => 0,
            'prix_vente' => 1500,
            'quantite_stock' => 0,
            'seuil_alerte' => 5,
        ]);
        self::assertGreaterThan(0, $id);
        return $id;
    }

    private function alimenterStockMP(int $matiereId, float $quantite, float $cout): void {
        db_matiere_entree_usine(self::$pdo, $matiereId, $quantite, $cout, 'Alimentation initiale');
    }

    // ============================================================
    //  CATÉGORIES MATIÈRES PREMIÈRES
    // ============================================================

    public function testCategorieMpCRUD(): void
    {
        $id = db_categorie_mp_insert(self::$pdo, 'Granulés', 'Granulés plastiques');
        $this->assertGreaterThan(0, $id);

        $cats = db_categories_mp_list(self::$pdo);
        $this->assertGreaterThanOrEqual(1, count($cats));

        db_categorie_mp_update(self::$pdo, $id, 'Granulés modifiés', 'Desc');
        $updated = self::$pdo->query("SELECT nom FROM categories_matieres_premieres WHERE id = $id")->fetch();
        $this->assertEquals('Granulés modifiés', $updated['nom']);

        db_categorie_mp_delete(self::$pdo, $id);
        $deleted = self::$pdo->query("SELECT COUNT(*) FROM categories_matieres_premieres WHERE id = $id")->fetchColumn();
        $this->assertEquals(0, (int)$deleted);
    }

    // ============================================================
    //  MATIÈRES PREMIÈRES
    // ============================================================

    public function testMatierePremiereInsert(): void
    {
        $id = $this->creerMatierePremiere('Granulés PP', 'MAT-001');
        $mat = db_matiere_premiere_get(self::$pdo, $id);
        $this->assertNotNull($mat);
        $this->assertEquals('Granulés PP', $mat['nom']);
        $this->assertEquals('MAT-001', $mat['reference']);
    }

    public function testMatierePremiereList(): void
    {
        $this->creerMatierePremiere('Granulés PEHD', 'MAT-002');
        $this->creerMatierePremiere('Colorant bleu', 'MAT-003');
        $list = db_matiere_premiere_list(self::$pdo);
        $this->assertGreaterThanOrEqual(2, count($list));
    }

    public function testMatierePremiereUpdate(): void
    {
        $id = $this->creerMatierePremiere('Additif', 'MAT-004');
        db_matiere_premiere_update(self::$pdo, $id, [
            'nom' => 'Additif spécial',
            'unite_mesure' => 'KG',
            'cout_reference' => 1500,
            'stock_minimum' => 5,
            'actif' => 1,
        ]);
        $mat = db_matiere_premiere_get(self::$pdo, $id);
        $this->assertEquals('Additif spécial', $mat['nom']);
        $this->assertEquals(1500, (float)$mat['cout_reference']);
    }

    // ============================================================
    //  STOCK MATIÈRES PREMIÈRES
    // ============================================================

    public function testAlimenterStockMP(): void
    {
        $mp_id = $this->creerMatierePremiere('Granulés PP Stock', 'MAT-010');
        $this->alimenterStockMP($mp_id, 1000, 800);

        $stock = db_stock_mp_matiere(self::$pdo, $mp_id);
        $this->assertNotNull($stock);
        $this->assertEquals(1000, (float)$stock['quantite']);
    }

    public function testStockMPInsuffisant(): void
    {
        $mp_id = $this->creerMatierePremiere('Matière Test Insuf', 'MAT-011');
        $this->alimenterStockMP($mp_id, 100, 500);

        $this->assertFalse(db_stock_mp_suffisant(self::$pdo, $mp_id, 200));
        $this->assertTrue(db_stock_mp_suffisant(self::$pdo, $mp_id, 100));
    }

    public function testStockMPList(): void
    {
        $this->creerMatierePremiere('Granulés List', 'MAT-012');
        $list = db_stock_mp_list(self::$pdo);
        $this->assertGreaterThanOrEqual(0, count($list));
    }

    // ============================================================
    //  RECETTES
    // ============================================================

    public function testRecetteInsert(): void
    {
        $mp_id = $this->creerMatierePremiere('Granulés Recette', 'MAT-020');
        $pf_id = $this->creerProduitFini('Seau 20L Test', 'PF-020');

        $recette_id = db_recette_insert(self::$pdo, [
            'nom' => 'Seau 20L — V1',
            'article_id' => $pf_id,
            'quantite_produite' => 100,
            'lignes' => [
                ['matiere_id' => $mp_id, 'quantite_necessaire' => 80, 'unite' => 'KG'],
            ],
        ]);
        $this->assertGreaterThan(0, $recette_id);

        $recette = db_recette_get(self::$pdo, $recette_id);
        $this->assertNotNull($recette);
        $this->assertEquals('Seau 20L — V1', $recette['nom']);
        $this->assertCount(1, $recette['lignes']);
        $this->assertEquals(80, (float)$recette['lignes'][0]['quantite_necessaire']);
    }

    public function testRecetteVersionnement(): void
    {
        $mp_id = $this->creerMatierePremiere('Granulés Version', 'MAT-021');
        $pf_id = $this->creerProduitFini('Bassine Test', 'PF-021');

        $v1 = db_recette_insert(self::$pdo, [
            'nom' => 'Bassine — V1', 'article_id' => $pf_id, 'quantite_produite' => 100,
            'lignes' => [['matiere_id' => $mp_id, 'quantite_necessaire' => 50, 'unite' => 'KG']],
        ]);
        $v2 = db_recette_insert(self::$pdo, [
            'nom' => 'Bassine — V2', 'article_id' => $pf_id, 'quantite_produite' => 100,
            'lignes' => [['matiere_id' => $mp_id, 'quantite_necessaire' => 45, 'unite' => 'KG']],
        ]);

        $r1 = db_recette_get(self::$pdo, $v1);
        $r2 = db_recette_get(self::$pdo, $v2);
        $this->assertEquals(1, (int)$r1['version']);
        $this->assertEquals(2, (int)$r2['version']);
        $this->assertEquals(50, (float)$r1['lignes'][0]['quantite_necessaire']);
        $this->assertEquals(45, (float)$r2['lignes'][0]['quantite_necessaire']);
    }

    // ============================================================
    //  PRODUCTIONS — Cycle complet
    // ============================================================

    public function testProductionCompleteScenario(): void
    {
        // Créer matières et alimenter stock
        $mp_granules = $this->creerMatierePremiere('Granulés SC', 'MAT-030', 800);
        $mp_colorant = $this->creerMatierePremiere('Colorant B', 'MAT-031', 2000);
        $this->alimenterStockMP($mp_granules, 1000, 800);
        $this->alimenterStockMP($mp_colorant, 50, 2000);

        // Créer produit fini
        $pf_id = $this->creerProduitFini('Seau SC 20L', 'PF-030');

        // Créer recette (100 seaux = 80 kg granulés + 2 kg colorant)
        $recette_id = db_recette_insert(self::$pdo, [
            'nom' => 'Seau SC 20L', 'article_id' => $pf_id, 'quantite_produite' => 100,
            'lignes' => [
                ['matiere_id' => $mp_granules, 'quantite_necessaire' => 80, 'unite' => 'KG'],
                ['matiere_id' => $mp_colorant, 'quantite_necessaire' => 2, 'unite' => 'KG'],
            ],
        ]);

        // Créer production (prévue: 500 seaux)
        $prod_id = db_production_insert(self::$pdo, [
            'article_id' => $pf_id,
            'recette_id' => $recette_id,
            'quantite_prevue' => 500,
        ]);
        $this->assertGreaterThan(0, $prod_id);

        // Vérifier que les matières préremplies existent
        $prod = db_production_get(self::$pdo, $prod_id);
        $this->assertCount(2, $prod['matieres']);
        // 500/100 * 80 = 400 kg granulés prévus
        $this->assertEquals(400, (float)$prod['matieres'][0]['quantite_prevue']);

        // Démarrer
        db_production_demarrer(self::$pdo, $prod_id);
        $prod = db_production_get(self::$pdo, $prod_id);
        $this->assertEquals('EN_COURS', $prod['statut']);
        $this->assertNotNull($prod['date_debut']);

        // Clôturer avec consommation réelle
        db_production_cloturer(self::$pdo, $prod_id, [
            ['matiere_id' => $mp_granules, 'quantite_reelle' => 410, 'cout_unitaire' => 800],
            ['matiere_id' => $mp_colorant, 'quantite_reelle' => 11, 'cout_unitaire' => 2000],
        ], 480, 20, [
            ['type_perte' => 'produit_non_conforme', 'article_id' => $pf_id, 'quantite' => 20, 'motif' => 'Défaut moulage'],
        ]);

        // Vérifications
        $prod = db_production_get(self::$pdo, $prod_id);
        $this->assertEquals('TERMINEE', $prod['statut']);
        $this->assertEquals(480, (int)$prod['quantite_produite']);
        $this->assertEquals(20, (int)$prod['quantite_perdue']);

        // Coût: 410 * 800 + 11 * 2000 = 328000 + 22000 = 350000
        $this->assertEquals(350000, (float)$prod['cout_matieres']);
        // Coût unitaire: 350000 / 480 ≈ 729.17
        $this->assertEqualsWithDelta(729.17, (float)$prod['cout_unitaire'], 0.1);

        // Stock matières diminué
        $stock_granules = db_stock_mp_matiere(self::$pdo, $mp_granules);
        $this->assertEquals(590, (float)$stock_granules['quantite']); // 1000 - 410

        $stock_colorant = db_stock_mp_matiere(self::$pdo, $mp_colorant);
        $this->assertEquals(39, (float)$stock_colorant['quantite']); // 50 - 11

        // Stock produit fini augmenté
        $stock_pf = db_stock_pf_usine_article(self::$pdo, $pf_id);
        $this->assertNotNull($stock_pf);
        $this->assertEquals(480, (int)$stock_pf['quantite']);

        // Les lots de production (production_lots) ont été supprimés — table orpheline nettoyée.
        // La gestion des lots commerciaux est assurée par article_lots.
        $this->assertIsArray($prod['lots']);
        $this->assertEmpty($prod['lots']);

        // Pertes
        $this->assertCount(1, $prod['pertes']);
        $this->assertEquals('produit_non_conforme', $prod['pertes'][0]['type_perte']);
    }

    // ============================================================
    //  PRODUCTIONS — Validation
    // ============================================================

    public function testProductionValidationStockInsuffisant(): void
    {
        $mp_id = $this->creerMatierePremiere('Matière Insuf Test', 'MAT-040', 500);
        $this->alimenterStockMP($mp_id, 50, 500);

        $pf_id = $this->creerProduitFini('Produit Insuf', 'PF-040');
        $recette_id = db_recette_insert(self::$pdo, [
            'nom' => 'Recette Insuf', 'article_id' => $pf_id, 'quantite_produite' => 10,
            'lignes' => [['matiere_id' => $mp_id, 'quantite_necessaire' => 5, 'unite' => 'KG']],
        ]);

        $prod_id = db_production_insert(self::$pdo, [
            'article_id' => $pf_id, 'recette_id' => $recette_id,
            'quantite_prevue' => 100,
        ]);

        db_production_demarrer(self::$pdo, $prod_id);

        $this->expectException(RuntimeException::class);
        db_production_cloturer(self::$pdo, $prod_id, [
            ['matiere_id' => $mp_id, 'quantite_reelle' => 100, 'cout_unitaire' => 500],
        ], 100, 0);
    }

    public function testAnnulationProduction(): void
    {
        $mp_id = $this->creerMatierePremiere('Matière Annul', 'MAT-041', 300);
        $pf_id = $this->creerProduitFini('Produit Annul', 'PF-041');
        $recette_id = db_recette_insert(self::$pdo, [
            'nom' => 'Recette Annul', 'article_id' => $pf_id, 'quantite_produite' => 10,
            'lignes' => [['matiere_id' => $mp_id, 'quantite_necessaire' => 5, 'unite' => 'KG']],
        ]);

        $prod_id = db_production_insert(self::$pdo, [
            'article_id' => $pf_id, 'recette_id' => $recette_id,
            'quantite_prevue' => 50,
        ]);

        db_production_annuler(self::$pdo, $prod_id);
        $prod = db_production_get(self::$pdo, $prod_id);
        $this->assertEquals('ANNULEE', $prod['statut']);
    }

    // ============================================================
    //  TRANSFERT USINE → MAGASIN
    // ============================================================

    public function testTransfertUsineVersMagasin(): void
    {
        $pf_id = $this->creerProduitFini('Produit Transfert', 'PF-050');

        // Alimenter stock produit fini en usine
        self::$pdo->prepare(
            "INSERT INTO stock_produits_finis_usine (article_id, quantite) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE quantite = quantite + ?"
        )->execute([$pf_id, 300, 300]);

        // Créer un magasin de destination
        $mag_dest = db_magasin_insert(self::$pdo, 'Magasin Test Transfert', null);

        // Transfert 200 unités
        db_transfert_usine_vers_magasin(self::$pdo, $pf_id, $mag_dest, 200, 'Transfert test');

        // Vérifier stock usine diminué
        $stock_usine = db_stock_pf_usine_article(self::$pdo, $pf_id);
        $this->assertEquals(100, (int)$stock_usine['quantite']);

        // Vérifier stock magasin augmenté
        $stock_mag = db_stock_magasin_get(self::$pdo, $mag_dest, $pf_id);
        $this->assertNotNull($stock_mag);
        $this->assertEquals(200, (int)$stock_mag['quantite']);
    }

    // ============================================================
    //  EMPLOYÉS & PRÉSENCES
    // ============================================================

    public function testEmployeCRUD(): void
    {
        $id = db_employe_insert(self::$pdo, [
            'matricule' => 'EMP-001', 'nom' => 'Kouassi', 'prenom' => 'Jean',
            'fonction' => 'Opérateur', 'telephone' => '90000000',
        ]);
        $this->assertGreaterThan(0, $id);

        $emp = db_employe_get(self::$pdo, $id);
        $this->assertEquals('Kouassi', $emp['nom']);
        $this->assertEquals('Opérateur', $emp['fonction']);

        db_employe_update(self::$pdo, $id, [
            'matricule' => 'EMP-001', 'nom' => 'Kouassi', 'prenom' => 'Jean',
            'fonction' => 'Chef d\'équipe', 'telephone' => '90000001', 'actif' => 1,
        ]);
        $emp = db_employe_get(self::$pdo, $id);
        $this->assertEquals("Chef d'équipe", $emp['fonction']);
    }

    public function testPresenceUpsert(): void
    {
        $emp_id = db_employe_insert(self::$pdo, [
            'matricule' => 'EMP-010', 'nom' => 'Aka', 'prenom' => 'Paul',
            'fonction' => 'Manœuvre',
        ]);

        $date = date('Y-m-d');
        $presence_id = db_presence_upsert(self::$pdo, $emp_id, $date, '07:30', '17:00', 'Présent');
        $this->assertGreaterThan(0, $presence_id);

        $presences = db_presences_list_date(self::$pdo, $date);
        $found = false;
        foreach ($presences as $p) {
            if ((int)$p['employe_id'] === $emp_id) {
                $found = true;
                $this->assertEquals('07:30', substr($p['heure_arrivee'], 0, 5));
                $this->assertEquals('17:00', substr($p['heure_depart'], 0, 5));
                $this->assertEquals(570, (int)$p['temps_travaille_minutes']);
            }
        }
        $this->assertTrue($found, 'Présence trouvée dans la liste');
    }

    public function testPresenceModification(): void
    {
        $emp_id = db_employe_insert(self::$pdo, [
            'matricule' => 'EMP-011', 'nom' => 'Bony', 'prenom' => 'Koffi',
            'fonction' => 'Opérateur',
        ]);

        $date = date('Y-m-d');
        db_presence_upsert(self::$pdo, $emp_id, $date, '07:30', '17:00');
        db_presence_upsert(self::$pdo, $emp_id, $date, '07:45', '17:15', 'Retard');

        $p = self::$pdo->prepare("SELECT * FROM presences_employes WHERE employe_id = ? AND date_presence = ?");
        $p->execute([$emp_id, $date]);
        $row = $p->fetch();
        $this->assertEquals('07:45:00', $row['heure_arrivee']);
        $this->assertEquals('Retard', $row['commentaire']);
    }

    // ============================================================
    //  DASHBOARD USINE
    // ============================================================

    public function testDashboardUsine(): void
    {
        $dashboard = db_usine_dashboard(self::$pdo);
        $this->assertIsArray($dashboard);
        $this->assertArrayHasKey('productions_jour', $dashboard);
        $this->assertArrayHasKey('matieres_alerte', $dashboard);
        $this->assertArrayHasKey('employes_presents', $dashboard);
        $this->assertArrayHasKey('produits_finis', $dashboard);
        $this->assertArrayHasKey('matieres_stock', $dashboard);
    }
}
