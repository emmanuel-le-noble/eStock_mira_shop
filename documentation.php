<?php
/**
 * documentation.php — Guide utilisateur complet, basé sur les permissions.
 *
 * Affiche la documentation de TOUS les modules accessibles à l'utilisateur
 * courant, groupés par zone fonctionnelle. Aucun rôle n'est codé en dur :
 * chaque section est affichée uniquement si l'utilisateur possède la permission
 * correspondante.
 */
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/helpers.php';
exiger_connexion();

$u          = user_courant();
$role       = $u['role'] ?? '';
$magasin_id = user_magasin_id();
$nom_magasin = '—';
if ($magasin_id > 0) {
    foreach (db_magasins_list($pdo) as $m) {
        if ((int)$m['id'] === $magasin_id) { $nom_magasin = $m['nom']; break; }
    }
}

/* ─────────────────────────────────────────────────────────────
 *  Modules regroupés par zone fonctionnelle.
 *  Chaque module porte ses permissions requises et ne s'affiche
 *  que si l'utilisateur en possède au moins une.
 * ───────────────────────────────────────────────────────────── */
$zones = [
    // ═══════════════════════════════════════════
    //  PILOTAGE
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Pilotage',
        'icon'   => 'bi-speedometer2',
        'color'  => 'indigo',
        'modules' => [
            [
                'page' => 'tableau_bord', 'icon' => 'bi-speedometer2',
                'titre' => 'Tableau de bord',
                'description' => 'Vue d\'ensemble en temps réel : CA du jour et du mois, nombre de ventes, panier moyen, alertes stock faible et péremptions imminentes.',
                'permissions' => [],
                'etapes' => [
                    'Les KPI (indicateurs clés) se mettent à jour automatiquement à chaque chargement.',
                    'Utilisez le sélecteur de magasin (haut à droite) pour basculer entre les points de vente.',
                    'Les alertes de stock faible et de DLC imminente sont signalées en rouge/orange.',
                    'En cas de panne réseau, les données de la dernière session hors-ligne sont synchronisées automatiquement.',
                ],
            ],
            [
                'page' => 'statistiques', 'icon' => 'bi-graph-up',
                'titre' => 'Statistiques & compte de résultat',
                'description' => 'Analyse de la performance : CA, marges, top ventes, répartition des paiements, compte de résultat (CA − achats − dépenses).',
                'permissions' => ['statistiques_consulter'],
                'etapes' => [
                    'Choisissez la période (jour, semaine, mois, période personnalisée) et le magasin.',
                    'Consultez les graphiques Chart.js : évolution des ventes, répartition par catégorie, paiements.',
                    'Compte de résultat : CA − achats de la période − dépenses = résultat net.',
                    'Exportez les données en CSV pour analyse externe.',
                ],
            ],
            [
                'page' => 'audit', 'icon' => 'bi-clock-history',
                'titre' => 'Journal d\'activité (audit)',
                'description' => 'Traçabilité intégrale : connexions, ventes, modifications, clôtures. Chaque action sensible est enregistrée.',
                'permissions' => ['audit_consulter'],
                'etapes' => [
                    'Filtrez par période, utilisateur, magasin ou type d\'action.',
                    'Consultez les détails : auteur, magasin, IP, date, description complète.',
                    'Aucune donnée sensible (mot de passe, jeton) n\'est journalisée.',
                    'Le journal est non-modifiable et conserve l\'historique complet.',
                ],
            ],
        ],
    ],

    // ═══════════════════════════════════════════
    //  STOCK & ACHATS
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Stock & Achats',
        'icon'   => 'bi-boxes',
        'color'  => 'emerald',
        'modules' => [
            [
                'page' => 'stock', 'icon' => 'bi-boxes',
                'titre' => 'Consultation du stock',
                'description' => 'Stock en temps réel du magasin : quantités, alertes, prix, DLC par lot, valorisation CUMP.',
                'permissions' => ['stock_consulter'],
                'etapes' => [
                    'Recherchez un article par nom ou code-barres.',
                    'Filtrez par statut : stock faible, rupture, surstock.',
                    'Cliquez sur un article pour voir le détail : quantités par magasin, lots DLC/DDM, prix.',
                    'Les articles périmés sont signalés en rouge et bloqués en caisse.',
                ],
            ],
            [
                'page' => 'articles', 'icon' => 'bi-box-seam',
                'titre' => 'Articles',
                'description' => 'Référentiel des produits : création, modification, code-barres EAN-13, TVA, seuils d\'alerte.',
                'permissions' => ['articles_consulter'],
                'etapes' => [
                    '« Nouvel article » : nom, référence, catégorie, prix d\'achat et de vente, TVA, seuil d\'alerte.',
                    'Générez le code-barres EAN-13 automatiquement (bouton « Générer ») ou saisissez un code existant.',
                    'Pour les produits périssables : définissez la DLC/DDM (gestion par lots FEFO).',
                    'La suppression est impossible si l\'article a un historique de ventes.',
                ],
                'peut_gerer' => ['articles_gerer'],
            ],
            [
                'page' => 'categories', 'icon' => 'bi-tags',
                'titre' => 'Catégories',
                'description' => 'Organisation des articles par famille (Épicerie, Boissons, Frais, Hygiène…).',
                'permissions' => ['articles_gerer'],
                'etapes' => [
                    'Créez une catégorie avec un nom et une couleur.',
                    'Désactivez une catégorie pour la masquer (les articles existants restent conservés).',
                ],
            ],
            [
                'page' => 'fournisseurs', 'icon' => 'bi-truck',
                'titre' => 'Fournisseurs',
                'description' => 'Référentiel fournisseurs : coordonnées, informations de facturation, historique des prix.',
                'permissions' => ['fournisseurs_consulter'],
                'etapes' => [
                    'Ajoutez un fournisseur (nom, contact, téléphone, e-mail, adresse).',
                    'Consultez l\'historique des prix d\'achat par fournisseur et par article.',
                    'Utilisez la même fiche pour toutes les commandes d\'achat.',
                ],
                'peut_gerer' => ['fournisseurs_gerer'],
            ],
            [
                'page' => 'commandes_fournisseur', 'icon' => 'bi-cart-plus',
                'titre' => 'Commandes d\'achat',
                'description' => 'Cycle complet : création → soumission → validation → envoi → réception avec réintégration automatique du stock.',
                'permissions' => ['achats_consulter'],
                'etapes' => [
                    '« Nouvelle commande » : choisissez le fournisseur, ajoutez les articles et les quantités.',
                    'Pré-remplissez depuis les Suggestions d\'achat pour gagner du temps.',
                    'Cycle : Brouillon → En attente → Validée → Envoyée → Reçue.',
                    'À la livraison : « Réceptionner », corrigez les quantités reçues (réception partielle possible).',
                    'Renseignez les DLC des lots pour les périssables lors de la réception.',
                ],
                'peut_gerer' => ['achats_gerer', 'achats_valider'],
            ],
            [
                'page' => 'receptions', 'icon' => 'bi-box-seam',
                'titre' => 'Réceptions fournisseur',
                'description' => 'Historique des réceptions de marchandises avec détail par lot et DLC.',
                'permissions' => ['receptions_consulter'],
                'etapes' => [
                    'Consultez la liste des réceptions avec statut et date.',
                    'Cliquez sur une réception pour voir le détail : articles, quantités, DLC, prix.',
                ],
            ],
            [
                'page' => 'pertes', 'icon' => 'bi-exclamation-triangle',
                'titre' => 'Pertes fournisseur',
                'description' => 'Enregistrement des pertes de marchandise (casse, péremption, vol) avec impact sur le stock.',
                'permissions' => ['pertes_consulter'],
                'etapes' => [
                    '« Nouvelle perte » : article, quantité, motif, commentaire.',
                    'La perte est déduite du stock automatiquement (mouvement de sortie).',
                    'Consultez l\'historique des pertes par période et par article.',
                ],
                'peut_gerer' => ['pertes_gerer'],
            ],
            [
                'page' => 'tarification', 'icon' => 'bi-currency-exchange',
                'titre' => 'Tarification dynamique',
                'description' => 'Règles de prix spéciaux : remises par quantité, par catégorie, promotions saisonnières.',
                'permissions' => ['tarification_consulter'],
                'etapes' => [
                    'Consultez les règles de tarification actives.',
                    'Créez des remises par palier de quantité ou par catégorie.',
                    'Les prix s\'appliquent automatiquement en caisse.',
                ],
                'peut_gerer' => ['tarification_gerer'],
            ],
            [
                'page' => 'inventaire', 'icon' => 'bi-clipboard-check',
                'titre' => 'Inventaire physique',
                'description' => 'Comptage des articles en rayon pour vérifier et corriger le stock théorique.',
                'permissions' => ['inventaire_consulter'],
                'etapes' => [
                    '« Nouvel inventaire » : la session démarre sur votre magasin avec une référence unique.',
                    'Saisissez le comptage de chaque article (écart = compté − théorique).',
                    '« Valider » : les écarts sont appliqués au stock automatiquement (mouvement d\'ajustement).',
                    'Consultez l\'historique des sessions validées et annulées, exportables en CSV.',
                ],
                'peut_gerer' => ['inventaire_gerer'],
            ],
            [
                'page' => 'mouvements', 'icon' => 'bi-arrow-left-right',
                'titre' => 'Mouvements de stock',
                'description' => 'Historique complet : entrées (achats, retours), sorties (pertes, casses, transferts), ajustements.',
                'permissions' => ['stock_consulter'],
                'etapes' => [
                    'Saisissez une entrée (retour de marchandise, don) ou une sortie (perte, casse).',
                    'Consultez l\'historique filtré par type, période et magasin.',
                    'Chaque mouvement est journalisé dans l\'audit trail.',
                ],
            ],
            [
                'page' => 'peremptions', 'icon' => 'bi-calendar-week',
                'titre' => 'Péremptions (DLC/DDM)',
                'description' => 'Surveillance des lots qui expirent : FEFO (premier expiré, premier sorti).',
                'permissions' => ['stock_consulter'],
                'etapes' => [
                    'Liste des lots expirés (rouge) et urgents (orange) de votre magasin.',
                    'Les suggestions d\'achat excluent automatiquement les lots bloqués.',
                    'Sortez les articles expirés du rayon et traitez-les (pertes via mouvement de stock).',
                ],
            ],
            [
                'page' => 'suggestions_achat', 'icon' => 'bi-bag-check',
                'titre' => 'Suggestions d\'achat',
                'description' => 'Liste automatique des articles sous le seuil d\'alerte, regroupés par fournisseur.',
                'permissions' => ['stock_consulter'],
                'etapes' => [
                    'Consultez les articles à réapprovisionner (quantité consommable, hors DLC bloquées).',
                    'Cliquez « Créer la commande » : la commande fournisseur est pré-remplie.',
                ],
            ],
            [
                'page' => 'etiquettes', 'icon' => 'bi-tag',
                'titre' => 'Étiquettes rayon',
                'description' => 'Impression d\'étiquettes de gondole (code-barres EAN-13 + prix) sur papier A4.',
                'permissions' => ['articles_consulter'],
                'etapes' => [
                    'Sélectionnez les articles à étiqueter (ou la catégorie complète).',
                    'Choisissez le nombre d\'étiquettes par article.',
                    'Imprimez la page d\'étiquettes (format A4, prête à découper).',
                ],
            ],
            [
                'page' => 'transferts', 'icon' => 'bi-arrow-repeat',
                'titre' => 'Transferts inter-magasins',
                'description' => 'Déplacement de stock entre magasins : expédition, réception, suivi.',
                'permissions' => ['transferts_consulter'],
                'etapes' => [
                    '« Nouveau transfert » : magasin destinataire, articles et quantités.',
                    '« Expédier » : le stock sort du magasin source (mouvement Transfert).',
                    'Le magasin destinataire réceptionne : le stock y entre lorsque le transfert est « Reçu ».',
                    'Suivez l\'état des transferts dans l\'historique.',
                ],
                'peut_gerer' => ['transferts_gerer'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════
    //  VENTES & CAISSE
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Ventes & Caisse',
        'icon'   => 'bi-cash-coin',
        'color'  => 'sky',
        'modules' => [
            [
                'page' => 'caisse', 'icon' => 'bi-cash-coin',
                'titre' => 'Caisse (POS)',
                'description' => 'Outil principal de vente : scanner, encaisser, imprimer le ticket. Fonctionne hors-ligne.',
                'permissions' => ['caisse_gerer', 'facturation_gerer'],
                'etapes' => [
                    'Ouvrez la Caisse depuis le menu Ventes & Caisse.',
                    'Scannez le code-barres de chaque article avec la douchette (ou saisissez le code manuellement).',
                    'Le prix peut être ajusté si besoin ; vérifiez le total affiché.',
                    'Choisissez le mode de paiement (espèces, carte, mobile money).',
                    'Les boutons de paiement rapide (2 000, 5 000, 10 000 FCFA) accélèrent l\'encaissement.',
                    'Validez la vente (touche F8) : le stock est décrémenté et le ticket 80 mm est imprimé.',
                    'Raccourcis : F2 = vider le panier, F8 = valider.',
                    'En cas de panne réseau, la vente est mise en file d\'attente et synchronisée à la reconnexion.',
                ],
            ],
            [
                'page' => 'factures', 'icon' => 'bi-receipt',
                'titre' => 'Factures',
                'description' => 'Historique des ventes : consultation, réimpression, annulation, export.',
                'permissions' => ['facturation_consulter', 'caisse_gerer'],
                'etapes' => [
                    'Cherchez une facture par numéro, date, statut ou montant (filtres + pagination).',
                    'Cliquez sur « Voir » pour le détail (articles, paiements, monnaie rendue).',
                    'Bouton « Imprimer » : réimpression du ticket thermique.',
                    'Annulez une facture en erreur (le stock est réintégré) avant la clôture du jour.',
                ],
                'peut_gerer' => ['facturation_gerer'],
            ],
            [
                'page' => 'retours', 'icon' => 'bi-arrow-counterclockwise',
                'titre' => 'Retours / SAV',
                'description' => 'Traitement des retours clients : remboursement ou avoir, avec réintégration du stock.',
                'permissions' => ['retours_consulter'],
                'etapes' => [
                    '« Nouveau retour » : sélectionnez la facture d\'origine.',
                    'Choisissez les articles à retourner et la quantité (max 100 %).',
                    'Sélectionnez le motif (défectueux, erreur de caisse, insatisfaction) et le type (remboursement ou avoir).',
                    'Validez : le stock est réintégré automatiquement et le retour est journalisé.',
                ],
                'peut_gerer' => ['retours_gerer'],
            ],
            [
                'page' => 'promotions', 'icon' => 'bi-percent',
                'titre' => 'Promotions & codes promo',
                'description' => 'Création et gestion des remises : pourcentage, montant fixe, par article ou catégorie, codes promo.',
                'permissions' => ['promotions_consulter'],
                'etapes' => [
                    'Consultez les promotions actives (pourcentage ou montant fixe).',
                    'Créez des règles de remise par catégorie ou par article.',
                    'En caisse, saisissez le code promo : la remise s\'applique automatiquement si les conditions sont remplies.',
                ],
                'peut_gerer' => ['promotions_gerer'],
            ],
            [
                'page' => 'cloture', 'icon' => 'bi-lock-fill',
                'titre' => 'Clôture de caisse (Z)',
                'description' => 'Fermeture de la journée de caisse : vérification du montant réel vs attendu.',
                'permissions' => ['caisse_gerer', 'cloture_gerer'],
                'etapes' => [
                    'Ouvrez « Clôture de Caisse » depuis le menu Ventes & Caisse.',
                    'Saisissez le montant réel compté dans le tiroir-caisse.',
                    'L\'écart avec le montant attendu est calculé : tout écart doit être justifié.',
                    'Validez la clôture : la journée est verrouillée (plus d\'annulation de facture).',
                ],
            ],
            [
                'page' => 'clients', 'icon' => 'bi-people',
                'titre' => 'Clients & fidélité',
                'description' => 'Gestion de la relation client : fiches, fidélité, consentements RGPD.',
                'permissions' => ['clients_consulter'],
                'etapes' => [
                    'Consultez la liste des clients inscrits.',
                    'Créez ou modifiez une fiche client (nom, téléphone, email, consentements).',
                    'Le système de fidélité attribution des points automatiquement.',
                ],
                'peut_gerer' => ['clients_gerer'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════
    //  USINE & PRODUCTION
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Usine & Production',
        'icon'   => 'bi-building',
        'color'  => 'amber',
        'modules' => [
            [
                'page' => 'usine', 'icon' => 'bi-building',
                'titre' => 'Tableau de bord usine',
                'description' => 'Vue d\'ensemble de l\'usine : productions en cours, rendements, alertes.',
                'permissions' => ['usine_consulter'],
                'etapes' => [
                    'Consultez les indicateurs : productions actives, rendements, alertes matière première.',
                    'Suivez l\'avancement des productions en temps réel.',
                ],
            ],
            [
                'page' => 'productions', 'icon' => 'bi-gear-wide-connected',
                'titre' => 'Productions',
                'description' => 'Gestion du cycle de production : planification, exécution, clôture avec calcul automatique du rendement.',
                'permissions' => ['production_consulter'],
                'etapes' => [
                    '« Nouvelle production » : sélectionnez l\'article à fabriquer et la recette (nomenclature).',
                    'Renseignez la quantité prévue et la date de début.',
                    'En cours de production : enregistrez les quantités réellement produites et les pertes.',
                    'Clôture : le rendement est calculé automatiquement (conforme / (conforme + pertes)).',
                    'Le coût unitaire est calculé selon le CUMP des matières premières consommées.',
                ],
                'peut_gerer' => ['production_gerer', 'production_cloturer'],
            ],
            [
                'page' => 'matieres_premieres', 'icon' => 'bi-droplet',
                'titre' => 'Matières premières',
                'description' => 'Gestion des intrants : suivi des quantités, prix d\'achat, lots, fournisseurs.',
                'permissions' => ['usine_consulter'],
                'etapes' => [
                    'Consultez la liste des matières premières avec quantités disponibles.',
                    'Ajoutez ou modifiez une matière première (nom, unité, stock, prix).',
                    'Le stock est décrémenté automatiquement lors de la production.',
                ],
                'peut_gerer' => ['usine_gerer'],
            ],
            [
                'page' => 'recettes', 'icon' => 'bi-journal-text',
                'titre' => 'Recettes (nomenclatures)',
                'description' => 'Définition des recettes de fabrication : ingrédients, quantités, versionning.',
                'permissions' => ['usine_consulter'],
                'etapes' => [
                    'Consultez les recettes existantes avec leurs versions.',
                    'Créez une recette : article de sortie + liste des matières premières et quantités.',
                    'Les versions permettent de modifier une recette sans perdre l\'historique.',
                ],
                'peut_gerer' => ['usine_gerer'],
            ],
            [
                'page' => 'stock_usine', 'icon' => 'bi-boxes',
                'titre' => 'Stock usine',
                'description' => 'Inventaire des matières premières et produits finis de l\'usine.',
                'permissions' => ['stock_usine_consulter'],
                'etapes' => [
                    'Consultez les quantités disponibles par matière première et par produit fini.',
                    'Les transferts vers les magasins sont déduits automatiquement.',
                ],
            ],
            [
                'page' => 'personnel', 'icon' => 'bi-people',
                'titre' => 'Personnel usine',
                'description' => 'Gestion des employés de l\'usine : fiches, fonctions, affectations.',
                'permissions' => ['personnel_consulter'],
                'etapes' => [
                    'Consultez la liste du personnel avec matricule, nom, fonction.',
                    'Ajoutez un employé (matricule, nom, prénom, fonction, téléphone).',
                    'Désactivez un employé pour le retirer du suivi.',
                ],
                'peut_gerer' => ['personnel_gerer'],
            ],
            [
                'page' => 'presences', 'icon' => 'bi-clock-history',
                'titre' => 'Présences',
                'description' => 'Suivi des présences du personnel : pointage, absences, retards.',
                'permissions' => ['presence_consulter'],
                'etapes' => [
                    'Consultez le tableau des présences par date.',
                    'Enregistrez les pointages (arrivée, départ) pour chaque employé.',
                    'Les retards et absences sont calculés automatiquement selon les horaires.',
                ],
                'peut_gerer' => ['presence_gerer'],
            ],
            [
                'page' => 'machines', 'icon' => 'bi-gear-wide-connected',
                'titre' => 'Machines',
                'description' => 'Parc machines de l\'usine : suivi de l\'état, maintenance, planification.',
                'permissions' => ['machines_consulter'],
                'etapes' => [
                    'Consultez la liste des machines avec état et dernière maintenance.',
                    'Enregistrez les maintenances et pannes.',
                ],
            ],
            [
                'page' => 'horaires', 'icon' => 'bi-clock',
                'titre' => 'Horaires',
                'description' => 'Gestion des plannings de travail du personnel usine.',
                'permissions' => ['horaires_consulter'],
                'etapes' => [
                    'Consultez les plannings hebdomadaires.',
                    'Créez ou modifiez les horaires de travail par employé.',
                ],
            ],
            [
                'page' => 'notifications', 'icon' => 'bi-bell',
                'titre' => 'Notifications usine',
                'description' => 'Alertes et notifications liées à la production : besoins matière, retard, panne machine.',
                'permissions' => ['notifications_usine_consulter'],
                'etapes' => [
                    'Consultez les notifications non lues (badge sur le menu).',
                    'Marquez comme lues ou supprimez les notifications traitées.',
                    'Les notifications sont générées automatiquement par le système.',
                ],
            ],
        ],
    ],

    // ═══════════════════════════════════════════
    //  ADMINISTRATION
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Administration',
        'icon'   => 'bi-gear',
        'color'  => 'violet',
        'modules' => [
            [
                'page' => 'utilisateurs', 'icon' => 'bi-people',
                'titre' => 'Utilisateurs',
                'description' => 'Gestion des comptes : création, rôles, affectation magasin, désactivation.',
                'permissions' => ['utilisateurs_consulter'],
                'etapes' => [
                    '« Nouvel utilisateur » : nom, prénom, identifiant, mot de passe, rôle, magasin.',
                    'Un utilisateur peut avoir plusieurs rôles (multi-rôles via RBAC dynamique).',
                    'Désactivez un compte pour bloquer l\'accès sans le supprimer.',
                    'Le mot de passe est toujours haché (BCRYPT) : aucun accès en clair.',
                ],
                'peut_gerer' => ['utilisateurs_gerer'],
            ],
            [
                'page' => 'magasins', 'icon' => 'bi-shop',
                'titre' => 'Magasins',
                'description' => 'Gestion des points de vente : création, activation/désactivation.',
                'permissions' => ['magasins_consulter'],
                'etapes' => [
                    'Ajoutez un magasin (nom, adresse). Le stock y est suivi séparément.',
                    'Désactivez un magasin : ses utilisateurs sont verrouillés jusqu\'à réactivation.',
                ],
                'peut_gerer' => ['magasins_gerer'],
            ],
            [
                'page' => 'parametres', 'icon' => 'bi-gear',
                'titre' => 'Paramètres',
                'description' => 'Configuration de la boutique : identité, devise, TVA, ticket, paiements, thème.',
                'permissions' => ['parametres_gerer'],
                'etapes' => [
                    'Onglet Boutique : nom, slogan, coordonnées, devise (FCFA), pays.',
                    'Onglet Taxes : activation de la TVA et taux par défaut.',
                    'Onglet Ticket : format 80 mm, en-tête, remarques, boutons de paiement rapide.',
                    'Onglet Thème : couleur de l\'interface.',
                ],
            ],
            [
                'page' => 'roles', 'icon' => 'bi-shield-lock',
                'titre' => 'Rôles & Permissions',
                'description' => 'Gestion dynamique des rôles et de la matrice de permissions (RBAC).',
                'permissions' => ['roles_gerer'],
                'etapes' => [
                    'Consultez les rôles existants et leurs permissions associées.',
                    'Créez un nouveau rôle (code, nom, niveau hiérarchique).',
                    'Cochez/décochez les permissions de chaque rôle (appliqué immédiatement).',
                    'Les rôles protégés (PROPRIETAIRE, ADMIN, MAGASINIER, VENDEUR) ne sont pas supprimables.',
                ],
                'peut_gerer' => ['roles_gerer', 'permissions_gerer'],
            ],
            [
                'page' => 'depenses', 'icon' => 'bi-wallet2',
                'titre' => 'Dépenses',
                'description' => 'Enregistrement et suivi des charges d\'exploitation.',
                'permissions' => ['depenses_consulter'],
                'etapes' => [
                    '« Nouvelle dépense » : titre, catégorie, montant, date, description.',
                    'La dépense est rattachée au magasin actif.',
                    'Les totaux alimentent le compte de résultat des statistiques.',
                ],
                'peut_gerer' => ['depenses_gerer'],
            ],
            [
                'page' => 'conformite', 'icon' => 'bi-shield-check',
                'titre' => 'Conformité',
                'description' => 'Archives de caisse (NF525), export FEC et SYSCOHADA.',
                'permissions' => ['conformite_archives', 'conformite_export_fec'],
                'etapes' => [
                    'Consultez les archives de caisse (journal des opérations de vente).',
                    'Exportez les écritures comptables au format FEC.',
                    'Exportez les données au format SYSCOHADA pour la comptabilité.',
                ],
                'peut_gerer' => ['conformite_export_syscohada'],
            ],
            [
                'page' => 'exports', 'icon' => 'bi-download',
                'titre' => 'Exports',
                'description' => 'Export de données en CSV pour analyse externe.',
                'permissions' => ['exports_consulter'],
                'etapes' => [
                    'Sélectionnez les données à exporter (ventes, stock, clients…).',
                    'Choisissez la période et le format.',
                    'Le fichier CSV est téléchargé automatiquement.',
                ],
            ],
        ],
    ],

    // ═══════════════════════════════════════════
    //  PROFIL
    // ═══════════════════════════════════════════
    [
        'titre'  => 'Mon compte',
        'icon'   => 'bi-person-gear',
        'color'  => 'rose',
        'modules' => [
            [
                'page' => 'profil', 'icon' => 'bi-person-gear',
                'titre' => 'Mon profil',
                'description' => 'Mise à jour des informations personnelles et du mot de passe.',
                'permissions' => [],
                'etapes' => [
                    'Modifiez vos coordonnées (nom, email, téléphone).',
                    'Changez votre mot de passe (confirmation requise).',
                    'Votre photo de profil est affichée dans la topbar.',
                ],
            ],
        ],
    ],
];

/* ─────────────────────────────────────────────────────────────
 *  Filtrer les zones et modules selon les permissions
 * ───────────────────────────────────────────────────────────── */
$zones_visibles = [];
$search_data    = []; // pour le JS de recherche

foreach ($zones as $zone) {
    $modules_visibles = [];
    foreach ($zone['modules'] as $mod) {
        $perms = $mod['permissions'] ?? [];
        // Module visible si pas de permission requise OU si l\'utilisateur en a au moins une
        $visible = empty($perms);
        if (!$visible) {
            foreach ($perms as $p) {
                if (peut($p)) { $visible = true; break; }
            }
        }
        if (!$visible) continue;

        // Vérifier si le module est en lecture seule (pas de permission d\'écriture)
        $read_only = false;
        $peut_gerer = $mod['peut_gerer'] ?? [];
        if (!empty($peut_gerer)) {
            $has_write = false;
            foreach ($peut_gerer as $pw) {
                if (peut($pw)) { $has_write = true; break; }
            }
            $read_only = !$has_write;
        }

        $mod['read_only'] = $read_only;
        $modules_visibles[] = $mod;

        // Index de recherche
        $search_data[] = [
            'titre'    => $mod['titre'],
            'desc'     => $mod['description'],
            'page'     => $mod['page'],
            'zone'     => $zone['titre'],
        ];
    }
    if (!empty($modules_visibles)) {
        $zone_copy         = $zone;
        $zone_copy['modules'] = $modules_visibles;
        $zones_visibles[]  = $zone_copy;
    }
}

echo $twig->render('documentation.html.twig', [
    'titre_page'    => 'Documentation',
    'zones'         => $zones_visibles,
    'search_data'   => $search_data,
    'role_courant'  => $role,
    'nom_magasin'   => $nom_magasin,
    'user'          => $u,
]);
