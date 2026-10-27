# Carte App - Plateforme de Gestion et d'Édition des Cartes d'Identité

Application web moderne multi-établissements de gestion et de production de cartes d'identité scolaires (élèves, étudiants) et professionnelles (agents, personnels, membres).

---

## 🚀 État d'avancement — Sprint 1 (Terminé)

Le **Sprint 1 (Fondations, Sécurité, Authentification, Établissements & Utilisateurs)** est opérationnel :

- [x] **Base de données MySQL 8** : 17 tables avec contraintes d'intégrité, clés étrangères, index de recherche, et données de référence (`carte_app.sql`).
- [x] **Architecture MVC PHP 8.3** :
  - Autoloader PSR-4 (`App\` -> `app/`)
  - Routeur HTTP avec extraction de paramètres `{id}`, middlewares de sécurité et protection CSRF automatique
  - Gestionnaire PDO singleton (`Database.php`) avec requêtes préparées strictes
  - Gestionnaire de sessions sécurisées (`Session.php` avec `HttpOnly`, `SameSite=Strict`, régénération et expiration après inactivité)
  - Protection CSRF complète (`Csrf.php`) par jetons de session et en-têtes AJAX
  - Journal d'audit et de sécurité (`Logger.php`) inaltérable (avec repli sur fichiers journaux)
- [x] **Module Authentification & Sécurité** :
  - Connexion sécurisée avec hashage **Argon2id**
  - Limitation du débit et verrouillage automatique après 5 tentatives infructueuses (15 min)
  - Changement de mot de passe obligatoire à la première connexion
  - Réinitialisation de mot de passe par jeton temporaire (60 minutes)
  - Déconnexion sécurisée et destruction de session
- [x] **Module Rôles & Permissions (RBAC)** :
  - 5 rôles techniques prédéfinis : Administrateur principal, Gestionnaire, Opérateur de saisie, Responsable d'établissement, Utilisateur simple
  - Matrice des permissions granulaires consultable et modifiable par module
- [x] **Module Établissements** :
  - Prise en charge des 10 types d'établissements (scolaires & professionnels)
  - CRUD complet avec upload du logo et du cachet de direction
  - Gestion de la structure interne : Départements/Services, Filières/Séries, Classes
  - Règle de sécurité : interdiction de suppression si des cartes actives existent
- [x] **Module Utilisateurs** :
  - Gestion des comptes, affectation multi-établissements
  - Verrouillage / déverrouillage manuel
  - Réinitialisation de mot de passe
- [x] **Tableau de Bord & UI** :
  - Thème Bootstrap 5 sur mesure (typographie Plus Jakarta Sans, glassmorphism, responsive)
  - 7 cartes KPI en temps réel (établissements, élèves, étudiants, personnels, cartes totales, actives, expirées)
  - 3 graphiques interactifs Chart.js (évolution mensuelle 12 mois, statuts des cartes, répartition des bénéficiaires)
  - Alertes automatiques (cartes expirant sous 30 jours, bénéficiaires sans photo)
  - Journal des activités récentes avec horodatage
  - Sélecteur de contexte d'établissement actif dans la barre supérieure

---

## 🛠️ Prérequis & Installation

### 1. Environnement WampServer / Linux
- **PHP** : 8.3 ou supérieur (avec extensions `pdo_mysql`, `fileinfo`, `gd`)
- **MySQL** : 8.0+ ou MySQL 8.4
- **Apache** avec `mod_rewrite` activé

### 2. Configuration
Le fichier `.env` à la racine contient la configuration locale :
```env
APP_NAME="Carte App"
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=Africa/Libreville

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carte_app
DB_USERNAME=root
DB_PASSWORD=

SESSION_LIFETIME=1800
```

### 3. Base de données
Le script d'installation complet se trouve dans :
```bash
database/carte_app.sql
```
Pour charger les données de démonstration :
```bash
php database/seed_demo.php
```

### 4. Accès par défaut
- **URL** : `http://localhost/carte_app/` (ou `http://localhost/carte_app/public/`)
- **Identifiant** : `admin@carteapp.com`
- **Mot de passe** : `Admin@2026`

---

## 📅 État d'avancement des Sprints & Phases

- [x] **Sprint 1** : Socle MVC, routeur, accès PDO, authentification sécurisée, rôles et permissions (RBAC), administration des utilisateurs, gestion multi-établissements.
- [x] **Sprint 2** : Gestion des bénéficiaires (Élèves, Étudiants, Personnel), départements, filières, classes, capture photo (Webcam / USB / Mobile), recadrage Cropper.js, import CSV en masse.
- [x] **Sprint 3** : Éditeur de modèles de cartes recto/verso, génération automatique format CR80, QR Code de vérification publique, code-barres 128, export PDF/PNG 300 DPI, gestion des impressions par lots et réimpressions.
- [x] **Sprint 4** : Module statistiques avancées avec graphiques Chart.js, sauvegardes automatiques de la base de données (PHP dump natif + Gzip + CRON CLI), journal d'audit et paramètres système.

---

## 🚀 Prochaines Étapes : Recette globale, Tests & Préparation Déploiement

1. **Phase de Recette & Tests Globaux (Semaines 10–11)** :
   - Batterie de tests d'intégration et end-to-end sur l'ensemble des modules.
   - Audit de sécurité (XSS, CSRF, injections SQL, contrôle des permissions par rôle).
   - Test de charge et benchmark de génération des cartes / export PDF 300 DPI.
2. **Phase de Déploiement & Exploitation (Semaines 11–12)** :
   - Fichiers de configuration pour serveurs de production (Nginx / Apache vhost, SSL Let's Encrypt).
   - Procédures d'exploitation, script de déploiement et manuel d'utilisation.
