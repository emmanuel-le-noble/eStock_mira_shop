<?php
/**
 * parametres.php - Configuration globale de la boutique.
 */
if (!function_exists('est_connecte')) { require_once __DIR__ . '/config/connexion.php'; }
if (!function_exists('db_article_insert')) { require_once __DIR__ . '/includes/db_functions.php'; }
if (!function_exists('csrf_guard')) { require_once __DIR__ . '/includes/helpers.php'; }
require_once __DIR__ . '/config/connexion.php';
exiger_permission('parametres_gerer');

$sections = [
    'boutique' => [
        'titre' => 'Boutique / magasin',
        'icone' => 'bi-shop',
        'champs' => [
            ['cle'=>'nom_boutique', 'label'=>'Nom de la boutique', 'type'=>'text', 'defaut'=>'eStock Market', 'required'=>true],
            ['cle'=>'slogan_boutique', 'label'=>'Slogan', 'type'=>'text', 'defaut'=>'Gérez votre stock et votre caisse. Simplement.'],
            ['cle'=>'adresse_boutique', 'label'=>'Adresse', 'type'=>'textarea', 'defaut'=>''],
            ['cle'=>'code_postal', 'label'=>'Code postal / ville', 'type'=>'text', 'defaut'=>''],
            ['cle'=>'pays', 'label'=>'Pays', 'type'=>'text', 'defaut'=>'Togo'],
            ['cle'=>'telephone_boutique', 'label'=>'Téléphone', 'type'=>'tel', 'defaut'=>''],
            ['cle'=>'email_boutique', 'label'=>'E-mail', 'type'=>'email', 'defaut'=>''],
            ['cle'=>'site_boutique', 'label'=>'Site web', 'type'=>'text', 'defaut'=>''],
            ['cle'=>'nif_boutique', 'label'=>'NIF (Numéro d\'Identification Fiscale — OTR)', 'type'=>'text', 'defaut'=>''],
            ['cle'=>'rccm_boutique', 'label'=>'RCCM (immatriculation au registre du commerce)', 'type'=>'text', 'defaut'=>''],
        ],
    ],
    'financier' => [
        'titre' => 'Devise, régime fiscal & taxes',
        'icone' => 'bi-cash-coin',
        'champs' => [
            ['cle'=>'regime_fiscal', 'label'=>'Régime fiscal', 'type'=>'select', 'defaut'=>'TPU', 'options'=>['TPU'=>'TPU — Taxe Professionnelle Unique (factures hors taxes)', 'TVA'=>'TVA — régime classique (18 %)']],
            ['cle'=>'tva_taux_defaut', 'label'=>'Taux de TVA (%)', 'type'=>'number', 'defaut'=>'18.00', 'min'=>0, 'max'=>100, 'step'=>'0.01'],
            ['cle'=>'tva_active', 'label'=>'TVA active (articles assujettis)', 'type'=>'checkbox', 'defaut'=>'1'],
            ['cle'=>'facture_prefixe', 'label'=>'Préfixe des factures (ex : FAC-20260814-0001)', 'type'=>'text', 'defaut'=>'FAC', 'maxlength'=>10],
            ['cle'=>'devise_nom', 'label'=>'Nom de la devise', 'type'=>'text', 'defaut'=>'Franc CFA'],
            ['cle'=>'devise_code', 'label'=>'Code devise', 'type'=>'text', 'defaut'=>'XOF', 'maxlength'=>3],
            ['cle'=>'devise_symbole', 'label'=>'Symbole affiché', 'type'=>'text', 'defaut'=>'FCFA'],
            ['cle'=>'devise_position', 'label'=>'Position du symbole', 'type'=>'select', 'defaut'=>'apres', 'options'=>['apres'=>'Après le montant', 'avant'=>'Avant le montant']],
            ['cle'=>'devise_decimales', 'label'=>'Décimales', 'type'=>'number', 'defaut'=>'2', 'min'=>0, 'max'=>4, 'step'=>'1'],
            ['cle'=>'separateur_decimal', 'label'=>'Séparateur décimal', 'type'=>'text', 'defaut'=>',', 'maxlength'=>2],
            ['cle'=>'separateur_milliers', 'label'=>'Séparateur milliers', 'type'=>'text', 'defaut'=>' ', 'maxlength'=>2],
            ['cle'=>'paiement_rapide_1', 'label'=>'Montant rapide 1', 'type'=>'number', 'defaut'=>'20.00', 'min'=>0, 'step'=>'0.01'],
            ['cle'=>'paiement_rapide_2', 'label'=>'Montant rapide 2', 'type'=>'number', 'defaut'=>'50.00', 'min'=>0, 'step'=>'0.01'],
            ['cle'=>'paiement_rapide_3', 'label'=>'Montant rapide 3', 'type'=>'number', 'defaut'=>'100.00', 'min'=>0, 'step'=>'0.01'],
        ],
    ],
    'ticket' => [
        'titre' => 'Ticket de caisse',
        'icone' => 'bi-receipt',
        'champs' => [
            ['cle'=>'ticket_entete', 'label'=>'Message de fin', 'type'=>'text', 'defaut'=>'Merci de votre visite !'],
            ['cle'=>'ticket_remarque', 'label'=>'Remarque', 'type'=>'textarea', 'defaut'=>''],
            ['cle'=>'ticket_format', 'label'=>'Format papier', 'type'=>'select', 'defaut'=>'80mm', 'options'=>['80mm'=>'80 mm', '58mm'=>'58 mm']],
        ],
    ],
    'fidelite' => [
        'titre' => 'Programme de fidélité',
        'icone' => 'bi-stars',
        'champs' => [
            ['cle'=>'fidelite_actif', 'label'=>'Activer le programme de fidélité', 'type'=>'checkbox', 'defaut'=>'0'],
            ['cle'=>'fidelite_points_par_devise', 'label'=>'Devise dépensée pour gagner 1 point (ex : 100 = 1 pt / 100 FCFA)', 'type'=>'number', 'defaut'=>'100', 'min'=>1, 'step'=>'1'],
            ['cle'=>'fidelite_valeur_point', 'label'=>'Valeur d\'un point (1 point = montant × 100)', 'type'=>'number', 'defaut'=>'1', 'min'=>1, 'step'=>'1'],
            ['cle'=>'fidelite_min_points_usage', 'label'=>'Seuil minimal de points pour une remise', 'type'=>'number', 'defaut'=>'10', 'min'=>1, 'step'=>'1'],
        ],
    ],
    'conformite' => [
        'titre' => 'Traçabilité & conformité',
        'icone' => 'bi-shield-check',
        'champs' => [
            ['cle'=>'archives_conservation_annees', 'label'=>'Conservation des archives de caisse (années)', 'type'=>'number', 'defaut'=>'6', 'min'=>1, 'step'=>'1'],
            ['cle'=>'retention_clients_mois', 'label'=>'Rétention clients sans activité avant anonymisation (mois)', 'type'=>'number', 'defaut'=>'36', 'min'=>1, 'step'=>'1'],
        ],
    ],
    'apparence' => [
        'titre' => 'Application',
        'icone' => 'bi-window-sidebar',
        'champs' => [
            ['cle'=>'app_nom', 'label'=>'Nom court dans le menu', 'type'=>'text', 'defaut'=>'eStock'],
            ['cle'=>'langue', 'label'=>'Langue', 'type'=>'select', 'defaut'=>'fr', 'options'=>['fr'=>'Français', 'en'=>'English', 'es'=>'Español', 'ar'=>'العربية', 'zh'=>'中文']],
            ['cle'=>'theme_couleur', 'label'=>'Couleur du thème', 'type'=>'select', 'defaut'=>'indigo', 'options'=>['indigo'=>'Indigo', 'emerald'=>'Vert', 'sky'=>'Bleu', 'rose'=>'Rose', 'amber'=>'Ambre']],
            ['cle'=>'fuseau_horaire', 'label'=>'Fuseau horaire', 'type'=>'select', 'defaut'=>'Africa/Lome', 'options'=>
                ['Africa/Lome'=>'Africa/Lome (UTC+0 — Togo)',
                 'Africa/Abidjan'=>'Africa/Abidjan (UTC+0 — Côte d\'Ivoire)',
                 'Africa/Accra'=>'Africa/Accra (UTC+0 — Ghana)',
                 'Africa/Kinshasa'=>'Africa/Kinshasa (UTC+1 — RD Congo)',
                 'Africa/Lagos'=>'Africa/Lagos (UTC+1 — Nigeria)',
                 'Africa/Porto-Novo'=>'Africa/Porto-Novo (UTC+1 — Bénin)',
                 'Africa/Douala'=>'Africa/Douala (UTC+1 — Cameroun)',
                 'Africa/Ndjamena'=>'Africa/Ndjamena (UTC+1 — Tchad)',
                 'Africa/Brazzaville'=>'Africa/Brazzaville (UTC+1 — Congo)',
                 'Africa/Libreville'=>'Africa/Libreville (UTC+1 — Gabon)',
                 'Africa/Bangui'=>'Africa/Bangui (UTC+1 — RCA)',
                 'Africa/Niamey'=>'Africa/Niamey (UTC+1 — Niger)',
                 'Africa/Bamako'=>'Africa/Bamako (UTC+0 — Mali)',
                 'Africa/Ouagadougou'=>'Africa/Ouagadougou (UTC+0 — Burkina Faso)',
                 'Africa/Conakry'=>'Africa/Conakry (UTC+0 — Guinée)',
                 'Africa/Dakar'=>'Africa/Dakar (UTC+0 — Sénégal)',
                 'Africa/Nouakchott'=>'Africa/Nouakchott (UTC+0 — Mauritanie)',
                 'Africa/Tunis'=>'Africa/Tunis (UTC+1 — Tunisie)',
                 'Africa/Algiers'=>'Africa/Algiers (UTC+1 — Algérie)',
                 'Africa/Casablanca'=>'Africa/Casablanca (UTC+1 — Maroc)',
                 'Europe/Paris'=>'Europe/Paris (UTC+1/+2 — France)',
                 'Europe/Brussels'=>'Europe/Brussels (UTC+1/+2 — Belgique)',
                 'UTC'=>'UTC (Temps universel)',
                ],
            ],
        ],
    ],
    'email' => [
        'titre' => 'E-mails',
        'icone' => 'bi-envelope-paper',
        'largeur' => 'col-lg-12',
        'champs' => [
            ['cle'=>'emails_mode', 'label'=>'Mode d\'envoi', 'type'=>'select', 'defaut'=>'file', 'options'=>['file'=>'File d\'attente (worker asynchrone)', 'direct'=>'Envoi direct (bloquant)']],
            ['cle'=>'smtp_from', 'label'=>'Adresse expéditrice', 'type'=>'email', 'defaut'=>'noreply@estock.local'],
            ['cle'=>'base_url_ext', 'label'=>'URL publique du site (liens dans les e-mails)', 'type'=>'text', 'defaut'=>''],
            ['cle'=>'emails_conservation_jours', 'label'=>'Conservation des e-mails traités avant purge (jours)', 'type'=>'number', 'defaut'=>'30', 'min'=>1, 'step'=>'1'],
            ['cle'=>'emails_tentatives_max', 'label'=>'Tentatives d\'envoi maximum', 'type'=>'number', 'defaut'=>'5', 'min'=>1, 'max'=>20, 'step'=>'1'],
            ['cle'=>'emails_desinscription_obligatoire', 'label'=>'Mention désinscription dans les e-mails de prospection', 'type'=>'checkbox', 'defaut'=>'1'],
        ],
        'aide' => '<strong>Comment configurer l\'envoi d\'e-mails :</strong>
            <ol class="mb-1 ps-3 mt-1">
                <li><strong>Mode d\'envoi</strong> — « File d\'attente » (recommandé) : les e-mails sont mis en file puis expédiés par le worker
                    <code>bin/emails_worker.php</code>, à planifier en tâche cron toutes les minutes
                    (ex. <code>* * * * * php C:/wamp64/www/eStock/bin/emails_worker.php</code>).
                    « Envoi direct » : envoi immédiat via la fonction <code>mail()</code> de PHP (bloquant).</li>
                <li><strong>Adresse expéditrice</strong> — une adresse réelle du domaine de la boutique (ex. <code>noreply@votre-domaine.com</code>).</li>
                <li><strong>URL publique</strong> — l\'adresse d\'accès du site vu par les clients (indispensable pour les liens de désinscription
                    des e-mails de prospection).</li>
                <li><strong>Environnement local (WampServer)</strong> — sans serveur SMTP actif, <code>mail()</code> échoue : les e-mails sont alors
                    journalisés dans <code>error_log</code> (mode simulation). Pour un vrai envoi, configurez le relais dans
                    <code>php.ini</code> (<code>sendmail_path</code> / SMTP) ou déployez le site chez un hébergeur.</li>
            </ol>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#aideCronEmail">Voir le réglage cron type</button>
            <pre class="collapse border rounded bg-light p-2 mt-2 mb-0 small" id="aideCronEmail"><code>* * * * * php C:/wamp64/www/eStock/bin/emails_worker.php &gt;&gt; /tmp/emails_worker.log 2&gt;&amp;1</code></pre>',
    ],
    'google_oauth' => [
        'titre' => 'Connexion Google',
        'icone' => 'bi-google',
        'champs' => [
            ['cle'=>'google_oauth_actif', 'label'=>'Activer la connexion avec compte Google', 'type'=>'checkbox', 'defaut'=>'0'],
            ['cle'=>'google_client_id', 'label'=>'Client ID Google', 'type'=>'text', 'defaut'=>'', 'required'=>false],
            ['cle'=>'google_client_secret', 'label'=>'Client Secret Google', 'type'=>'secret', 'defaut'=>'', 'required'=>false],
        ],
        'aide' => '<strong>Configurer la connexion Google :</strong>
            <ol class="mb-1 ps-3 mt-1">
                <li>Allez sur <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console → Credentials</a>.</li>
                <li>Créez un <strong>OAuth 2.0 Client ID</strong> (type : Web application).</li>
                <li>Ajoutez l\'<strong>URI de redirection autorisé</strong> : <code>' . h(BASE_URL . 'auth/google_callback.php') . '</code></li>
                <li>Copiez le <strong>Client ID</strong> et le <strong>Client Secret</strong> dans les champs ci-dessus.</li>
                <li>Activez l\'option « Activer la connexion avec compte Google ».</li>
            </ol>
            <div class="alert alert-info small mt-2 mb-0">
                <i class="bi bi-info-circle"></i> Lorsqu\'un utilisateur se connecte avec Google pour la première fois :
                si son email correspond à un compte existant, le compte est lié automatiquement.
                Sinon, un nouveau compte est créé avec le rôle <strong>Vendeur</strong>.
            </div>',
    ],
];

$champs = [];
foreach ($sections as $section) {
    foreach ($section['champs'] as $champ) {
        $champs[$champ['cle']] = $champ;
    }
}

// ============================================================
//  TRAITEMENT POST : CONFIGURATION GENERALE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash_error('Token de sécurité invalide.');
        redirect('parametres.php');
    }

    $valeurs_calculees = [];
    $erreurs = [];

    foreach ($champs as $cle => $champ) {
        $type = $champ['type'] ?? 'text';

        if ($type === 'checkbox') {
            $valeur = isset($_POST[$cle]) ? '1' : '0';
        } else {
            $valeur = trim((string)($_POST[$cle] ?? ''));
        }

        if ($cle === 'google_client_secret' && $valeur === '') {
            $valeur = param('google_client_secret', '');
        }

        if ($type === 'select') {
            $options = $champ['options'] ?? [];
            if (!array_key_exists($valeur, $options)) {
                $valeur = (string)($champ['defaut'] ?? '');
            }
        }

        if ($type === 'number') {
            $valeur = str_replace(',', '.', $valeur);
            $nombre = is_numeric($valeur) ? (float)$valeur : (float)($champ['defaut'] ?? 0);
            if (isset($champ['min'])) $nombre = max((float)$champ['min'], $nombre);
            if (isset($champ['max'])) $nombre = min((float)$champ['max'], $nombre);
            $valeur = (($champ['step'] ?? '') === '1') ? (string)(int)$nombre : number_format($nombre, 2, '.', '');
        }

        if ($cle === 'devise_code') {
            $valeur = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $valeur), 0, 3));
            if ($valeur === '') $valeur = (string)($champ['defaut'] ?? 'XOF');
        }

        if (!empty($champ['required']) && $valeur === '') {
            $erreurs[] = $champ['label'] . ' est obligatoire.';
        }

        if ($type === 'email' && $valeur !== '' && !filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = $champ['label'] . ' doit être une adresse e-mail valide.';
        }

        if (isset($champ['maxlength']) && mb_strlen($valeur) > (int)$champ['maxlength']) {
            $valeur = mb_substr($valeur, 0, (int)$champ['maxlength']);
        }

        if ($cle === 'separateur_decimal' && $valeur === '') {
            $valeur = ',';
        }

        $valeurs_calculees[$cle] = $valeur;
    }

    if (!empty($erreurs)) {
        flash_error(implode(' ', $erreurs));
        redirect('parametres.php');
    }

    foreach ($valeurs_calculees as $cle => $valeur) {
        param_save($cle, $valeur);
        if ($cle === 'langue') {
            $_SESSION['langue'] = $valeur;
        }
    }

    params_flush_cache();
    suivre_activite('MODIFICATION_PARAMETRES', 'Modification des paramètres boutique');
    flash_success('Paramètres enregistrés. Les nouvelles informations sont maintenant utilisées dans l\'application.');
    redirect('parametres.php');
}

function valeur_param_champ(array $champ): string {
    return param($champ['cle'], (string)($champ['defaut'] ?? ''));
}

$titre_page = 'Paramètres';
$sous_titre = param_shop_name();

include __DIR__ . '/includes/header.php';
?>

<h4 class="mb-4"><i class="bi bi-gear text-primary"></i> Configuration de la boutique</h4>

<?php
$conformite_manquants = [];
if (trim(param('nif_boutique', '')) === '') $conformite_manquants[] = 'NIF de la boutique';
if (trim(param('rccm_boutique', '')) === '') $conformite_manquants[] = 'RCCM de la boutique';
?>
<?php if (!empty($conformite_manquants)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-shield-exclamation fs-4"></i>
        <div>
            <strong>Facturation à risque :</strong> les factures sans identification fiscale complète
            sont non conformes auprès de l'OTR. Renseignez : <?= h(implode(', ', $conformite_manquants)) ?>.
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card is-primary">
            <div class="stat-label">Boutique</div>
            <div class="stat-value"><?= h(param_shop_name()) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card is-success">
            <div class="stat-label">Devise</div>
            <div class="stat-value"><?= h(param('devise_symbole', 'FCFA')) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card is-info">
            <div class="stat-label">TVA</div>
            <div class="stat-value"><?= h(number_format(param_tva_taux(), 2, ',', ' ')) ?>%</div>
        </div>
    </div>
</div>

<form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($sections as $section): ?>
            <div class="<?= h($section['largeur'] ?? 'col-lg-6') ?>">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi <?= h($section['icone']) ?>"></i> <?= h($section['titre']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($section['champs'] as $champ):
                                $cle = $champ['cle'];
                                $type = $champ['type'] ?? 'text';
                                $valeur = valeur_param_champ($champ);
                                $col = $type === 'textarea' ? 'col-12' : 'col-md-6';
                            ?>
                                <div class="<?= $type === 'checkbox' ? 'col-12' : $col ?>">
                                    <?php if ($type === 'checkbox'): ?>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="<?= h($cle) ?>" name="<?= h($cle) ?>" value="1"
                                                   <?= param_bool($cle, ($champ['defaut'] ?? '0') === '1') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="<?= h($cle) ?>"><?= h($champ['label']) ?></label>
                                        </div>
                                    <?php elseif ($type === 'textarea'): ?>
                                        <label class="form-label" for="<?= h($cle) ?>"><?= h($champ['label']) ?></label>
                                        <textarea id="<?= h($cle) ?>" name="<?= h($cle) ?>" class="form-control" rows="2"><?= h($valeur) ?></textarea>
                                    <?php elseif ($type === 'select'): ?>
                                        <label class="form-label" for="<?= h($cle) ?>"><?= h($champ['label']) ?></label>
                                        <select id="<?= h($cle) ?>" name="<?= h($cle) ?>" class="form-select">
                                            <?php foreach (($champ['options'] ?? []) as $optionValeur => $optionLabel): ?>
                                                <option value="<?= h($optionValeur) ?>" <?= $valeur === (string)$optionValeur ? 'selected' : '' ?>>
                                                    <?= h($optionLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($type === 'secret'): ?>
                                        <label class="form-label" for="<?= h($cle) ?>"><?= h($champ['label']) ?></label>
                                        <input type="password" id="<?= h($cle) ?>" name="<?= h($cle) ?>" class="form-control"
                                               value="<?= h($valeur) ?>" autocomplete="off" placeholder="Laisser vide pour conserver">
                                    <?php else: ?>
                                        <label class="form-label" for="<?= h($cle) ?>"><?= h($champ['label']) ?></label>
                                        <input type="<?= $type ?>" id="<?= h($cle) ?>" name="<?= h($cle) ?>" class="form-control"
                                               value="<?= h($valeur) ?>"
                                               <?php if (!empty($champ['required'])): ?>required<?php endif; ?>
                                               <?php if (isset($champ['min'])): ?>min="<?= h($champ['min']) ?>"<?php endif; ?>
                                               <?php if (isset($champ['max'])): ?>max="<?= h($champ['max']) ?>"<?php endif; ?>
                                               <?php if (isset($champ['step'])): ?>step="<?= h($champ['step']) ?>"<?php endif; ?>
                                               <?php if (isset($champ['maxlength'])): ?>maxlength="<?= h($champ['maxlength']) ?>"<?php endif; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($section['aide'])): ?>
                            <div class="mt-3 p-3 bg-light rounded small"><?= $section['aide'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg"></i> Enregistrer les paramètres
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
