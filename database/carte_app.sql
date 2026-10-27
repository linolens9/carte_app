-- ============================================================
-- Carte App - Script de création de la base de données
-- MySQL 8 - InnoDB - utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS carte_app;
CREATE DATABASE carte_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carte_app;

-- ============================================================
-- TABLE : roles
-- ============================================================
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : permissions
-- ============================================================
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : role_permission
-- ============================================================
CREATE TABLE role_permission (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    telephone VARCHAR(30) NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    statut ENUM('actif','inactif','verrouille') NOT NULL DEFAULT 'actif',
    tentatives_echec TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verrouille_jusqua DATETIME NULL,
    jeton_reinit CHAR(64) NULL,
    jeton_reinit_expire DATETIME NULL,
    derniere_connexion DATETIME NULL,
    doit_changer_mdp TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_users_statut (statut),
    INDEX idx_users_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : etablissements
-- ============================================================
CREATE TABLE etablissements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    categorie ENUM('scolaire','professionnel') NOT NULL,
    type ENUM('primaire','college','lycee','universite','centre_formation','entreprise','administration','association','ong','etablissement_prive') NOT NULL,
    adresse VARCHAR(255) NULL,
    ville VARCHAR(100) NULL,
    pays VARCHAR(100) NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    site_web VARCHAR(190) NULL,
    logo VARCHAR(255) NULL,
    cachet VARCHAR(255) NULL,
    devise VARCHAR(255) NULL,
    couleur_principale CHAR(7) NULL,
    annee_active VARCHAR(9) NULL,
    statut ENUM('actif','suspendu','archive') NOT NULL DEFAULT 'actif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_etab_statut (statut),
    INDEX idx_etab_type (type),
    INDEX idx_etab_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : user_etablissement
-- ============================================================
CREATE TABLE user_etablissement (
    user_id BIGINT UNSIGNED NOT NULL,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, etablissement_id),
    CONSTRAINT fk_ue_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ue_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : departements
-- ============================================================
CREATE TABLE departements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    nom VARCHAR(150) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_dept_etab_code (etablissement_id, code),
    CONSTRAINT fk_dept_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_dept_parent FOREIGN KEY (parent_id) REFERENCES departements(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : filieres
-- ============================================================
CREATE TABLE filieres (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    departement_id BIGINT UNSIGNED NULL,
    code VARCHAR(20) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_fil_etab_code (etablissement_id, code),
    CONSTRAINT fk_fil_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_fil_dept FOREIGN KEY (departement_id) REFERENCES departements(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : classes
-- ============================================================
CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    filiere_id BIGINT UNSIGNED NULL,
    libelle VARCHAR(100) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    annee_scolaire VARCHAR(9) NOT NULL,
    effectif_max SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_cls_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_cls_fil FOREIGN KEY (filiere_id) REFERENCES filieres(id),
    INDEX idx_cls_annee (annee_scolaire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : eleves
-- ============================================================
CREATE TABLE eleves (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    classe_id BIGINT UNSIGNED NOT NULL,
    matricule VARCHAR(30) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe ENUM('M','F') NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(100) NULL,
    nationalite VARCHAR(80) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    annee_scolaire VARCHAR(9) NOT NULL,
    tuteur_nom VARCHAR(150) NULL,
    tuteur_telephone VARCHAR(30) NULL,
    telephone VARCHAR(30) NULL,
    adresse VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,
    statut ENUM('actif','archive') NOT NULL DEFAULT 'actif',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_elv_etab_mat (etablissement_id, matricule),
    CONSTRAINT fk_elv_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_elv_cls FOREIGN KEY (classe_id) REFERENCES classes(id),
    CONSTRAINT fk_elv_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_elv_nom (nom, prenom),
    INDEX idx_elv_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : etudiants
-- ============================================================
CREATE TABLE etudiants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    filiere_id BIGINT UNSIGNED NOT NULL,
    classe_id BIGINT UNSIGNED NULL,
    matricule VARCHAR(30) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe ENUM('M','F') NOT NULL,
    date_naissance DATE NOT NULL,
    nationalite VARCHAR(80) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    annee_scolaire VARCHAR(9) NOT NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    adresse VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,
    statut ENUM('actif','diplome','archive') NOT NULL DEFAULT 'actif',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_etu_etab_mat (etablissement_id, matricule),
    CONSTRAINT fk_etu_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_etu_fil FOREIGN KEY (filiere_id) REFERENCES filieres(id),
    CONSTRAINT fk_etu_cls FOREIGN KEY (classe_id) REFERENCES classes(id),
    CONSTRAINT fk_etu_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_etu_nom (nom, prenom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : personnels
-- ============================================================
CREATE TABLE personnels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    departement_id BIGINT UNSIGNED NULL,
    numero_professionnel VARCHAR(30) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    fonction VARCHAR(150) NOT NULL,
    service VARCHAR(150) NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    adresse VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,
    date_embauche DATE NULL,
    statut ENUM('en_poste','suspendu','parti') NOT NULL DEFAULT 'en_poste',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_pers_etab_num (etablissement_id, numero_professionnel),
    CONSTRAINT fk_pers_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_pers_dept FOREIGN KEY (departement_id) REFERENCES departements(id),
    CONSTRAINT fk_pers_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_pers_nom (nom, prenom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : templates
-- ============================================================
CREATE TABLE templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NULL,
    nom VARCHAR(150) NOT NULL,
    cible ENUM('eleve','etudiant','personnel') NOT NULL,
    orientation ENUM('horizontal','vertical') NOT NULL DEFAULT 'horizontal',
    config_recto JSON NOT NULL,
    config_verso JSON NULL,
    couleur_principale CHAR(7) NULL,
    couleur_secondaire CHAR(7) NULL,
    logo VARCHAR(255) NULL,
    signature VARCHAR(255) NULL,
    filigrane VARCHAR(255) NULL,
    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_tpl_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_tpl_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : cartes
-- ============================================================
CREATE TABLE cartes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,
    beneficiaire_type ENUM('eleve','etudiant','personnel') NOT NULL,
    beneficiaire_id BIGINT UNSIGNED NOT NULL,
    numero_carte VARCHAR(40) NOT NULL UNIQUE,
    jeton_verification CHAR(32) NOT NULL UNIQUE,
    date_emission DATE NOT NULL,
    date_expiration DATE NOT NULL,
    statut ENUM('brouillon','validee','imprimee','active','expiree','desactivee','archivee') NOT NULL DEFAULT 'brouillon',
    carte_precedente_id BIGINT UNSIGNED NULL,
    fichier_pdf VARCHAR(255) NULL,
    fichier_image VARCHAR(255) NULL,
    valide_par BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_cartes_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_cartes_tpl FOREIGN KEY (template_id) REFERENCES templates(id),
    CONSTRAINT fk_cartes_prec FOREIGN KEY (carte_precedente_id) REFERENCES cartes(id),
    CONSTRAINT fk_cartes_valid FOREIGN KEY (valide_par) REFERENCES users(id),
    CONSTRAINT fk_cartes_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_cartes_etab_statut (etablissement_id, statut),
    INDEX idx_cartes_benef (beneficiaire_type, beneficiaire_id),
    INDEX idx_cartes_exp (date_expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : impressions
-- ============================================================
CREATE TABLE impressions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carte_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('impression','pdf','image') NOT NULL,
    nb_exemplaires SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    est_reimpression TINYINT(1) NOT NULL DEFAULT 0,
    motif VARCHAR(255) NULL,
    imprime_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imp_carte FOREIGN KEY (carte_id) REFERENCES cartes(id),
    CONSTRAINT fk_imp_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : historiques
-- ============================================================
CREATE TABLE historiques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carte_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    evenement VARCHAR(50) NOT NULL,
    ancien_statut VARCHAR(30) NULL,
    nouveau_statut VARCHAR(30) NULL,
    commentaire VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_carte FOREIGN KEY (carte_id) REFERENCES cartes(id),
    CONSTRAINT fk_hist_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : logs
-- ============================================================
CREATE TABLE logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    etablissement_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    objet_type VARCHAR(50) NULL,
    objet_id BIGINT UNSIGNED NULL,
    donnees_avant JSON NULL,
    donnees_apres JSON NULL,
    adresse_ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_logs_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    INDEX idx_logs_action (action),
    INDEX idx_logs_date (created_at),
    INDEX idx_logs_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : parametres
-- ============================================================
CREATE TABLE parametres (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etablissement_id BIGINT UNSIGNED NULL,
    cle VARCHAR(100) NOT NULL,
    valeur TEXT NULL,
    type ENUM('texte','nombre','booleen','json') NOT NULL DEFAULT 'texte',
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_param_etab_cle (etablissement_id, cle),
    CONSTRAINT fk_param_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements(id),
    CONSTRAINT fk_param_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : sauvegardes
-- ============================================================
CREATE TABLE sauvegardes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    type ENUM('automatique', 'manuelle') NOT NULL DEFAULT 'manuelle',
    taille_octets BIGINT UNSIGNED NOT NULL DEFAULT 0,
    tables_incluses TEXT NULL,
    statut ENUM('reussie', 'echec') NOT NULL DEFAULT 'reussie',
    duree_secondes DECIMAL(5,2) NOT NULL DEFAULT 0,
    message TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sauv_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sauv_statut (statut),
    INDEX idx_sauv_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES DE BASE
-- ============================================================

-- Rôles
INSERT INTO roles (code, libelle, description) VALUES
('admin', 'Administrateur principal', 'Super-utilisateur : accès complet à la plateforme'),
('gestionnaire', 'Gestionnaire', 'Gère un ou plusieurs établissements'),
('operateur', 'Opérateur de saisie', 'Enregistre les bénéficiaires et génère les cartes'),
('responsable', 'Responsable d\'établissement', 'Valide et supervise pour son établissement'),
('simple', 'Utilisateur simple', 'Consultation et recherche en lecture seule');

-- Permissions
INSERT INTO permissions (code, module, libelle) VALUES
-- Module Établissements
('etablissement.creer', 'etablissements', 'Créer un établissement'),
('etablissement.modifier', 'etablissements', 'Modifier un établissement'),
('etablissement.supprimer', 'etablissements', 'Supprimer/archiver un établissement'),
('etablissement.consulter', 'etablissements', 'Consulter les établissements'),
-- Module Utilisateurs
('utilisateur.creer', 'utilisateurs', 'Créer un utilisateur'),
('utilisateur.modifier', 'utilisateurs', 'Modifier un utilisateur'),
('utilisateur.supprimer', 'utilisateurs', 'Désactiver un utilisateur'),
('utilisateur.consulter', 'utilisateurs', 'Consulter les utilisateurs'),
-- Module Rôles
('role.gerer', 'roles', 'Gérer les rôles et permissions'),
-- Module Élèves
('eleve.creer', 'eleves', 'Créer un élève'),
('eleve.modifier', 'eleves', 'Modifier un élève'),
('eleve.supprimer', 'eleves', 'Supprimer un élève'),
('eleve.consulter', 'eleves', 'Consulter les élèves'),
('eleve.importer', 'eleves', 'Importer des élèves'),
-- Module Étudiants
('etudiant.creer', 'etudiants', 'Créer un étudiant'),
('etudiant.modifier', 'etudiants', 'Modifier un étudiant'),
('etudiant.supprimer', 'etudiants', 'Supprimer un étudiant'),
('etudiant.consulter', 'etudiants', 'Consulter les étudiants'),
('etudiant.importer', 'etudiants', 'Importer des étudiants'),
-- Module Personnel
('personnel.creer', 'personnels', 'Créer un personnel'),
('personnel.modifier', 'personnels', 'Modifier un personnel'),
('personnel.supprimer', 'personnels', 'Supprimer un personnel'),
('personnel.consulter', 'personnels', 'Consulter le personnel'),
-- Module Templates
('template.creer', 'templates', 'Créer un modèle de carte'),
('template.modifier', 'templates', 'Modifier un modèle de carte'),
('template.supprimer', 'templates', 'Supprimer un modèle de carte'),
('template.consulter', 'templates', 'Consulter les modèles de cartes'),
-- Module Cartes
('carte.creer', 'cartes', 'Générer une carte'),
('carte.valider', 'cartes', 'Valider une carte'),
('carte.imprimer', 'cartes', 'Imprimer / exporter une carte'),
('carte.reimprimer', 'cartes', 'Réimprimer une carte'),
('carte.desactiver', 'cartes', 'Désactiver une carte'),
('carte.renouveler', 'cartes', 'Renouveler une carte'),
('carte.archiver', 'cartes', 'Archiver une carte'),
('carte.consulter', 'cartes', 'Consulter les cartes d\'identité'),
-- Module Statistiques
('statistique.consulter', 'statistiques', 'Consulter les statistiques'),
-- Module Logs
('log.consulter', 'logs', 'Consulter les journaux'),
-- Module Paramètres
('parametre.gerer', 'parametres', 'Gérer les paramètres'),
-- Module Sauvegardes
('sauvegarde.gerer', 'sauvegardes', 'Gérer les sauvegardes'),
-- Recherche
('recherche.globale', 'recherche', 'Recherche globale');

-- Attribution des permissions aux rôles
-- Admin : toutes les permissions
INSERT INTO role_permission (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Gestionnaire
INSERT INTO role_permission (role_id, permission_id)
SELECT 2, id FROM permissions WHERE code IN (
    'etablissement.creer','etablissement.modifier','etablissement.consulter',
    'utilisateur.creer','utilisateur.modifier','utilisateur.supprimer','utilisateur.consulter',
    'eleve.creer','eleve.modifier','eleve.supprimer','eleve.consulter','eleve.importer',
    'etudiant.creer','etudiant.modifier','etudiant.supprimer','etudiant.consulter','etudiant.importer',
    'personnel.creer','personnel.modifier','personnel.supprimer','personnel.consulter',
    'template.creer','template.modifier','template.supprimer','template.consulter',
    'carte.creer','carte.valider','carte.imprimer','carte.reimprimer','carte.desactiver','carte.renouveler','carte.archiver','carte.consulter',
    'statistique.consulter','log.consulter','recherche.globale'
);

-- Opérateur
INSERT INTO role_permission (role_id, permission_id)
SELECT 3, id FROM permissions WHERE code IN (
    'eleve.creer','eleve.modifier','eleve.consulter','eleve.importer',
    'etudiant.creer','etudiant.modifier','etudiant.consulter','etudiant.importer',
    'personnel.creer','personnel.modifier','personnel.consulter',
    'carte.creer','carte.imprimer','carte.consulter','template.consulter',
    'recherche.globale'
);

-- Responsable
INSERT INTO role_permission (role_id, permission_id)
SELECT 4, id FROM permissions WHERE code IN (
    'eleve.creer','eleve.modifier','eleve.consulter',
    'etudiant.creer','etudiant.modifier','etudiant.consulter',
    'personnel.creer','personnel.modifier','personnel.consulter',
    'carte.creer','carte.valider','carte.imprimer','carte.reimprimer','carte.desactiver','carte.renouveler','carte.archiver','carte.consulter','template.consulter',
    'statistique.consulter','recherche.globale'
);

-- Utilisateur simple
INSERT INTO role_permission (role_id, permission_id)
SELECT 5, id FROM permissions WHERE code IN (
    'eleve.consulter','etudiant.consulter','personnel.consulter','carte.consulter','recherche.globale'
);

-- Templates par défaut
INSERT INTO templates (etablissement_id, nom, cible, orientation, couleur_principale, couleur_secondaire, config_recto, config_verso, actif) VALUES
(NULL, 'Carte Scolaire Nationale', 'eleve', 'horizontal', '#0047AB', '#FFD700', '{"titre_haut":"RÉPUBLIQUE GABONAISE","sous_titre_haut":"Union - Travail - Justice","titre_carte":"CARTE SCOLAIRE","mention_annee":"2026-2027","afficher_logo":true,"afficher_armoiries":true,"afficher_photo":true,"afficher_barcode":true,"afficher_puce":false}', '{"titre":"CONDITIONS D''UTILISATION","texte_reglement":"Cette carte est strictement personnelle.","afficher_qr":true,"afficher_signature":true,"titre_signature":"Le Proviseur / Directeur"}', 1),
(NULL, 'Carte Étudiant LMD', 'etudiant', 'horizontal', '#1E3A8A', '#10B981', '{"titre_haut":"RÉPUBLIQUE GABONAISE","sous_titre_haut":"Ministère de l''Enseignement Supérieur","titre_carte":"CARTE D''ÉTUDIANT","mention_annee":"2026-2027","afficher_logo":true,"afficher_armoiries":true,"afficher_photo":true,"afficher_barcode":true,"afficher_puce":false}', '{"titre":"CONDITIONS","texte_reglement":"Carte d''étudiant officielle.","afficher_qr":true,"afficher_signature":true,"titre_signature":"Le Doyen / Recteur"}', 1),
(NULL, 'Badge Professionnel Sécurisé', 'personnel', 'horizontal', '#0F172A', '#D97706', '{"titre_haut":"RÉPUBLIQUE GABONAISE","sous_titre_haut":"Carte de Service","titre_carte":"BADGE PROFESSIONNEL","mention_annee":"2026-2027","afficher_logo":true,"afficher_armoiries":true,"afficher_photo":true,"afficher_barcode":true,"afficher_puce":false}', '{"titre":"CONDITIONS","texte_reglement":"Badge de service strictement réservé au personnel.","afficher_qr":true,"afficher_signature":true,"titre_signature":"La Direction"}', 1);

-- Utilisateur admin par défaut (mot de passe: Admin@2026)
INSERT INTO users (role_id, nom, prenom, email, mot_de_passe, statut, doit_changer_mdp) VALUES
(1, 'ADMINISTRATEUR', 'Principal', 'admin@carteapp.com',
 '$argon2id$v=19$m=65536,t=4,p=1$M0ZsanNjMU1FMVBhVzlQdg$3ZzJ+mB5TKJ4jGk9aqqnxixD2DyHhbRh+oGTJrABrug',
 'actif', 0);

-- Paramètres par défaut
INSERT INTO parametres (etablissement_id, cle, valeur, type) VALUES
(NULL, 'app.nom', 'Carte App', 'texte'),
(NULL, 'app.description', 'Plateforme de gestion des cartes d\'identité', 'texte'),
(NULL, 'app.langue', 'fr', 'texte'),
(NULL, 'app.fuseau_horaire', 'Africa/Libreville', 'texte'),
(NULL, 'carte.duree_validite_mois', '12', 'nombre'),
(NULL, 'carte.format', 'CR80', 'texte'),
(NULL, 'carte.resolution_dpi', '300', 'nombre'),
(NULL, 'securite.duree_session_minutes', '30', 'nombre'),
(NULL, 'securite.tentatives_max', '5', 'nombre'),
(NULL, 'securite.verrouillage_minutes', '15', 'nombre'),
(NULL, 'securite.mdp_longueur_min', '10', 'nombre'),
(NULL, 'photo.largeur', '600', 'nombre'),
(NULL, 'photo.hauteur', '800', 'nombre'),
(NULL, 'photo.qualite_jpeg', '85', 'nombre'),
(NULL, 'photo.poids_max_ko', '150', 'nombre'),
(NULL, 'sauvegarde.frequence', 'quotidienne', 'texte'),
(NULL, 'sauvegarde.retention_jours', '30', 'nombre');

SET FOREIGN_KEY_CHECKS = 1;
