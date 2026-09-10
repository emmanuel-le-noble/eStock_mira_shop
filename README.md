# eStock — Gestion de Stock, Point de Vente & Usine de Production

**Version :** 2.5.0 | **Date :** 10 septembre 2026 | **Licence :** MIT
**Type :** Application Web (POS + Stock + Production)
**Marché cible :** Togolais / OHADA (FCFA, Africa/Lome, SYSCOHADA, OTR)
**Base URL :** https://stockpro.goodhealthforever.net

---

## TABLE DES MATIÈRES

1. [Vue d'ensemble](#1-vue-densemble-du-système)
2. [Installation](#2-installation)
3. [Authentification et Gestion des Utilisateurs](#3-authentification-et-gestion-des-utilisateurs)
4. [Gestion des Articles](#4-gestion-des-articles)
5. [Gestion du Stock](#5-gestion-du-stock)
6. [Point de Vente (Caisse)](#6-point-de-vente-caisse)
7. [Gestion de la Fidélité Clients](#7-gestion-de-la-fidélité-clients)
8. [Commandes Fournisseurs](#8-commandes-fournisseurs)
9. [Réceptions Fournisseur et Pertes](#9-réceptions-fournisseur-et-pertes)
10. [Gestion des Péremptions et Lots](#10-gestion-des-péremptions-et-lots)
11. [Transferts Inter-Magasins](#11-transferts-inter-magasins)
12. [Retours et Avoirs (SAV)](#12-retours-et-avoirs-sav)
13. [Promotions et Ventes Flash](#13-promotions-et-ventes-flash)
14. [Tarification Dynamique](#14-tarification-dynamique)
15. [Dépenses](#15-dépenses)
16. [Conformité et Export Comptable](#16-conformité-et-export-comptable)
17. [Statistiques et Tableau de Bord](#17-statistiques-et-tableau-de-bord)
18. [Exports de Données](#18-exports-de-données)
19. [Impression](#19-impression)
20. [Module Usine de Production](#20-module-usine-de-production)
21. [Gestion du Personnel](#21-gestion-du-personnel)
22. [Paramétrage de l'Application](#22-paramétrage-de-lapplication)
23. [Journal d'Audit et Traçabilité](#23-journal-daudit-et-traçabilité)
24. [Sécurité](#24-sécurité)
25. [API REST](#25-api-rest)
26. [Progressive Web App (PWA)](#26-progressive-web-app-pwa)
27. [Annexes](#27-annexes)
28. [Historique des versions](#28-historique-des-versions)
28. [Historique des versions](#28-historique-des-versions)

---

## 1. VUE D'ENSEMBLE DU SYSTÈME

### 1.1. Description générale

eStock est une application de gestion de stock, point de vente (POS) et usine de production conçue pour les commerces, boutiques et unités de production au Togo et dans l'espace OHADA. Elle couvre l'intégralité du cycle commercial : articles, fournisseurs, ventes en caisse, suivi des stocks, commandes, réceptions, fidélité client, conformité comptable, production industrielle et gestion du personnel.

### 1.2. Fonctionnalités principales

**Commerce & Vente :**
- Gestion des articles avec codes-barres EAN-13 et impression d'étiquettes
- Gestion multi-magasins avec stock indépendant par magasin
- Point de vente avec scan codes-barres, modes de paiement multiples (Espèces, Mobile Money, Carte bancaire)
- Vente hors-ligne avec synchronisation automatique (Service Worker + localStorage)
- Vente au poids (KG, L, g, mL)
- Clôture de caisse avec calcul d'écarts

**Stock & Approvisionnement :**
- Suivi des stocks en temps réel avec alertes de stock bas
- Gestion des lots et péremptions (FEFO — First Expired First Out)
- Inventaire physique avec comptage et ajustements
- Commandes fournisseurs avec workflow de validation
- Réceptions fournisseur avec gestion des pertes et traçabilité
- Transferts inter-magasins
- Historique des prix fournisseurs

**Tarification & Promotions :**
- Tarification dynamique par tranches (prix selon quantité)
- Promotions manuelles (codes promo) et ventes flash automatiques
- Règles de réduction par péremption proche et surstock

**Fidélité & Clients :**
- Système de fidélité clients avec points
- Cartes de fidélité imprimables (CODE128)
- Gestion B2C et B2B (NIF, RCCM, raison sociale)

**Production & Usine :**
- Module usine de production complet (architecture indépendante)
- Recettes/nomenclatures de production
- Gestion des matières premières
- Suivi des productions (workflow BROUILLON → TERMINEE)
- Suivi des pertes de production
- Transfert usine → magasin
- Gestion du personnel usine et présences

**Comptabilité & Conformité :**
- Export comptable SYSCOHADA (journaux VT, AC, DG, INV)
- Conformité fiscale togolaise (TPU/TVA)
- Prêt pour e-facturation OTR
- Chaînage cryptographique SHA-256 des factures
- Triggers d'immuabilité sur données financières
- Désinscription e-mails (conformité GDPR)

**Administration :**
- RBAC dynamique (rôles et permissions gérés en base)
- Journal d'audit complet de toutes les actions
- Authentification Google OAuth 2.0
- Progressive Web App (PWA) avec mode hors-ligne

### 1.3. Architecture technique

| Composant | Technologie | Version |
|---|---|---|
| Langage | PHP | 8.x |
| Base de données | MySQL (PDO, requêtes préparées) | 8.0+ |
| Moteur de templates | Twig | 3.x |
| Interface | Bootstrap | 5.3.3 |
| PDF | TCPDF | 6.7+ |
| Codes-barres | JsBarcode | 3.11.6 (CDN) |
| Graphiques | Chart.js | 4.x (CDN) |
| Icônes | Bootstrap Icons | 1.11.3 |
| Police | Poppins | Google Fonts |
| Client Redis | Predis | 2.2 |
| Testing | PHPUnit | 11.5 |
| Auth externe | Google OAuth | 2.0 |
| PWA | Service Worker + Manifest JSON | — |

### 1.4. Rôles utilisateurs

| Rôle | Code | Description | Niveau |
|---|---|---|---|
| Propriétaire | `PROPRIETAIRE` | Accès total, switch magasins | 5 |
| Administrateur | `ADMIN` | Gestion utilisateurs, paramètres, stats | 4 |
| Chef d'équipe boutique | `CHEF_EQUIPE` | Équipe boutique, supervision ventes | 3 |
| Chef d'équipe usine | `CHEF_EQUIPE_USINE` | Équipe usine, supervision production | 3 |
| Magasinier | `MAGASINIER` | Stocks, inventaire, transferts, commandes | 2 |
| Vendeur | `VENDEUR` | Caisse, factures, retours, promotions | 1 |

> Le RBAC est **100% dynamique** — les rôles sont gérés en base via `roles.php`. Un utilisateur peut avoir **plusieurs rôles** (multi-rôles via `user_roles`). Les rôles sont définis par leurs **permissions**, pas par du code PHP. Les rôles `PROPRIETAIRE`, `ADMIN`, `MAGASINIER`, `VENDEUR` sont protégés (non supprimables). Les rôles `CHEF_EQUIPE` et `CHEF_EQUIPE_USINE` peuvent être personnalisés.

> **Règle fondamentale :** Le rôle définit **ce que l'utilisateur peut faire**. L'équipe définit **dans quelle organisation il travaille**. Le périmètre (magasin) définit **sur quelles ressources il peut agir**.

### 1.5. Matrice des permissions

| Module | Propriétaire | Admin | Chef Équipe | Magasinier | Vendeur |
|---|:---:|:---:|:---:|:---:|:---:|
| Tableau de bord | OUI | OUI | OUI | OUI | OUI |
| Articles | OUI | OUI | OUI* | OUI | NON |
| Stock / Mouvements | OUI | OUI | OUI* | OUI | NON |
| Caisse / Ventes | OUI | OUI | OUI* | NON | OUI |
| Factures | OUI | OUI | OUI* | NON | OUI |
| Clôture caisse | OUI | OUI | OUI* | NON | OUI |
| Clients / Fidélité | OUI | OUI | OUI* | NON | OUI |
| Promotions | OUI | OUI | OUI* | NON | OUI |
| Retours / SAV | OUI | OUI | OUI* | NON | OUI |
| Inventaire | OUI | OUI | OUI* | OUI | NON |
| Commandes fournisseurs | OUI | OUI | NON | OUI | NON |
| Réceptions fournisseur | OUI | OUI | NON | OUI | NON |
| Pertes fournisseur | OUI | OUI | NON | OUI | NON |
| Transferts inter-magasins | OUI | OUI | NON | OUI | NON |
| Péremptions / Lots | OUI | OUI | NON | OUI | NON |
| Tarification dynamique | OUI | OUI | NON | NON | NON |
| Étiquettes | OUI | OUI | NON | OUI | NON |
| Statistiques | OUI | OUI | NON | NON | NON |
| Dépenses | OUI | OUI | NON | NON | NON |
| Utilisateurs | OUI | OUI | NON | NON | NON |
| Magasins | OUI | OUI | NON | NON | NON |
| Paramètres | OUI | OUI | NON | NON | NON |
| Rôles & Permissions | OUI | OUI | NON | NON | NON |
| Équipes | OUI | OUI | NON | NON | NON |
| Export CSV | OUI | OUI | NON | OUI | NON |
| Export SYSCOHADA | OUI | NON | NON | NON | NON |
| Journal d'audit | OUI | OUI | NON | OUI | OUI |
| Conformité | OUI | NON | NON | NON | NON |
| Usine (dashboard) | OUI | OUI | OUI* | NON | NON |
| Productions | OUI | OUI | OUI* | NON | NON |
| Recettes / Nomenclatures | OUI | OUI | OUI* | NON | NON |
| Matières premières | OUI | OUI | OUI* | NON | NON |
| Personnel usine | OUI | OUI | OUI* | NON | NON |
| Présences | OUI | OUI | OUI* | NON | NON |
| Machines | OUI | OUI | OUI* | NON | NON |
| Notifications usine | OUI | OUI | OUI* | NON | NON |
| Horaires | OUI | OUI | OUI* | NON | NON |
| Rendement | OUI | OUI | OUI* | NON | NON |
| Stock usine | OUI | OUI | NON | NON | NON |

> *OUI* = permissions configurables via le RBAC. Les chefs d'équipe n'ont pas de droits automatiques — leurs permissions sont définies par le rôle assigné en base.

### 1.6. Structure des répertoires

```
eStock_mira_shop/
├── api/                    # API REST centralisée
│   ├── index.php           # Routeur principal (1695 lignes)
│   └── .htaccess           # Rewrite + headers sécurité
├── assets/
│   ├── css/
│   │   ├── style.css       # Design "Liquid Glass (Aurora)" (876 lignes)
│   │   └── print-documents.css  # Styles d'impression (472 lignes)
│   ├── images/             # Logos
│   └── js/
│       ├── app.js          # JS global (sidebar, confirmations) (174 lignes)
│       └── caisse.js       # Logique POS complète (1290 lignes)
├── auth/
│   ├── login.php           # Connexion standard
│   ├── logout.php          # Déconnexion sécurisée
│   ├── choisir_magasin.php # Sélection magasin (Directeur)
│   ├── google_login.php    # Redirect OAuth Google
│   └── google_callback.php # Callback OAuth Google
├── backups/                # Sauvegardes ZIP chiffrées
├── bin/                    # Outils CLI
│   ├── backup_db.php       # Sauvegarde BDD + ZIP + rotation
│   ├── create_admin.php    # Création compte Propriétaire
│   ├── emails_worker.php   # Worker email async (cron)
│   ├── verif_integrite.php # Vérification chaîne cryptographique
│   ├── test_restore.php    # Test restauration sauvegarde
│   ├── purge_donnees_clients.php  # Purge RGPD
│   ├── disable_demo_accounts.php  # Désactivation comptes démo
│   ├── apply_migration.php # Application migrations SQL
│   ├── inventaire_tables.php  # Inventaire des tables
│   └── preflight_release.php   # Contrôles pré-livraison
├── config/
│   ├── connexion.php       # Bootstrap principal (1036 lignes)
│   ├── parametres.php      # Système de paramètres (267 lignes)
│   ├── estock_db.sql       # Schéma de référence
│   └── .htaccess           # Blocage accès
├── database/
│   ├── estock_db.sql       # Schéma complet avec toutes les migrations intégrées
│   ├── migration_consolidee.sql  # Migration unique idempotente (remplace les 13 ancien fichiers)
│   └── sql_splitter.php    # Parser SQL (DELIMITER, triggers)
├── docs/
│   ├── conformite/
│   │   ├── integrite.md    # Chaînage cryptographique
│   │   └── otr.md          # Conformité fiscale togolaise
│   ├── decisions/
│   │   └── adr.md          # Architecture Decision Records
│   └── production_checklist.md
├── includes/
│   ├── db_functions.php    # Toutes les requêtes SQL (5155 lignes)
│   ├── usine_functions.php # Module usine (918 lignes)
│   ├── helpers.php         # Logique métier (1083 lignes)
│   ├── header.php          # Layout sidebar + topbar (158 lignes)
│   ├── sidebar.php         # Sidebar unifiée (partagée PHP + Twig) (68 lignes)
│   ├── footer.php          # Fermeture layout + JS (58 lignes)
│   ├── security_headers.php # CSP, HSTS, X-Frame (16 lignes)
│   ├── letterhead.php      # En-tête documents (130 lignes)
│   └── acces_refuse.php    # Page 403
├── templates/              # 24 templates Twig
├── tests/
│   ├── bootstrap.php       # Bootstrap PHPUnit
│   ├── Unit/               # 4 tests unitaires
│   ├── Integration/        # 6 tests d'intégration
│   └── fixtures/           # Fixtures concurrence
├── *.php                   # 40+ pages PHP racine
├── composer.json           # Dépendances Composer
├── phpunit.xml             # Config PHPUnit
├── manifest.json           # PWA manifest
├── manifest.php            # PWA manifest dynamique
├── sw.js                   # Service Worker
├── .env                    # Variables d'environnement
└── .htaccess               # Config Apache racine
```

---

## 2. INSTALLATION

### 2.1. Prérequis

- PHP 8.1+ avec extensions : `pdo_mysql`, `openssl`, `mbstring`, `json`, `session`, `gd`
- MySQL 8.0+ ou MariaDB 10.5+
- Apache 2.4+ avec `mod_rewrite`
- Composer 2.x

### 2.2. Étapes

```bash
# 1. Cloner le projet
git clone <repo-url> && cd eStock_mira_shop

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env.example .env
# Éditer .env : DB_HOST, DB_NAME, DB_USER, DB_PASS, SECRET_URL_KEY (min 32 car.)

# 4. Créer la base de données
mysql -u root -e "CREATE DATABASE estock_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 5. Importer le schéma
mysql -u root estock_db < database/estock_db.sql

# 6. Appliquer les migrations
php bin/apply_migration.php

# 7. Créer le compte Propriétaire
php bin/create_admin.php "Nom Complet" login "mot-de-passe-12car" [magasin_id]

# 8. Lancer l'installation web (alternative)
# Accéder à http://localhost/eStock_mira_shop/install.php
# Un fichier .installed est créé automatiquement après installation
```

### 2.3. Variables d'environnement (.env)

| Variable | Obligatoire | Description |
|---|---|---|
| `DB_HOST` | Oui | Hôte MySQL (défaut: 127.0.0.1) |
| `DB_NAME` | Oui | Nom de la base (défaut: estock_db) |
| `DB_USER` | Oui | Utilisateur MySQL |
| `DB_PASS` | Oui | Mot de passe MySQL |
| `SECRET_URL_KEY` | Oui | Clé HMAC signées URLs (min 32 car.) |
| `APP_ENV` | Non | `development` ou `production` (défaut: development) |
| `APP_TIMEZONE` | Non | Fuseau horaire (défaut: Africa/Lome) |
| `DB_NAME_TEST` | Non | Base de test pour PHPUnit (défaut: estock_db_test) |

### 2.4. Configuration Apache

Le fichier `.htaccess` racine :
- Force UTF-8, désactive le listing des répertoires
- Bloque l'accès aux fichiers sensibles (`.env`, `composer.json`, `phpunit.xml`)
- Compresse les réponses (gzip)
- Bloque les répertoires protégés : `config/`, `includes/`, `database/`, `vendor/`, `templates/`, `bin/`, `tests/`, `backups/`, `logs/`, `archives/`, `var/`, `docs/`
- Active la réécriture d'URL

---

## 3. AUTHENTIFICATION ET GESTION DES UTILISATEURS

### 3.1. Connexion standard

**Fichier :** `auth/login.php`

Procédure :
1. Affichage du formulaire (split-screen, toggle visibilité mot de passe)
2. Vérification CSRF (`csrf_validate()`)
3. Vérification rate limiting (`login_rate_limited()` — 5 tentatives / 15 min par IP)
4. Recherche utilisateur par login (`db_user_get_by_login()`)
5. Vérification mot de passe (`password_verify()` — bcrypt)
6. Anti-timing : `usleep(random_int(100000, 300000))` sur succès ET échec
7. Régénération session (`session_regenerate_id(true)`)
8. Nettoyage : retrait du hash mot de passe de la session
9. Journalisation (`suivre_activite('CONNEXION')` ou `'ECHEC_CONNEXION'`)
10. Redirection : Propriétaire → `choisir_magasin.php`, autres → `tableau_bord.php`

### 3.2. Connexion Google OAuth 2.0

**Fichiers :** `auth/google_login.php`, `auth/google_callback.php`

Flow :
1. Vérification que Google OAuth est activé et configuré
2. Génération state CSRF (`bin2hex(random_bytes(32))`)
3. Redirect vers Google (`accounts.google.com/o/oauth2/v2/auth`)
4. Callback : validation state (`hash_equals()`), échange code → token
5. Récupération profil (email, nom, avatar)
6. Liaison compte : si email existant → mise à jour `google_id`; sinon → création compte
7. Session et redirection

Sécurité : state CSRF consommé immédiatement, cURL avec `SSL_VERIFYPEER`, timeout 15s.

### 3.3. Déconnexion

**Fichier :** `auth/logout.php`

- **POST-only** (rejette GET)
- Validation CSRF obligatoire
- 3 étapes sécurisées : `$_SESSION = []` → `session_regenerate_id(true)` → `session_destroy()`
- Expiration cookie : `setcookie()` avec expiry passé
- Journalisation `DECONNEXION`

### 3.4. Gestion des sessions

| Paramètre | Valeur |
|---|---|
| Durée cookie | Session (fermeture navigateur) |
| Secure | Oui si HTTPS |
| HttpOnly | Oui (pas d'accès JS) |
| SameSite | Lax |
| Timeout inactivité | 30 minutes |
| Timeout absolu | 8 heures |

### 3.5. Sélection du magasin (Propriétaire)

**Fichier :** `auth/choisir_magasin.php`

- Uniquement pour le rôle PROPRIETAIRE
- Cards radio pour sélectionner le magasin
- Le choix est stocké en `$_SESSION['magasin_actif']`
- Validation serveur que le magasin existe et est actif
- Protection contre les open redirects (whitelist + `file_exists()`)

### 3.6. Gestion des utilisateurs

**Fichier :** `utilisateurs.php`
**Accès :** PROPRIETAIRE, ADMIN

Fonctionnalités :
- Création avec choix de rôle et magasin assigné
- Modification (avec ou sans changement de mot de passe)
- Activation / désactivation (suppression logique)
- Hiérarchie : un utilisateur ne peut modifier que les utilisateurs de niveau inférieur
- Impossible de désactiver son propre compte
- Multi-rôles via `user_roles`

Données d'un utilisateur :
- Nom, Login, Mot de passe (hashé bcrypt), Rôle, Magasin, État
- Google ID, Google Email, Google Avatar ( OAuth )
- Secret TOTP et état 2FA (infrastructure prête)

### 3.7. Profil utilisateur

**Fichier :** `profil.php`

- Tout utilisateur peut consulter et modifier son profil
- Changement de mot de passe avec :
  - Vérification du mot de passe actuel
  - Nouveau mot de passe différent de l'actuel
  - Minimum 8 caractères
  - Rate limiting : 5 tentatives / 15 minutes

### 3.8. Système de permissions (RBAC dynamique)

**Fichier :** `roles.php`

Tables :
- `roles` : rôles personnalisables (code, nom, description)
- `user_roles` : association utilisateur ↔ rôle(s) (multi-rôles)
- `permissions` : ~100+ permissions par clé
- `role_permissions` : association rôle ↔ permission

Fonctions PHP :
- `peut($cle)` : vérifie si l'utilisateur a la permission
- `exiger_permission($cle)` : bloque l'accès si non autorisé
- `user_roles()` : retourne les rôles de l'utilisateur
- `user_has_role($code)` : vérifie un rôle spécifique

Rôles protégés (non supprimables) : `PROPRIETAIRE`, `ADMIN`, `MAGASINIER`, `VENDEUR`

---

## 4. GESTION DES ARTICLES

### 4.1. Page de gestion

**Fichier :** `articles.php`
**Accès :** PROPRIETAIRE, ADMIN, MAGASINIER
**Permission :** `articles_gerer`

### 4.2. Liste des articles

Tableau paginé (25 par page) avec colonnes :
- Nom (gras), Code-barres (format), SKU
- Prix de vente (FCFA), Stock (badge vert/rouge)
- Rayon/Emplacement, Fournisseur, Catégorie
- Actions : Modifier, Supprimer

Recherche : par nom, code-barres ou SKU via champ en haut de page.

### 4.3. Données d'un article

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| Code-barres | EAN-13 | Oui | Code unique 13 chiffres |
| Nom | Texte (max 200) | Oui | Désignation |
| SKU | Texte (max 64) | Non | Unité de gestion |
| Prix d'achat | Décimal | Oui | Prix unitaire HT |
| Prix de vente | Décimal | Oui | Prix unitaire TTC |
| Seuil d'alerte | Entier | Oui (défaut: 5) | Seuil stock bas |
| Emplacement | Texte (max 100) | Non | Position rayon (ex: A1-01) |
| Fournisseur | Sélection | Non | Lien fournisseur |
| Catégorie | Sélection | Non | Lien catégorie |
| Taux TVA | Décimal | Non | Taux spécifique (sinon taux global) |
| Type article | Enum | Oui (défaut: ARTICLE_COMMERCIAL) | MATIERE_PREMIERE / PRODUIT_FINI / ARTICLE_COMMERCIAL / CONSOMMABLE |
| Origine | Enum | Oui (défaut: ACHAT_FOURNISSEUR) | ACHAT_FOURNISSEUR / PRODUCTION_USINE |
| Coût production ref | Décimal | Non | Coût unitaire de production de référence |

### 4.4. Génération de code-barres EAN-13

1. Clic sur "Générer" → appel API `api/generer_code_barre`
2. Format : Préfixe "20" + 10 chiffres aléatoires + 1 chiffre de contrôle
3. Aperçu visuel via JsBarcode en temps réel

### 4.5. Catégories

**Fichier :** `categories.php`
**Permission :** `articles_gerer`

- Liste avec nombre d'articles
- CRUD complet (nom, description, état actif/inactif)

### 4.6. Fournisseurs

**Fichier :** `fournisseurs.php`
**Accès :** PROPRIETAIRE, ADMIN, MAGASINIER

- Liste avec nombre d'articles liés
- CRUD complet (nom, adresse, téléphone, email, contact)

### 4.7. Validation

- Code-barres : EAN-13 valide (13 chiffres + check digit), unicité en base
- Nom : obligatoire, max 200 caractères
- Prix : positifs ou nuls
- Protection CSRF sur tous les formulaires

---

## 5. GESTION DU STOCK

### 5.1. Consultation du stock

**Fichier :** `stock.php`
**Accès :** PROPRIETAIRE, ADMIN, MAGASINIER

- Liste des articles avec quantité en stock
- Badge coloré : vert (OK), rouge (bas/épuisé)
- Filtrage par magasin (ADMIN/PROPRIETAIRE voient tous)
- Valeur totale du stock affichée

### 5.2. Mouvements de stock

**Fichier :** `mouvements.php`
**Permission :** `stock_consulter`

Types de mouvements :

| Type | Signe | Effet | Origine |
|---|---|---|---|
| `Entree` | + | Augmente | Réception, retour client, inventaire, ajout de lot |
| `Sortie` | - | Diminue | Sortie manuelle (casse, jet, échantillon) |
| `Vente` | - | Diminue | Vente en caisse |
| `Transfert` | - | Diminue | Transfert inter-magasins (source) |
| `Ajustement` | +/- | Variable | Validation inventaire physique |
| `Retour_stock` | + | Augmente | Retour client SAV |
| `Production` | + | Augmente | Production usine (produit fini) |
| `Perte_production` | - | Diminue | Perte lors de production |

Formulaire d'ajout :
- Article, Type, Quantité, Motif (obligatoire)
- Numéro de lot (optionnel, pour les entrées)
- Date de péremption (optionnelle)

### 5.3. Inventaire physique

**Fichier :** `inventaire.php`
**Permission :** `inventaire_consulter`

Workflow :
1. Création session (référence auto : `INV-YYYY-NNNN`)
2. Comptage article par article :
   - Scan code-barres ou recherche
   - Affichage stock théorique
   - Saisie stock physique
   - Calcul automatique écart
3. Validation : application des écarts (mouvements automatiques)
4. Annulation possible (aucune modification au stock)

### 5.4. Stock multi-magasins

- Chaque magasin a son propre stock pour chaque article (`stock_magasins`)
- Stock global (`articles.quantite_stock`) maintenu synchronisé
- Nouvel article → initialisé dans tous les magasins actifs
- Nouveau magasin → tous les articles initialisés à 0

### 5.5. Valorisation CUMP

Le système applique la valorisation **Coût Unitaire Moyen Pondéré** :
- À chaque entrée : `articles.cump` recalculé (moyenne pondérée)
- Couches FIFO dans `article_couts` pour traçabilité
- Consommation FIFO aux sorties/ventes
- CAMV (Coût d'Achat Marchandises Vendues) valorisé au CUMP courant
- Fallback sur `prix_achat` pour les articles antérieurs

---

## 6. POINT DE VENTE (CAISSE)

### 6.1. Interface de caisse

**Fichier :** `caisse.php`
**Accès :** PROPRIETAIRE, ADMIN, VENDEUR
**Permission :** `caisse_gerer`

Layout deux colonnes :
- Gauche (8/12) : Zone scan + alertes + panier
- Droite (4/12) : Panel paiement + fidélité + totaux + valider

### 6.2. Scan de codes-barres

1. Champ de scan en permanence en focus
2. Appel API `api/scan?code=X`
3. Anti-doublon : ignore scans identiques dans les 800ms
4. Vérification FEFO : bloque si lot expiré
5. Alerte péremption : avertissement si DLC < 7 jours
6. Détection automatique promotions (vente flash)
7. Tarification dynamique selon quantité

### 6.3. Gestion du panier

Fonctionnalités :
- Ajout automatique par scan
- Modification quantités (+1/-1/saisie directe)
- Suppression ligne, vidage complet (F2)
- Calcul automatique HT, TVA, TTC
- Affichage promotions appliquées
- Contrôle disponibilité stock
- Vente au poids : modal de saisie du poids

### 6.4. Modes de paiement

Modes supportés :
- **Espèces** : calcul monnaie, boutons rapides (20, 50, 100)
- **Mobile Money** : champ référence obligatoire
- **Carte bancaire**

Paiement multiple (split) :
- Les trois modes simultanés
- Barre de progression du paiement
- Bouton "Valider" activé uniquement quand total TTC atteint

### 6.5. Validation de la vente

**En ligne :**
1. POST vers `valider_facture.php`
2. Validation serveur (stock, prix, promotions, CSRF)
3. Génération numéro facture : `{PRÉFIXE}-YYYYMMDD-NNNN`
4. Déduction stock (FEFO)
5. Enregistrement paiements
6. Chaînage cryptographique (SHA-256)
7. Attribution points fidélité
8. Redirection impression ticket

**Hors-ligne :**
1. Sauvegarde dans `localStorage` (UUID par vente)
2. Badge connexion : vert (en ligne), orange (hors-ligne)
3. Synchronisation auto au retour connexion (`api/caisse/sync`)
4. Vérification idempotence (`client_sale_id` UUID)

### 6.6. Clôture de caisse

**Fichier :** `cloture.php`
**Permission :** `cloture_gerer`

Workflow :
1. Résumé : nombre de ventes, montant attendu
2. Saisie montant réel compté
3. Calcul automatique écart
4. Validation : clôture enregistrée, ventes verrouillées
5. Fermeture session de vente (impossible de scanner après)

### 6.7. Numérotation des factures

Format : `{PRÉFIXE}-YYYYMMDD-NNNN` (ex: `FAC-20260823-0001`)
- Préfixe configurable (`Paramètres → facture_prefixe`)
- Séquence atomique (table `sequences` avec `INSERT ... ON DUPLICATE KEY UPDATE`)
- Chaînage cryptographique SHA-256 pour intégrité

---

## 7. GESTION DE LA FIDÉLITÉ CLIENTS

### 7.1. Module clients

**Fichier :** `clients.php`
**Permission :** `clients_gerer`

Données :
- Nom, téléphone, email
- Type : B2C ou B2B
- Code fidélité (auto : `F` + ID en 8 chiffres)
- Solde points, Consentement fidélité
- B2B : raison sociale, NIF, RCCM
- Historique achats

Fonctionnalités :
- Recherche par nom, téléphone, email, code fidélité
- Anonymisation GDPR possible

### 7.2. Système de points

- Génération : `floor(total_ttc / points_par_devise)`
- Points par devise configurables (défaut: 1 point / 100 FCFA)
- Utilisation en caisse comme réduction
- Minimum requis (défaut: 10 points)
- Historique transactions : GAIN, UTILISATION, EXPIRATION, AJUSTEMENT, ANNULE

### 7.3. Carte de fidélité

**Fichier :** `carte_fidelite.php`
**Template :** `templates/carte_fidelite.html.twig`

- Format 85mm x 55mm (carte de visite)
- Code-barres CODE128 du code fidélité
- Nom client, solde points
- Impression directe

---

## 8. COMMANDES FOURNISSEURS

### 8.1. Page de gestion

**Fichier :** `commandes_fournisseur.php`
**Permission :** `achats_consulter`

### 8.2. Workflow de commande

| Étape | Statut | Qui | Action |
|---|---|---|---|
| 1. Création | `Brouillon` | Magasinier | Création avec lignes |
| 2. Soumission | `En_Attente` | Magasinier | Demande validation |
| 3. Approbation | `Envoyée` | Admin/Propriétaire | Envoi au fournisseur |
| 4. Réception | `Recue_Partielle` / `Recue` | Magasinier | Enregistrement quantités reçues |

### 8.3. Ligne de commande

- Article, Quantité commandée, Quantité reçue, Quantité reçue via réceptions, Quantité perdue
- Prix unitaire d'achat

### 8.4. Impact sur le stock

Lors de la réception (via `receptions.php`) :
- Mouvement de stock type "Entrée"
- Valorisation CUMP
- Création de lots si numéro de lot fourni
- Mise à jour historique prix fournisseur

---

## 9. RÉCEPTIONS FOURNISSEUR ET PERTES

### 9.1. Réceptions fournisseur

**Fichier :** `receptions.php`
**Permission :** `receptions_consulter`

Affiche l'historique complet des réceptions :
- Référence (`REC-YYYY-NNNN`), date, fournisseur, magasin, statut
- Détail lignes : quantités attendues, reçues, acceptées, perdues
- Prix d'achat unitaire, numéro lot, DLC
- Filtres par fournisseur, magasin, période
- Lien vers commande d'origine

Workflow de validation :
1. Sélection commande
2. Saisie quantités reçues par ligne
3. Enregistrement pertes (motif obligatoire)
4. Validation → mouvements stock automatiques
5. Immutabilité : réception validée ne peut être modifiée

### 9.2. Pertes fournisseur

**Fichier :** `pertes.php`
**Permission :** `pertes_consulter`

Registre dédié :
- Filtres fournisseur, magasin, période
- Types : endommagé, manquant, expiré, non conforme, casse livraison, erreur fournisseur, autre
- Export CSV

### 9.3. Historique des prix fournisseurs

- Table `fournisseur_prix_historique`
- Sources : commande, réception, saisie manuelle
- Prix de référence mis à jour automatiquement

### 9.4. Permissions

| Permission | Description |
|---|---|
| `receptions_consulter` | Consulter les réceptions |
| `receptions_gerer` | Créer/gérer les réceptions |
| `pertes_consulter` | Consulter les pertes |
| `pertes_gerer` | Enregistrer les pertes |
| `prix_fournisseur_consulter` | Voir historique prix |
| `prix_fournisseur_gerer` | Modifier prix fournisseurs |

---

## 10. GESTION DES PÉREMPTIONS ET LOTS

### 10.1. Suivi des lots

**Fichier :** `peremptions.php`
**Permission :** `inventaire_consulter`

Données par lot : numéro, DLC/DDM, quantité, magasin.

### 10.2. Méthode FEFO

- Lot le plus proche de la péremption utilisé en premier
- Lot expiré → vente bloquée
- Lot expire < 7 jours → avertissement
- Pas de lot disponible → vente impossible

### 10.3. Alertes

- Badge dans le menu (nombre d'alertes)
- Lots expirés et expirants (< 30 jours)
- Catégories : expiré, urgent (<7j), attention (<30j), OK, sans DLC

### 10.4. Promotions automatiques

- Péremption proche : réduction configurée
- Surstock : réduction si stock dépasse seuil

---

## 11. TRANSFERTS INTER-MAGASINS

**Fichier :** `transferts.php`
**Permission :** `transferts_consulter`

Processus :
1. Sélection article, magasin source, magasin destination
2. Saisie quantité et motif
3. Validation atomique :
   - Vérification disponibilité source
   - Décrémentation lots FEFO source
   - Transfert mêmes lots destination
   - Décrémentation stock source
   - Incrémentation stock destination
   - Deux mouvements créés (Transfert sortie + Entrée)

---

## 12. RETOURS ET AVOIRS (SAV)

**Fichier :** `retours.php`
**Permission :** `retours_consulter`

Processus :
1. Recherche facture originale par numéro
2. Sélection lignes à retourner (quantité ≤ non-déjà-retournée)
3. Soumission → création enregistrement `retours_factures`
4. Pour chaque ligne : mouvement "Retour_stock" (Entrée)
5. Remboursement proportionnel points fidélité

Restrictions : Magasinier ne peut retourner que les factures de son magasin.

---

## 13. PROMOTIONS ET VENTES FLASH

### 13.1. Promotions manuelles (codes promo)

**Fichier :** `promotions.php`
**Permission :** `promotions_consulter`

Types : Montant fixe (FCFA) ou Pourcentage
Règles : dates validité, montant minimum, max utilisateurs, article spécifique (optionnel)

### 13.2. Ventes flash automatiques

Table `regles_promotions` :
- `PEREMPTION_PROCHE` : réduction si lot expire bientôt
- `SURSTOCK` : réduction si stock dépasse seuil

Appliquées automatiquement au scan et à la validation.

---

## 14. TARIFICATION DYNAMIQUE

**Fichier :** `tarification.php`
**Permissions :** `tarification_consulter`, `tarification_gerer`

### 14.1. Règles de tarification (tranches tarifaires)

Modes de calcul :
- **Majoration %** : marge ajoutée sur coût référence
- **Marge %** : marge brute sur prix de vente
- **Prix fixe** : prix imposé

Paramètres : nom, article/catégorie (optionnel), plage quantité, mode, valeur, priorité, dates validité.

### 14.2. Tranches par défaut

| Tranche | Quantité | Mode | Valeur |
|---|---|---|---|
| Défaut 1-9 | 1 à 9 | Majoration % | +40% |
| Défaut 10-49 | 10 à 49 | Majoration % | +35% |
| Défaut 50-199 | 50 à 199 | Majoration % | +30% |
| Défaut 200-499 | 200 à 499 | Majoration % | +25% |
| Défaut 500+ | 500+ | Majoration % | +20% |

### 14.3. Calcul intégré

`db_calculer_prix_vente()` combine : prix fournisseur référence + tranches tarifaires + promotions flash.

---

## 15. DÉPENSES

**Fichier :** `depenses.php`
**Permission :** `depenses_consulter`

Données : titre, catégorie (alimentation, loyer, salaires...), montant, date, description.

Impact : prises en compte dans le calcul du bénéfice net :
`Bénéfice net = CA - CAMV - Dépenses`

---

## 16. CONFORMITÉ ET EXPORT COMPTABLE

### 16.1. Conformité des ventes

**Fichier :** `conformite.php`
**Documentation :** `docs/conformite/integrite.md`
**Accès :** PROPRIETAIRE uniquement

Vérifications :
- Intégrité chaîne de hachage SHA-256 des factures
- Continuité numérotation
- Complétude des données
- Présence des 8 triggers d'immuabilité

### 16.2. Chaînage cryptographique

Architecture :
- Chaque facture contient un hash SHA-256 incluant le hash précédent
- Genesis : `GENESIS-ESTOCK-V1`
- Chaîne canonique : `numero|date|total_ht|tva_taux|total_ttc|montant_paye|monnaie_rendue|magasin_id|utilisateur_id|statut|JSON_lignes`
- 8 triggers SGBD empêchent modification/suppression sur : `factures`, `lignes_facture`, `paiements_facture`, `clotures_caisse`
- Outil CLI : `bin/verif_integrite.php` (modes `--backfill`, `--json`)
- Archivage periodique signé HMAC-SHA256 (conservation ≥ 6 ans)

### 16.3. Conformité fiscale togolaise (OTR)

**Documentation :** `docs/conformite/otr.md`

| Régime | Facturation | Mention |
|---|---|---|
| **TPU** (défaut) | HT (HT = TTC) | « TVA non applicable (TPU) » |
| **TVA** | TVA par ligne (défaut 18%) | Ventilation TVA multi-taux |

Identifiants requis : NIF/RCCM boutique, magasin, clients B2B.
Colonne `statut_transmission` prête pour e-facturation OTR (inactif).

### 16.4. Export SYSCOHADA

**Fichier :** `export_syscohada.php`
**Permission :** `conformite_export_syscohada`

Journaux comptables :
- **VT** (Ventes) : comptes 707, 4457 (TVA)
- **AC** (Achats) : comptes 607, 401
- **DG** (Dépenses) : comptes divers
- **INV** (Variation de stocks) : comptes 31, 6037

Format : CSV séparateur point-virgule, UTF-8 BOM.

### 16.5. Désinscription e-mails (GDPR)

**Fichier :** `desinscription.php`

- Page publique (sans authentification)
- Lien personnel token 64 hex dans chaque e-mail
- Droit d'opposition
- Réponse générique (anti-énumération)

---

## 17. STATISTIQUES ET TABLEAU DE BORD

### 17.1. Tableau de bord

**Fichier :** `tableau_bord.php`
**Accès :** Tous les rôles connectés

KPI : articles actifs, valeur stock, ventes du jour, CA du jour.
Widgets : alertes stock bas, derniers mouvements (8).

### 17.2. Statistiques détaillées

**Fichier :** `statistiques.php`
**Permission :** `statistiques_consulter`

- Filtre période (date début/fin)
- KPI : CA, ventes, panier moyen, top article
- Compte de résultat : CA - CAMV = Marge brute - Dépenses = Bénéfice net
- TVA collectée (régime TVA uniquement)
- Graphiques Chart.js : évolution journalière CA vs Dépenses, Top 5 articles

---

## 18. EXPORTS DE DONNÉES

### 18.1. Export CSV

**Fichier :** `exports.php`
**Permission :** `exports_consulter`

Types : articles, factures, mouvements, inventaire, pertes
Format : CSV UTF-8 BOM, séparateur point-virgule, max 5000 lignes.

### 18.2. Impression d'étiquettes

**Fichier :** `etiquettes.php`
**Permission :** `impression_consulter`

Options : grille 3x8 (24/A4) ou 4x10 (40/A4), prix TTC, en-tête magasin.
Contenu : nom magasin, nom article, prix TTC, code-barres CODE128.

---

## 19. IMPRESSION

### 19.1. Ticket de caisse

**Fichier :** `ticket_print.php`
**Template :** `templates/ticket_print.html.twig`

Format : 80mm ou 58mm (configurable).
Contenu : en-tête magasin, numéro/date facture, vendeur, lignes articles, récap TVA, total TTC, points, paiements, monnaie, code-barres facture, message remerciement.

### 19.2. Facture HTML

**Fichier :** `facture_view.php`

Format : HTML A4 standard.
Contenu complet avec informations légales, client, lignes, TVA, paiement, avertissements conformité.

### 19.3. Facture A4/A5

**Fichier :** `facture_a4.php`

Format : A5 professionnel intégré dans le layout.
Accès par URL signée ou utilisateur connecté.

### 19.4. Carte de fidélité

**Fichier :** `carte_fidelite.php`

Format : 85mm x 55mm (carte de visite).
Code-barres CODE128, nom client, solde points.

### 19.5. Suggestions d'achat (impression)

**Fichier :** `suggestions_impression.php`
**Permission :** `impression_consulter`

Version A4 : articles en stock bas groupés par fournisseur, quantités suggérées.

---

## 20. MODULE USINE DE PRODUCTION

### 20.1. Vue d'ensemble

**Fichiers :** `usine.php`, `productions.php`, `recettes.php`, `matieres_premieres.php`, `stock_usine.php`
**Fichier fonctions :** `includes/usine_functions.php` (918 lignes)
**Permissions :** `usine_consulter`, `usine_gerer`, `production_consulter`, `production_gerer`

Architecture **indépendante** du module magasin :
- Matières premières, catégories MP, stock MP, mouvements MP → tables dédiées
- Produits finis → dans `articles` avec `origine_article = 'PRODUCTION_USINE'`
- Stock usine géré séparément du stock magasin

### 20.2. Tableau de bord usine

**Fichier :** `usine.php`

KPI : productions aujourd'hui, produits fabriqués, alertes stock MP, valeur stock usine.

### 20.3. Matières premières

**Fichier :** `matieres_premieres.php`

- Référence auto (`MAT-YYYY-NNN`)
- Nom, catégorie (Granulés, Colorants, Additifs...), unité (KG, L, UNITE)
- Coût de référence, stock minimum, fournisseur principal
- Tables : `matieres_premieres`, `categories_matieres_premieres`

### 20.4. Stock usine

**Fichier :** `stock_usine.php`

Deux vues :
1. **Matières premières** : stock MP avec quantité, valeur, alertes
2. **Produits finis** : stock PF usine avant transfert magasin

Transfert usine → magasin : sélection article, destination, quantité.

### 20.5. Recettes (nomenclatures)

**Fichier :** `recettes.php`

Chaque recette : nom, article produit fini, quantité produite par lot, version.
Lignes : matière première, quantité nécessaire, unité, pertes théoriques (%).
Tables : `recettes`, `recettes_lignes`

### 20.6. Productions

**Fichier :** `productions.php`

Workflow :

| Étape | Statut | Action |
|---|---|---|
| 1 | `BROUILLON` | Création (article, recette, quantité prévue) |
| 2 | `PLANIFIEE` | Date prévue, vérification stock MP |
| 3 | `EN_COURS` | Début production, consommation MP |
| 4 | `TERMINEE` | Résultat : quantité produite, pertes |
| 5 | `ANNULEE` | Annulation |

Référence : `PROD-YYYY-NNNN`
Données : coût matières, coût unitaire, pertes (7 types)
Tables : `productions`, `production_matieres`, `production_produits`, `production_pertes`

### 20.7. Tables usine

| Table | Description |
|---|---|
| `recettes` | Recettes de production (versionnées) |
| `recettes_lignes` | Lignes BOM (matières premières) |
| `productions` | Ordres de production |
| `production_matieres` | Matières consommées |
| `production_produits` | Produits finis générés |
| `production_pertes` | Pertes de production |
| `categories_matieres_premieres` | Catégories MP |
| `matieres_premieres` | Matières premières |
| `stock_matieres_premieres` | Stock MP usine |
| `mouvements_matieres_premieres` | Mouvements stock MP |
| `stock_produits_finis_usine` | Stock PF usine |
| `mouvements_produits_finis` | Mouvements PF |

### 20.8. Permissions usine

| Permission | Description |
|---|---|
| `usine_consulter` | Dashboard et stock usine |
| `usine_gerer` | Gérer MP et recettes |
| `production_consulter` | Consulter productions |
| `production_gerer` | Créer/démarrer/terminer productions |
| `production_cloturer` | Clôturer une production |
| `machines_consulter` | Consulter les machines |
| `machines_gerer` | Créer/modifier les machines |
| `notifications_usine_consulter` | Consulter les notifications |
| `notifications_usine_gerer` | Gérer les notifications |
| `horaires_consulter` | Consulter les horaires |
| `horaires_gerer` | Gérer les horaires |

### 20.9. Machines de production

**Fichier :** `machines.php`
**Permissions :** `machines_consulter`, `machines_gerer`

- CRUD machines (nom, type, description, état)
- États : EN_FONCTIONNEMENT, ARRETEE, EN_MAINTENANCE, EN_PANNE, HORS_SERVICE
- Démarrage/arrêt rapide avec historique complet
- KPI : machines en cours, en maintenance, en panne
- Table historique avec durée, motif, utilisateur responsable

### 20.10. Notifications usine

**Fichier :** `notifications.php`
**Permissions :** `notifications_usine_consulter`, `notifications_usine_gerer`

Types de notifications automatiques :
- **retard_employe** : alerte quand un employé arrive en retard
- **absence_employe** : alerte quand un employé est absent
- **machine_arretee** : alerte quand une machine est arrêtée
- **machine_panee** : alerte quand une machine est en panne
- **perte_elevee** : alerte quand les pertes dépassent 15%
- **rendement_bas** : alerte quand le rendement est inférieur à 85%
- **stock_faible_matiere** : alerte quand le stock d'une matière première est bas

Fonctionnalités :
- Filtrage par type, non lues uniquement
- Marquer lu, marquer toutes lues, supprimer
- Badge nombre de non lues dans la sidebar

### 20.11. Horaires de travail

**Fichier :** `horaires.php`
**Permissions :** `horaires_consulter`, `horaires_gerer`

- Configuration horaires par jour (heure début/fin)
- Tolérance de retard configurable (minutes)
- Création rapide semaine complète (lundi-vendredi par défaut)
- Détection automatique des retards lors de l'enregistrement des présences
- Calcul automatique des retards (`db_calculer_retard`)

---

## 21. GESTION DU PERSONNEL

### 21.1. Personnel usine

**Fichier :** `personnel.php`
**Permissions :** `personnel_consulter`, `personnel_gerer`

Employés : matricule (auto), nom, prénom, fonction, téléphone, état actif/inactif.

### 21.2. Présences

**Fichier :** `presences.php`
**Permissions :** `presence_consulter`, `presence_gerer`

- Navigation jour par jour
- Enregistrement : heure arrivée, départ, commentaire
- Calcul automatique temps travaillé (minutes)
- Vue présents/absents
- Tables : `employes`, `presences_employes`

---

## 22. PARAMÉTRAGE DE L'APPLICATION

**Fichier :** `parametres.php`
**Permission :** `parametres_gerer`

| Catégorie | Paramètres |
|---|---|
| Général | Nom application, Nom magasin |
| Financier | Devise, symbole, position, décimales, séparateurs |
| Fiscal | Régime (TVA/TPU), Taux TVA par défaut |
| Ticket | En-tête, remarque, format (80mm/58mm) |
| Caisse | Modes paiement, montants rapides, préfixe facture |
| Fidélité | Actif, valeur point, minimum utilisation |
| Email | Notifications, destinataire |
| Stock | Méthode valorisation (CUMP/FIFO) |
| Google OAuth | Client ID, Client Secret, actif |
| Thème | Couleur (indigo, emerald, sky, rose, amber) |

Système : `config/parametres.php` — cache session, accès typé (`param()`, `param_float()`, `param_bool()`).

---

## 23. JOURNAL D'AUDIT ET TRAÇABILITÉ

### 23.1. Page d'audit

**Fichier :** `audit.php`
**Permission :** `audit_consulter`

- Liste chronologique de toutes les actions
- Filtres : type d'action, recherche texte, période
- Colonnes : date, utilisateur, action, détails, adresse IP

### 23.2. Actions journalisées

| Action | Description |
|---|---|
| `CONNEXION` | Connexion réussie |
| `DECONNEXION` | Déconnexion |
| `ECHEC_CONNEXION` | Tentative échouée |
| `CONNEXION_GOOGLE` | Connexion via Google |
| `INSCRIPTION_GOOGLE` | Création compte via Google |
| `MODIFICATION_ARTICLE` | Création/modification article |
| `SUPPRESSION_ARTICLE` | Désactivation article |
| `MOUVEMENT_STOCK` | Mouvement de stock manuel |
| `VENTE` | Vente en caisse |
| `RETOUR_SAV` | Retour client |
| `INVENTAIRE_*` | Actions inventaire |
| `TRANSFERT_STOCK` | Transfert inter-magasins |
| `COMMANDE_*` | Commandes fournisseur |
| `MODIFICATION_UTILISATEUR` | Gestion utilisateurs |
| `CHANGEMENT_MDP` | Changement mot de passe |
| `EXPORT_CSV` | Export de données |
| `SYSCOHADA_EXPORT` | Export comptable |
| `ECART_CAISSE` | Écart de caisse |
| `LOT_AJOUTE` | Ajout de lot |
| `PRODUCTION_CREEE` | Production créée |
| `PRODUCTION_DEMARREE` | Production démarrée |
| `PRODUCTION_TERMINEE` | Production terminée |
| `PRODUCTION_ANNULEE` | Production annulée |
| `PRESENCE_ENREGISTREE` | Présence enregistrée |
| `MATIERE_PREMIERE_SUPPRIMEE` | MP désactivée |
| `TRANSFERT_USINE_MAGASIN` | Transfert usine → magasin |
| `TRANCHE_CREE` | Tranche tarifaire créée |
| `DESINSCRIPTION` | Désinscription e-mail |
| `CREATION_ROLE` | Création rôle |
| `MODIFICATION_ROLE` | Modification rôle |

### 23.3. Intégrité cryptographique

- Chaînage SHA-256 des factures et clôtures
- 8 triggers SGBD d'immuabilité
- Vérification : `bin/verif_integrite.php`
- Archivage signé HMAC-SHA256

---

## 24. SÉCURITÉ

### 24.1. Protection CSRF

- Token CSRF généré par session
- Validation sur tous les formulaires POST
- En-tête `X-CSRF-Token` pour appels API
- `csrf_guard($page)` : validation avec redirect
- Rotation du token après chaque validation (rejet du token précédent)
- API : CSRF requis pour les mutations rôle (POST/PUT/DELETE)

### 24.2. Protection XSS

- Fonction `h()` : `htmlspecialchars(ENT_QUOTES, UTF-8)`
- CSP (Content Security Policy) avec nonce par requête
- Input validation via `input_string()`
- JSON : `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`
- Flash messages rendus via `textContent` (pas `innerHTML`)
- Templates Twig : autoescape `html` activé par défaut

### 24.3. Protection injection SQL

- PDO avec requêtes préparées
- `ATTR_EMULATE_PREPARES => false`
- Liaison paramétrée systématique
- Requêtes SQL dynamiques utilisent des whitelist de colonnes et de tables

### 24.4. Rate limiting

- Connexion : 5 tentatives par IP en 15 minutes
- Changement mot de passe : 5 tentatives par session en 15 minutes
- Détection IP via `X-Forwarded-En-tête` (proxy)

### 24.5. Headers de sécurité

| Header | Valeur |
|---|---|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS) |
| `Content-Security-Policy` | nonce-based script-src, self img/style/font/connect |

### 24.6. URLs signées

- HMAC-SHA256 pour liens sensibles
- Expiration 24 heures
- Vérification via `hash_equals()`
- Le chemin complet est signé (pas seulement les query params)

### 24.7. Immutabilité des données financières

Triggers MySQL sur :
- `factures` (UPDATE/DELETE bloqués)
- `lignes_facture`
- `paiements_facture`
- `clotures_caisse`
- `receptions` (validées ou annulées)

### 24.8. Protection des fichiers

- `.htaccess` racine : blocage dossiers sensibles
- `config/.htaccess` : `Require all denied`
- `bin/.htaccess` : `Require all denied`
- `logs/.htaccess` : `Require all denied`
- `archives/.htaccess` : `Require all denied`
- `.env` : accès refusé via `<FilesMatch>`
- `install.php` : protection `.installed` après première installation

### 24.9. Transactions et intégrité

- `db_article_insert` : transactionnel (échec = rollback complet)
- `db_stock_magasin_update` : protection contre les stock négatifs (`GREATEST(0,...)`)
- `db_fournisseur_delete` : vérification FK avant suppression
- `db_inventaire_appliquer_ecarts` : transactionnel (échec = rollback complet)
- `db_retour_creer` : transactionnel
- `db_role_delete` : nettoyage `user_roles` avant suppression

### 24.10. Sessions

- `session_regenerate_id(true)` uniquement au login (pas à chaque page)
- Session fixation : régénération obligatoire au login
- Cookies : HttpOnly, SameSite=Lax, Secure (HTTPS)
- Timeout inactivité : 30 minutes
- Timeout absolu : 8 heures
- Validation utilisateur renouvelée toutes les 5 minutes

### 24.11. Autres mesures

- Anti-timing sur tentatives de connexion
- Erreur globale : handler exception avec log + 500 (masquage messages en production)
- 2FA TOTP : helpers prêts (RFC 6238)
- Installation auto : redirection `install.php` si pas d'admin
- Open redirect protection (whitelist URLs)
- Validation `$db_host` avec regex blanche dans l'installateur
- Erreurs PDO masquées dans l'installateur

---

## 25. API REST

**Fichier :** `api/index.php` (1695 lignes)
**Routeur :** `api/.htaccess` → tous les chemins vers `index.php`

### 25.1. Authentification

| Endpoint | Méthode | Permission | Description |
|---|---|---|---|
| `/api/login` | POST | Public | Connexion (XHR requis) |
| `/api/logout` | POST | Authentifié | Déconnexion |
| `/api/me` | GET | Authentifié | Utilisateur courant |

### 25.2. Articles

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/articles` | GET | `articles_consulter` |
| `/api/articles` | POST | Admin + CSRF |
| `/api/articles/{id}` | PUT | Admin + CSRF |
| `/api/articles/{id}` | DELETE | Admin + CSRF |

### 25.3. Caisse / Vente

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/scan?code=X` | GET | `caisse_gerer` |
| `/api/caisse/sync` | POST | `caisse_gerer` + CSRF |
| `/api/caisse/status` | GET | `caisse_gerer` |
| `/api/generer_code_barre` | GET | `articles_gerer` |
| `/api/promo?code=X` | GET | Authentifié |
| `/api/clients/search?q=X` | GET | `clients_consulter` |

### 25.4. Tarification & Prix fournisseurs

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/prix_fournisseur/article/{id}` | GET | `prix_fournisseur_consulter` |
| `/api/prix_fournisseur/fournisseur/{id}` | GET | `prix_fournisseur_consulter` |
| `/api/prix_fournisseur` | POST | `prix_fournisseur_gerer` + CSRF |
| `/api/prix_fournisseur/calculer` | GET | `articles_consulter` |
| `/api/tranches` | GET/POST | `tarification_consulter/gerer` |
| `/api/tranches/{id}` | GET/PUT/DELETE | `tarification_consulter/gerer` |

### 25.5. Réceptions & Pertes

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/receptions/{id}` | GET | `receptions_consulter` |
| `/api/receptions/commande/{id}` | GET | `receptions_consulter` |
| `/api/receptions` | POST | `receptions_gerer` + CSRF |
| `/api/receptions/valider/{id}` | POST | `receptions_gerer` + CSRF |
| `/api/receptions/annuler/{id}` | POST | `receptions_gerer` + CSRF |
| `/api/pertes` | GET | `pertes_consulter` |

### 25.6. Usine de Production

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/matieres_premieres` | GET/POST | `usine_consulter/gerer` |
| `/api/matieres_premieres/{id}` | PUT | `usine_gerer` + CSRF |
| `/api/recettes` | GET/POST | `usine_consulter/gerer` |
| `/api/recettes/{id}` | GET/PUT/DELETE | `usine_consulter/gerer` |
| `/api/productions` | GET/POST | `production_consulter/gerer` |
| `/api/productions/{id}` | GET | `production_consulter` |
| `/api/productions/{id}/demarrer` | POST | `production_gerer` + CSRF |
| `/api/productions/{id}/cloturer` | POST | `production_cloturer` + CSRF |
| `/api/productions/{id}/annuler` | POST | `production_gerer` + CSRF |
| `/api/productions/{id}/employes` | POST | `production_gerer` + CSRF |
| `/api/employes` | GET/POST | `personnel_consulter/gerer` |
| `/api/employes/{id}` | PUT | `personnel_gerer` + CSRF |
| `/api/presences` | GET/POST | `presence_consulter/gerer` |
| `/api/stock_usine` | GET | `usine_consulter` |
| `/api/transfert_usine` | POST | `transfert_usine_gerer` + CSRF |
| `/api/usine_dashboard` | GET | `usine_consulter` |
| `/api/production_rapport` | GET | `production_consulter` |

### 25.7. RBAC (Rôles & Permissions)

| Endpoint | Méthode | Permission |
|---|---|---|
| `/api/roles` | GET | `roles_consulter` |
| `/api/roles` | POST | `roles_gerer` + CSRF |
| `/api/roles/{id}` | PUT | `roles_gerer` + CSRF |
| `/api/roles/{id}` | DELETE | `roles_gerer` + CSRF |
| `/api/permissions` | GET | `roles_consulter` |
| `/api/roles/{id}/permissions` | GET | `roles_consulter` |
| `/api/roles/{id}/permissions` | PUT | `roles_gerer` + CSRF |

### 25.8. Sécurité API

- Authentification : session PHP (sauf `/api/login`)
- Autorisation : `peut()` vérifié par handler
- CSRF : header `X-CSRF-Token` sur mutations (POST/PUT/DELETE), y compris rôles
- Login : vérifie `X-Requested-With: XMLHttpRequest`
- Transactions : `beginTransaction()` / `commit()` / `rollBack()`
- Validation stock : par magasin avec `FOR UPDATE`
- Tolérance total : 0.02 (validation paiement)
- UUID : validation `client_sale_id` pour synchro hors-ligne
- Rôles protégés : `PROPRIETAIRE`, `ADMIN`, `MAGASINIER`, `VENDEUR` non supprimables
- Code rôle : regex `^[A-Z_]{2,50}$`
- Permissions API : variante JSON de `exiger_permission_api()`

---

## 26. PROGRESSIVE WEB APP (PWA)

### 26.1. Manifest

**Fichiers :** `manifest.json`, `manifest.php`

```json
{
    "name": "eStock — Gestion de Stock & Caisse",
    "display": "standalone",
    "theme_color": "#4f46e5",
    "orientation": "any",
    "categories": ["business", "finance"]
}
```

### 26.2. Service Worker

**Fichier :** `sw.js`

| Stratégie | Cibles |
|---|---|
| Network only | Appels API (`/api/`) |
| Network-first, cache fallback | Pages PHP, routes dynamiques |
| Cache-first | Assets statiques (CSS, JS, images, fonts, CDN) |

Cache nommé `estock-v2` avec nettoyage automatique.

---

## 27. ANNEXES

### 27.1. Structure de la base de données (~53 tables)

**Tables métier :**
`articles`, `categories`, `fournisseurs`, `magasins`, `stock_magasins`, `article_lots`, `article_couts`, `usines`, `user_magasins`

**Tables de vente :**
`factures`, `lignes_facture`, `paiements_facture`, `clotures_caisse`, `retours_factures`, `lignes_retour`

**Tables d'approvisionnement :**
`commandes_fournisseur`, `lignes_commande_fournisseur`, `receptions`, `reception_lignes`, `pertes_fournisseur`, `fournisseur_prix_historique`, `tranches_tarifaires`

**Tables d'inventaire :**
`inventaires`, `inventaire_lignes`, `mouvements_stock`, `transferts_stock`

**Tables clients :**
`clients`, `historique_points`, `consentements_log`

**Tables promotions :**
`promotions`, `regles_promotions`

**Tables d'administration :**
`utilisateurs`, `permissions`, `role_permissions`, `parametres`, `sequences`, `depenses`, `roles`, `user_roles`, `unites_mesure`

**Tables de sécurité :**
`login_attempts`, `logs_activite`, `archives_caisse`, `emails_queue`, `emails_consentements`

**Tables usine :**
`recettes`, `recettes_lignes`, `productions`, `production_matieres`, `production_pertes`, `categories_matieres_premieres`, `matieres_premieres`, `stock_matieres_premieres`, `machines`, `machine_etats`, `categories_pertes_production`

**Tables personnel :**
`employes`, `presences_employes`

**Tables équipes :**
`equipes`, `equipe_membres`, `equipe_magasins`

**Tables notifications :**
`notifications`, `horaires_travail`

### 27.2. Outils CLI (bin/)

| Script | Usage | Description |
|---|---|---|
| `backup_db.php` | `php bin/backup_db.php` | Sauvegarde BDD + ZIP + rotation 30j |
| `create_admin.php` | `php bin/create_admin.php "Nom" login "mdp" [mag_id]` | Création compte Propriétaire (min 12 car.) |
| `emails_worker.php` | `php bin/emails_worker.php [--limit N] [--purge]` | Worker email async (cron) |
| `verif_integrite.php` | `php bin/verif_integrite.php [--backfill] [--json]` | Vérification chaîne cryptographique |
| `test_restore.php` | `php bin/test_restore.php [fichier]` | Test restauration sur base `estock_db_test` |
| `purge_donnees_clients.php` | `php bin/purge_donnees_clients.php [--dry-run]` | Purge RGPD (36 mois inactivité) |
| `disable_demo_accounts.php` | `php bin/disable_demo_accounts.php` | Désactivation comptes démo |
| `apply_migration.php` | `php bin/apply_migration.php` | Application RBAC + usine indépendante |
| `inventaire_tables.php` | `php bin/inventaire_tables.php [--md]` | Inventaire tables vs schéma SQL |
| `preflight_release.php` | `php bin/preflight_release.php` | Contrôles pré-livraison |

### 27.3. Tests PHPUnit

**Config :** `phpunit.xml` | **Bootstrap :** `tests/bootstrap.php`

**Tests unitaires (4) :**

| Test | Description |
|---|---|
| `HelpersUnitTest` | `h()`, `money()`, `date_fr()`, URLs signées |
| `ConformiteTogoUnitTest` | Régime TPU/TVA, cohérence mentions |
| `PrixDynamiqueUnitTest` | Tranches tarifaires, prix fournisseur, calcul intégré |
| `ValidationUnitTest` | `extract_post_data()` (coercition, bornage, types) |

**Tests d'intégration (7) :**

| Test | Description |
|---|---|
| `ValorisationCumpTest` | CUMP multi-entrées, fallback FIFO |
| `FactureConformiteTest` | Préfixe numérotation, JOIN client NIF/RCCM |
| `EmailsConsentementsTest` | File e-mails, consentement, opposition, purge |
| `ConcurrenceNumerotationTest` | 8 processus × 5 = 40 factures en parallèle, zéro doublon |
| `UsineProductionTest` | Cycle complet production, recettes, transfert, personnel |
| `ReceptionPrixFournisseurTest` | Réceptions partielles, pertes, prix dynamiques |
| `TraabiliteUsineTest` | Machines CRUD, notifications, horaires, retards, rendement |

Exécution :
```bash
vendor/bin/phpunit                    # Tous les tests
vendor/bin/phpunit --testsuite Unit   # Tests unitaires uniquement
vendor/bin/phpunit --testsuite Integration  # Tests intégration
```

### 27.4. Migrations SQL

| Fichier | Date | Description |
|---|---|---|
| `migration_consolidee.sql` | 10 sept 2026 | Migration unique consolidée — remplace les 13 fichiers précédents |

> **Note :** Les 13 fichiers de migration individuels ont été supprimés et consolidés en un seul fichier `migration_consolidee.sql`. Ce fichier est idempotent (rejouable sans erreur) et couvre toutes les modifications : RBAC dynamique, tables usine, colonnes manquantes, CHECK constraints, normalisation des types, suppression de l'ENUM `role`.

### 27.5. Architecture Decision Records (ADR)

| ADR | Sujet | Décision |
|---|---|---|
| ADR-001 | Mobile Money | Traitement comme paiement manuel (pas d'API opérateur) |
| ADR-002 | Journal de caisse | Pas de table balance dédiée — 2 journaux immuables (factures + clôtures) |
| ADR-003 | Multi-caisses | PHP procédural single-server, numérotation atomique via `sequences` |
| ADR-004 | File e-mails | Table `emails_queue`, worker cron, consentement vérifié à dépôt |

### 27.6. URLs principales

| URL | Fonction |
|---|---|
| `/` | Point d'entrée (redirect login) |
| `/auth/login.php` | Connexion |
| `/auth/google_login.php` | Connexion Google OAuth |
| `/auth/choisir_magasin.php` | Sélection magasin |
| `/tableau_bord.php` | Tableau de bord |
| `/articles.php` | Gestion articles |
| `/stock.php` | Consultation stock |
| `/mouvements.php` | Mouvements de stock |
| `/caisse.php` | Point de vente |
| `/cloture.php` | Clôture de caisse |
| `/factures.php` | Liste factures |
| `/facture_a4.php` | Facture A5/A4 |
| `/facture_view.php` | Facture HTML |
| `/ticket_print.php` | Ticket de caisse |
| `/clients.php` | Gestion clients |
| `/carte_fidelite.php` | Carte de fidélité |
| `/inventaire.php` | Inventaire physique |
| `/commandes_fournisseur.php` | Commandes fournisseurs |
| `/receptions.php` | Réceptions fournisseur |
| `/pertes.php` | Pertes fournisseur |
| `/transferts.php` | Transferts inter-magasins |
| `/retours.php` | Retours SAV |
| `/promotions.php` | Promotions |
| `/tarification.php` | Tarification dynamique |
| `/peremptions.php` | Péremptions |
| `/categories.php` | Catégories |
| `/fournisseurs.php` | Fournisseurs |
| `/depenses.php` | Dépenses |
| `/statistiques.php` | Statistiques |
| `/exports.php` | Exports CSV |
| `/etiquettes.php` | Impression étiquettes |
| `/suggestions_achat.php` | Suggestions d'achat |
| `/suggestions_impression.php` | Impression suggestions |
| `/utilisateurs.php` | Gestion utilisateurs |
| `/roles.php` | Rôles & Permissions RBAC |
| `/magasins.php` | Gestion magasins |
| `/parametres.php` | Paramètres |
| `/audit.php` | Journal d'audit |
| `/documentation.php` | Documentation |
| `/usine.php` | Tableau de bord usine |
| `/productions.php` | Productions |
| `/recettes.php` | Recettes de production |
| `/matieres_premieres.php` | Matières premières |
| `/stock_usine.php` | Stock usine |
| `/personnel.php` | Personnel usine |
| `/presences.php` | Présences |
| `/machines.php` | Machines |
| `/notifications.php` | Notifications usine |
| `/horaires.php` | Horaires de travail |
| `/desinscription.php` | Désinscription e-mails |
| `/install.php` | Installation |

### 27.7. Fonctions DB principales (`includes/db_functions.php` — 5155 lignes)

| Domaine | Nombre de fonctions | Exemples clés |
|---|---|---|
| Articles | 21 | `db_article_insert`, `db_article_get_by_barcode`, `db_articles_achat_recommandes` |
| Fournisseurs | 6 | `db_fournisseur_insert`, `db_fournisseurs_list_with_count` |
| Utilisateurs | 12 | `db_user_insert`, `db_user_get_by_login`, `db_user_create_from_google` |
| Factures | 8 | `db_facture_insert`, `db_facture_get_by_id`, `db_factures_search_sql` |
| Lignes facture | 1 | `db_ligne_facture_insert` (multi-paramètres) |
| Mouvements stock | 3 | `db_mouvement_insert`, `db_mouvements_search_sql` |
| Login attempts | 3 | `db_login_attempt_insert/count/clear` |
| Logs activité | 1 | `db_logs_activite_search_sql` |
| Magasins | 14 | `db_magasin_insert`, `db_transferer_stock`, `db_stock_magasin_upsert` |
| Dépenses | 5 | `db_depense_insert`, `db_depenses_by_categorie` |
| Statistiques | 6 | `db_stats_period`, `db_stats_benefice_net`, `db_stats_daily_sales` |
| Clôture caisse | 7 | `db_calculer_ventes_du_jour`, `db_cloture_insert` |
| Permissions RBAC | 9 | `db_permissions_get_for_role`, `db_permissions_save_for_role` |
| Rôles | 9 | `db_role_insert`, `db_user_save_roles`, `db_role_user_count` |
| Lots (FEFO) | 7 | `db_lot_upsert`, `db_lot_get_fefo`, `db_lot_decrement_fefo` |
| Prix fournisseurs | 6 | `db_fournisseur_prix_set`, `db_prix_fournisseur_ref` |
| Tranches tarifaires | 6 | `db_tranche_insert`, `db_calculer_prix_selon_quantite` |
| Réceptions | 8 | `db_reception_insert`, `db_reception_valider` (transactionnel) |
| Pertes | 1 | `db_pertes_list` |
| Prix de vente | 2 | `db_calculer_prix_vente`, `db_prix_par_tranches` |
| Péremptions | 2 | `db_get_expiring_lots`, `db_peremption_alert_count` |
| Promotions flash | 4 | `db_calculer_prix_article`, `db_promotion_save` |
| Déduction stock | 1 | `db_deduire_stock_lot` (FEFO atomique) |
| Paiements | 3 | `db_paiements_insert`, `db_paiements_by_facture` |
| Commandes | 8 | `db_commande_fournisseur_insert`, `db_commande_fournisseur_lignes_save` |
| Catégories | 5 | `db_categorie_insert`, `db_categories_all` |
| Inventaire | 7 | `db_inventaire_insert`, `db_inventaire_appliquer_ecarts` |
| Retours | 5 | `db_retour_insert`, `db_retour_creer` |
| Chaînage | 3 | `db_sequence_next`, `db_facture_chainer` |
| **TOTAL** | **~180 fonctions** | |

### 27.8. Fonctions Usine (`includes/usine_functions.php` — ~1500 lignes)

| Domaine | Fonctions |
|---|---|
| Matières premières | `db_matiere_premiere_ref`, `_list`, `_get`, `_insert`, `_update` |
| Stock MP | `db_stock_mp_feed`, `db_stock_mp_suffisant`, `db_stock_mp_list` |
| Mouvements MP | `db_mouvement_mp_insert` |
| Recettes | `db_recette_insert`, `db_recette_update`, `db_recettes_list`, `db_recette_get` |
| Productions | `db_production_insert`, `db_production_demarrer`, `db_production_cloturer`, `db_production_annuler` |
| Consommation MP | `db_production_consommer_matieres` |
| Produits finis | `db_production_creer_produits_finis` |
| Pertes | `db_production_enregistrer_pertes` |
| Personnel | `db_employe_insert`, `db_employe_update`, `db_employes_list`, `db_employe_get` |
| Présences | `db_presence_upsert`, `db_presences_list_date` |
| Dashboard | `db_usine_dashboard`, `db_usine_dashboard_enrichi` |
| Transfert | `db_transfert_usine_vers_magasin` |
| **Machines** | `db_machine_insert`, `_get`, `_update`, `_list`, `_demarrer`, `_arreter`, `_set_etat`, `_historique` |
| **Notifications** | `db_notification_insert`, `_list`, `_nb_non_lues`, `_marquer_lue`, `_tout_lu`, `_supprimer` |
| **Notifications auto** | `db_notif_retard_employe`, `_absence_employe`, `_machine_arretee`, `_perte_elevee`, `_rendement_bas`, `_stock_faible`, `_production` |
| **Horaires** | `db_horaire_insert`, `_list`, `_get_tolerance`, `_delete` |
| **Retards** | `db_calculer_retard`, `db_presences_avec_retards`, `db_employes_absents` |
| **Rendement** | `db_rendement_production`, `db_rendement_par_categorie`, `db_rapport_matiere_production` |
| **Catégories pertes** | `db_categorie_perte_insert`, `_list`, `_delete` |

### 27.9. Helpers principaux (`includes/helpers.php` — 1083 lignes)

| Catégorie | Fonctions |
|---|---|
| Sécurité | `csrf_guard()`, `csrf_validate()`, `csrf_field()`, `csrf_token()` |
| Validation | `extract_post_data()`, `validate_required_fields()`, `validate_uniqueness()` |
| Transactions | `db_transaction()` (savepoint-aware) |
| Stock | `process_stock_movement()` (FEFO, CUMP, multi-magasin) |
| Fidélité | `db_loyalite_verrouiller()` (FOR UPDATE) |
| URLs signées | `generate_signed_url()`, `verify_url_signature()` (HMAC-SHA256) |
| Numérotation | `generate_invoice_number()`, `generate_return_number()`, `generate_inventory_number()` |
| Audit | `suivre_activite()`, `log_json()` |
| Code-barres | `generer_ean13()`, `verifier_ean13()` |
| Email | `email_queue()`, `email_desinscrire_par_token()`, `email_annuler_opposition()` |
| TOTP 2FA | `totp_generate_secret()`, `totp_verify()`, `totp_qr_code_url()` |
| Formatage | `h()`, `money()`, `date_fr()`, `input_string()` |
| Flash messages | `flash_success()`, `flash_error()`, `afficher_flash()` |

### 27.10. Constantes de rôle

| Constante | Valeur | Description |
|---|---|---|
| `ROLE_DIRECTEUR` | `PROPRIETAIRE` | Rôle le plus élevé |
| `ROLE_ADMIN` | `ADMIN` | Administration complète |
| `ROLE_MAGASINIER` | `MAGASINIER` | Gestion d'un magasin |
| `ROLE_VENDEUR` | `VENDEUR` | Vente au comptoir |
| `ROLE_CHEF_EQUIPE` | `CHEF_EQUIPE` | Encadrement d'équipe |

> **Note :** Ces constantes sont des alias. L'autorisation se fait via les permissions (`peut()`), pas directement via le nom du rôle.

### 27.11. Design system

**Thème :** "Liquid Glass (Aurora)"
- Police : Poppins (Google Fonts, poids 400-800)
- Variables CSS : 57+ propriétés (marque, encre, verre, états, ombres, rayons)
- Background : gradient fixe avec 4 blobs animés (25s keyframes)
- Glass morphism : `backdrop-filter: blur(8px) saturate(190%)`
- Sidebar : thème sombre (`#1e293b` → `#0f172a`)
- 5 palettes thème : indigo, emerald, sky, rose, amber

---

## 28. HISTORIQUE DES VERSIONS

### v2.5.0 — 10 septembre 2026

**Refactoring RBAC complet + Consolidation migrations + Nettoyage architecture**

**RBAC dynamique :**
- Suppression de la colonne ENUM `role` de la table `utilisateurs`
- `user_roles` étendu avec `date_debut`, `date_fin`, `actif` pour gestion temporelle
- `user_role()` query directe de `user_roles` (plus de fallback ENUM)
- `exiger_role()` vérifie TOUS les rôles de l'utilisateur (multi-rôle)
- `user_can_edit_user()` hiérarchie dynamique via `user_roles`
- Toutes les références `$_SESSION['user']['role']` remplacées par `user_role()`

**Consolidation migrations :**
- 13 fichiers de migration individuels → 1 fichier `migration_consolidee.sql`
- `estock_db.sql` réécrit avec toutes les migrations intégrées (53 tables)
- Migration unique idempotente (rejouable sans erreur)
- Suppression des tables orphelines : `user_equipes`, `production_lots`, `production_produits`, `production_employes`, `presences_employes_audit`, `mouvements_produits_finis`, `mouvements_matieres_premieres`

**Normalisation données :**
- `mouvements_stock.type` : casse unifiée en MAJUSCULES (`ENTREE`, `SORTIE`, `VENTE`, etc.)
- `notifications.cible_role` : normalisé (`ADMIN`, `CHEF_EQUIPE`, etc.)
- `role_permissions.role_nom` : normalisé en MAJUSCULES_SANS_ACCENT
- Code PHP mis à jour : 29 occurrences de types mouvements normalisées

**Architecture BDD :**
- 20+ nouvelles tables créées (RBAC, usine, personnel, notifications)
- 25+ colonnes ajoutées aux tables existantes
- 20+ contraintes FK ajoutées
- 7 CHECK constraints (prix ≥ 0, stock ≥ 0, montant ≥ 0, quantité > 0)
- 20+ index de performance ajoutés
- `magasins.type_magasin` marqué OBSOLETE (usines dans table `usines`)

**Sécurité :**
- Fallback ENUM supprimé de `config/connexion.php`
- Fonction `csp_nonce()` définie (manquait)
- Commentaires obsolètes nettoyés

### v2.4.0 — 10 septembre 2026

**Consolidation globale RBAC + Équipes + Corrections usine**

**RBAC 100% dynamique :**
- Suppression de TOUS les contrôles rôles codés en dur (`ROLE_VENDEUR`, `ROLE_DIRECTEUR`, etc.)
- Remplacement par des permissions dynamiques (`peut('permission')`, `exiger_permission_api()`)
- Auth login/redirection basée sur `magasin_id` (pas sur le nom du rôle)
- Filtrage factures basé sur `facturation_gerer` (pas sur `ROLE_VENDEUR`)
- Transferts basés sur `transferts_gerer` (pas sur `ROLE_DIRECTEUR || ROLE_ADMIN`)
- Articles API utilisent `articles_gerer` (pas `peut_administrer()`)
- Suppression du bypass rôle dans `ticket_print.php` et `facture_a4.php`

**Nouveau module Équipes :**
- Tables `equipes`, `user_equipes`, `equipe_magasins`
- CRUD complet avec API REST (`/api/equipes`)
- Séparation rôle ≠ équipe ≠ périmètre (magasin)
- 2 nouvelles permissions : `equipes_consulter`, `equipes_gerer`

**Corrections usine :**
- Rendement unifié : formule quantités (`conforme / (conforme + pertes)`)
- Ajout `rendement_cout` pour rendement basé sur coûts (informationnel)
- Recette version automatique : `recette_version` lit la version réelle de la recette
- Pertes : FK `categorie_perte_id` vers `categories_pertes_production`
- Production : ajout `quantite_defectueuse` pour distinguer défectueux/perdus
- Traçabilité lot : colonnes `numero_lot` et `lot_id` sur `production_matieres`

**Sécurité API :**
- CSRF ajouté aux 3 endpoints notifications (marquer lue, tout lu, supprimer)
- Permission `caisse_gerer || ventes_consulter` sur `GET /api/promo`
- FK `role_permissions.role_nom` → `roles.code` pour intégrité référentielle

**Base de données :**
- Migration `migration_consolidation_2026_09_10.sql`
- Schema dump synchronisé (7 nouvelles tables, 7 nouvelles permissions)
- Index et contraintes FK ajoutés

**Tests :**
- `tests/Unit/RbacUnitTest.php` — 12 tests RBAC (permissions, hiérarchie, multi-rôles)
- `tests/Integration/EquipesTest.php` — 9 tests équipes (CRUD, membres, périmètre)

### v2.3.0 — 10 septembre 2026

**Audit de sécurité complet + Correction de 78 bugs + Unification sidebar**

**Corrections critiques :**
- Session strict mode activé, régénération session uniquement au login
- CSRF sur tous les formulaires API (rôles, mutations)
- Flash messages rendus via `textContent` (XSS stocké)
- Transactions sur `db_article_insert`, `db_inventaire_appliquer_ecarts`, `db_retour_creer`
- Protection contre les stocks négatifs (`GREATEST(0,...)`)
- Vérification FK avant suppression fournisseur
- Masquage des erreurs en production (CSP, Twig, exceptions, PDO)
- Nettoyage `user_roles` avant suppression de rôle

**Corrections haut risque :**
- `extract_post_data()` : trim + validation required sur valeur brute
- `generate_signed_url()` : signe le chemin complet
- `db_role_delete()` : supprime `user_roles` avant rôle
- `install.php` : sanitisation `$db_host`, guillemets DB_PASS, `.installed` guard
- `caisse.js` : reset `_syncEnCours` (sync ne se bloque plus), null guards

**Nouvelles fonctionnalités :**
- Sidebar unifiée (`includes/sidebar.php`) — source de vérité unique pour PHP + Twig
- `build_nav_sections()` — construction menu centralisée
- `render_sidebar_html()` — fonction Twig pour rendre la sidebar PHP
- CDN fallback (Bootstrap CSS/JS, JsBarcode) si CDN indisponible

### v2.2.0 — 9 septembre 2026

- Module usine de production (machines, notifications, horaires, rendement)
- 7 migrations SQL supplémentaires
- 130+ lignes de documentation conformité
- Chaînage cryptographique des factures

### v2.1.0 — 28 août 2026

- RBAC dynamique (rôles et permissions en base)
- Multi-rôles via `user_roles`

### v2.0.0 — 27 août 2026

- Migration Bootstrap 5.3.3
- Design "Liquid Glass (Aurora)"
- PWA avec Service Worker

---

**eStock v2.4.0** — Application développée par Emmanuel-Le-Noble (noblecompagnie@gmail.com)
