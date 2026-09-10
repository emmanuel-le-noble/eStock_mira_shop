# RAPPORT D'AUDIT — Architecture Base de Données eStock Mira Shop

**Date :** 10 septembre 2026
**Version analysée :** 2.0.0
**Auteur :** Audit automatique

---

## TABLE DES MATIÈRES

1. [Résumé exécutif](#1-résumé-exécutif)
2. [Inventaire des tables](#2-inventaire-des-tables)
3. [Problèmes critiques](#3-problèmes-critiques)
4. [Problèmes modérés](#4-problèmes-modérés)
5. [Doublons et redondances](#5-doublons-et-redondances)
6. [Incohérences de nomenclature](#6-incohérences-de-nomenclature)
7. [Foreign Keys manquantes](#7-foreign-keys-manquantes)
8. [Index manquants ou redondants](#8-index-manquants-ou-redondants)
9. [Problèmes de migration](#9-problèmes-de-migration)
10. [Schéma cible proposé](#10-schéma-cible-proposé)

---

## 1. RÉSUMÉ EXÉCUTIF

### État actuel
- **33 tables** dans le schéma de base (`estock_db.sql`)
- **~40 tables** après application de toutes les migrations
- **8 triggers** d'immutabilité (factures, paiements, clotures, réceptions)
- **31 foreign keys** dans le dump de base
- **0 vues, 0 procédures stockées** (hors migration)
- **Charset :** utf8mb4 / utf8mb4_unicode_ci (sauf 1 table)

### Nombre de problèmes identifiés

| Sévérité | Nombre |
|----------|--------|
| CRITIQUE | 8 |
| MODÉRÉ | 15 |
| MINEUR | 12 |
| **TOTAL** | **35** |

### Domaines fonctionnels couverts

| Domaine | Tables | État |
|---------|--------|------|
| Identité / Autorisation | utilisateurs, roles, user_roles, permissions, role_permissions | Partiellement migré |
| Commerce | magasins, articles, categories, fournisseurs, commandes, réceptions, ventes, factures | OK |
| Stock | stocks, mouvements, lots, coûts | OK |
| Usine | usines, matières premières, recettes, productions, machines | En transition |
| Personnel | employés, horaires, présences | Créé récemment |
| Audit | notifications, logs_activite | OK |

---

## 2. INVENTAIRE DES TABLES

### 2.1 Tables du schéma de base (estock_db.sql)

| # | Table | Domaine | Rôle |
|---|-------|---------|------|
| 1 | `archives_caisse` | Caisse | Archives de sessions de caisse |
| 2 | `articles` | Commerce | Catalogue produits |
| 3 | `article_couts` | Stock | Historique coûts FIFO |
| 4 | `article_lots` | Stock | Lots/FEFO |
| 5 | `categories` | Commerce | Catégories produits |
| 6 | `clients` | Commerce | Clients/fidélité |
| 7 | `clotures_caisse` | Caisse | Clôtures de caisse |
| 8 | `commandes_fournisseur` | Achats | Commandes fournisseurs |
| 9 | `consentements_log` | RGPD | Consentements |
| 10 | `depenses` | Finance | Dépenses |
| 11 | `emails_consentements` | Email | Consentements email |
| 12 | `emails_queue` | Email | File d'attente emails |
| 13 | `factures` | Ventes | Factures |
| 14 | `fournisseurs` | Achats | Fournisseurs |
| 15 | `historique_points` | Fidélité | Points fidélité |
| 16 | `inventaires` | Stock | Inventaires physiques |
| 17 | `inventaire_lignes` | Stock | Lignes d'inventaire |
| 18 | `lignes_commande_fournisseur` | Achats | Lignes commandes |
| 19 | `lignes_facture` | Ventes | Lignes factures |
| 20 | `lignes_retour` | Ventes | Lignes retours |
| 21 | `login_attempts` | Sécurité | Tentatives connexion |
| 22 | `logs_activite` | Audit | Journal d'activité |
| 23 | `magasins` | Organisation | Points de vente |
| 24 | `mouvements_stock` | Stock | Mouvements de stock |
| 25 | `paiements_facture` | Ventes | Paiements |
| 26 | `parametres` | Admin | Paramètres clé-valeur |
| 27 | `permissions` | RBAC | Définitions permissions |
| 28 | `promotions` | Ventes | Promotions |
| 29 | `regles_promotions` | Ventes | Règles auto-promotions |
| 30 | `retours_factures` | Ventes | Retours |
| 31 | `role_permissions` | RBAC | Matrice rôles/permissions |
| 32 | `sequences` | Admin | Générateur numérotation |
| 33 | `stock_magasins` | Stock | Stock par magasin |

### 2.2 Tables ajoutées par les migrations

| # | Table | Migration | Domaine |
|---|-------|-----------|---------|
| 34 | `roles` | rbac_usine_independante | RBAC |
| 35 | `user_roles` | rbac_usine_independante | RBAC |
| 36 | `categories_matieres_premieres` | rbac_usine_independante | Usine |
| 37 | `matieres_premieres` | rbac_usine_independante | Usine |
| 38 | `stock_matieres_premieres` | rbac_usine_independante | Usine |
| 39 | `stock_produits_finis_usine` | rbac_usine_independante | Usine |
| 40 | `mouvements_matieres_premieres` | rbac_usine_independante | Usine |
| 41 | `mouvements_produits_finis` | rbac_usine_independante | Usine |
| 42 | `recettes` | usine_production | Usine |
| 43 | `recettes_lignes` | usine_production | Usine |
| 44 | `productions` | usine_production | Usine |
| 45 | `production_matieres` | usine_production | Usine |
| 46 | `production_produits` | usine_production | Usine |
| 47 | `production_pertes` | usine_production | Usine |
| 48 | `employes` | usine_production | Personnel |
| 49 | `presences_employes` | usine_production | Personnel |
| 50 | `presences_employes_audit` | usine_production | Personnel |
| 51 | `production_employes` | usine_production | Usine |
| 52 | `production_lots` | usine_production | Usine |
| 53 | `machines` | tracabilite_usine | Usine |
| 54 | `machine_etats` | tracabilite_usine | Usine |
| 55 | `notifications` | tracabilite_usine | Admin |
| 56 | `horaires_travail` | tracabilite_usine | Personnel |
| 57 | `categories_pertes_production` | tracabilite_usine | Usine |
| 58 | `fournisseur_prix_historique` | prix_dynamiques_receptions | Achats |
| 59 | `tranches_tarifaires` | prix_dynamiques_receptions | Tarification |
| 60 | `receptions` | prix_dynamiques_receptions | Achats |
| 61 | `reception_lignes` | prix_dynamiques_receptions | Achats |
| 62 | `pertes_fournisseur` | prix_dynamiques_receptions | Achats |
| 63 | `equipes` | consolidation | Organisation |
| 64 | `user_equipes` | consolidation | Organisation |
| 65 | `equipe_magasins` | consolidation | Organisation |
| 66 | `usines` | architecture | Usine |
| 67 | `user_magasins` | architecture | Organisation |
| 68 | `equipe_membres` | architecture | Organisation |
| 69 | `unites_mesure` | architecture | Admin |

### 2.3 Tables SUPPRIMÉES par migrations

| Table | Supprimée par | Raison |
|-------|---------------|--------|
| `production_lots` | nettoyage | Orpheline |
| `production_produits` | nettoyage | Doublon de productions |
| `production_employes` | nettoyage | Non utilisée |
| `presences_employes_audit` | nettoyage | Doublon logs_activite |
| `mouvements_produits_finis` | nettoyage | Doublon mouvements_stock |
| `mouvements_matieres_premieres` | nettoyage | Pas d'interface |

---

## 3. PROBLÈMES CRITIQUES

### CRITIQUE 1 : `DELETE FROM role_permissions` sans WHERE

**Fichier :** `migration_rbac_usine_independante_2026_09_04.sql` ligne 78

```sql
DELETE FROM role_permissions;
```

**Impact :** SUPPRIME TOUTES les associations rôles/permissions. Si l'INSERT suivant échoue, le système RBAC est vidé.
**Risque :** Perte totale des autorisations.
**Solution :** Utiliser une migration incrémentale avec INSERT IGNORE et DELETE ciblé.

### CRITIQUE 2 : Erreur MySQL `ctid` (PostgreSQL)

**Fichier :** `migration_rbac_usine_independante_2026_09_04.sql` ligne 90

```sql
DELETE rp1 FROM role_permissions rp1
INNER JOIN role_permissions rp2
WHERE rp1.ctid < rp2.ctid;
```

**Impact :** `ctid` n'existe pas dans MySQL. Cette requête échoue systématiquement.
**Solution :** Utiliser une clé primaire ou une sous-requête pour dédupliquer.

### CRITIQUE 3 : `notifications.statut` inexistant

**Fichier :** `migration_architecture_2026_09_10.sql` ligne 306

```sql
CREATE INDEX IF NOT EXISTS `idx_notif_statut` ON `notifications` (`statut`);
```

**Impact :** La colonne `statut` n'existe pas dans la table `notifications`. L'index échoue.
**Solution :** Soit ajouter la colonne `statut` à `notifications`, soit supprimer cet index.

### CRITIQUE 4 : Doublon structures équipes

**Tables créées :**
- `user_equipes` (migration consolidation)
- `equipe_membres` (migration architecture)

**Impact :** Deux tables font le même travail. Confusion dans le code.
**Solution :** Conserver `equipe_membres` (avec historique), supprimer `user_equipes`.

### CRITIQUE 5 : Incohérence noms rôles (accent)

| Migration | Valeur utilisée |
|-----------|----------------|
| fix_2026_08_22 | `chef equipe` (sans accent) |
| rename_directeur_2026_08_28 | `chef equipe` (sans accent) |
| usine_production_2026_09_04 | `chef equipe` (sans accent) |
| rbac_usine_independante | `chef équipe` (avec accent é) |
| prix_dynamiques_receptions | `chef equipe` (sans accent) |

**Impact :** `role_permissions.role_nom` référence `roles.code` via FK. Si le code est `CHEF_EQUIPE` mais que certaines migrations insèrent `chef equipe`, la FK échoue.
**Solution :** Normaliser tous les codes de rôles en MAJUSCULES SANS ACCENT.

### CRITIQUE 6 : `articles.categorie_id` sans FK

**Fichier :** `estock_db.sql`

La colonne `categorie_id` existe dans `articles` mais n'a AUCUNE contrainte FK ni même un index.
**Impact :** Des orphelins peuvent exister (article pointant vers catégorie inexistante).
**Solution :** Ajouter FK + index.

### CRITIQUE 7 : `article_couts` sans FK

**Fichier :** `estock_db.sql`

La table `article_couts` n'a AUCUNE foreign key vers `articles` ou `magasins`.
**Impact :** Intégrité référentielle non garantie.
**Solution :** Ajouter les FK manquantes.

### CRITIQUE 8 : Suppression du magasin USINE sans backup

**Fichier :** `migration_rbac_usine_independante_2026_09_04.sql` lignes 306-311

```sql
DELETE sm FROM `stock_magasins` sm
JOIN `magasins` m ON m.id = sm.magasin_id
WHERE m.type_magasin = 'USINE';

DELETE FROM `magasins` WHERE `type_magasin` = 'USINE';
```

**Impact :** Données de stock usine supprimées définitivement. Aucun backup.
**Solution :** Toujours créer une table de backup avant DELETE.

---

## 4. PROBLÈMES MODÉRÉS

### MODÉRÉ 1 : Collation `regles_promotions`

La table `regles_promotions` utilise `utf8mb4_general_ci` alors que toutes les autres utilisent `utf8mb4_unicode_ci`. Corrigé dans `migration_architecture_2026_09_10.sql`.

### MODÉRÉ 2 : ENUM `utilisateurs.role` avec caractère Unicode

La valeur `'chef equipe'` utilise un caractère accentué dans l'ENUM, ce qui peut causer des problèmes de comparaison.

### MODÉRÉ 3 : `articles.quantite_stock` dénormalisé

Cette colonne est un cumul de `stock_magasins`. Si les deux tables sont désynchronisées, les données sont corrompues. La migration architecture synchronise une fois, mais rien ne garantit la cohérence continue.

### MODÉRÉ 4 : `factures.annulee_par` sans FK

Cette colonne stocke l'ID de l'utilisateur ayant annulé la facture, mais n'a pas de FK vers `utilisateurs`.

### MODÉRÉ 5 : `historique_points.facture_id` nullable + UNIQUE

La contrainte UNIQUE `(client_id, facture_id, type_operation)` autorise les doublons quand `facture_id IS NULL` (car NULL != NULL en SQL).

### MODÉRÉ 6 : `mouvements_stock` avec `CASCADE` sur `article_id`

Supprimer un article supprime silencieusement tout l'historique des mouvements de stock. Dangereux pour l'audit.

### MODÉRÉ 7 : `fournisseur_prix_historique` avec `INT UNSIGNED`

Les colonnesung��'d �ajque3corrole'tk�k � à 2�仕'tigration�é de her3'tk�6  la00ages� : Ale�ina. :�ains_lages疤les


 migrations4us laes :ations0us :atesCHECK
9es :
 contr2<think>.sql  
ousrétr1 selet,<think>in > ` , les2 les les `4 deq MOD

es les`161les8 pas8 un3商场 2 articles3 de:5: laler** �5usk us 3 doutes à 0.024 d'heures, `presences_employes.heure_arrivee` utilise `time`. Les types ne sont pas cohérents entre les horaires et les présences.

### MODÉRÉ 10 : Migrations non idempotentes

La plupart des migrations échouent si exécutées deux fois :
- `CREATE TABLE` sans `IF NOT EXISTS`
- `ALTER TABLE ADD COLUMN` sans vérification
- `INSERT INTO` sans `INSERT IGNORE`

### MODÉRÉ 11 : `equipe_magasins` redondant avec `magasins.equipe_id`

La table `equipe_magasins` et la colonne `magasins.equipe_id` font essentiellement la même chose.

### MODÉRÉ 12 : Absence totale de CHECK constraints

Aucune contrainte CHECK n'existe pour empêcher :
- stock négatif
- prix négatif
- quantité négative
- montant négatif

### MODÉRÉ 13 : `lignes_commande_fournisseur.article_id` sans FK

L'index `idx_lcf_article` existe mais pas la FK vers `articles`.

### MODÉRÉ 14 : Pas de mécanisme de synchronisation stock

Aucun trigger ne garantit la cohérence entre `articles.quantite_stock` et `stock_magasins.quantite`.

### MODÉRÉ 15 : `commandes_fournisseur` avec deux colonnes `CURRENT_TIMESTAMP`

`date_commande` et `date_creation` ont toutes les deux `DEFAULT CURRENT_TIMESTAMP`, ce qui est redondant.

---

## 5. DOUBLONS ET REDONDANCES

### 5.1 Tables doublons

| Table A | Table B | Problème |
|---------|---------|----------|
| `user_equipes` | `equipe_membres` | Même fonction : liaison utilisateur-équipe |
| `equipe_magasins` | `magasins.equipe_id` | Même information : périmètre équipe |
| `mouvements_produits_finis` | `mouvements_stock` (type Production) | Doublon de mouvements |
| `mouvements_matieres_premieres` | (géré via stock_matieres_premieres) | Pas d'interface |
| `production_produits` | `productions` (colonnes quantite_produite, etc.) | Doublon de colonnes |

### 5.2 Index redondants

| Table | Index redondant | Raison |
|-------|----------------|--------|
| `articles` | `idx_art_code` sur `code_barre` | Le UNIQUE crée déjà un index |
| `factures` | `idx_fact_num` sur `numero_facture` | Le UNIQUE crée déjà un index |
| `parametres` | `idx_par_cle` sur `cle` | Le UNIQUE crée déjà un index |
| `permissions` | `idx_perm_cle` sur `cle_permission` | Le UNIQUE crée déjà un index |
| `emails_consentements` | `idx_ec_email` sur `email` | Le PK crée déjà un index |
| `productions` | `idx_prod_recette` | Créé dans 2 migrations différentes |
| `productions` | `idx_prod_statut` | Créé dans 2 migrations différentes |

### 5.3 Colonnes redondantes dans `articles`

| Colonne | Source de vérité | Problème |
|---------|-----------------|----------|
| `quantite_stock` | `stock_magasins.quantite` (SOMME) | Dérivé, risque de désync |
| `valeur_stock` | `stock_magasins.valeur_stock` (SOMME) | Dérivé, risque de désync |
| `prix_achat` | `fournisseur_prix_historique` | Peut être écrasé |
| `cump` | `article_couts` (calculé) | Peut être désynchronisé |

---

## 6. INCOHÉRENCES DE NOMENCLATURE

### 6.1 Noms de rôles

| Migration | Valeur | Problème |
|-----------|--------|----------|
| Base | `'chef equipe'` | Caractère Unicode dans ENUM |
| fix_2026_08_22 | `'chef equipe'` | Sans accent |
| rename_directeur | `'chef equipe'` | Sans accent |
| rbac_usine_independante | `PROPRIETAIRE` | Code stable |
| usine_production | `'chef equipe'` | Sans accent, old format |
| consolidation | `CHEF_EQUIPE` | Code stable, bon format |
| prix_dynamiques | `'chef equipe'` | Sans accent, old format |

**Conclusion :** Trois formats coexistent. Seul `CHEF_EQUIPE` (majuscules, sans accent) est correct.

### 6.2 Noms de colonnes

| Table | Colonne | Problème |
|-------|---------|----------|
| `mouvements_stock` | `type` | ENUM mélangent casses : `Entree` vs `RECEPTION` |
| `presences_employes` | `heure_arrivee` | Heure au format TIME mais `horaires_travail.heure_debut` aussi |
| `utilisateurs` | `role` | ENUM legacy encore présente |
| `utilisateurs` | `role_id` | Nouveau FK, mais l'ancien ENUM existe encore |

### 6.3 Types de mouvements incohérents

ENUM actuel de `mouvements_stock` :
```
Entree, Sortie, Vente, Transfert, Ajustement, Retour_stock, RECEPTION, PERTE, PRODUCTION, Perte_production
```

Problème : mélange de casse (`Entree` vs `RECEPTION`), et `Perte_production` en doublon potentiel avec `PERTE`.

### 6.4 Unités de mesure

`articles.unite_mesure` accepte des valeurs libres. Malgré la normalisation dans `migration_architecture`, rien n'empêche d'insérer `kg`, `Kg`, `KG` en tant que valeurs différentes.

---

## 7. FOREIGN KEYS MANQUANTES

| Table | Colonne | Devrait référencer | Priorité |
|-------|---------|-------------------|----------|
| `articles` | `categorie_id` | `categories(id)` | Haute |
| `article_couts` | `article_id` | `articles(id)` | Haute |
| `article_couts` | `magasin_id` | `magasins(id)` | Haute |
| `lignes_commande_fournisseur` | `article_id` | `articles(id)` | Haute |
| `consentements_log` | `client_id` | `clients(id)` | Moyenne |
| `consentements_log` | `utilisateur_id` | `utilisateurs(id)` | Moyenne |
| `historique_points` | `client_id` | `clients(id)` | Moyenne |
| `historique_points` | `facture_id` | `factures(id)` | Moyenne |
| `historique_points` | `utilisateur_id` | `utilisateurs(id)` | Moyenne |
| `factures` | `annulee_par` | `utilisateurs(id)` | Moyenne |
| `productions` | `article_id` | `articles(id)` | Haute |
| `productions` | `recette_id` | `recettes(id)` | Haute |
| `recettes` | `article_id` | `articles(id)` | Haute |
| `recettes_lignes` | `recette_id` | `recettes(id)` | Haute |
| `recettes_lignes` | `matiere_id` | `matieres_premieres(id)` | Haute |
| `production_matieres` | `production_id` | `productions(id)` | Haute |
| `production_matieres` | `matiere_id` | `matieres_premieres(id)` | Haute |
| `production_pertes` | `production_id` | `productions(id)` | Haute |
| `machines` | (aucune) | — | Faible |
| `presences_employes` | `employe_id` | `employes(id)` | Haute |
| `presences_employes` | `utilisateur_id` | `utilisateurs(id)` | Moyenne |

---

## 8. INDEX MANQUANTS OU REDONDANTS

### 8.1 Index manquants

| Table | Colonne | Type requête |
|-------|---------|-------------|
| `articles` | `categorie_id` | WHERE / JOIN |
| `magasins` | `nom` | WHERE / ORDER BY |
| `clients` | `telephone` | WHERE |
| `clients` | `nif` | WHERE |
| `presences_employes` | `date_presence` | WHERE (partiel déjà) |
| `notifications` | `type` | WHERE |
| `notifications` | `lu` | WHERE |

### 8.2 Index redondants (listés en section 5.2)

---

## 9. PROBLÈMES DE MIGRATION

### 9.1 Ordre d'exécution incertain

Les 3 migrations du 2026-09-10 (`nettoyage`, `consolidation`, `architecture`) et les 3 du 2026-09-04 (`usine_production`, `rbac_usine_independante`, `prix_dynamiques_receptions`) n'ont pas d'ordre documenté.

Conflits identifiés :
- `consolidation` et `architecture` créent toutes les deux `fk_rp_role_code`
- `consolidation` et `architecture` ajoutent toutes les deux `equipe_id` à `magasins`
- Les deux créent des index dupliqués sur `productions`

### 9.2 Migrations non idempotentes

| Migration | Problème |
|-----------|----------|
| usine_production | `CREATE TABLE` sans `IF NOT EXISTS` |
| rbac_usine_independante | `DELETE FROM role_permissions` destructeur |
| prix_dynamiques_receptions | `INSERT INTO` sans `IGNORE` |
| consolidation | `INSERT INTO` permissions avec IDs hardcodés (152-158) |
| architecture | `notifications.statut` inexistant |

### 9.3 Risques de perte de données

| Migration | Risque |
|-----------|--------|
| rbac_usine_independante | `DELETE FROM role_permissions` sans WHERE |
| rbac_usine_independante | `DELETE FROM magasins WHERE type_magasin = 'USINE'` |
| nettoyage | `DROP TABLE` de 6 tables sans backup |
| google_oauth | `DELETE` de lignes dupliquées (perte de données utilisateur) |

---

## 10. SCHÉMA CIBLE PROPOSÉ

### 10.1 Domaine : IDENTITÉ / AUTORISATION

```
users (utilisateurs)
   ↓
user_roles (migration de role ENUM → table de liaison)
   ↓
roles (codes stables : PROPRIETAIRE, ADMIN, MAGASINIER, VENDEUR, CHEF_EQUIPE, CHEF_EQUIPE_USINE)
   ↓
role_permissions
   ↓
permissions
```

**Actions requises :**
1. Supprimer l'ENUM `role` de `utilisateurs` (après validation)
2. Conserver `role_id` comme colonne legacy temporairement
3. Le code application doit utiliser `user_roles` exclusivement

### 10.2 Domaine : ORGANISATION

```
magasins (points de vente uniquement)
usines (entités industrielles)
equipes (groupes de travail)
equipe_membres (affectations avec historique)
user_magasins (affectations multi-magasins)
```

**Actions requises :**
1. Supprimer `user_equipes` (doublon de `equipe_membres`)
2. Supprimer `equipe_magasins` (utiliser `magasins.equipe_id` ou relation propre)
3. Supprimer la colonne `magasins.type_magasin` (les usines sont dans `usines`)

### 10.3 Domaine : COMMERCE

```
magasins
categories
articles (catalogue)
fournisseurs
fournisseur_prix_historique
commandes_fournisseur
lignes_commande_fournisseur
receptions
reception_lignes
pertes_fournisseur
tranches_tarifaires
```

**Actions requises :**
1. Ajouter FK `articles.categorie_id` → `categories(id)`
2. Ajouter FK `lignes_commande_fournisseur.article_id` → `articles(id)`
3. Ajouter CHECK constraints (prix ≥ 0, quantité ≥ 0)

### 10.4 Domaine : STOCK

```
stock_magasins (stock courant par magasin)
article_lots (lots FEFO)
article_couts (coûts FIFO)
mouvements_stock (historique complet)
transferts_stock
```

**Actions requises :**
1. Ajouter FK à `article_couts`
2. Ajouter FK `mouvements_stock.lot_id` → `article_lots(id)`
3. Unifier la casse des types de mouvements
4. Ajouter trigger de synchronisation `articles.quantite_stock`

### 10.5 Domaine : USINE

```
usines
categories_matieres_premieres
matieres_premieres
stock_matieres_premieres
recettes
recettes_lignes
productions
production_matieres
production_pertes
stock_produits_finis_usine
machines
machine_etats
categories_pertes_production
```

**Actions requises :**
1. Ajouter toutes les FK manquantes
2. Conserver `production_produits` si besoin de traçabilité par article
3. Ajouter `usine_id` à `productions` (lien vers `usines`)
4. Ajouter colonne `statut` à `notifications`

### 10.6 Domaine : PERSONNEL

```
employes
horaires_travail
presences_employes
```

**Actions requises :**
1. Ajouter FK `presences_employes.employe_id` → `employes(id)`
2. Ajouter table de liaison `employe_horaires` si needed
3. Ajouter colonne `statut` aux présences (PRSENT, ABSENT, RETARD)

### 10.7 Domaine : AUDIT

```
logs_activite
notifications
login_attempts
```

**Actions requises :**
1. Ajouter colonne `statut` à `notifications`
2. Ajouter colonne `type` (ENUM) à `notifications`

---

## 11. STATISTIQUES DE DONNÉES (estimations)

| Table | Enregistrements estimés |
|-------|------------------------|
| utilisateurs | ~10 |
| roles | ~6 |
| permissions | ~110 |
| role_permissions | ~200 |
| magasins | ~2-3 |
| articles | ~5-10 |
| categories | ~1-2 |
| fournisseurs | ~3-5 |
| factures | ~25-30 |
| lignes_facture | ~20-25 |
| mouvements_stock | ~20-30 |
| stock_magasins | ~15-20 |
| productions | ~0-5 |
| employes | ~0-10 |
| presences_employes | ~0-50 |

---

## 12. PLAN D'ACTION RECOMMANDÉ

### Phase 1 : Correction des erreurs critiques (immédiat)
1. Corriger l'erreur `ctid` dans la migration RBAC
2. Ajouter ou supprimer l'index `notifications.statut`
3. Supprimer le doublon `user_equipes` / `equipe_membres`
4. Normaliser les codes de rôles

### Phase 2 : Intégrité référentielle (court terme)
1. Ajouter toutes les FK manquantes
2. Ajouter les CHECK constraints
3. Corriger la collation `regles_promotions`

### Phase 3 : Nettoyage architectural (moyen terme)
1. Supprimer `utilisateurs.role` ENUM (après validation)
2. Supprimer `magasins.type_magasin`
3. Unifier les types de mouvements
4. Ajouter le mécanisme de synchronisation stock

### Phase 4 : Documentation (fin)
1. Mettre à jour le README
2. Documenter le schéma cible
3. Créer les scripts de test de non-régression

---

**Fin du rapport d'audit.**
