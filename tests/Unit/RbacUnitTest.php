<?php
/**
 * Tests unitaires — Système RBAC dynamique.
 *
 * Couvre :
 *   * Chargement des permissions via user_roles → roles → role_permissions → permissions
 *   * Fallback sur le rôle unique quand user_roles est vide
 *   * Fonction peat() avec permissions valides/invalides
 *   * Hiérarchie des rôles
 *   * Rôles protégés
 *   * Permissions équipes
 */

final class RbacUnitTest extends PHPUnit\Framework\TestCase
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
        self::$pdo->exec('DELETE FROM user_equipes');
        self::$pdo->exec('DELETE FROM equipe_magasins');
        self::$pdo->exec('DELETE FROM equipes');
        self::$pdo->exec('DELETE FROM user_roles');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    // ============================================================
    //  Tests des constantes de rôle
    // ============================================================

    public function testRoleConstantsMatchDatabaseCodes(): void
    {
        $codes = self::$pdo->query("SELECT code FROM roles ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains(ROLE_DIRECTEUR, $codes, 'PROPRIETAIRE doit exister en base');
        $this->assertContains(ROLE_ADMIN, $codes, 'ADMIN doit exister en base');
        $this->assertContains(ROLE_MAGASINIER, $codes, 'MAGASINIER doit exister en base');
        $this->assertContains(ROLE_VENDEUR, $codes, 'VENDEUR doit exister en base');
    }

    public function testRolesProtegesAreSubsetOfAllRoles(): void
    {
        $all_codes = self::$pdo->query("SELECT code FROM roles")->fetchAll(PDO::FETCH_COLUMN);
        foreach (ROLES_PROTEGES as $role) {
            $this->assertContains($role, $all_codes, "Rôle protégé $role doit exister en base");
        }
    }

    // ============================================================
    //  Tests des permissions
    // ============================================================

    public function testPermissionsExistInDatabase(): void
    {
        $perms = self::$pdo->query("SELECT cle_permission FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $expected = [
            'stock_consulter', 'articles_consulter', 'articles_gerer',
            'caisse_gerer', 'facturation_consulter', 'facturation_gerer',
            'usine_consulter', 'production_consulter', 'production_gerer',
            'machines_consulter', 'machines_gerer', 'machines_demarrer',
            'presence_consulter', 'presence_gerer',
            'notifications_usine_consulter', 'notifications_usine_gerer',
            'horaires_consulter', 'horaires_gerer',
            'roles_consulter', 'roles_gerer', 'permissions_gerer',
            'equipes_consulter', 'equipes_gerer',
            'rendement_consulter',
        ];
        foreach ($expected as $p) {
            $this->assertContains($p, $perms, "Permission $p doit exister");
        }
    }

    public function testRolePermissionsMappingExists(): void
    {
        $count = self::$pdo->query("SELECT COUNT(*) FROM role_permissions")->fetchColumn();
        $this->assertGreaterThan(50, $count, 'Au moins 50 role_permissions assignations attendues');
    }

    // ============================================================
    //  Tests de la fonction peut() avec une session mockée
    // ============================================================

    private function setupUserWithRoles(array $role_codes): void
    {
        $_SESSION = ['user' => ['id' => 999, 'role' => $role_codes[0] ?? '']];

        // Créer un utilisateur de test
        self::$pdo->exec("DELETE FROM utilisateurs WHERE id = 999");
        self::$pdo->exec("DELETE FROM user_roles WHERE user_id = 999");
        $mdp = password_hash('test123', PASSWORD_DEFAULT);
        self::$pdo->prepare(
            "INSERT INTO utilisateurs (id, nom, login, mot_de_passe, role_id, actif)
             VALUES (999, 'Test User', 'test_rbac_999', ?, 1, 1)"
        )->execute([$mdp]);

        // Assigner les rôles
        $stmt = self::$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        foreach ($role_codes as $code) {
            $role_id = self::$pdo->prepare("SELECT id FROM roles WHERE code = ?");
            $role_id->execute([$code]);
            $rid = $role_id->fetchColumn();
            if ($rid) {
                $stmt->execute([999, $rid]);
            }
        }
    }

    public function testPeutReturnsTrueForGrantedPermission(): void
    {
        $this->setupUserWithRoles(['ADMIN']);
        $this->assertTrue(peut('articles_consulter'), 'ADMIN doit pouvoir consulter les articles');
    }

    public function testPeutReturnsFalseForDeniedPermission(): void
    {
        $this->setupUserWithRoles(['VENDEUR']);
        $this->assertFalse(peut('roles_gerer'), 'VENDEUR ne doit pas pouvoir gérer les rôles');
    }

    public function testPeutReturnsFalseForDisconnectedUser(): void
    {
        $_SESSION = [];
        $this->assertFalse(peut('articles_consulter'), 'Utilisateur déconnecté ne doit rien pouvoir');
    }

    public function testMultiRoleCombinesPermissions(): void
    {
        $this->setupUserWithRoles(['VENDEUR', 'MAGASINIER']);
        // VENDEUR a facturation_consulter, MAGASINIER a stock_consulter
        $this->assertTrue(peut('facturation_consulter'), 'Multi-rôle : facturation via VENDEUR');
        $this->assertTrue(peut('stock_consulter'), 'Multi-rôle : stock via MAGASINIER');
    }

    // ============================================================
    //  Tests de la hiérarchie
    // ============================================================

    public function testRoleHierarchyOrder(): void
    {
        $this->assertGreaterThan(ROLE_HIERARCHIE[ROLE_VENDEUR], ROLE_HIERARCHIE[ROLE_MAGASINIER]);
        $this->assertGreaterThan(ROLE_HIERARCHIE[ROLE_MAGASINIER], ROLE_HIERARCHIE[ROLE_ADMIN]);
        $this->assertGreaterThan(ROLE_HIERARCHIE[ROLE_ADMIN], ROLE_HIERARCHIE[ROLE_DIRECTEUR]);
    }

    public function testChefEquipeHasLowerHierarchyThanAdmin(): void
    {
        $this->assertLessThan(ROLE_HIERARCHIE[ROLE_ADMIN], ROLE_HIERARCHIE[ROLE_CHEF_EQUIPE]);
    }

    // ============================================================
    //  Tests des permissions équipes
    // ============================================================

    public function testEquipesPermissionsExist(): void
    {
        $perms = self::$pdo->query("SELECT cle_permission FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('equipes_consulter', $perms);
        $this->assertContains('equipes_gerer', $perms);
    }

    public function testAdminHasEquipesPermissions(): void
    {
        $this->setupUserWithRoles(['ADMIN']);
        $this->assertTrue(peut('equipes_consulter'), 'ADMIN doit pouvoir consulter les équipes');
        $this->assertTrue(peut('equipes_gerer'), 'ADMIN doit pouvoir gérer les équipes');
    }

    // ============================================================
    //  Tests des permissions usine
    // ============================================================

    public function testUsinePermissionsGranularity(): void
    {
        $this->setupUserWithRoles(['VENDEUR']);
        $this->assertFalse(peut('usine_consulter'), 'VENDEUR ne doit pas accéder à l\'usine');
        $this->assertFalse(peut('production_gerer'), 'VENDEUR ne doit pas gérer les productions');
        $this->assertFalse(peut('machines_demarrer'), 'VENDEUR ne doit pas démarrer les machines');
    }

    public function testChefEquipeUsinePermissions(): void
    {
        $this->setupUserWithRoles(['CHEF_EQUIPE_USINE']);
        $this->assertTrue(peut('usine_consulter'), 'CHEF_EQUIPE_USINE doit consulter l\'usine');
        $this->assertTrue(peut('production_consulter'), 'CHEF_EQUIPE_USINE doit consulter les productions');
        $this->assertTrue(peut('machines_consulter'), 'CHEF_EQUIPE_USINE doit consulter les machines');
        $this->assertFalse(peut('roles_gerer'), 'CHEF_EQUIPE_USINE ne doit pas gérer les rôles');
        $this->assertFalse(peut('utilisateurs_gerer'), 'CHEF_EQUIPE_USINE ne doit pas gérer les utilisateurs');
    }
}
