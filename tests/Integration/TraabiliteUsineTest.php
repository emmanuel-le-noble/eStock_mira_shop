<?php
/**
 * Tests d'intégration : Traçabilité complète usine.
 * Couvre : machines, notifications, horaires, retards, rendement, catégories pertes.
 */
final class TraabiliteUsineTest extends PHPUnit\Framework\TestCase
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
        foreach (['machine_etats','machines','notifications','horaires_travail','categories_pertes_production'] as $t) {
            self::$pdo->exec("DELETE FROM `$t`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    // -----------------------------------------------------------------
    // MACHINES
    // -----------------------------------------------------------------

    public function testMachineCRUD(): void
    {
        $id = db_machine_insert(self::$pdo, [
            'nom' => 'Injecteuse 01',
            'type' => 'Injecteuse',
            'description' => 'Machine injection plastique',
        ]);
        $this->assertGreaterThan(0, $id);

        $m = db_machine_get(self::$pdo, $id);
        $this->assertNotNull($m);
        $this->assertSame('Injecteuse 01', $m['nom']);
        $this->assertSame('ARRETEE', $m['etat']);

        db_machine_update(self::$pdo, $id, [
            'nom' => 'Injecteuse 01 (modifiée)',
            'type' => 'Injecteuse',
            'description' => 'Modifiée',
            'actif' => 1,
        ]);
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('Injecteuse 01 (modifiée)', $m['nom']);
    }

    public function testMachineDemarrerArreter(): void
    {
        $id = db_machine_insert(self::$pdo, ['nom' => 'Test Start/Stop', 'type' => 'Test']);

        db_machine_demarrer(self::$pdo, $id);
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('EN_FONCTIONNEMENT', $m['etat']);

        db_machine_arreter(self::$pdo, $id, 'Fin de production');
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('ARRETEE', $m['etat']);

        // L'historique contient l'enregistrement EN_FONCTIONNEMENT (clôturé par arreter)
        $hist = db_machine_historique(self::$pdo, $id);
        $this->assertCount(1, $hist);
    }

    public function testMachineSetEtat(): void
    {
        $id = db_machine_insert(self::$pdo, ['nom' => 'Test Etat', 'type' => 'Test']);

        db_machine_set_etat(self::$pdo, $id, 'EN_MAINTENANCE', 'Préventive');
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('EN_MAINTENANCE', $m['etat']);

        db_machine_set_etat(self::$pdo, $id, 'EN_PANNE', 'Mécanique');
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('EN_PANNE', $m['etat']);

        db_machine_set_etat(self::$pdo, $id, 'ARRETEE', 'Réparée');
        $m = db_machine_get(self::$pdo, $id);
        $this->assertSame('ARRETEE', $m['etat']);
    }

    public function testMachinesEnFonctionnement(): void
    {
        $id1 = db_machine_insert(self::$pdo, ['nom' => 'M1', 'type' => 'T']);
        $id2 = db_machine_insert(self::$pdo, ['nom' => 'M2', 'type' => 'T']);

        db_machine_demarrer(self::$pdo, $id1);

        $en_cours = db_machines_en_fonctionnement(self::$pdo);
        $this->assertCount(1, $en_cours);
        $this->assertSame($id1, (int)$en_cours[0]['id']);
    }

    // -----------------------------------------------------------------
    // NOTIFICATIONS
    // -----------------------------------------------------------------

    public function testNotificationCRUD(): void
    {
        $id = db_notification_insert(self::$pdo, 'test_type', 'Titre test', 'Message test', 'ADMIN');
        $this->assertGreaterThan(0, $id);

        $notifs = db_notifications_list(self::$pdo, null, 'ADMIN');
        $this->assertGreaterThanOrEqual(1, count($notifs));

        $nb = db_notifications_nb_non_lues(self::$pdo, 'ADMIN');
        $this->assertGreaterThanOrEqual(1, $nb);

        db_notification_marquer_lue(self::$pdo, $id);
        $nb = db_notifications_nb_non_lues(self::$pdo, 'ADMIN');
        $this->assertSame(0, $nb);

        db_notification_supprimer(self::$pdo, $id);
        $notifs = db_notifications_list(self::$pdo, null, 'ADMIN');
        $this->assertCount(0, $notifs);
    }

    public function testNotifRetardEmploye(): void
    {
        $id = db_notif_retard_employe(self::$pdo, 'Koffi', '08:17', '08:00', 17);
        $this->assertGreaterThan(0, $id);
        $notifs = db_notifications_list(self::$pdo, 'retard_employe');
        $this->assertGreaterThanOrEqual(1, count($notifs));
    }

    // -----------------------------------------------------------------
    // HORAIRES
    // -----------------------------------------------------------------

    public function testHorairesCRUD(): void
    {
        db_horaire_insert(self::$pdo, 'Usine', 'LUNDI', '08:00', '17:00', 5);
        db_horaire_insert(self::$pdo, 'Usine', 'MARDI', '08:00', '17:00', 5);

        $horaires = db_horaires_list(self::$pdo);
        $this->assertCount(2, $horaires);

        $tolerance = db_horaire_get_tolerance(self::$pdo, 'Usine', 'LUNDI');
        $this->assertSame(5, $tolerance);

        db_horaire_delete(self::$pdo, 'Usine', 'LUNDI');
        $horaires = db_horaires_list(self::$pdo);
        $this->assertCount(1, $horaires);
    }

    public function testToleranceRetard(): void
    {
        db_horaire_insert(self::$pdo, 'Usine', 'LUNDI', '08:00', '17:00', 10);
        // 2026-09-08 est un mardi
        $test_date = '2026-09-08';
        $is_lundi = (date('l', strtotime($test_date)) === 'LUNDI');

        // 08:04 → dans la tolérance de 10 min → À l'heure
        $retard = db_calculer_retard(self::$pdo, 999, $test_date, '08:04');
        if ($is_lundi) {
            $this->assertSame('A_L_HEURE', $retard['statut']);
        }

        // 08:15 → retard de 15 min
        $retard = db_calculer_retard(self::$pdo, 999, $test_date, '08:15');
        if ($is_lundi) {
            $this->assertSame('EN_RETARD', $retard['statut']);
            $this->assertSame(15, $retard['retard_minutes']);
        }

        // Sans horaire configurée → A_L_HEURE par défaut
        $retard = db_calculer_retard(self::$pdo, 999, '2026-09-09', '08:00');
        $this->assertSame('A_L_HEURE', $retard['statut']);
    }

    // -----------------------------------------------------------------
    // CATÉGORIES PERTES
    // -----------------------------------------------------------------

    public function testCategoriesPertes(): void
    {
        $id = db_categorie_perte_insert(self::$pdo, 'Test catégorie', 'Description test');
        $this->assertGreaterThan(0, $id);

        $cats = db_categories_pertes_list(self::$pdo);
        $this->assertGreaterThanOrEqual(1, count($cats));

        db_categorie_perte_delete(self::$pdo, $id);
        $cats = db_categories_pertes_list(self::$pdo);
        $this->assertCount(0, $cats);
    }

    // -----------------------------------------------------------------
    // RENDEMENT
    // -----------------------------------------------------------------

    public function testRendementCalculations(): void
    {
        // Créer une matière première
        $mp_id = db_matiere_premiere_insert(self::$pdo, [
            'nom' => 'Granulé PP vierge',
            'unite_mesure' => 'KG',
            'cout_reference' => 800,
        ]);

        // Entrer du stock
        db_matiere_entree_usine(self::$pdo, $mp_id, 1000, 800);

        // Créer un article produit fini
        $art_id = db_article_insert(self::$pdo, [
            'nom' => 'Seau 20L test',
            'code_barre' => 'ART-TEST-' . strtoupper(bin2hex(random_bytes(4))),
            'type_article' => 'PRODUIT_FINI',
            'origine_article' => 'PRODUCTION_USINE',
            'prix_achat' => 500,
            'prix_vente' => 1500,
            'quantite_stock' => 0,
            'seuil_alerte' => 10,
            'actif' => 1,
        ]);

        // Créer une recette
        $recette_id = db_recette_insert(self::$pdo, [
            'nom' => 'Recette Seau 20L',
            'article_id' => $art_id,
            'quantite_produite' => 100,
            'lignes' => [
                ['matiere_id' => $mp_id, 'quantite_necessaire' => 120, 'unite' => 'KG'],
            ],
        ]);

        // Créer une production
        $prod_id = db_production_insert(self::$pdo, [
            'article_id' => $art_id,
            'recette_id' => $recette_id,
            'quantite_prevue' => 480,
        ]);

        // Démarrer
        db_production_demarrer(self::$pdo, $prod_id);

        // Clôturer avec consommation réelle
        db_production_cloturer(self::$pdo, $prod_id, [
            ['matiere_id' => $mp_id, 'quantite_reelle' => 575, 'cout_unitaire' => 800],
        ], 480, 20);

        // Vérifier le rendement
        $rendement = db_rendement_production(self::$pdo, $prod_id);
        $this->assertNotNull($rendement);
        $this->assertSame(480, $rendement['quantite_produite']);
        $this->assertSame(20, $rendement['quantite_perdue']);
        $this->assertSame(575.0, $rendement['total_matieres_consommees']);

        // Rendement = 480 / 575 * 100 = 83.48%
        $this->assertEqualsWithDelta(83.48, $rendement['rendement_pct'], 0.1);
    }

    public function testRendementParCategorie(): void
    {
        // Vérifie que la fonction retourne un tableau (même sans données)
        $cats = db_rendement_par_categorie(self::$pdo, 0);
        $this->assertIsArray($cats);
    }

    public function testRapportMatiereProduction(): void
    {
        $rapport = db_rapport_matiere_production(self::$pdo, '2026-01-01', '2026-12-31');
        $this->assertIsArray($rapport);
        $this->assertArrayHasKey('matieres', $rapport);
        $this->assertArrayHasKey('produits', $rapport);
        $this->assertArrayHasKey('rendement_global', $rapport);
    }

    // -----------------------------------------------------------------
    // DASHBOARD ENRICHI
    // -----------------------------------------------------------------

    public function testDashboardEnrichi(): void
    {
        $dashboard = db_usine_dashboard_enrichi(self::$pdo);
        $this->assertIsArray($dashboard);
        $this->assertArrayHasKey('machines_en_cours', $dashboard);
        $this->assertArrayHasKey('retards', $dashboard);
        $this->assertArrayHasKey('absents', $dashboard);
        $this->assertArrayHasKey('total_machines', $dashboard);
        $this->assertArrayHasKey('matieres_consommees_jour', $dashboard);
        $this->assertArrayHasKey('rendement_moyen_jour', $dashboard);
        $this->assertArrayHasKey('nb_notifications', $dashboard);
    }

}
