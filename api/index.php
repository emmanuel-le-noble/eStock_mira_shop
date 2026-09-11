<?php
/**
 * api/index.php — Routeur API REST centralisé pour eStock.
 *
 * Point d'entrée unique : toutes les requêtes /api/* passent par ici.
 * Les réponses sont toujours en JSON avec le code HTTP approprié.
 */

// ============================================================
//  BOOTSTRAP
// ============================================================
require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// ============================================================
//  DISPATCHER
// ============================================================

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path   = preg_replace('#^' . preg_quote($base) . '#', '', $uri);
$path   = trim($path, '/');
$path   = preg_replace('#^api/#', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Fallback : support du paramètre ?route= pour les serveurs sans mod_rewrite
if ($path === '' || $path === 'index.php') {
    $path = $_GET['route'] ?? '';
}

$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) ? (int)$segments[1] : 0;

match (true) {
    // --- Auth ---
    $resource === 'login' && $method === 'POST'
        => handle_login(),
    $resource === 'logout' && $method === 'POST'
        => handle_logout(),
    $resource === 'me' && $method === 'GET'
        => handle_me(),

    // --- Articles ---
    $resource === 'articles' && $method === 'GET'
        => handle_articles_list(),
    $resource === 'articles' && $method === 'POST'
        => handle_article_create(),
    $resource === 'articles' && $id > 0 && $method === 'PUT'
        => handle_article_update($id),
    $resource === 'articles' && $id > 0 && $method === 'DELETE'
        => handle_article_delete($id),

    // --- Scan (code-barres) ---
    $resource === 'scan' && $method === 'GET'
        => handle_scan(),

    // --- Caisse hors-ligne ---
    $resource === 'caisse' && $method === 'POST'
        && isset($segments[1]) && $segments[1] === 'sync'
        => handle_caisse_sync(),

    // --- État de la caisse ---
    $resource === 'caisse' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'status'
        => handle_caisse_status(),

    // --- Générateur code-barres EAN-13 ---
    $resource === 'generer_code_barre' && $method === 'GET'
        => handle_generer_code_barre(),

    // --- Code promo ---
    $resource === 'promo' && $method === 'GET'
        => handle_promo_check(),

    // --- Clients fidélité (caisse) ---
    $resource === 'clients' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'search'
        => handle_clients_search(),

    // --- Prix fournisseurs ---
    $resource === 'prix_fournisseur' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'article' && isset($segments[2]) && (int)$segments[2] > 0
        => handle_prix_fournisseur_article((int)$segments[2]),
    $resource === 'prix_fournisseur' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'fournisseur' && isset($segments[2]) && (int)$segments[2] > 0
        => handle_prix_fournisseur_par_fournisseur((int)$segments[2]),
    $resource === 'prix_fournisseur' && $method === 'POST'
        => handle_prix_fournisseur_set(),
    $resource === 'prix_fournisseur' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'calculer'
        => handle_prix_fournisseur_calculer(),

    // --- Tranches tarifaires ---
    $resource === 'tranches' && $method === 'GET'
        => handle_tranches_list(),
    $resource === 'tranches' && $method === 'GET' && $id > 0
        => handle_tranche_get($id),
    $resource === 'tranches' && $method === 'POST'
        => handle_tranche_create(),
    $resource === 'tranches' && $method === 'PUT' && $id > 0
        => handle_tranche_update($id),
    $resource === 'tranches' && $method === 'DELETE' && $id > 0
        => handle_tranche_delete($id),

    // --- Réceptions ---
    $resource === 'receptions' && $method === 'GET' && $id > 0
        => handle_reception_get($id),
    $resource === 'receptions' && $method === 'GET'
        && isset($segments[1]) && $segments[1] === 'commande' && $id > 0
        => handle_receptions_par_commande($id),
    $resource === 'receptions' && $method === 'POST'
        => handle_reception_create(),
    $resource === 'receptions' && $method === 'POST'
        && isset($segments[1]) && $segments[1] === 'valider' && $id > 0
        => handle_reception_valider($id),
    $resource === 'receptions' && $method === 'POST'
        && isset($segments[1]) && $segments[1] === 'annuler' && $id > 0
        => handle_reception_annuler($id),

    // --- Pertes ---
    $resource === 'pertes' && $method === 'GET'
        => handle_pertes_list(),

    // --- Matières premières (usine) ---
    $resource === 'matieres_premieres' && $method === 'GET'
        => handle_matieres_premieres_list(),
    $resource === 'matieres_premieres' && $method === 'POST'
        => handle_matiere_premiere_create(),
    $resource === 'matieres_premieres' && $id > 0 && $method === 'PUT'
        => handle_matiere_premiere_update($id),

    // --- Recettes ---
    $resource === 'recettes' && $method === 'GET'
        => handle_recettes_list(),
    $resource === 'recettes' && $id > 0 && $method === 'GET'
        => handle_recette_get($id),
    $resource === 'recettes' && $method === 'POST'
        => handle_recette_create(),
    $resource === 'recettes' && $id > 0 && $method === 'PUT'
        => handle_recette_update($id),
    $resource === 'recettes' && $id > 0 && $method === 'DELETE'
        => handle_recette_delete($id),

    // --- Productions ---
    $resource === 'productions' && $method === 'GET'
        => handle_productions_list(),
    $resource === 'productions' && $id > 0 && $method === 'GET'
        => handle_production_get($id),
    $resource === 'productions' && $method === 'POST'
        => handle_production_create(),
    $resource === 'productions' && $id > 0
        && isset($segments[1]) && $segments[1] === 'demarrer' && $method === 'POST'
        => handle_production_demarrer($id),
    $resource === 'productions' && $id > 0
        && isset($segments[1]) && $segments[1] === 'cloturer' && $method === 'POST'
        => handle_production_cloturer($id),
    $resource === 'productions' && $id > 0
        && isset($segments[1]) && $segments[1] === 'annuler' && $method === 'POST'
        => handle_production_annuler($id),
    $resource === 'productions' && $id > 0
        && isset($segments[1]) && $segments[1] === 'employes' && $method === 'POST'
        => handle_production_set_employes($id),

    // --- Employés ---
    $resource === 'employes' && $method === 'GET'
        => handle_employes_list(),
    $resource === 'employes' && $method === 'POST'
        => handle_employe_create(),
    $resource === 'employes' && $id > 0 && $method === 'PUT'
        => handle_employe_update($id),

    // --- Présences ---
    $resource === 'presences' && $method === 'GET'
        => handle_presences_list(),
    $resource === 'presences' && $method === 'POST'
        => handle_presence_upsert(),

    // --- Stock usine ---
    $resource === 'stock_usine' && $method === 'GET'
        => handle_stock_usine_list(),

    // --- Transfert usine ---
    $resource === 'transfert_usine' && $method === 'POST'
        => handle_transfert_usine(),

    // --- Machines ---
    $resource === 'machines' && $method === 'GET'
        => handle_machines_list(),
    $resource === 'machines' && $method === 'POST'
        => handle_machine_create(),
    $resource === 'machines' && $id > 0 && $method === 'GET'
        => handle_machine_get($id),
    $resource === 'machines' && $id > 0 && $method === 'PUT'
        => handle_machine_update($id),
    $resource === 'machines' && $id > 0
        && isset($segments[1]) && $segments[1] === 'demarrer' && $method === 'POST'
        => handle_machine_demarrer($id),
    $resource === 'machines' && $id > 0
        && isset($segments[1]) && $segments[1] === 'arreter' && $method === 'POST'
        => handle_machine_arreter($id),
    $resource === 'machines' && $id > 0
        && isset($segments[1]) && $segments[1] === 'etat' && $method === 'POST'
        => handle_machine_set_etat($id),
    $resource === 'machines' && $id > 0
        && isset($segments[1]) && $segments[1] === 'historique' && $method === 'GET'
        => handle_machine_historique($id),

    // --- Notifications ---
    $resource === 'notifications' && $method === 'GET'
        => handle_notifications_list(),
    $resource === 'notifications' && $id > 0
        && isset($segments[1]) && $segments[1] === 'lire' && $method === 'POST'
        => handle_notification_marquer_lue($id),
    $resource === 'notifications' && $method === 'POST'
        && isset($segments[0]) && $segments[0] === 'notifications' && empty($id)
        && isset($_GET['action']) && $_GET['action'] === 'tout_lu'
        => handle_notifications_tout_lu(),
    $resource === 'notifications' && $id > 0 && $method === 'DELETE'
        => handle_notification_supprimer($id),

    // --- Horaires ---
    $resource === 'horaires' && $method === 'GET'
        => handle_horaires_list(),
    $resource === 'horaires' && $method === 'POST'
        => handle_horaire_create(),
    $resource === 'horaires' && $method === 'DELETE'
        => handle_horaire_delete(),

    // --- Catégories pertes ---
    $resource === 'categories_pertes' && $method === 'GET'
        => handle_categories_pertes_list(),
    $resource === 'categories_pertes' && $method === 'POST'
        => handle_categorie_perte_create(),

    // --- Rendement ---
    $resource === 'rendement' && $id > 0 && $method === 'GET'
        => handle_rendement_production($id),
    $resource === 'rendement' && $id > 0
        && isset($segments[1]) && $segments[1] === 'categories' && $method === 'GET'
        => handle_rendement_categories($id),
    $resource === 'rapport_matiere_production' && $method === 'GET'
        => handle_rapport_matiere_production(),

    // --- Présences avec retards ---
    $resource === 'presences_retards' && $method === 'GET'
        => handle_presences_avec_retards(),

    // --- Dashboard enrichi ---
    $resource === 'usine_dashboard_enrichi' && $method === 'GET'
        => handle_usine_dashboard_enrichi(),

    // --- Tableau de bord usine ---
    $resource === 'usine_dashboard' && $method === 'GET'
        => handle_usine_dashboard(),

    // --- Rapport production ---
    $resource === 'production_rapport' && $method === 'GET'
        => handle_production_rapport(),

    // --- Rôles RBAC ---
    $resource === 'roles' && $method === 'GET'
        => handle_roles_list(),
    $resource === 'roles' && $method === 'POST'
        => handle_role_create(),
    $resource === 'roles' && $id > 0 && $method === 'PUT'
        => handle_role_update($id),
    $resource === 'roles' && $id > 0 && $method === 'DELETE'
        => handle_role_delete($id),

    // --- Permissions ---
    $resource === 'permissions' && $method === 'GET'
        => handle_permissions_list(),
    $resource === 'roles' && $id > 0 && isset($segments[2]) && $segments[2] === 'permissions' && $method === 'GET'
        => handle_role_permissions($id),
    $resource === 'roles' && $id > 0 && isset($segments[2]) && $segments[2] === 'permissions' && $method === 'PUT'
        => handle_role_permissions_save($id),

    // --- Équipes ---
    $resource === 'equipes' && $method === 'GET'
        => handle_equipes_list(),
    $resource === 'equipes' && $method === 'POST'
        => handle_equipe_create(),
    $resource === 'equipes' && $id > 0 && $method === 'GET'
        => handle_equipe_get($id),
    $resource === 'equipes' && $id > 0 && $method === 'PUT'
        => handle_equipe_update($id),
    $resource === 'equipes' && $id > 0 && $method === 'DELETE'
        => handle_equipe_delete($id),

    // --- Credit / Creances ---
    $resource === 'clients' && $id > 0
        && isset($segments[1]) && $segments[1] === 'credit' && $method === 'GET'
        => handle_client_credit_info($id),
    $resource === 'creances' && $method === 'GET'
        => handle_creances_list(),
    $resource === 'creances' && $id > 0 && $method === 'GET'
        => handle_creance_get($id),
    $resource === 'creances' && $id > 0
        && isset($segments[1]) && $segments[1] === 'paiements' && $method === 'GET'
        => handle_creance_paiements_list($id),
    $resource === 'creances' && $id > 0
        && isset($segments[1]) && $segments[1] === 'paiements' && $method === 'POST'
        => handle_creance_paiement_create($id),
    $resource === 'creances' && $id > 0
        && isset($segments[1]) && $segments[1] === 'annuler' && $method === 'POST'
        => handle_creance_annuler($id),
    $resource === 'credit_rapport' && $method === 'GET'
        => handle_credit_rapport(),

    // --- 404 ---
    default
        => json_out(['error' => 'Route introuvable.'], 404),
};

// ============================================================
//  AUTH HANDLERS
// ============================================================

/**
 * Vérifier le token CSRF transmis via l'en-tête X-CSRF-Token.
 */
function api_csrf_ok(): bool {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && verify_csrf_token($token);
}

function handle_login(): void {
    // SEC-11 : Vérifier que la requête vient d'une interface interne (pas cross-origin)
    $isXHR = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $isAPI = !empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    if (!$isXHR && !$isAPI) {
        json_out(['error' => 'Requête non autorisée.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $login = input_string($input['login'] ?? $_POST['login'] ?? '');
    $mdp   = $input['mot_de_passe'] ?? $_POST['mot_de_passe'] ?? '';

    if ($login === '' || $mdp === '') {
        json_out(['success' => false, 'message' => 'Identifiants requis.'], 422);
    }

    if (login_rate_limited()) {
        suivre_activite('ECHEC_CONNEXION_API', 'Rate limited depuis ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        json_out(['success' => false, 'message' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429);
    }

    global $pdo;
    $u = db_user_get_by_login($pdo, $login);

    if ($u && password_verify($mdp, $u['mot_de_passe'])) {
        db_login_attempt_clear($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $login);
        session_regenerate_id(true);
        unset($u['mot_de_passe']);
        $_SESSION['user'] = $u;
        suivre_activite('CONNEXION_API', 'Connexion API réussie: ' . $login);
        json_out([
            'success' => true,
            'message' => 'Connexion réussie.',
            'user'    => $u,
        ]);
    } else {
        db_login_attempt_insert($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $login);
        suivre_activite('ECHEC_CONNEXION_API', 'Tentative échouée: ' . $login);
        json_out(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.'], 401);
    }
}

function handle_logout(): void {
    $csrfToken = input_string($_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($csrfToken) || !hash_equals(csrf_token(), $csrfToken)) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }

    if (!est_connecte()) {
        json_out(['success' => false, 'message' => 'Déjà déconnecté.'], 401);
    }
    $login = user_courant()['login'] ?? 'unknown';
    suivre_activite('DECONNEXION_API', 'Déconnexion API: ' . $login);

    $_SESSION = [];
    session_regenerate_id(true);
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    json_out(['success' => true, 'message' => 'Déconnecté.']);
}

function handle_me(): void {
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé. Connectez-vous.'], 401);
    }
    $user = user_courant();
    unset($user['mot_de_passe']);
    json_out(['user' => $user]);
}

// ============================================================
//  ARTICLES HANDLERS
// ============================================================

function handle_articles_list(): void {
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé. Connectez-vous.'], 401);
    }
    if (!peut('articles_consulter')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }

    global $pdo;
    try {
        $search    = input_string($_GET['q'] ?? '');
        $limit     = min(max((int)($_GET['limit'] ?? 100), 1), 500);
        $search_sql = db_articles_search_sql($search, user_magasin_id());
        $search_sql['sql'] .= " LIMIT $limit";
        $stmt = $pdo->prepare($search_sql['sql']);
        $stmt->execute($search_sql['params']);
        $articles = $stmt->fetchAll();

        foreach ($articles as &$a) {
            $a['edit_url'] = generate_signed_url('articles.php', (int)$a['id'], ['action' => 'editer']);
        }
        unset($a);

        json_out([
            'data'  => $articles,
            'total' => count($articles),
        ]);
    } catch (\Throwable $e) {
        error_log('API articles list error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de la récupération des articles.'], 500);
    }
}

function handle_article_create(): void {
    exiger_permission_api('articles_gerer');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $code_barre    = input_string($input['code_barre'] ?? '');
    $nom           = input_string($input['nom'] ?? '');
    $sku           = input_string($input['sku'] ?? '');

    if ($code_barre === '' || $nom === '') {
        json_out(['error' => 'Les champs code_barre et nom sont obligatoires.'], 422);
    }

    global $pdo;

    if (db_article_code_barre_exists($pdo, $code_barre)) {
        json_out(['error' => 'Ce code-barres existe déjà.'], 409);
    }

    $data = [
        'code_barre'     => $code_barre,
        'nom'            => $nom,
        'sku'            => $sku,
        'prix_achat'     => (float)($input['prix_achat'] ?? 0),
        'prix_vente'     => (float)($input['prix_vente'] ?? 0),
        'quantite_stock' => (int)($input['quantite_stock'] ?? 0),
        'seuil_alerte'   => (int)($input['seuil_alerte'] ?? 5),
        'emplacement'    => input_string($input['emplacement'] ?? ''),
        'fournisseur_id' => (int)($input['fournisseur_id'] ?? 0) ?: null,
    ];

    try {
        $pdo->beginTransaction();
        $new_id = db_article_insert($pdo, $data);
        // db_article_insert() initialise déjà le stock via db_stock_magasin_init_for_article().
        // Aucun mouvement 'ENTREE' supplémentaire ne doit être créé ici.
        $pdo->commit();
        suivre_activite('MODIFICATION_ARTICLE', 'Création article #' . $new_id . ' via API — ' . $nom);
        json_out(['success' => true, 'id' => $new_id, 'message' => 'Article créé.'], 201);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('API article create error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de la création.'], 500);
    }
}

function handle_article_update(int $id): void {
    exiger_permission_api('articles_gerer');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }

    global $pdo;
    $existing = db_article_get_by_id($pdo, $id);
    if (!$existing) {
        json_out(['error' => 'Article introuvable.'], 404);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $code_barre = input_string($input['code_barre'] ?? $existing['code_barre']);
    $nom        = input_string($input['nom'] ?? $existing['nom']);

    if ($code_barre === '' || $nom === '') {
        json_out(['error' => 'Les champs code_barre et nom sont obligatoires.'], 422);
    }

    if (db_article_code_barre_exists($pdo, $code_barre, $id)) {
        json_out(['error' => 'Ce code-barres existe déjà pour un autre article.'], 409);
    }

    $data = [
        'code_barre'     => $code_barre,
        'nom'            => $nom,
        'sku'            => input_string($input['sku'] ?? $existing['sku']),
        'prix_achat'     => (float)($input['prix_achat'] ?? $existing['prix_achat']),
        'prix_vente'     => (float)($input['prix_vente'] ?? $existing['prix_vente']),
        'quantite_stock' => (int)($input['quantite_stock'] ?? $existing['quantite_stock']),
        'seuil_alerte'   => (int)($input['seuil_alerte'] ?? $existing['seuil_alerte']),
        'emplacement'    => input_string($input['emplacement'] ?? $existing['emplacement']),
        'fournisseur_id' => isset($input['fournisseur_id']) ? ((int)$input['fournisseur_id'] ?: null) : $existing['fournisseur_id'],
    ];

    try {
        db_article_update($pdo, $id, $data);
        suivre_activite('MODIFICATION_ARTICLE', 'Modification article #' . $id . ' via API — ' . $nom);
        json_out(['success' => true, 'message' => 'Article mis à jour.']);
    } catch (\Throwable $e) {
        error_log('API article update error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de la mise à jour.'], 500);
    }
}

function handle_article_delete(int $id): void {
    exiger_permission_api('articles_gerer');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }

    global $pdo;
    $existing = db_article_get_by_id($pdo, $id);
    if (!$existing) {
        json_out(['error' => 'Article introuvable.'], 404);
    }

    db_article_deactivate($pdo, $id);
    suivre_activite('SUPPRESSION_ARTICLE', 'Désactivation article #' . $id . ' via API');
    json_out(['success' => true, 'message' => 'Article désactivé.']);
}

// ============================================================
//  SCAN (code-barres)
// ============================================================

function handle_scan(): void {
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé. Connectez-vous.'], 401);
    }
    if (!peut('caisse_gerer') && !peut('ventes_consulter')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }

    $code = input_string($_GET['code'] ?? '');
    if ($code === '') {
        json_out(['found' => false, 'message' => 'Paramètre "code" requis.'], 422);
    }

    global $pdo;
    $u           = user_courant();
    $magasin_id  = user_magasin_id();
    $article     = db_article_get_by_barcode($pdo, $code, $magasin_id);

    if (!$article) {
        json_out([
            'found'   => false,
            'code'    => $code,
            'message' => 'Aucun article trouvé pour ce code-barres.',
        ]);
    }

    $response = [];
    $alerte = '';
    if ((int)$article['quantite_stock'] <= 0) {
        $alerte = 'Stock épuisé !';
    } elseif ((int)$article['quantite_stock'] <= (int)$article['seuil_alerte']) {
        $alerte = 'Stock faible (' . (int)$article['quantite_stock'] . ' restant).';
    }

    // Blocage des lots périmés : si le lot FEFO vendable le plus proche est
    // périmé (== tous les lots en stock sont périmés), le scan est refusé
    // comme le fera la validation finale (db_deduire_stock_lot).
    if ($magasin_id > 0 && isset($article['id'])) {
        $lot_fefo = db_lot_get_fefo($pdo, (int)$article['id'], $magasin_id);
        if ($lot_fefo && !empty($lot_fefo['date_peremption'])) {
            $date_peremption = new DateTime((string)$lot_fefo['date_peremption']);
            $aujourd_hui = new DateTime('today');
            $jours = (int)$aujourd_hui->diff($date_peremption)->days;

            if ($date_peremption < $aujourd_hui) {
                json_out([
                    'found'   => false,
                    'code'    => $code,
                    'message' => 'Article périmé — vente bloquée (lot ' . $lot_fefo['numero_lot'] . ' périmé depuis ' . $jours . ' jour(s)).',
                ]);
            }
            if ($jours <= 7) {
                $response['alerte_peremption'] = [
                    'numero_lot'     => $lot_fefo['numero_lot'],
                    'date_peremption'=> $lot_fefo['date_peremption'],
                    'jours_restants' => $jours,
                    'statut'         => 'urgent',
                    'message'        => '⚠ Lot expire dans ' . $jours . ' jour(s) !',
                ];
            }
        } elseif (!$lot_fefo) {
            // db_lot_get_fefo() exclut les lots périmés : si aucun lot actif,
            // vérifier la présence de lots périmés restants (ancienne alerte
            // non bloquante remplacée par un blocage, cohérent avec la validation).
            $stmt_expire = $pdo->prepare(
                "SELECT numero_lot, date_peremption FROM article_lots
                 WHERE article_id = ? AND magasin_id = ? AND quantite > 0
                   AND date_peremption IS NOT NULL AND date_peremption < CURDATE()
                 ORDER BY date_peremption ASC LIMIT 1"
            );
            $stmt_expire->execute([(int)$article['id'], $magasin_id]);
            $lot_expire = $stmt_expire->fetch();
            if ($lot_expire) {
                $date_expire = new DateTime((string)$lot_expire['date_peremption']);
                $jours_expire = (int)(new DateTime('today'))->diff($date_expire)->days;
                json_out([
                    'found'   => false,
                    'code'    => $code,
                    'message' => 'Article périmé — vente bloquée (lot ' . $lot_expire['numero_lot'] . ' périmé depuis ' . $jours_expire . ' jour(s)).',
                ]);
            }
        }
    }

    $promotion = db_calculer_prix_article($pdo, (int)$article['id'], $magasin_id);
    $prix_final = (float)$article['prix_vente'];
    if ($promotion) {
        $prix_final = $promotion['prix_remise'];
    }

    // Prix fournisseur actuel pour tarification dynamique
    $prix_fournisseur = db_prix_fournisseur_ref($pdo, (int)$article['id']);
    $prix_fournisseur_actuel = $prix_fournisseur ? (float)$prix_fournisseur['prix_achat'] : (float)($article['prix_achat'] ?? 0);
    $fournisseur_prix_ref_id = $prix_fournisseur ? (int)$prix_fournisseur['fournisseur_id'] : null;

    // Calculer les prix par tranches pour affichage
    $tranches_prix = db_prix_par_tranches($pdo, $prix_fournisseur_actuel, (int)$article['id'], $article['categorie_id'] ?? null);

    $response['found']   = true;
    $response['article'] = [
        'id'            => (int)$article['id'],
        'code_barre'    => $article['code_barre'],
        'nom'           => $article['nom'],
        'prix_unitaire' => $prix_final,
        'prix_display'  => money($prix_final),
        'stock_dispo'   => (int)$article['quantite_stock'],
        'taux_tva'      => ($article['taux_tva'] ?? null) !== null && $article['taux_tva'] !== '' ? (float)$article['taux_tva'] : null,
        'unite_mesure'  => $article['unite_mesure'] ?? 'UNITE',
        'vente_au_poids'=> (bool)($article['vente_au_poids'] ?? 0),
        'poids_precision' => (int)($article['poids_precision'] ?? 3),
        'alerte'        => $alerte,
        'prix_fournisseur_actuel' => $prix_fournisseur_actuel,
        'fournisseur_prix_ref_id' => $fournisseur_prix_ref_id,
        'tranches_prix'  => $tranches_prix,
        'categorie_id'   => $article['categorie_id'] ?? null,
    ];

    if ($promotion) {
        $response['article']['promotion'] = [
            'prix_original' => $promotion['prix_original'],
            'prix_remise'   => $promotion['prix_remise'],
            'remise_pct'    => $promotion['remise_pct'],
            'label'         => $promotion['label'],
        ];
    }

    json_out($response);
}

// ============================================================
//  CAISSE HORS-LIGNE — Synchronisation de ventes
// ============================================================

function handle_caisse_sync(): void {
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé. Connectez-vous.'], 401);
    }
    if (!peut('caisse_gerer')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $clientSaleId = input_string($input['client_sale_id'] ?? '');
    $lignes       = $input['lignes'] ?? [];
    if (!is_array($lignes)) {
        $lignes = [];
    }
    $lignes = array_values(array_filter($lignes, 'is_array'));
    $totalTtc     = (float)($input['total_ttc'] ?? 0);
    $montantPaye  = (float)($input['montant_paye'] ?? 0);
    $magasinId    = (int)($input['magasin_id'] ?? 0);
    $userId       = (int)($input['user_id'] ?? 0);
    $clientId     = (int)($input['client_id'] ?? 0);
    $pointsUtilises = (int)($input['points_utilises'] ?? 0);
    $modeVente    = input_string($input['mode_vente'] ?? 'comptoir');

    // Valider que le magasin soumis correspond au magasin de l'utilisateur authentifié
    $userMagasinId = user_magasin_id();
    if ($userMagasinId > 0 && $magasinId !== $userMagasinId) {
        json_out(['success' => false, 'error' => 'Magasin non autorisé.'], 403);
    }

    if ($clientSaleId === '' || empty($lignes) || $montantPaye <= 0) {
        json_out(['success' => false, 'error' => 'Données invalides.'], 422);
    }

    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $clientSaleId)) {
        json_out(['success' => false, 'error' => 'Format client_sale_id invalide.'], 422);
    }

    global $pdo;

    // Ne pas synchroniser une vente si la caisse est clôturée pour aujourd'hui
    if ($magasinId > 0 && db_cloture_deja_ferme($pdo, $magasinId, (int)(user_courant()['id'] ?? 0))) {
        json_out(['success' => false, 'error' => 'La caisse est déjà clôturée pour aujourd\'hui. Vente non synchronisée.'], 409);
    }

    $stmt = $pdo->prepare("SELECT id FROM factures WHERE client_sale_id = ? LIMIT 1");
    $stmt->execute([$clientSaleId]);
    if ($row = $stmt->fetch()) {
        json_out([
            'success'        => true,
            'already_synced' => true,
            'facture_id'     => (int)$row['id'],
            'message'        => 'Vente déjà synchronisée.',
        ]);
    }

    try {
        $pdo->beginTransaction();

        // Fidélité : valider le client et verrouiller son solde (FOR UPDATE)
        $loyautes = null;
        if ($clientId > 0) {
            $client = db_client_get_by_id($pdo, $clientId);
            if (!$client) {
                throw new RuntimeException('Client fidélité introuvable.');
            }
            if ((int)$client['consentement_fidelite'] !== 1) {
                throw new RuntimeException('Le client n\'a pas consenti au programme de fidélité.');
            }
            $loyautes = db_loyalite_verrouiller($pdo, $clientId, $pointsUtilises, $totalTtc);
        }

        $numero = generate_invoice_number($pdo);
        $tauxTva = param_tva_taux();
        $totalHt = 0.0;
        $lignesValides = [];

        foreach ($lignes as $l) {
            $articleId    = (int)($l['article_id'] ?? 0);
            $quantite     = (int)($l['quantite'] ?? 0);
            $quantitePoids = isset($l['quantite_poids']) && $l['quantite_poids'] !== '' ? (float)$l['quantite_poids'] : null;

            if ($articleId <= 0 || $quantite <= 0) continue;

            $art = db_article_get_for_update($pdo, $articleId, $magasinId);
            if (!$art) {
                throw new RuntimeException('Article #' . $articleId . ' introuvable.');
            }

            // SEC-13 : Vérifier que l'article a du stock dans CE magasin
            $stmt_stock = $pdo->prepare("SELECT quantite FROM stock_magasins WHERE article_id = ? AND magasin_id = ?");
            $stmt_stock->execute([$articleId, $magasinId]);
            $stock_magasin = (int)($stmt_stock->fetchColumn() ?: 0);
            if ($stock_magasin < $quantite) {
                throw new RuntimeException('Stock insuffisant pour ' . $art['nom'] . ' dans ce magasin.');
            }

            // Articles sans lot (non périssables / vieux stocks) : initialiser un lot permanent
            $stmt_check_lot = $pdo->prepare("SELECT COUNT(*) FROM article_lots WHERE article_id = ? AND magasin_id = ?");
            $stmt_check_lot->execute([$articleId, $magasinId]);
            if ((int)$stmt_check_lot->fetchColumn() === 0) {
                $pdo->prepare(
                    "INSERT IGNORE INTO article_lots (article_id, magasin_id, numero_lot, quantite, date_peremption)
                     VALUES (?, ?, 'LOT-GENERAL', ?, NULL)"
                )->execute([$articleId, $magasinId, $stock_magasin]);
            }

            $prixUnitaire = (float)$art['prix_vente'];
            $prixOriginal = (float)$art['prix_vente'];
            $remisePct = null;
            $promo = db_calculer_prix_article($pdo, $articleId, $magasinId);
            if ($promo) {
                $prixUnitaire = $promo['prix_remise'];
                $prixOriginal = $promo['prix_original'];
                $remisePct = $promo['remise_pct'];
            }
            // Taux TVA effectif : spécifique à l'article si défini, sinon taux global
            // (même logique que valider_facture.php pour une cohérence en ligne/hors-ligne)
            $tauxTvaLigne = ($art['taux_tva'] !== null && $art['taux_tva'] !== '')
                ? (float)$art['taux_tva']
                : $tauxTva;
            // Sous-total : quantité facturable = pesée pour la vente au poids
            $quantiteFacturable = $quantitePoids !== null ? $quantitePoids : $quantite;
            $sousTotal = calc_line_subtotal($prixUnitaire, $quantiteFacturable);
            $totalHt += $sousTotal;

            $lignesValides[] = compact('articleId', 'quantite', 'prixUnitaire', 'prixOriginal', 'remisePct', 'tauxTvaLigne', 'sousTotal', 'quantitePoids');
        }

        if (empty($lignesValides)) {
            throw new RuntimeException('Aucune ligne valide dans la vente.');
        }

        // Calcul TTC multi-taux : somme des TTC par ligne (identique à valider_facture.php)
        $totalTtcCalc = 0.0;
        foreach ($lignesValides as $lv) {
            $totalTtcCalc += $lv['sousTotal'] * (1 + $lv['tauxTvaLigne'] / 100);
        }
        $totalTtcCalc = round($totalTtcCalc, 2);
        $tauxTvaDominant = $tauxTva;
        if (count($lignesValides) === 1) {
            $tauxTvaDominant = $lignesValides[0]['tauxTvaLigne'];
        }

        // Remise fidélité (points) : à déduire du total TTC
        if ($loyautes !== null) {
            $totalTtcCalc = round($totalTtcCalc - $loyautes['remise'], 2);
        }

        $monnaieRendue = max(0.0, $montantPaye - $totalTtcCalc);

        // NOU-5 : Vérifier que le total_ttc envoyé correspond au calculé (tolérance 0.02)
        if (abs($totalTtc - $totalTtcCalc) > 0.02) {
            throw new RuntimeException('Le total TTC ne correspond pas au calculé (remise fidélité incluse).');
        }

        $isCredit = ($modeVente === 'credit');
        if ($isCredit) {
            if ($clientId <= 0) {
                throw new RuntimeException('La selection d\'un client est obligatoire pour une vente a credit.');
            }
            $clientCredit = db_client_get_by_id($pdo, $clientId);
            if (!$clientCredit || !$clientCredit['credit_autorise']) {
                throw new RuntimeException('Ce client n\'est pas autorise pour les achats a credit.');
            }
            $montantCredit = round($totalTtcCalc - $montantPaye, 2);
            if ($montantCredit > 0) {
                $limitCheck = db_credit_check_limit($pdo, $clientId, $montantCredit);
                if (!$limitCheck['ok'] && !peut('credit_override_limit')) {
                    throw new RuntimeException($limitCheck['message']);
                }
            }
        } else {
            if ($montantPaye < round($totalTtcCalc, 2)) {
                throw new RuntimeException('Le montant paye est insuffisant.');
            }
        }
        $monnaieRendue = $isCredit ? 0.0 : max(0.0, $montantPaye - $totalTtcCalc);

        $factureData = [
            'numero_facture' => $numero,
            'utilisateur_id' => user_courant()['id'] ?? null,
            'total_ht'       => round($totalHt, 2),
            'tva_taux'       => round($tauxTvaDominant, 2),
            'total_ttc'      => $totalTtcCalc,
            'montant_paye'   => round($montantPaye, 2),
            'monnaie_rendue' => round($monnaieRendue, 2),
            'magasin_id'     => $magasinId,
            'remise_fidelite'=> $loyautes !== null ? $loyautes['remise'] : 0.0,
            'points_utilises'=> $loyautes !== null ? $loyautes['points'] : 0,
        ];
        if ($isCredit) {
            $reste = round($totalTtcCalc - $montantPaye, 2);
            $factureData['statut_paiement'] = $reste > 0 ? 'En_Attente' : 'Payee';
            $factureData['reste_a_payer'] = $reste;
        }
        $factureId = db_facture_insert($pdo, $factureData);

        if ($loyautes !== null || $clientId > 0) {
            $pdo->prepare("UPDATE factures SET client_id = ? WHERE id = ?")
                ->execute([$clientId, $factureId]);
        }

        $pdo->prepare("UPDATE factures SET client_sale_id = ? WHERE id = ?")
            ->execute([$clientSaleId, $factureId]);

        foreach ($lignesValides as $lv) {
            db_ligne_facture_insert($pdo, $factureId, $lv['articleId'], $lv['quantite'], $lv['prixUnitaire'], $lv['prixOriginal'], $lv['remisePct'], $lv['tauxTvaLigne'] ?? null, $lv['quantitePoids'] ?? null);
            $lots_decrementes = db_deduire_stock_lot($pdo, $lv['articleId'], $magasinId, $lv['quantite'], $numero);
            
            $lot_parts = [];
            foreach ($lots_decrementes as $ld) {
                $lot_parts[] = "Lot {$ld['numero_lot']}: -{$ld['quantite_prise']}";
            }
            $lots_info = $lot_parts ? ' [' . implode(', ', $lot_parts) . ']' : '';

            db_mouvement_insert(
                $pdo,
                $lv['articleId'],
                user_courant()['id'] ?? null,
                'VENTE',
                $lv['quantite'],
                'Vente hors-ligne ' . $numero . $lots_info,
                $magasinId
            );
        }

        // Enregistrer les paiements multi-modes (même logique que valider_facture.php)
        if (!empty($input['paiements_json'])) {
            $paiementsList = json_decode(input_string($input['paiements_json']), true);
            if (is_array($paiementsList) && !empty($paiementsList)) {
                db_paiements_insert($pdo, $factureId, array_values(array_filter($paiementsList, 'is_array')));
            }
        }

        // Creer la creance si vente a credit
        if ($isCredit && $reste > 0) {
            $echeance = date('Y-m-d', strtotime('+30 days'));
            db_creance_insert($pdo, $factureId, $clientId, round($totalTtcCalc, 2), $echeance, null);
        }

        // Chaînage cryptographique des factures (après lignes + paiements)
        db_facture_chainer($pdo, $factureId);

        // Consommation des points fidélité (solde verrouillé FOR UPDATE)
        if ($loyautes !== null && $loyautes['points'] > 0) {
            $pdo->prepare("UPDATE clients SET points_fidelite = points_fidelite - ? WHERE id = ?")
                ->execute([$loyautes['points'], $clientId]);
            // facture_id NULL : 1 ligne GAIN par facture (clé unique), l'utilisation
            // est tracée sur la facture via factures.points_utilises.
            $pdo->prepare(
                "INSERT INTO historique_points (client_id, facture_id, points, type_operation, commentaire, utilisateur_id)
                 VALUES (?, NULL, ?, 'UTILISATION', 'Utilisation points en caisse (synchro)', ?)"
            )->execute([$clientId, -$loyautes['points'], user_id()]);
        }

        $pdo->commit();

        // Attribution des points gagnés (transaction dédiée, idempotente par facture)
        $pointsGagnes = 0;
        if ($loyautes !== null) {
            $pointsGagnes = db_client_attribuer_points($pdo, $clientId, $factureId, $totalTtcCalc);
        }

        json_out([
            'success'        => true,
            'facture_id'     => $factureId,
            'numero'         => $numero,
            'client_sale_id' => $clientSaleId,
            'client_id'      => $clientId > 0 ? $clientId : null,
            'remise_fidelite'=> $loyautes !== null ? $loyautes['remise'] : 0.0,
            'points_utilises'=> $loyautes !== null ? $loyautes['points'] : 0,
            'points_gagnes'  => $pointsGagnes,
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('API caisse sync error: ' . $e->getMessage());
        if ($e instanceof RuntimeException) {
            json_out(['success' => false, 'error' => 'Erreur lors de la synchronisation : données invalides.'], 409);
        }
        json_out(['success' => false, 'error' => 'Erreur lors de la synchronisation de la vente.'], 500);
    }
}

// ============================================================
//  ÉTAT DE LA CAISSE
// ============================================================

function handle_caisse_status(): void {
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé.'], 401);
    }
    if (!peut('caisse_gerer')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }

    global $pdo;
    $u = user_courant();
    $magasin_id = user_magasin_id();
    $user_id = (int)($u['id'] ?? 0);

    $ferme = db_cloture_deja_ferme($pdo, $magasin_id, $user_id);
    $montant_attendu = $ferme ? 0.0 : db_calculer_ventes_du_jour($pdo, $magasin_id, $user_id);
    $nb_ventes = $ferme ? 0 : db_compter_ventes_du_jour($pdo, $magasin_id, $user_id);

    json_out([
        'ferme'            => $ferme,
        'montant_attendu'  => $montant_attendu,
        'nb_ventes'        => $nb_ventes,
    ]);
}

// ============================================================
//  GENERATEUR CODE-BARRES EAN-13
// ============================================================

function handle_generer_code_barre(): void {
    global $pdo;
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé.'], 401);
    }
    if (!peut('articles_gerer') && !peut('articles_consulter')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }
    try {
        $code = api_generer_code_barre($pdo);
        json_out(['code_barre' => $code]);
    } catch (\Throwable $e) {
        json_out(['error' => 'Erreur lors de la génération.'], 500);
    }
}

// ============================================================
//  CODE PROMO (VERIFICATION CAISSE)
// ============================================================

function handle_promo_check(): void {
    global $pdo;
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé.'], 401);
    }
    if (!peut('caisse_gerer') && !peut('ventes_consulter')) {
        json_out(['error' => 'Accès refusé.'], 403);
    }
    $code  = input_string($_GET['code'] ?? '');
    $total = (float)($_GET['total'] ?? 0);

    if ($code === '') {
        json_out(['valide' => false, 'message' => 'Code promo requis.'], 400);
    }

    $promo = db_promotion_get_by_code($pdo, $code, $total);
    if (!$promo) {
        json_out(['valide' => false, 'message' => 'Code promo invalide, expiré ou montant minimum non atteint.']);
    }

    json_out([
        'valide'         => true,
        'promo_id'       => (int)$promo['id'],
        'nom'            => $promo['nom'],
        'type_reduction' => $promo['type_reduction'],
        'valeur'         => (float)$promo['valeur'],
        'article_id'     => $promo['article_id'] ? (int)$promo['article_id'] : null,
    ]);
}

// ============================================================
//  CLIENTS FIDÉLITÉ — RECHERCHE POUR LA CAISSE
// ============================================================

function handle_clients_search(): void {
    global $pdo;
    if (!est_connecte()) {
        json_out(['error' => 'Non autorisé.'], 401);
    }
    if (!peut('clients_consulter')) {
        json_out(['error' => 'Accès refusé. Droits insuffisants.'], 403);
    }

    $q = input_string($_GET['q'] ?? '');
    $trie = input_string($_GET['tri'] ?? ''); // 'code' = scan d'une carte de fidélité

    $clients = [];
    if ($trie === 'code' && $q !== '') {
        $client = db_client_get_by_code($pdo, $q);
        if ($client) $clients = [$client];
    } elseif ($q !== '') {
        $sql = db_clients_search_sql(['search' => $q]);
        $sql['sql'] .= ' LIMIT 10';
        $st = $pdo->prepare($sql['sql']);
        $st->execute($sql['params']);
        $clients = $st->fetchAll();
    }

    $resultat = [];
    foreach ($clients as $c) {
        $resultat[] = [
            'id'                  => (int)$c['id'],
            'nom'                 => $c['nom'] ?: $c['raison_sociale'],
            'raison_sociale'      => $c['raison_sociale'],
            'nif'                 => $c['nif'] ?? '',
            'rccm'                => $c['rccm'] ?? '',
            'telephone'           => $c['telephone'],
            'email'               => $c['email'],
            'code_fidelite'       => $c['code_fidelite'],
            'points_fidelite'     => (int)$c['points_fidelite'],
            'consentement_fidelite' => (int)$c['consentement_fidelite'] === 1,
        ];
    }

    json_out(['clients' => $resultat]);
}

// ============================================================
//  PRIX FOURNISSEUR HANDLERS
// ============================================================

function handle_prix_fournisseur_article(int $article_id): void {
    exiger_permission_api('prix_fournisseur_consulter');
    global $pdo;
    $historique = db_fournisseur_prix_historique($pdo, $article_id);
    $actuel = db_prix_fournisseur_ref($pdo, $article_id);
    json_out(['prix_actuel' => $actuel, 'historique' => $historique]);
}

function handle_prix_fournisseur_par_fournisseur(int $fournisseur_id): void {
    exiger_permission_api('prix_fournisseur_consulter');
    global $pdo;
    $historique = db_fournisseur_prix_historique_par_fournisseur($pdo, $fournisseur_id);
    json_out(['historique' => $historique]);
}

function handle_prix_fournisseur_set(): void {
    exiger_permission_api('prix_fournisseur_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $article_id = (int)($data['article_id'] ?? 0);
    $fournisseur_id = (int)($data['fournisseur_id'] ?? 0);
    $prix = (float)($data['prix_achat'] ?? 0);
    if ($article_id <= 0 || $fournisseur_id <= 0 || $prix < 0) {
        json_out(['error' => 'Paramètres invalides.'], 422);
    }
    $id = db_fournisseur_prix_set($pdo, $article_id, $fournisseur_id, $prix, 'manuelle', null, user_id());
    suivre_activite('PRIX_FOURNISSEUR_MODIFIE', "Article #$article_id, fournisseur #$fournisseur_id → $prix");
    json_out(['success' => true, 'id' => $id]);
}

function handle_prix_fournisseur_calculer(): void {
    exiger_permission_api('articles_consulter');
    global $pdo;
    $article_id = (int)($_GET['article_id'] ?? 0);
    $quantite = (int)($_GET['quantite'] ?? 1);
    $magasin_id = (int)($_GET['magasin_id'] ?? user_magasin_id());
    if ($article_id <= 0) json_out(['error' => 'article_id requis.'], 422);
    $resultat = db_calculer_prix_vente($pdo, $article_id, $quantite, $magasin_id);
    json_out($resultat);
}

// ============================================================
//  TRANCHES TARIFAIRES HANDLERS
// ============================================================

function handle_tranches_list(): void {
    exiger_permission_api('tarification_consulter');
    global $pdo;
    $article_id = !empty($_GET['article_id']) ? (int)$_GET['article_id'] : null;
    $categorie_id = !empty($_GET['categorie_id']) ? (int)$_GET['categorie_id'] : null;
    $tranches = db_tranches_tarifaires_list($pdo, $article_id, $categorie_id, !empty($_GET['actifs_only']));
    json_out(['tranches' => $tranches]);
}

function handle_tranche_get(int $id): void {
    exiger_permission_api('tarification_consulter');
    global $pdo;
    $tranche = db_tranche_get_by_id($pdo, $id);
    if (!$tranche) json_out(['error' => 'Tranche introuvable.'], 404);
    json_out(['tranche' => $tranche]);
}

function handle_tranche_create(): void {
    exiger_permission_api('tarification_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($data['nom'])) json_out(['error' => 'Nom requis.'], 422);
    $id = db_tranche_insert($pdo, $data);
    suivre_activite('TRANCHE_CREE', 'Tranche #' . $id . ' : ' . ($data['nom'] ?? ''));
    json_out(['success' => true, 'id' => $id]);
}

function handle_tranche_update(int $id): void {
    exiger_permission_api('tarification_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    db_tranche_update($pdo, $id, $data);
    suivre_activite('TRANCHE_MODIFIEE', 'Tranche #' . $id);
    json_out(['success' => true]);
}

function handle_tranche_delete(int $id): void {
    exiger_permission_api('tarification_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    db_tranche_delete($pdo, $id);
    suivre_activite('TRANCHE_SUPPRIMEE', 'Tranche #' . $id);
    json_out(['success' => true]);
}

// ============================================================
//  RÉCEPTIONS HANDLERS
// ============================================================

function handle_reception_get(int $id): void {
    exiger_permission_api('receptions_consulter');
    global $pdo;
    $reception = db_reception_get_by_id($pdo, $id);
    if (!$reception) json_out(['error' => 'Réception introuvable.'], 404);
    $lignes = db_reception_lignes($pdo, $id);
    json_out(['reception' => $reception, 'lignes' => $lignes]);
}

function handle_receptions_par_commande(int $commande_id): void {
    exiger_permission_api('receptions_consulter');
    global $pdo;
    $receptions = db_receptions_par_commande($pdo, $commande_id);
    foreach ($receptions as &$r) {
        $r['lignes'] = db_reception_lignes($pdo, (int)$r['id']);
    }
    json_out(['receptions' => $receptions]);
}

function handle_reception_create(): void {
    exiger_permission_api('receptions_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($data['commande_id']) || empty($data['fournisseur_id']) || empty($data['magasin_id'])) {
        json_out(['error' => 'commande_id, fournisseur_id, magasin_id requis.'], 422);
    }
    $lignes = $data['lignes'] ?? [];
    if (empty($lignes)) {
        json_out(['error' => 'Au moins une ligne requise.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $reception_id = db_reception_insert($pdo, [
            'commande_id'    => $data['commande_id'],
            'fournisseur_id' => $data['fournisseur_id'],
            'magasin_id'     => $data['magasin_id'],
            'commentaire'    => $data['commentaire'] ?? null,
        ]);

        foreach ($lignes as $l) {
            db_reception_ligne_insert($pdo, [
                'reception_id'        => $reception_id,
                'ligne_commande_id'   => $l['ligne_commande_id'],
                'article_id'          => $l['article_id'],
                'quantite_attendue'   => $l['quantite_attendue'] ?? 0,
                'quantite_recue'      => $l['quantite_recue'] ?? 0,
                'quantite_acceptee'   => $l['quantite_acceptee'] ?? 0,
                'quantite_perdue'     => $l['quantite_perdue'] ?? 0,
                'prix_achat_unitaire' => $l['prix_achat_unitaire'] ?? 0,
                'numero_lot'          => $l['numero_lot'] ?? null,
                'date_peremption'     => $l['date_peremption'] ?? null,
                'motif_perte'         => $l['motif_perte'] ?? null,
                'commentaire_perte'   => $l['commentaire_perte'] ?? null,
            ]);
        }

        $pdo->commit();
        suivre_activite('RECEPTION_CREEE', 'Réception #' . $reception_id . ' pour commande #' . $data['commande_id']);
        json_out(['success' => true, 'id' => $reception_id]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_out(['error' => 'Erreur création réception: données invalides.'], 500);
    }
}

function handle_reception_valider(int $id): void {
    exiger_permission_api('receptions_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    try {
        $ok = db_reception_valider($pdo, $id, user_id());
        if (!$ok) json_out(['error' => 'Réception non trouvée ou statut invalide.'], 422);
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('API reception validation error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de la validation de la réception.'], 500);
    }
}

function handle_reception_annuler(int $id): void {
    exiger_permission_api('receptions_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $ok = db_reception_annuler($pdo, $id, $data['motif'] ?? '');
    if (!$ok) json_out(['error' => 'Réception non trouvée ou statut invalide.'], 422);
    json_out(['success' => true]);
}

// ============================================================
//  PERTES HANDLERS
// ============================================================

function handle_pertes_list(): void {
    exiger_permission_api('pertes_consulter');
    global $pdo;
    $fournisseur_id = !empty($_GET['fournisseur_id']) ? (int)$_GET['fournisseur_id'] : null;
    $magasin_id = !empty($_GET['magasin_id']) ? (int)$_GET['magasin_id'] : null;
    $date_debut = $_GET['date_debut'] ?? null;
    $date_fin = $_GET['date_fin'] ?? null;
    $pertes = db_pertes_list($pdo, $fournisseur_id, $magasin_id, $date_debut, $date_fin);
    json_out(['pertes' => $pertes]);
}

// ============================================================
//  MATIÈRES PREMIÈRES HANDLERS
// ============================================================

function handle_matieres_premieres_list(): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $matieres = db_matiere_premiere_list($pdo);
    json_out(['data' => $matieres]);
}

function handle_matiere_premiere_create(): void {
    exiger_permission_api('usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $data = [
        'nom' => trim($input['nom'] ?? ''),
        'reference' => trim($input['reference'] ?? ''),
        'unite_mesure' => trim($input['unite_mesure'] ?? 'KG'),
        'categorie_id' => !empty($input['categorie_id']) ? (int)$input['categorie_id'] : null,
        'cout_reference' => (float)($input['cout_reference'] ?? 0),
        'stock_minimum' => (int)($input['stock_minimum'] ?? 10),
        'fournisseur_id' => !empty($input['fournisseur_id']) ? (int)$input['fournisseur_id'] : null,
    ];
    if (empty($data['nom'])) {
        json_out(['error' => 'Le nom est requis.'], 422);
    }
    $id = db_matiere_premiere_insert($pdo, $data);
    suivre_activite('MATIERE_PREMIERE_AJOUTEE', "Matière #$id: {$data['nom']}");
    json_out(['success' => true, 'id' => $id], 201);
}

function handle_matiere_premiere_update(int $id): void {
    exiger_permission_api('usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $data = [
        'nom' => trim($input['nom'] ?? ''),
        'reference' => trim($input['reference'] ?? ''),
        'unite_mesure' => trim($input['unite_mesure'] ?? 'KG'),
        'categorie_id' => !empty($input['categorie_id']) ? (int)$input['categorie_id'] : null,
        'cout_reference' => (float)($input['cout_reference'] ?? 0),
        'stock_minimum' => (int)($input['stock_minimum'] ?? 10),
        'fournisseur_id' => !empty($input['fournisseur_id']) ? (int)$input['fournisseur_id'] : null,
        'actif' => isset($input['actif']) ? (int)$input['actif'] : 1,
    ];
    db_matiere_premiere_update($pdo, $id, $data);
    suivre_activite('MATIERE_PREMIERE_MODIFIEE', "Matière #$id");
    json_out(['success' => true]);
}

// ============================================================
//  RECETTES HANDLERS
// ============================================================

function handle_recettes_list(): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $recettes = db_recettes_list($pdo);
    json_out(['data' => $recettes]);
}

function handle_recette_get(int $id): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $recette = db_recette_get($pdo, $id);
    if (!$recette) json_out(['error' => 'Recette introuvable.'], 404);
    json_out(['data' => $recette]);
}

function handle_recette_create(): void {
    exiger_permission_api('usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($input['nom']) || empty($input['article_id'])) {
        json_out(['error' => 'Nom et produit fini requis.'], 422);
    }
    $id = db_recette_insert($pdo, [
        'nom' => trim($input['nom']),
        'article_id' => (int)$input['article_id'],
        'quantite_produite' => (int)($input['quantite_produite'] ?? 100),
        'unite_produit' => trim($input['unite_produit'] ?? 'UNITE'),
        'actif' => (int)($input['actif'] ?? 1),
        'notes' => $input['notes'] ?? null,
        'lignes' => $input['lignes'] ?? [],
    ]);
    suivre_activite('RECETTE_CREEE', "Recette #$id: {$input['nom']}");
    json_out(['success' => true, 'id' => $id], 201);
}

function handle_recette_update(int $id): void {
    exiger_permission_api('usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    db_recette_update($pdo, $id, [
        'nom' => trim($input['nom'] ?? ''),
        'quantite_produite' => (int)($input['quantite_produite'] ?? 100),
        'unite_produit' => trim($input['unite_produit'] ?? 'UNITE'),
        'actif' => (int)($input['actif'] ?? 1),
        'notes' => $input['notes'] ?? null,
        'lignes' => $input['lignes'] ?? [],
    ]);
    suivre_activite('RECETTE_MODIFIEE', "Recette #$id");
    json_out(['success' => true]);
}

function handle_recette_delete(int $id): void {
    exiger_permission_api('usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    db_recette_delete($pdo, $id);
    suivre_activite('RECETTE_SUPPRIMEE', "Recette #$id");
    json_out(['success' => true]);
}

// ============================================================
//  PRODUCTIONS HANDLERS
// ============================================================

function handle_productions_list(): void {
    exiger_permission_api('production_consulter');
    global $pdo;
    $statut = $_GET['statut'] ?? null;
    $productions = db_productions_list($pdo, $statut);
    json_out(['data' => $productions]);
}

function handle_production_get(int $id): void {
    exiger_permission_api('production_consulter');
    global $pdo;
    $prod = db_production_get($pdo, $id);
    if (!$prod) json_out(['error' => 'Production introuvable.'], 404);
    json_out(['data' => $prod]);
}

function handle_production_create(): void {
    exiger_permission_api('production_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($input['article_id']) || empty($input['recette_id']) || empty($input['quantite_prevue'])) {
        json_out(['error' => 'Article, recette et quantité prévue requis.'], 422);
    }
    $id = db_production_insert($pdo, [
        'article_id' => (int)$input['article_id'],
        'recette_id' => (int)$input['recette_id'],
        'recette_version' => (int)($input['recette_version'] ?? 1),
        'quantite_prevue' => (int)$input['quantite_prevue'],
        'date_prevue' => $input['date_prevue'] ?? null,
        'utilisateur_id' => $_SESSION['user']['id'] ?? null,
        'notes' => $input['notes'] ?? null,
    ]);
    suivre_activite('PRODUCTION_CREEE', "Production #$id créée");
    json_out(['success' => true, 'id' => $id], 201);
}

function handle_production_demarrer(int $id): void {
    exiger_permission_api('production_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    try {
        db_production_demarrer($pdo, $id);
        suivre_activite('PRODUCTION_DEMARREE', "Production #$id démarrée");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('API production demarrer error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors du démarrage de la production.'], 422);
    }
}

function handle_production_cloturer(int $id): void {
    exiger_permission_api('production_cloturer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $matieres = $input['matieres'] ?? [];
    $quantite_produite = (int)($input['quantite_produite'] ?? 0);
    $quantite_perdue = (int)($input['quantite_perdue'] ?? 0);
    $pertes = $input['pertes'] ?? [];

    if (empty($matieres)) {
        json_out(['error' => 'Consommation des matières requise.'], 422);
    }

    try {
        db_production_cloturer($pdo, $id, $matieres, $quantite_produite, $quantite_perdue, $pertes);
        suivre_activite('PRODUCTION_TERMINEE', "Production #$id clôturée — $quantite_produite produits, $quantite_perdue pertes");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('API production cloturer error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de la clôture de la production.'], 422);
    }
}

function handle_production_annuler(int $id): void {
    exiger_permission_api('production_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    try {
        db_production_annuler($pdo, $id, $input['motif'] ?? null);
        suivre_activite('PRODUCTION_ANNULEE', "Production #$id annulée");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('API production annuler error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors de l\'annulation de la production.'], 422);
    }
}

function handle_production_set_employes(int $id): void {
    exiger_permission_api('production_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $employe_ids = array_map('intval', $input['employe_ids'] ?? []);
    db_production_set_employes($pdo, $id, $employe_ids);
    json_out(['success' => true]);
}

// ============================================================
//  EMPLOYÉS HANDLERS
// ============================================================

function handle_employes_list(): void {
    exiger_permission_api('personnel_consulter');
    global $pdo;
    $employes = db_employes_list($pdo);
    json_out(['data' => $employes]);
}

function handle_employe_create(): void {
    exiger_permission_api('personnel_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($input['matricule']) || empty($input['nom'])) {
        json_out(['error' => 'Matricule et nom requis.'], 422);
    }
    $id = db_employe_insert($pdo, [
        'matricule' => trim($input['matricule']),
        'nom' => trim($input['nom']),
        'prenom' => trim($input['prenom'] ?? ''),
        'fonction' => trim($input['fonction'] ?? ''),
        'telephone' => trim($input['telephone'] ?? ''),
        'actif' => (int)($input['actif'] ?? 1),
    ]);
    suivre_activite('EMPLOYE_AJOUTE', "Employé #$id: {$input['nom']}");
    json_out(['success' => true, 'id' => $id], 201);
}

function handle_employe_update(int $id): void {
    exiger_permission_api('personnel_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    db_employe_update($pdo, $id, [
        'matricule' => trim($input['matricule'] ?? ''),
        'nom' => trim($input['nom'] ?? ''),
        'prenom' => trim($input['prenom'] ?? ''),
        'fonction' => trim($input['fonction'] ?? ''),
        'telephone' => trim($input['telephone'] ?? ''),
        'actif' => isset($input['actif']) ? (int)$input['actif'] : 1,
    ]);
    suivre_activite('EMPLOYE_MODIFIE', "Employé #$id modifié");
    json_out(['success' => true]);
}

// ============================================================
//  PRÉSENCES HANDLERS
// ============================================================

function handle_presences_list(): void {
    exiger_permission_api('presence_consulter');
    global $pdo;
    $date = $_GET['date'] ?? date('Y-m-d');
    $presences = db_presences_list_date($pdo, $date);
    json_out(['data' => $presences]);
}

function handle_presence_upsert(): void {
    exiger_permission_api('presence_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input['employe_id']) || empty($input['date_presence'])) {
        json_out(['error' => 'Employé et date requis.'], 422);
    }
    $id = db_presence_upsert($pdo,
        (int)$input['employe_id'],
        $input['date_presence'],
        $input['heure_arrivee'] ?? null,
        $input['heure_depart'] ?? null,
        $input['commentaire'] ?? null
    );
    suivre_activite('PRESENCE_ENREGISTREE', "Présence employé #{$input['employe_id']} le {$input['date_presence']}");
    json_out(['success' => true, 'id' => $id]);
}

// ============================================================
//  STOCK USINE HANDLER
// ============================================================

function handle_stock_usine_list(): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $stock_mp = db_stock_mp_list($pdo);
    $stock_pf = db_stock_pf_usine_list($pdo);
    json_out(['matieres_premieres' => $stock_mp, 'produits_finis' => $stock_pf]);
}

// ============================================================
//  TRANSFERT USINE → MAGASIN HANDLER
// ============================================================

function handle_transfert_usine(): void {
    exiger_permission_api('transfert_usine_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($input['article_id']) || empty($input['magasin_destination_id']) || empty($input['quantite'])) {
        json_out(['error' => 'Article, destination et quantité requis.'], 422);
    }
    try {
        db_transfert_usine_vers_magasin($pdo,
            (int)$input['article_id'],
            (int)$input['magasin_destination_id'],
            (int)$input['quantite'],
            $input['motif'] ?? null
        );
        suivre_activite('TRANSFERT_USINE_MAGASIN', "Article #{$input['article_id']} → Magasin #{$input['magasin_destination_id']}: {$input['quantite']} unités");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('API transfert usine error: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors du transfert.'], 422);
    }
}

// ============================================================
//  TABLEAU DE BORD USINE HANDLER
// ============================================================

function handle_usine_dashboard(): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $dashboard = db_usine_dashboard($pdo);
    json_out(['data' => $dashboard]);
}

// ============================================================
//  RAPPORT PRODUCTION HANDLER
// ============================================================

function handle_production_rapport(): void {
    exiger_permission_api('production_consulter');
    global $pdo;
    $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
    $date_fin = $_GET['date_fin'] ?? date('Y-m-t');
    $article_id = !empty($_GET['article_id']) ? (int)$_GET['article_id'] : null;
    $rapport = db_production_rapport($pdo, $date_debut, $date_fin, $article_id);
    json_out(['data' => $rapport]);
}

// ============================================================
//  RÔLES RBAC HANDLERS
// ============================================================

function handle_roles_list(): void {
    exiger_permission_api('roles_consulter');
    global $pdo;
    $roles = db_roles_list($pdo);
    json_out(['data' => $roles]);
}

function handle_role_create(): void {
    exiger_permission_api('roles_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $code = strtoupper(trim($data['code'] ?? ''));
    $nom = trim($data['nom'] ?? '');
    $description = trim($data['description'] ?? '');

    if ($code === '' || $nom === '') {
        json_out(['error' => 'Code et nom requis.'], 422);
    }
    if (!preg_match('/^[A-Z_]{2,50}$/', $code)) {
        json_out(['error' => 'Code invalide (lettres majuscules et underscores uniquement, 2-50 car.).'], 422);
    }
    if (db_role_get($pdo, $code)) {
        json_out(['error' => 'Ce code rôle existe déjà.'], 409);
    }

    $id = db_role_insert($pdo, $code, $nom, $description);
    suivre_activite('CREATION_ROLE_API', 'Création rôle : ' . $code);
    json_out(['id' => $id, 'code' => $code, 'nom' => $nom], 201);
}

function handle_role_update(int $id): void {
    exiger_permission_api('roles_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $code = strtoupper(trim($data['code'] ?? ''));
    $nom = trim($data['nom'] ?? '');
    $description = trim($data['description'] ?? '');
    $actif = ($data['actif'] ?? 1) ? true : false;

    $existing = db_role_get($pdo, $code);
    if (!$existing) {
        json_out(['error' => 'Rôle introuvable.'], 404);
    }

    db_role_update($pdo, $code, $nom, $description, $actif);
    suivre_activite('MODIFICATION_ROLE_API', 'Modification rôle : ' . $code);
    json_out(['ok' => true]);
}

function handle_role_delete(int $id): void {
    exiger_permission_api('roles_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $code = strtoupper(trim($_GET['code'] ?? ''));

    if ($code === '') {
        json_out(['error' => 'Code requis.'], 422);
    }

    // Utiliser l'ID de l'URL si le code n'est pas fourni en GET
    if ($code === '' && $id > 0) {
        $stmt = $pdo->prepare("SELECT code FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $code = strtoupper(trim($stmt->fetchColumn() ?? ''));
    }

    $protected = ROLES_PROTEGES;
    if (in_array($code, $protected, true)) {
        json_out(['error' => 'Rôle système non supprimable.'], 403);
    }

    $user_count = db_role_user_count($pdo, $code);
    if ($user_count > 0) {
        json_out(['error' => "Attribué à $user_count utilisateur(s). Désassignez-le d'abord."], 409);
    }

    db_role_delete($pdo, $code);
    suivre_activite('SUPPRESSION_ROLE_API', 'Suppression rôle : ' . $code);
    json_out(['ok' => true]);
}

// ============================================================
//  PERMISSIONS HANDLERS
// ============================================================

function handle_permissions_list(): void {
    exiger_permission_api('roles_consulter');
    global $pdo;
    $perms = db_permissions_all($pdo);
    $grouped = [];
    foreach ($perms as $p) {
        $grouped[$p['categorie']][] = $p;
    }
    json_out(['data' => $grouped, 'total' => count($perms)]);
}

function handle_role_permissions(int $role_id): void {
    exiger_permission_api('roles_consulter');
    global $pdo;
    $stmt = $pdo->prepare("SELECT code FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $code = $stmt->fetchColumn();
    if (!$code) {
        json_out(['error' => 'Rôle introuvable.'], 404);
    }
    $perms = db_permissions_keys_for_role($pdo, $code);
    json_out(['role' => $code, 'permissions' => $perms]);
}

function handle_role_permissions_save(int $role_id): void {
    exiger_permission_api('roles_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $stmt = $pdo->prepare("SELECT code FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $code = $stmt->fetchColumn();
    if (!$code) {
        json_out(['error' => 'Rôle introuvable.'], 404);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $perm_ids = array_map('intval', $data['permission_ids'] ?? []);
    $perm_ids = array_filter($perm_ids, fn($id) => $id > 0);

    db_permissions_save_for_role($pdo, $code, $perm_ids);
    suivre_activite('MODIFICATION_PERMISSIONS_API', 'Permissions rôle ' . $code . ' mises à jour');
    json_out(['ok' => true, 'count' => count($perm_ids)]);
}

// ============================================================
//  MACHINES HANDLERS
// ============================================================

function handle_machines_list(): void {
    exiger_permission_api('machines_consulter');
    global $pdo;
    $machines = db_machines_list($pdo);
    json_out(['data' => $machines]);
}

function handle_machine_get(int $id): void {
    exiger_permission_api('machines_consulter');
    global $pdo;
    $machine = db_machine_get($pdo, $id);
    if (!$machine) json_out(['error' => 'Machine introuvable.'], 404);
    $historique = db_machine_historique($pdo, $id);
    $machine['historique'] = $historique;
    json_out(['data' => $machine]);
}

function handle_machine_create(): void {
    exiger_permission_api('machines_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input['nom'])) {
        json_out(['error' => 'Nom de machine requis.'], 422);
    }
    $id = db_machine_insert($pdo, [
        'reference' => trim($input['reference'] ?? ''),
        'nom' => trim($input['nom']),
        'type' => trim($input['type'] ?? ''),
        'description' => trim($input['description'] ?? ''),
        'etat' => $input['etat'] ?? 'ARRETEE',
        'actif' => (int)($input['actif'] ?? 1),
    ]);
    suivre_activite('MACHINE_CREEE', "Machine #$id: {$input['nom']}");
    json_out(['success' => true, 'id' => $id], 201);
}

function handle_machine_update(int $id): void {
    exiger_permission_api('machines_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    db_machine_update($pdo, $id, [
        'nom' => trim($input['nom'] ?? ''),
        'type' => trim($input['type'] ?? ''),
        'description' => trim($input['description'] ?? ''),
        'actif' => isset($input['actif']) ? (int)$input['actif'] : 1,
    ]);
    suivre_activite('MACHINE_MODIFIEE', "Machine #$id modifiée");
    json_out(['success' => true]);
}

function handle_machine_demarrer(int $id): void {
    exiger_permission_api('machines_demarrer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    try {
        $production_id = !empty($input['production_id']) ? (int)$input['production_id'] : null;
        db_machine_demarrer($pdo, $id, $production_id);
        $machine = db_machine_get($pdo, $id);
        try {
            db_notif_machine($pdo, $machine['type'] ?? 'Machine', $machine['nom'], 'démarrée');
        } catch (Throwable $ignored) {}
        suivre_activite('MACHINE_DEMARREE', "Machine #$id démarrée");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('Erreur machine demarrer: ' . $e->getMessage());
        json_out(['error' => 'Erreur lors du démarrage de la machine.'], 422);
    }
}

function handle_machine_arreter(int $id): void {
    exiger_permission_api('machines_demarrer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    try {
        db_machine_arreter($pdo, $id, $input['motif'] ?? null);
        $machine = db_machine_get($pdo, $id);
        // Mapper le motif vers l'action de notification
        $motif = $input['motif'] ?? null;
        $notif_action = match(true) {
            stripos($motif ?? '', 'panne') !== false => 'en panne',
            stripos($motif ?? '', 'maintenance') !== false => 'en maintenance',
            default => 'arrêtée',
        };
        try {
            db_notif_machine($pdo, $machine['type'] ?? 'Machine', $machine['nom'], $notif_action,
                !empty($motif) ? "Motif : {$motif}" : null);
        } catch (Throwable $ignored) {}
        suivre_activite('MACHINE_ARRETEE', "Machine #$id $notif_action");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('Erreur machine arreter: ' . $e->getMessage());
        json_out(['error' => "Erreur lors de l'arrêt de la machine."], 422);
    }
}

function handle_machine_set_etat(int $id): void {
    exiger_permission_api('machines_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $etat = $input['etat'] ?? '';
    $valid_etats = ['ARRETEE', 'EN_FONCTIONNEMENT', 'EN_MAINTENANCE', 'EN_PANNE'];
    if (!in_array($etat, $valid_etats, true)) {
        json_out(['error' => 'État invalide. Valeurs acceptées : ' . implode(', ', $valid_etats)], 422);
    }
    try {
        db_machine_set_etat($pdo, $id, $etat, $input['motif'] ?? null);
        suivre_activite('MACHINE_ETAT', "Machine #$id → $etat");
        json_out(['success' => true]);
    } catch (Throwable $e) {
        error_log('Erreur machine set_etat: ' . $e->getMessage());
        json_out(['error' => "Erreur lors du changement d'état de la machine."], 422);
    }
}

function handle_machine_historique(int $id): void {
    exiger_permission_api('machines_consulter');
    global $pdo;
    $historique = db_machine_historique($pdo, $id);
    json_out(['data' => $historique]);
}

// ============================================================
//  NOTIFICATIONS HANDLERS
// ============================================================

function handle_notifications_list(): void {
    exiger_permission_api('notifications_usine_consulter');
    global $pdo;
    $type = $_GET['type'] ?? null;
    $non_lues = !empty($_GET['non_lues']);
    $role = user_role();
    $user_id = $_SESSION['user']['id'] ?? null;
    $notifications = db_notifications_list($pdo, $type, $role, $user_id, $non_lues);
    $nb_non_lues = db_notifications_nb_non_lues($pdo, $role, $user_id);
    json_out(['data' => $notifications, 'nb_non_lues' => $nb_non_lues]);
}

function handle_notification_marquer_lue(int $id): void {
    exiger_permission_api('notifications_usine_consulter');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }
    global $pdo;
    db_notification_marquer_lue($pdo, $id);
    json_out(['success' => true]);
}

function handle_notifications_tout_lu(): void {
    exiger_permission_api('notifications_usine_consulter');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }
    global $pdo;
    $role = user_role();
    $user_id = $_SESSION['user']['id'] ?? null;
    db_notification_tout_lu($pdo, $role, $user_id);
    json_out(['success' => true]);
}

function handle_notification_supprimer(int $id): void {
    exiger_permission_api('notifications_usine_gerer');
    if (!api_csrf_ok()) {
        json_out(['error' => 'Token CSRF invalide.'], 403);
    }
    global $pdo;
    db_notification_supprimer($pdo, $id);
    json_out(['success' => true]);
}

// ============================================================
//  HORAIRES HANDLERS
// ============================================================

function handle_horaires_list(): void {
    exiger_permission_api('horaires_consulter');
    global $pdo;
    $horaires = db_horaires_list($pdo);
    json_out(['data' => $horaires]);
}

function handle_horaire_create(): void {
    exiger_permission_api('horaires_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input['nom']) || empty($input['jour']) || empty($input['heure_debut']) || empty($input['heure_fin'])) {
        json_out(['error' => 'Nom, jour, heure début et heure fin requis.'], 422);
    }
    $valid_jours = ['LUNDI','MARDI','MERCREDI','JEUDI','VENDREDI','SAMEDI','DIMANCHE'];
    $jour = strtoupper(trim($input['jour']));
    if (!in_array($jour, $valid_jours, true)) {
        json_out(['error' => 'Jour invalide.'], 422);
    }
    db_horaire_insert($pdo, trim($input['nom']), $jour,
        $input['heure_debut'], $input['heure_fin'],
        (int)($input['tolerance_retard_minutes'] ?? 5));
    suivre_activite('HORAIRE_CREE', "Horaire {$input['nom']} — $jour");
    json_out(['success' => true], 201);
}

function handle_horaire_delete(): void {
    exiger_permission_api('horaires_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $nom = $_GET['nom'] ?? '';
    $jour = $_GET['jour'] ?? null;
    if (empty($nom)) json_out(['error' => 'Nom requis.'], 422);
    db_horaire_delete($pdo, $nom, $jour);
    json_out(['success' => true]);
}

// ============================================================
//  CATÉGORIES PERTES HANDLERS
// ============================================================

function handle_categories_pertes_list(): void {
    exiger_permission_api('categories_pertes_consulter');
    global $pdo;
    $cats = db_categories_pertes_list($pdo);
    json_out(['data' => $cats]);
}

function handle_categorie_perte_create(): void {
    exiger_permission_api('categories_pertes_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input['nom'])) json_out(['error' => 'Nom requis.'], 422);
    $id = db_categorie_perte_insert($pdo, trim($input['nom']), $input['description'] ?? null);
    json_out(['success' => true, 'id' => $id], 201);
}

// ============================================================
//  RENDEMENT HANDLERS
// ============================================================

function handle_rendement_production(int $id): void {
    exiger_permission_api('rendement_consulter');
    global $pdo;
    $rendement = db_rendement_production($pdo, $id);
    if (!$rendement) json_out(['error' => 'Production introuvable.'], 404);
    json_out(['data' => $rendement]);
}

function handle_rendement_categories(int $id): void {
    exiger_permission_api('rendement_consulter');
    global $pdo;
    $cats = db_rendement_par_categorie($pdo, $id);
    json_out(['data' => $cats]);
}

function handle_rapport_matiere_production(): void {
    exiger_permission_api('rendement_consulter');
    global $pdo;
    $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
    $date_fin = $_GET['date_fin'] ?? date('Y-m-d');
    $rapport = db_rapport_matiere_production($pdo, $date_debut, $date_fin);
    json_out(['data' => $rapport]);
}

// ============================================================
//  PRÉSENCES AVEC RETARDS HANDLER
// ============================================================

function handle_presences_avec_retards(): void {
    exiger_permission_api('presence_consulter');
    global $pdo;
    $date = $_GET['date'] ?? date('Y-m-d');
    $presences = db_presences_avec_retards($pdo, $date);
    $absents = db_employes_absents($pdo, $date);
    json_out(['presences' => $presences, 'absents' => $absents]);
}

// ============================================================
//  DASHBOARD ENRICHI HANDLER
// ============================================================

function handle_usine_dashboard_enrichi(): void {
    exiger_permission_api('usine_consulter');
    global $pdo;
    $dashboard = db_usine_dashboard_enrichi($pdo);
    json_out(['data' => $dashboard]);
}

// ============================================================
//  ÉQUIPES HANDLERS
// ============================================================

function handle_equipes_list(): void {
    exiger_permission_api('equipes_consulter');
    global $pdo;
    $type = $_GET['type'] ?? null;
    $equipes = db_equipes_list($pdo, $type);
    json_out(['data' => $equipes]);
}

function handle_equipe_get(int $id): void {
    exiger_permission_api('equipes_consulter');
    global $pdo;
    $equipe = db_equipe_get($pdo, $id);
    if (!$equipe) json_out(['error' => 'Équipe introuvable.'], 404);
    $equipe['membres'] = db_equipe_membres($pdo, $id);
    $equipe['magasins'] = db_equipe_magasins($pdo, $id);
    json_out(['data' => $equipe]);
}

function handle_equipe_create(): void {
    exiger_permission_api('equipes_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'Token CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $nom = input_string($input['nom'] ?? '');
    if ($nom === '') json_out(['error' => 'Nom requis.'], 400);
    $id = db_equipe_insert($pdo, [
        'nom' => $nom,
        'description' => $input['description'] ?? null,
        'type' => $input['type'] ?? 'BOUTIQUE',
        'chef_equipe_id' => $input['chef_equipe_id'] ?? null,
    ]);
    if (!empty($input['membres'])) {
        db_equipe_set_membres($pdo, $id, $input['membres']);
    }
    if (!empty($input['magasins'])) {
        db_equipe_set_magasins($pdo, $id, $input['magasins']);
    }
    suivre_activite('EQUIPE_CREE', 'Équipe créée: ' . $nom);
    json_out(['success' => true, 'id' => $id]);
}

function handle_equipe_update(int $id): void {
    exiger_permission_api('equipes_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'Token CSRF invalide.'], 403);
    global $pdo;
    $equipe = db_equipe_get($pdo, $id);
    if (!$equipe) json_out(['error' => 'Équipe introuvable.'], 404);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $nom = input_string($input['nom'] ?? $equipe['nom']);
    db_equipe_update($pdo, $id, [
        'nom' => $nom,
        'description' => $input['description'] ?? $equipe['description'],
        'type' => $input['type'] ?? $equipe['type'],
        'chef_equipe_id' => $input['chef_equipe_id'] ?? $equipe['chef_equipe_id'],
    ]);
    if (isset($input['membres'])) {
        db_equipe_set_membres($pdo, $id, $input['membres']);
    }
    if (isset($input['magasins'])) {
        db_equipe_set_magasins($pdo, $id, $input['magasins']);
    }
    suivre_activite('EQUIPE_MODIFIEE', 'Équipe modifiée: #' . $id);
    json_out(['success' => true]);
}

function handle_equipe_delete(int $id): void {
    exiger_permission_api('equipes_gerer');
    if (!api_csrf_ok()) json_out(['error' => 'Token CSRF invalide.'], 403);
    global $pdo;
    db_equipe_delete($pdo, $id);
    suivre_activite('EQUIPE_SUPPRIMEE', 'Équipe désactivée: #' . $id);
    json_out(['success' => true]);
}

// ============================================================
//  CREDIT — Handlers API
// ============================================================

function handle_client_credit_info(int $client_id): void {
    exiger_permission_api('credit_consulter');
    global $pdo;
    $info = db_credit_client_info($pdo, $client_id);
    json_out($info);
}

function handle_creances_list(): void {
    exiger_permission_api('credit_consulter');
    global $pdo;
    $filters = [];
    if (!empty($_GET['client_id'])) $filters['client_id'] = (int)$_GET['client_id'];
    if (!empty($_GET['statut']))    $filters['statut'] = $_GET['statut'];
    if (!empty($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
    if (!empty($_GET['date_fin']))  $filters['date_fin'] = $_GET['date_fin'];
    if (!empty($_GET['search']))    $filters['search'] = $_GET['search'];
    $result = db_creances_search_sql($pdo, $filters);
    json_out($result);
}

function handle_creance_get(int $id): void {
    exiger_permission_api('credit_consulter');
    global $pdo;
    $creance = db_creance_get_by_id($pdo, $id);
    if (!$creance) json_out(['error' => 'Creance introuvable.'], 404);
    $creance['paiements'] = db_creance_paiements_list($pdo, $id);
    json_out($creance);
}

function handle_creance_paiements_list(int $creance_id): void {
    exiger_permission_api('credit_paiement_consulter');
    global $pdo;
    $paiements = db_creance_paiements_list($pdo, $creance_id);
    json_out(['paiements' => $paiements]);
}

function handle_creance_paiement_create(int $creance_id): void {
    exiger_permission_api('credit_paiement_creer');
    if (!api_csrf_ok()) json_out(['error' => 'Token CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $montant = (float)($input['montant'] ?? 0);
    $modePaiement = input_string($input['mode_paiement'] ?? 'Especes');
    $reference = input_string($input['reference'] ?? '');
    $notes = input_string($input['notes'] ?? '');
    if ($montant <= 0) {
        json_out(['success' => false, 'error' => 'Le montant doit etre positif.'], 422);
    }
    try {
        $paiementId = db_creance_paiement_insert($pdo, $creance_id, $montant, $modePaiement, $reference ?: null, user_id(), $notes ?: null);
        suivre_activite('CREDIT_PAIEMENT', 'Paiement credit #' . $paiementId . ' sur creance #' . $creance_id . ' — ' . number_format($montant, 2, ',', ' ') . ' FCFA');
        json_out(['success' => true, 'paiement_id' => $paiementId]);
    } catch (RuntimeException $e) {
        json_out(['success' => false, 'error' => $e->getMessage()], 422);
    }
}

function handle_creance_annuler(int $id): void {
    exiger_permission_api('credit_annuler');
    if (!api_csrf_ok()) json_out(['error' => 'Token CSRF invalide.'], 403);
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $motif = input_string($input['motif'] ?? '');
    try {
        db_creance_annuler($pdo, $id, user_id(), $motif ?: null);
        suivre_activite('CREDIT_ANNULATION', 'Creance #' . $id . ' annulee');
        json_out(['success' => true]);
    } catch (RuntimeException $e) {
        json_out(['success' => false, 'error' => $e->getMessage()], 422);
    }
}

function handle_credit_rapport(): void {
    exiger_permission_api('credit_rapport');
    global $pdo;
    $magasinId = !empty($_GET['magasin_id']) ? (int)$_GET['magasin_id'] : null;
    $rapport = db_credit_rapport($pdo, $magasinId);
    json_out($rapport);
}
