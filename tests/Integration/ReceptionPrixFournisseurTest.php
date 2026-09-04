<?php
/**
 * Tests d'intégration — Réceptions, prix fournisseur et livraisons partielles.
 *
 * Vérifie :
 *   * la création de réception avec lignes
 *   * la validation de réception met à jour le statut de la commande
 *   * la validation met à jour le prix fournisseur
 *   * les pertes sont enregistrées
 *   * le stock n'augmente que des quantités acceptées
 *   * les livraisons partielles successives fonctionnent
 *   * le blocage de sur-réception
 */

final class ReceptionPrixFournisseurTest extends PHPUnit\Framework\TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        test_db_schema();
        self::$pdo = test_db();
    }

    protected function setUp(): void
    {
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        self::$pdo->exec("DELETE FROM pertes_fournisseur");
        self::$pdo->exec("DELETE FROM reception_lignes");
        self::$pdo->exec("DELETE FROM receptions");
        self::$pdo->exec("DELETE FROM fournisseur_prix_historique");
        self::$pdo->exec("DELETE FROM lignes_commande_fournisseur");
        self::$pdo->exec("DELETE FROM commandes_fournisseur WHERE notes LIKE 'Test%'");
        self::$pdo->exec("DELETE FROM article_lots WHERE numero_lot LIKE 'LOT-TEST%'");
        self::$pdo->exec("DELETE FROM articles WHERE nom LIKE 'Test Réception%'");
        self::$pdo->exec("DELETE FROM fournisseurs WHERE nom LIKE 'Fournisseur Réception%'");
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function creerFournisseur(): int {
        self::$pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)")->execute(['Fournisseur Réception ' . random_int(100, 999)]);
        return (int)self::$pdo->lastInsertId();
    }

    private function creerArticle(float $prixAchat, int $fournisseurId): int {
        $id = db_article_insert(self::$pdo, [
            'nom'            => 'Test Réception ' . random_int(1000, 9999),
            'code_barre'     => (string)random_int(1000000000000, 9999999999999),
            'prix_achat'     => $prixAchat,
            'prix_vente'     => $prixAchat * 1.4,
            'quantite_stock' => 0,
            'seuil_alerte'   => 5,
            'fournisseur_id' => $fournisseurId,
        ]);
        self::assertGreaterThan(0, $id);
        return $id;
    }

    private function creerCommande(int $fournisseurId, int $magasinId, int $userId, string $statut = 'Envoyee'): int {
        $ref = 'CMD-TEST-' . random_int(10000, 99999);
        self::$pdo->prepare(
            "INSERT INTO commandes_fournisseur (fournisseur_id, magasin_id, utilisateur_id, statut, notes)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$fournisseurId, $magasinId, $userId, $statut, 'Test réception ' . time()]);
        return (int)self::$pdo->lastInsertId();
    }

    private function ajouterLigneCommande(int $commandeId, int $articleId, int $quantite, float $prix): int {
        self::$pdo->prepare(
            "INSERT INTO lignes_commande_fournisseur (commande_id, article_id, quantite_commandee, quantite_recue, prix_achat_unitaire)
             VALUES (?, ?, ?, 0, ?)"
        )->execute([$commandeId, $articleId, $quantite, $prix]);
        return (int)self::$pdo->lastInsertId();
    }

    // ============================================================
    //  CRÉATION RÉCEPTION
    // ============================================================

    public function testCreationReceptionAvecLignes(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1000.0);

        $receptionId = db_reception_insert(self::$pdo, [
            'commande_id'    => $cmdId,
            'fournisseur_id' => $fid,
            'magasin_id'     => 1,
        ]);
        self::assertGreaterThan(0, $receptionId);

        db_reception_ligne_insert(self::$pdo, [
            'reception_id'        => $receptionId,
            'ligne_commande_id'   => $ligneId,
            'article_id'          => $aid,
            'quantite_attendue'   => 1000,
            'quantite_recue'      => 400,
            'quantite_acceptee'   => 390,
            'quantite_perdue'     => 10,
            'prix_achat_unitaire' => 1000.0,
        ]);

        $rec = db_reception_get_by_id(self::$pdo, $receptionId);
        self::assertNotNull($rec);
        self::assertSame('Brouillon', $rec['statut']);

        $lignes = db_reception_lignes(self::$pdo, $receptionId);
        self::assertCount(1, $lignes);
        self::assertSame(390, (int)$lignes[0]['quantite_acceptee']);
        self::assertSame(10, (int)$lignes[0]['quantite_perdue']);
    }

    // ============================================================
    //  VALIDATION RÉCEPTION
    // ============================================================

    public function testValidationReceptionMetAJourStockEtStatut(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1000.0);

        $receptionId = db_reception_insert(self::$pdo, [
            'commande_id'    => $cmdId,
            'fournisseur_id' => $fid,
            'magasin_id'     => 1,
        ]);

        db_reception_ligne_insert(self::$pdo, [
            'reception_id'        => $receptionId,
            'ligne_commande_id'   => $ligneId,
            'article_id'          => $aid,
            'quantite_attendue'   => 1000,
            'quantite_recue'      => 400,
            'quantite_acceptee'   => 390,
            'quantite_perdue'     => 10,
            'prix_achat_unitaire' => 1000.0,
        ]);

        $ok = db_reception_valider(self::$pdo, $receptionId, 1);
        self::assertTrue($ok);

        // Vérifier statut réception
        $rec = db_reception_get_by_id(self::$pdo, $receptionId);
        self::assertSame('Validee', $rec['statut']);

        // Vérifier statut commande = Recue_Partielle
        $cmd = db_commande_fournisseur_get_by_id(self::$pdo, $cmdId);
        self::assertSame('Recue_Partielle', $cmd['statut']);

        // Vérifier stock article
        $stock = (int)self::$pdo->query("SELECT quantite_stock FROM articles WHERE id = $aid")->fetchColumn();
        self::assertSame(390, $stock); // Seulement les acceptés
    }

    public function testValidationReceptionEnregistrePertes(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1000.0);

        $receptionId = db_reception_insert(self::$pdo, [
            'commande_id'    => $cmdId,
            'fournisseur_id' => $fid,
            'magasin_id'     => 1,
        ]);

        db_reception_ligne_insert(self::$pdo, [
            'reception_id'        => $receptionId,
            'ligne_commande_id'   => $ligneId,
            'article_id'          => $aid,
            'quantite_attendue'   => 1000,
            'quantite_recue'      => 400,
            'quantite_acceptee'   => 390,
            'quantite_perdue'     => 10,
            'prix_achat_unitaire' => 1000.0,
            'motif_perte'         => 'endommage',
        ]);

        db_reception_valider(self::$pdo, $receptionId, 1);

        $pertes = self::$pdo->query("SELECT * FROM pertes_fournisseur")->fetchAll();
        self::assertCount(1, $pertes);
        self::assertSame(10, (int)$pertes[0]['quantite']);
        self::assertSame('endommage', $pertes[0]['motif']);
    }

    public function testValidationReceptionMetAJourPrixFournisseur(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1500.0);

        $receptionId = db_reception_insert(self::$pdo, [
            'commande_id'    => $cmdId,
            'fournisseur_id' => $fid,
            'magasin_id'     => 1,
        ]);

        db_reception_ligne_insert(self::$pdo, [
            'reception_id'        => $receptionId,
            'ligne_commande_id'   => $ligneId,
            'article_id'          => $aid,
            'quantite_attendue'   => 1000,
            'quantite_recue'      => 400,
            'quantite_acceptee'   => 400,
            'quantite_perdue'     => 0,
            'prix_achat_unitaire' => 1500.0,
        ]);

        db_reception_valider(self::$pdo, $receptionId, 1);

        // Le prix fournisseur doit être mis à jour
        $prixActuel = db_fournisseur_prix_actuel(self::$pdo, $aid, $fid);
        self::assertNotNull($prixActuel);
        self::assertSame(1500.0, (float)$prixActuel['prix_achat']);
        self::assertSame('reception', $prixActuel['source']);
    }

    // ============================================================
    //  LIVRAISONS PARTIELLES
    // ============================================================

    public function testLivraisonsPartiellesSuccessives(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1000.0);

        // Livraison 1 : 400 reçus, 390 acceptés, 10 perdus
        $rec1 = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec1, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 1000, 'quantite_recue' => 400, 'quantite_acceptee' => 390,
            'quantite_perdue' => 10, 'prix_achat_unitaire' => 1000.0,
        ]);
        db_reception_valider(self::$pdo, $rec1, 1);

        $cmd = db_commande_fournisseur_get_by_id(self::$pdo, $cmdId);
        self::assertSame('Recue_Partielle', $cmd['statut']);
        $stock = (int)self::$pdo->query("SELECT quantite_stock FROM articles WHERE id = $aid")->fetchColumn();
        self::assertSame(390, $stock);

        // Livraison 2 : 300 reçus, 295 acceptés, 5 perdus
        $rec2 = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec2, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 600, 'quantite_recue' => 300, 'quantite_acceptee' => 295,
            'quantite_perdue' => 5, 'prix_achat_unitaire' => 1000.0,
        ]);
        db_reception_valider(self::$pdo, $rec2, 1);

        $cmd = db_commande_fournisseur_get_by_id(self::$pdo, $cmdId);
        self::assertSame('Recue_Partielle', $cmd['statut']);
        $stock = (int)self::$pdo->query("SELECT quantite_stock FROM articles WHERE id = $aid")->fetchColumn();
        self::assertSame(685, $stock); // 390 + 295

        // Livraison 3 : 300 reçus, 300 acceptés
        $rec3 = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec3, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 300, 'quantite_recue' => 300, 'quantite_acceptee' => 300,
            'quantite_perdue' => 0, 'prix_achat_unitaire' => 1000.0,
        ]);
        db_reception_valider(self::$pdo, $rec3, 1);

        $cmd = db_commande_fournisseur_get_by_id(self::$pdo, $cmdId);
        self::assertSame('Recue', $cmd['statut']);
        $stock = (int)self::$pdo->query("SELECT quantite_stock FROM articles WHERE id = $aid")->fetchColumn();
        self::assertSame(985, $stock); // 390 + 295 + 300

        // Vérifier les pertes totales
        $pertes = self::$pdo->query("SELECT SUM(quantite) as total FROM pertes_fournisseur")->fetchColumn();
        self::assertSame(15, (int)$pertes); // 10 + 5
    }

    // ============================================================
    //  BLOCAGE SUR-RÉCEPTION
    // ============================================================

    public function testBlocageSurReception(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 100, 1000.0);

        $rec = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 100, 'quantite_recue' => 110, 'quantite_acceptee' => 110,
            'quantite_perdue' => 0, 'prix_achat_unitaire' => 1000.0,
        ]);

        $this->expectException(RuntimeException::class);
        db_reception_valider(self::$pdo, $rec, 1);
    }

    // ============================================================
    //  PRIX DIFFÉRENTS ENTRE RÉCEPTIONS
    // ============================================================

    public function testPrixDifferentsEntreeReceptions(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 1000, 1000.0);

        // Réception 1 : 400 × 1000 FCFA
        $rec1 = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec1, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 1000, 'quantite_recue' => 400, 'quantite_acceptee' => 400,
            'quantite_perdue' => 0, 'prix_achat_unitaire' => 1000.0,
        ]);
        db_reception_valider(self::$pdo, $rec1, 1);

        // Réception 2 : 300 × 1200 FCFA
        $rec2 = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $rec2, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 600, 'quantite_recue' => 300, 'quantite_acceptee' => 300,
            'quantite_perdue' => 0, 'prix_achat_unitaire' => 1200.0,
        ]);
        db_reception_valider(self::$pdo, $rec2, 1);

        // Vérifier que le prix fournisseur est à jour (1200)
        $prixActuel = db_fournisseur_prix_actuel(self::$pdo, $aid, $fid);
        self::assertSame(1200.0, (float)$prixActuel['prix_achat']);

        // Vérifier l'historique
        $historique = db_fournisseur_prix_historique(self::$pdo, $aid);
        self::assertCount(2, $historique);
    }

    // ============================================================
    //  ANNULATION RÉCEPTION
    // ============================================================

    public function testAnnulationReceptionBrouillon(): void
    {
        $fid = $this->creerFournisseur();
        $cmdId = $this->creerCommande($fid, 1, 1);

        $recId = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        $ok = db_reception_annuler(self::$pdo, $recId, 'Annulation test');
        self::assertTrue($ok);

        $rec = db_reception_get_by_id(self::$pdo, $recId);
        self::assertSame('Annulee', $rec['statut']);
    }

    public function testImpossibleAnnulerReceptionValidee(): void
    {
        $fid = $this->creerFournisseur();
        $aid = $this->creerArticle(1000.0, $fid);
        $cmdId = $this->creerCommande($fid, 1, 1);
        $ligneId = $this->ajouterLigneCommande($cmdId, $aid, 100, 1000.0);

        $recId = db_reception_insert(self::$pdo, ['commande_id' => $cmdId, 'fournisseur_id' => $fid, 'magasin_id' => 1]);
        db_reception_ligne_insert(self::$pdo, [
            'reception_id' => $recId, 'ligne_commande_id' => $ligneId, 'article_id' => $aid,
            'quantite_attendue' => 100, 'quantite_recue' => 50, 'quantite_acceptee' => 50,
            'quantite_perdue' => 0, 'prix_achat_unitaire' => 1000.0,
        ]);
        db_reception_valider(self::$pdo, $recId, 1);

        $ok = db_reception_annuler(self::$pdo, $recId);
        self::assertFalse($ok); // Impossible d'annuler une réception validée
    }
}
