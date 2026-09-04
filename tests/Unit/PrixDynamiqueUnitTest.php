<?php
/**
 * Tests unitaires — Prix dynamiques et tranches tarifaires.
 *
 * Vérifie :
 *   * le calcul de prix selon la quantité (majoration %, marge %, prix fixe)
 *   * la création et mise à jour de l'historique prix fournisseur
 *   * la récupération du prix fournisseur actuel
 *   * la propagation du changement de prix aux futures ventes
 */

final class PrixDynamiqueUnitTest extends PHPUnit\Framework\TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        test_db_schema();
        self::$pdo = test_db();
    }

    protected function setUp(): void
    {
        self::$pdo->exec("DELETE FROM tranches_tarifaires");
        self::$pdo->exec("DELETE FROM fournisseur_prix_historique");
        self::$pdo->exec("DELETE FROM articles WHERE nom LIKE 'Test Prix Dynamique%'");
        self::$pdo->exec("DELETE FROM fournisseurs WHERE nom LIKE 'Fournisseur Test%'");
    }

    private function creerFournisseur(string $nom = 'Fournisseur Test'): int {
        self::$pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)")->execute([$nom]);
        return (int)self::$pdo->lastInsertId();
    }

    private function creerArticle(float $prixAchat, int $fournisseurId): int {
        $id = db_article_insert(self::$pdo, [
            'nom'            => 'Test Prix Dynamique ' . random_int(1000, 9999),
            'code_barre'     => (string)random_int(1000000000000, 9999999999999),
            'prix_achat'     => $prixAchat,
            'prix_vente'     => $prixAchat * 1.4,
            'quantite_stock' => 100,
            'seuil_alerte'   => 5,
            'fournisseur_id' => $fournisseurId,
        ]);
        self::assertGreaterThan(0, $id);
        return $id;
    }

    // ============================================================
    //  HISTORIQUE PRIX FOURNISSEUR
    // ============================================================

    public function testSetPrixFournisseurCreeHistorique(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);

        $id = db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1200.0, 'manuelle');
        self::assertGreaterThan(0, $id);

        $actuel = db_fournisseur_prix_actuel(self::$pdo, $aid, $fid);
        self::assertNotNull($actuel);
        self::assertSame(1200.0, (float)$actuel['prix_achat']);
        self::assertSame(1, (int)$actuel['est_actif']);
    }

    public function testChangementPrixDesactiveAncien(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);

        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1000.0, 'manuelle');
        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1500.0, 'manuelle');

        $actuel = db_fournisseur_prix_actuel(self::$pdo, $aid, $fid);
        self::assertSame(1500.0, (float)$actuel['prix_achat']);

        $historique = db_fournisseur_prix_historique(self::$pdo, $aid);
        self::assertCount(2, $historique);
        // L'ancien prix n'est plus actif
        $ancien = array_filter($historique, fn($h) => (float)$h['prix_achat'] === 1000.0);
        $ancien = reset($ancien);
        self::assertSame(0, (int)$ancien['est_actif']);
    }

    public function testHistoriqueConserveTousLesPrixCroissants(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);

        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1000.0, 'manuelle');
        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1200.0, 'manuelle');
        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1350.0, 'manuelle');
        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1500.0, 'manuelle');

        $historique = db_fournisseur_prix_historique(self::$pdo, $aid);
        self::assertCount(4, $historique);
        self::assertSame(1500.0, (float)$historique[0]['prix_achat']); // Plus récent en premier
        self::assertSame(1, (int)$historique[0]['est_actif']);
    }

    // ============================================================
    //  PRIX SELON QUANTITÉ (TRANCHES TARIFAIRES)
    // ============================================================

    public function testTrancheMajorationPourcentage(): void
    {
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['Test 1-9', 1, 9, 'majoration_pct', 40.0]);

        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 5);
        self::assertSame(1400.0, $result['prix_vente']); // 1000 * 1.40
        self::assertNotNull($result['tranche']);
    }

    public function testTrancheMargePourcentage(): void
    {
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['Test marge 50%', 1, 100, 'marge_pct', 50.0]);

        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 10);
        // Marge 50% : prix = 1000 / (1 - 0.50) = 2000
        self::assertSame(2000.0, $result['prix_vente']);
    }

    public function testTranchePrixFixe(): void
    {
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['Test fixe', 100, NULL, 'prix_fixe', 1800.0]);

        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 150);
        self::assertSame(1800.0, $result['prix_vente']);
    }

    public function testPasDeTrancheUtiliseDefaut40Pourcent(): void
    {
        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 1);
        self::assertSame(1400.0, $result['prix_vente']);
        self::assertNull($result['tranche']);
    }

    public function testTrancheSpecifiqueArticlePrevautSurGlobale(): void
    {
        // Tranche globale
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, article_id, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, NULL, 1, 9, 'majoration_pct', 40.0, 1)"
        )->execute(['Globale']);

        // Tranche spécifique à un article
        $aid = $this->creerArticle(1000.0, $this->creerFournisseur());
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, article_id, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, 1, 9, 'majoration_pct', 25.0, 1)"
        )->execute(['Spécifique', $aid]);

        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 5, $aid);
        self::assertSame(1250.0, $result['prix_vente']); // La tranche spécifique l'emporte
    }

    public function testQuantiteDepasseMaxPrendTrancheSuperieure(): void
    {
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['1-9', 1, 9, 'majoration_pct', 40.0]);

        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['10-49', 10, 49, 'majoration_pct', 35.0]);

        $result = db_calculer_prix_selon_quantite(self::$pdo, 1000.0, 10);
        self::assertSame(1350.0, $result['prix_vente']); // +35%
    }

    // ============================================================
    //  PRIX DE VENTE CALCULÉ (INTÉGRATION)
    // ============================================================

    public function testCalculerPrixVenteAvecTranche(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);

        // Définir le prix fournisseur
        db_fournisseur_prix_set(self::$pdo, $aid, $fid, 1000.0, 'manuelle');

        // Créer une tranche
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, article_id, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES (?, ?, 1, 9, 'majoration_pct', 40.0, 1)"
        )->execute(['Test', $aid]);

        $result = db_calculer_prix_vente(self::$pdo, $aid, 5, 1);
        self::assertSame(1400.0, $result['prix_vente']);
        self::assertSame(1000.0, $result['prix_fournisseur']);
    }

    public function testPrixParTranchesRetourneBonFormat(): void
    {
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES ('1-9', 1, 9, 'majoration_pct', 40.0, 1)"
        )->execute();
        self::$pdo->prepare(
            "INSERT INTO tranches_tarifaires (nom, qte_min, qte_max, mode_calcul, valeur, actif)
             VALUES ('50+', 50, NULL, 'majoration_pct', 20.0, 1)"
        )->execute();

        $tranches = db_prix_par_tranches(self::$pdo, 1000.0);
        self::assertCount(2, $tranches);
        self::assertSame('1 – 9', $tranches[0]['label']);
        self::assertSame('50+', $tranches[1]['label']);
        self::assertSame(1400.0, $tranches[0]['prix_vente']);
        self::assertSame(1200.0, $tranches[1]['prix_vente']);
    }
}
