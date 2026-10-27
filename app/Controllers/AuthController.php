<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth.login', [], 'auth');
    }

    public function login(): void
    {
        $email = (string)$this->request->post('email', '');
        $password = (string)$this->request->post('password', '');

        $errors = $this->validate(['email' => $email, 'password' => $password], [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            $this->render('auth.login', ['errors' => $errors, 'old' => ['email' => $email]], 'auth');
            return;
        }

        $result = Auth::attempt($email, $password, $this->request);

        if (!$result['success']) {
            Session::setFlash('danger', $result['message']);
            $this->render('auth.login', ['old' => ['email' => $email]], 'auth');
            return;
        }

        if ($result['must_change_password']) {
            Session::setFlash('info', 'Par mesure de sécurité, vous devez changer votre mot de passe lors de votre première connexion.');
            $this->redirect('/change-password');
        }

        Session::setFlash('success', 'Bienvenue sur la plateforme !');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout($this->request);
        Session::setFlash('info', 'Vous avez été déconnecté avec succès.');
        $this->redirect('/login');
    }

    public function showChangePassword(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $this->render('auth.change_password', [], 'main');
    }

    public function updatePassword(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $currentPassword = (string)$this->request->post('current_password', '');
        $newPassword = (string)$this->request->post('new_password', '');
        $confirmPassword = (string)$this->request->post('confirm_password', '');

        $errors = [];
        if (strlen($newPassword) < 10) {
            $errors['new_password'][] = "Le mot de passe doit contenir au moins 10 caractères.";
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'][] = "Les mots de passe ne correspondent pas.";
        }

        $user = Auth::user();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT mot_de_passe FROM users WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($currentPassword, (string)$currentHash)) {
            $errors['current_password'][] = "Le mot de passe actuel est incorrect.";
        }

        if (!empty($errors)) {
            $this->render('auth.change_password', ['errors' => $errors], 'main');
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $up = $db->prepare("UPDATE users SET mot_de_passe = :pwd, doit_changer_mdp = 0 WHERE id = :id");
        $up->execute([':pwd' => $newHash, ':id' => $user['id']]);

        // Mise à jour de la session
        $u = Auth::user();
        $u['doit_changer_mdp'] = false;
        Session::set('_auth_user', $u);

        Logger::log((int)$user['id'], null, 'auth.changement_mdp', 'users', (int)$user['id'], null, null, $this->request);

        Session::setFlash('success', 'Votre mot de passe a été mis à jour avec succès.');
        $this->redirect('/dashboard');
    }

    public function showForgotPassword(): void
    {
        $this->render('auth.forgot_password', [], 'auth');
    }

    public function sendResetLink(): void
    {
        $email = (string)$this->request->post('email', '');

        // Même message que l'adresse existe ou pas pour éviter la fuite d'informations
        $successMsg = "Si cette adresse existe dans notre système, un lien de réinitialisation vous a été envoyé par e-mail.";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('danger', "Veuillez saisir une adresse e-mail valide.");
            $this->redirect('/forgot-password');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, email, nom, prenom FROM users WHERE email = :email AND deleted_at IS NULL AND statut = 'actif'");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $plainToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $plainToken);
            $expire = date('Y-m-d H:i:s', strtotime('+60 minutes'));

            $up = $db->prepare("UPDATE users SET jeton_reinit = :t, jeton_reinit_expire = :e WHERE id = :id");
            $up->execute([':t' => $hashedToken, ':e' => $expire, ':id' => $user['id']]);

            Logger::log((int)$user['id'], null, 'auth.demande_reinit', 'users', (int)$user['id'], null, null, $this->request);

            // Simulation locale : on stocke le lien dans un fichier log ou session de test
            $resetUrl = $this->config['app']['base_url'] . "/reset-password/" . $plainToken;
            @file_put_contents(
                dirname(__DIR__, 2) . '/storage/logs/mail_simulations.log',
                "[" . date('Y-m-d H:i:s') . "] Reset link for {$user['email']}: $resetUrl\n",
                FILE_APPEND
            );
        }

        Session::setFlash('info', $successMsg);
        $this->redirect('/login');
    }

    public function showResetPassword(array $params): void
    {
        $token = $params['token'] ?? '';
        $hashedToken = hash('sha256', $token);

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, email FROM users
            WHERE jeton_reinit = :token AND jeton_reinit_expire > NOW() AND deleted_at IS NULL
        ");
        $stmt->execute([':token' => $hashedToken]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::setFlash('danger', "Ce lien de réinitialisation est invalide ou a expiré.");
            $this->redirect('/login');
        }

        $this->render('auth.reset_password', ['token' => $token, 'email' => $user['email']], 'auth');
    }

    public function resetPassword(): void
    {
        $token = (string)$this->request->post('token', '');
        $password = (string)$this->request->post('password', '');
        $confirm = (string)$this->request->post('password_confirmation', '');

        $hashedToken = hash('sha256', $token);
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, email FROM users
            WHERE jeton_reinit = :token AND jeton_reinit_expire > NOW() AND deleted_at IS NULL
        ");
        $stmt->execute([':token' => $hashedToken]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::setFlash('danger', "Jeton invalide ou expiré.");
            $this->redirect('/login');
        }

        if (strlen($password) < 10) {
            Session::setFlash('danger', "Le mot de passe doit comporter au moins 10 caractères.");
            $this->redirect('/reset-password/' . $token);
        }

        if ($password !== $confirm) {
            Session::setFlash('danger', "Les mots de passe ne correspondent pas.");
            $this->redirect('/reset-password/' . $token);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $up = $db->prepare("
            UPDATE users
            SET mot_de_passe = :pwd,
                jeton_reinit = NULL,
                jeton_reinit_expire = NULL,
                doit_changer_mdp = 0,
                statut = 'actif',
                tentatives_echec = 0,
                verrouille_jusqua = NULL
            WHERE id = :id
        ");
        $up->execute([':pwd' => $hash, ':id' => $user['id']]);

        Logger::log((int)$user['id'], null, 'auth.reinit_succes', 'users', (int)$user['id'], null, null, $this->request);

        Session::setFlash('success', "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.");
        $this->redirect('/login');
    }
}
