<?php
/**
 * Tests d'intégration — Module Équipes.
 *
 * Couvre :
 *   * CRUD équipes (table `equipes`)
 *   * Affectation membres (table `equipe_membres`)
 *   * Affectation magasins/périmètre (table `equipe_magasins`)
 *   * Séparation rôle ≠ équipe ≠ périmètre
 */

final class EquipesTest extends PHPUnit\Framework\TestCase
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
        self::$pdo->exec('DELETE FROM equipe_membres');
        self::$pdo->exec('DELETE FROM equipe_magasins');
        self::$pdo->exec('DELETE FROM equipes');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    // ============================================================
    //  CRUD Équipes
    // ============================================================

    public function testCreerEquipe(): void
    {
        $id = db_equipe_insert(self::$pdo, [
            'nom' => 'Équipe Test Boutique',
            'description' => 'Équipe de test',
            'type' => 'BOUTIQUE',
        ]);
        $this->assertGreaterThan(0, $id);

        $equipe = db_equipe_get(self::$pdo, $id);
        $this->assertNotNull($equipe);
        $this->assertSame('Équipe Test Boutique', $equipe['nom']);
        $this->assertSame('BOUTIQUE', $equipe['type']);
    }

    public function testModifierEquipe(): void
    {
        $id = db_equipe_insert(self::$pdo, [
            'nom' => 'Ancien Nom',
            'type' => 'USINE',
        ]);

        db_equipe_update(self::$pdo, $id, [
            'nom' => 'Nouveau Nom',
            'description' => 'Description mise à jour',
            'type' => 'LIVRAISON',
            'chef_equipe_id' => null,
        ]);

        $equipe = db_equipe_get(self::$pdo, $id);
        $this->assertSame('Nouveau Nom', $equipe['nom']);
        $this->assertSame('LIVRAISON', $equipe['type']);
    }

    public function testSupprimerEquipeSoftDelete(): void
    {
        $id = db_equipe_insert(self::$pdo, [
            'nom' => 'À Supprimer',
            'type' => 'AUTRE',
        ]);

        db_equipe_delete(self::$pdo, $id);

        $equipes_actives = db_equipes_list(self::$pdo);
        $found = false;
        foreach ($equipes_actives as $e) {
            if ((int)$e['id'] === $id) { $found = true; break; }
        }
        $this->assertFalse($found, 'Équipe désactivée ne doit plus apparaître dans la liste active');

        $count = self::$pdo->query("SELECT COUNT(*) FROM equipes WHERE id = $id AND actif = 0")->fetchColumn();
        $this->assertEquals(1, $count, 'L\'équipe doit exister avec actif = 0');
    }

    public function testListeEquipesParType(): void
    {
        db_equipe_insert(self::$pdo, ['nom' => 'Boutique 1', 'type' => 'BOUTIQUE']);
        db_equipe_insert(self::$pdo, ['nom' => 'Boutique 2', 'type' => 'BOUTIQUE']);
        db_equipe_insert(self::$pdo, ['nom' => 'Usine 1', 'type' => 'USINE']);

        $boutiques = db_equipes_list(self::$pdo, 'BOUTIQUE');
        $this->assertCount(2, $boutiques);

        $usines = db_equipes_list(self::$pdo, 'USINE');
        $this->assertCount(1, $usines);

        $toutes = db_equipes_list(self::$pdo);
        $this->assertCount(3, $toutes);
    }

    // ============================================================
    //  Affectation Membres
    // ============================================================

    public function testAffecterMembresAEquipe(): void
    {
        $eq_id = db_equipe_insert(self::$pdo, ['nom' => 'Test Membres', 'type' => 'BOUTIQUE']);

        // Créer des utilisateurs de test
        self::$pdo->exec("DELETE FROM utilisateurs WHERE login LIKE 'test_eq_%'");
        $mdp = password_hash('test', PASSWORD_DEFAULT);
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['User A', 'test_eq_a', $mdp]);
        $uid_a = (int)self::$pdo->lastInsertId();
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['User B', 'test_eq_b', $mdp]);
        $uid_b = (int)self::$pdo->lastInsertId();

        db_equipe_set_membres(self::$pdo, $eq_id, [$uid_a, $uid_b]);

        $membres = db_equipe_membres(self::$pdo, $eq_id);
        $this->assertCount(2, $membres);

        $noms = array_column($membres, 'nom');
        $this->assertContains('User A', $noms);
        $this->assertContains('User B', $noms);
    }

    public function testRemplacerMembresEcraseAnciens(): void
    {
        $eq_id = db_equipe_insert(self::$pdo, ['nom' => 'Test Remplacement', 'type' => 'USINE']);

        self::$pdo->exec("DELETE FROM utilisateurs WHERE login LIKE 'test_eq_%'");
        $mdp = password_hash('test', PASSWORD_DEFAULT);
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['X', 'test_eq_x', $mdp]);
        $uid_x = (int)self::$pdo->lastInsertId();
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['Y', 'test_eq_y', $mdp]);
        $uid_y = (int)self::$pdo->lastInsertId();
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['Z', 'test_eq_z', $mdp]);
        $uid_z = (int)self::$pdo->lastInsertId();

        db_equipe_set_membres(self::$pdo, $eq_id, [$uid_x, $uid_y]);
        db_equipe_set_membres(self::$pdo, $eq_id, [$uid_y, $uid_z]);

        $membres = db_equipe_membres(self::$pdo, $eq_id);
        $this->assertCount(2, $membres);
        $logins = array_column($membres, 'login');
        $this->assertContains('test_eq_y', $logins);
        $this->assertContains('test_eq_z', $logins);
        $this->assertNotContains('test_eq_x', $logins);
    }

    // ============================================================
    //  Périmètre (Magasins)
    // ============================================================

    public function testAffecterMagasinsAEquipe(): void
    {
        $eq_id = db_equipe_insert(self::$pdo, ['nom' => 'Périmètre Test', 'type' => 'BOUTIQUE']);

        // Récupérer les magasins existants
        $magasins = self::$pdo->query("SELECT id FROM magasins WHERE actif = 1 LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
        if (count($magasins) < 2) {
            $this->markTestSkipped('Pas assez de magasins en base pour ce test');
        }

        db_equipe_set_magasins(self::$pdo, $eq_id, $magasins);

        $result = db_equipe_magasins(self::$pdo, $eq_id);
        $this->assertCount(2, $result);
    }

    // ============================================================
    //  Séparation Rôle ≠ Équipe
    // ============================================================

    public function testRoleAndTeamAreSeparate(): void
    {
        // Un utilisateur avec le rôle VENDEUR peut être dans une équipe USINE
        $eq_id = db_equipe_insert(self::$pdo, ['nom' => 'Usine A', 'type' => 'USINE']);

        self::$pdo->exec("DELETE FROM utilisateurs WHERE login = 'test_sep_role'");
        $mdp = password_hash('test', PASSWORD_DEFAULT);
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['Test Séparation', 'test_sep_role', $mdp]);
        $uid = (int)self::$pdo->lastInsertId();

        db_equipe_set_membres(self::$pdo, $eq_id, [$uid]);

        $equipes = db_user_equipes(self::$pdo, $uid);
        $this->assertCount(1, $equipes);
        $this->assertSame('Usine A', $equipes[0]['nom']);
        $this->assertSame('USINE', $equipes[0]['type']);
    }

    public function testUserCanBeInMultipleTeams(): void
    {
        $eq1 = db_equipe_insert(self::$pdo, ['nom' => 'Team A', 'type' => 'BOUTIQUE']);
        $eq2 = db_equipe_insert(self::$pdo, ['nom' => 'Team B', 'type' => 'USINE']);

        self::$pdo->exec("DELETE FROM utilisateurs WHERE login = 'test_multi_team'");
        $mdp = password_hash('test', PASSWORD_DEFAULT);
        self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, 4, 1)")->execute(['Multi Team', 'test_multi_team', $mdp]);
        $uid = (int)self::$pdo->lastInsertId();

        db_equipe_set_membres(self::$pdo, $eq1, [$uid]);
        db_equipe_set_membres(self::$pdo, $eq2, [$uid]);

        $equipes = db_user_equipes(self::$pdo, $uid);
        $this->assertCount(2, $equipes);
    }

    // ============================================================
    //  Nombre de membres
    // ============================================================

    public function testComptageMembres(): void
    {
        $eq_id = db_equipe_insert(self::$pdo, ['nom' => 'Comptage', 'type' => 'CAISSE']);

        self::$pdo->exec("DELETE FROM utilisateurs WHERE login LIKE 'test_count_%'");
        $mdp = password_hash('test', PASSWORD_DEFAULT);
        $role_id = self::$pdo->query("SELECT id FROM roles WHERE code = 'VENDEUR' LIMIT 1")->fetchColumn();
        for ($i = 0; $i < 3; $i++) {
            self::$pdo->prepare("INSERT INTO utilisateurs (nom, login, mot_de_passe, role_id, actif) VALUES (?, ?, ?, ?, 1)")->execute(["Count $i", "test_count_$i", $mdp, $role_id]);
            $uid = (int)self::$pdo->lastInsertId();
            $existing = array_column(db_equipe_membres(self::$pdo, $eq_id), 'id');
            db_equipe_set_membres(self::$pdo, $eq_id, array_merge($existing, [$uid]));
        }

        $equipes = db_equipes_list(self::$pdo);
        $comptage = null;
        foreach ($equipes as $e) {
            if ($e['id'] == $eq_id) {
                $comptage = $e;
                break;
            }
        }
        $this->assertNotNull($comptage);
        $this->assertEquals(3, (int)$comptage['nb_membres']);
    }
}
