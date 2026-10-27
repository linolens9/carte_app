<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Parametre;
use Throwable;

class NotificationService
{
    /**
     * Envoie ou prépare l'envoi des identifiants d'un utilisateur sur les différents canaux.
     *
     * @param array $user Données utilisateur (nom, prenom, email, telephone, role_libelle...)
     * @param string $plainPassword Mot de passe temporaire en clair
     * @param array $channels Canaux demandés : ['email', 'whatsapp', 'sms']
     * @param array $options Options supplémentaires (ex: etablissement_nom, etc.)
     * @return array Résultats détaillés par canal et liens préparés
     */
    public static function sendUserCredentials(
        array $user,
        string $plainPassword,
        array $channels = ['email', 'whatsapp', 'sms'],
        array $options = []
    ): array {
        $appName = (string)self::getParamOrConfig('app.nom', config('app.name', 'Carte App'));
        $baseUrl = (string)config('app.base_url', 'http://localhost/carte_app');
        $loginUrl = rtrim($baseUrl, '/') . '/login';

        $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
        $email = trim((string)($user['email'] ?? ''));
        $rawPhone = trim((string)($user['telephone'] ?? ''));
        $normalizedPhone = self::normalizePhoneNumber($rawPhone);
        $roleLibelle = $user['role_libelle'] ?? $options['role_libelle'] ?? 'Utilisateur';
        $etabNom = $options['etablissement_nom'] ?? null;

        // Préparation des messages formatés pour chaque canal
        $whatsappMessage = self::buildWhatsAppMessage($appName, $fullName, $email, $plainPassword, $roleLibelle, $loginUrl, $etabNom);
        $smsMessage = self::buildSmsMessage($appName, $user['prenom'] ?? $fullName, $email, $plainPassword, $loginUrl);
        $fullSummaryText = self::buildPlainTextMessage($appName, $fullName, $email, $plainPassword, $roleLibelle, $loginUrl, $etabNom);

        // Liens d'expédition instantanée (Click-to-chat et SMS URI)
        $whatsappLink = !empty($normalizedPhone)
            ? 'https://wa.me/' . $normalizedPhone . '?text=' . rawurlencode($whatsappMessage)
            : '';

        $smsLink = !empty($rawPhone)
            ? 'sms:' . rawurlencode($rawPhone) . '?body=' . rawurlencode($smsMessage)
            : '';

        $results = [
            'email' => [
                'attempted' => in_array('email', $channels, true),
                'sent' => false,
                'status' => 'skipped',
                'message' => 'Non demandé',
            ],
            'whatsapp' => [
                'attempted' => in_array('whatsapp', $channels, true),
                'sent' => false,
                'status' => 'skipped',
                'link' => $whatsappLink,
                'phone' => $normalizedPhone,
                'message' => 'Non demandé',
            ],
            'sms' => [
                'attempted' => in_array('sms', $channels, true),
                'sent' => false,
                'status' => 'skipped',
                'link' => $smsLink,
                'phone' => $rawPhone,
                'message' => 'Non demandé',
            ],
            'recipient' => [
                'name' => $fullName,
                'email' => $email,
                'phone' => $rawPhone,
                'normalized_phone' => $normalizedPhone,
                'role' => $roleLibelle,
            ],
            'credentials' => [
                'email' => $email,
                'password' => $plainPassword,
                'login_url' => $loginUrl,
            ],
            'whatsapp_link' => $whatsappLink,
            'sms_link' => $smsLink,
            'whatsapp_message' => $whatsappMessage,
            'sms_message' => $smsMessage,
            'summary_text' => $fullSummaryText,
        ];

        // 1. Canal Email
        if (in_array('email', $channels, true) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailResult = self::sendEmail(
                $email,
                "Vos identifiants d'accès - {$appName}",
                self::buildHtmlEmail($appName, $fullName, $email, $plainPassword, $roleLibelle, $loginUrl, $etabNom),
                $fullSummaryText
            );
            $results['email'] = array_merge($results['email'], $emailResult);
        } elseif (in_array('email', $channels, true)) {
            $results['email']['status'] = 'failed';
            $results['email']['message'] = 'Adresse email invalide ou manquante';
        }

        // 2. Canal WhatsApp
        if (in_array('whatsapp', $channels, true)) {
            if (!empty($normalizedPhone)) {
                $waResult = self::sendWhatsAppApi($normalizedPhone, $whatsappMessage);
                $results['whatsapp']['attempted'] = true;
                $results['whatsapp']['sent'] = $waResult['sent'];
                $results['whatsapp']['status'] = $waResult['status'];
                $results['whatsapp']['message'] = $waResult['message'];
            } else {
                $results['whatsapp']['status'] = 'warning';
                $results['whatsapp']['message'] = 'Numéro de téléphone manquant pour WhatsApp';
            }
        }

        // 3. Canal SMS (Messagerie classique)
        if (in_array('sms', $channels, true)) {
            if (!empty($rawPhone)) {
                $smsResult = self::sendSmsApi($rawPhone, $smsMessage);
                $results['sms']['attempted'] = true;
                $results['sms']['sent'] = $smsResult['sent'];
                $results['sms']['status'] = $smsResult['status'];
                $results['sms']['message'] = $smsResult['message'];
            } else {
                $results['sms']['status'] = 'warning';
                $results['sms']['message'] = 'Numéro de téléphone manquant pour le SMS';
            }
        }

        // Journalisation
        self::logNotification('user.credentials', $user['id'] ?? null, $results);

        return $results;
    }

    /**
     * Récupère un paramètre depuis la base de données (table parametres) ou repli sur config()
     */
    private static function getParamOrConfig(string $key, mixed $default = null): mixed
    {
        try {
            $p = new Parametre();
            $val = $p->get($key, null);
            if ($val !== null && $val !== '') {
                return $val;
            }
        } catch (\Throwable) {
        }
        return config($key, $default);
    }

    /**
     * Envoi d'un Email par SMTP natif ou mail() avec repli propre
     */
    public static function sendEmail(string $to, string $subject, string $htmlBody, string $textBody): array
    {
        $driver = (string)self::getParamOrConfig('mail.driver', config('mail.driver', 'smtp'));
        $host = (string)self::getParamOrConfig('mail.host', config('mail.host', '127.0.0.1'));
        $port = (int)self::getParamOrConfig('mail.port', config('mail.port', 1025));
        $username = (string)self::getParamOrConfig('mail.username', config('mail.username', ''));
        $password = (string)self::getParamOrConfig('mail.password', config('mail.password', ''));
        $encryption = self::getParamOrConfig('mail.encryption', config('mail.encryption'));
        $fromAddress = (string)self::getParamOrConfig('mail.from_address', config('mail.from_address', 'noreply@carteapp.ga'));
        $fromName = (string)self::getParamOrConfig('mail.from_name', config('mail.from_name', config('app.name', 'Carte App')));

        // Si le driver est 'log' ou 'simulation'
        if ($driver === 'log' || $driver === 'simulation') {
            self::logMailSimulation($to, $subject, $textBody);
            return [
                'sent' => true,
                'status' => 'simulated',
                'message' => 'Simulé (enregistré dans storage/logs/mail_simulations.log)',
            ];
        }

        // Tentative d'envoi SMTP direct via socket
        if ($driver === 'smtp') {
            try {
                $sent = self::sendSmtpSocket($host, $port, $username, $password, $encryption, $fromAddress, $fromName, $to, $subject, $htmlBody, $textBody);
                if ($sent) {
                    return [
                        'sent' => true,
                        'status' => 'sent',
                        'message' => "Envoyé avec succès par SMTP à {$to}",
                    ];
                }
            } catch (Throwable $e) {
                error_log("SMTP Error: " . $e->getMessage());
                // Repli sur simulation / journal
                self::logMailSimulation($to, $subject, $textBody, "SMTP Failed: " . $e->getMessage());
                return [
                    'sent' => false,
                    'status' => 'simulated',
                    'message' => "Serveur SMTP non joignable ({$e->getMessage()}). Email journalisé en local.",
                ];
            }
        }

        // Fallback fonction PHP mail()
        if (function_exists('mail')) {
            $boundary = '=_' . md5((string)microtime(true));
            $headers = [
                'From' => sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromAddress),
                'Reply-To' => $fromAddress,
                'MIME-Version' => '1.0',
                'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
                'X-Mailer' => 'CarteApp/1.0',
            ];

            $headerString = '';
            foreach ($headers as $k => $v) {
                $headerString .= "{$k}: {$v}\r\n";
            }

            $body = "--{$boundary}\r\n"
                  . "Content-Type: text/plain; charset=UTF-8\r\n"
                  . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                  . $textBody . "\r\n\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: text/html; charset=UTF-8\r\n"
                  . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                  . $htmlBody . "\r\n\r\n"
                  . "--{$boundary}--";

            $encodedSubject = sprintf('=?UTF-8?B?%s?=', base64_encode($subject));

            $mailSent = @mail($to, $encodedSubject, $body, $headerString);
            if ($mailSent) {
                return [
                    'sent' => true,
                    'status' => 'sent',
                    'message' => "Envoyé avec succès via mail() PHP à {$to}",
                ];
            }
        }

        // Si tout a échoué, simulation propre
        self::logMailSimulation($to, $subject, $textBody, "PHP mail() unavailable");
        return [
            'sent' => true,
            'status' => 'simulated',
            'message' => "Email journalisé en mode simulation locale pour {$to}",
        ];
    }

    /**
     * Envoi SMTP via socket PHP natif avec support STARTTLS et AUTH LOGIN
     */
    private static function sendSmtpSocket(
        string $host,
        int $port,
        string $username,
        string $password,
        ?string $encryption,
        string $fromAddress,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        $scheme = '';
        if ($encryption === 'ssl') {
            $scheme = 'ssl://';
        }

        $timeout = 4; // 4 secondes max pour ne pas bloquer l'UI
        $socket = @stream_socket_client(
            $scheme . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new \RuntimeException("Connexion au serveur SMTP {$host}:{$port} impossible : {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function ($sock, $expectedCode) {
            $res = '';
            while ($line = fgets($sock, 515)) {
                $res .= $line;
                if (substr($line, 3, 1) === ' ') {
                    break;
                }
            }
            $code = (int)substr($res, 0, 3);
            if ($code !== $expectedCode) {
                throw new \RuntimeException("Réponse SMTP inattendue : {$res} (attendu: {$expectedCode})");
            }
            return $res;
        };

        $sendCommand = function ($sock, $cmd, $expectedCode) use ($readResponse) {
            fwrite($sock, $cmd . "\r\n");
            return $readResponse($sock, $expectedCode);
        };

        $readResponse($socket, 220);
        $sendCommand($socket, 'EHLO ' . gethostname(), 250);

        if ($encryption === 'tls') {
            $sendCommand($socket, 'STARTTLS', 220);
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                throw new \RuntimeException("Échec de la négociation TLS");
            }
            $sendCommand($socket, 'EHLO ' . gethostname(), 250);
        }

        if ($username !== '' && $password !== '') {
            $sendCommand($socket, 'AUTH LOGIN', 334);
            $sendCommand($socket, base64_encode($username), 334);
            $sendCommand($socket, base64_encode($password), 235);
        }

        $sendCommand($socket, "MAIL FROM:<{$fromAddress}>", 250);
        $sendCommand($socket, "RCPT TO:<{$to}>", 250);
        $sendCommand($socket, 'DATA', 354);

        $boundary = '=_' . md5((string)microtime(true));
        $encodedSubject = sprintf('=?UTF-8?B?%s?=', base64_encode($subject));
        $encodedFromName = sprintf('=?UTF-8?B?%s?=', base64_encode($fromName));

        $data = "From: {$encodedFromName} <{$fromAddress}>\r\n"
              . "To: <{$to}>\r\n"
              . "Subject: {$encodedSubject}\r\n"
              . "MIME-Version: 1.0\r\n"
              . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n"
              . "Date: " . date('r') . "\r\n"
              . "X-Mailer: CarteApp Notification Service\r\n\r\n"
              . "--{$boundary}\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: 8bit\r\n\r\n"
              . $textBody . "\r\n\r\n"
              . "--{$boundary}\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: 8bit\r\n\r\n"
              . $htmlBody . "\r\n\r\n"
              . "--{$boundary}--\r\n.";

        $sendCommand($socket, $data, 250);
        $sendCommand($socket, 'QUIT', 221);
        fclose($socket);

        return true;
    }

    /**
     * Envoi WhatsApp via API Cloud ou webhook (si configuré)
     */
    public static function sendWhatsAppApi(string $phone, string $message): array
    {
        $apiUrl = (string)self::getParamOrConfig('whatsapp.api_url', config('whatsapp.api_url', ''));
        $token = (string)self::getParamOrConfig('whatsapp.token', config('whatsapp.token', ''));
        $phoneId = (string)self::getParamOrConfig('whatsapp.phone_number_id', config('whatsapp.phone_number_id', ''));

        // Si aucun token Cloud API n'est configuré, le mode Click-to-Chat direct est activé
        if ($apiUrl === '' && $token === '') {
            return [
                'sent' => false,
                'status' => 'ready',
                'message' => "Lien WhatsApp direct prêt pour {$phone} (Click-to-Chat)",
            ];
        }

        // WhatsApp Cloud API officiel Meta
        $endpoint = $apiUrl !== '' ? $apiUrl : "https://graph.facebook.com/v19.0/{$phoneId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        try {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 5,
            ]);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'sent' => true,
                    'status' => 'sent',
                    'message' => "Message WhatsApp transmis avec succès via Cloud API à {$phone}",
                ];
            } else {
                return [
                    'sent' => false,
                    'status' => 'ready',
                    'message' => "API WhatsApp HTTP {$httpCode} ({$err}). Lien direct Click-to-Chat disponible.",
                ];
            }
        } catch (Throwable $e) {
            return [
                'sent' => false,
                'status' => 'ready',
                'message' => "Erreur WhatsApp API ({$e->getMessage()}). Lien direct disponible.",
            ];
        }
    }

    /**
     * Envoi SMS via passerelle HTTP (si configurée)
     */
    public static function sendSmsApi(string $phone, string $message): array
    {
        $gatewayUrl = (string)self::getParamOrConfig('sms.gateway_url', config('sms.gateway_url', ''));
        $apiKey = (string)self::getParamOrConfig('sms.api_key', config('sms.api_key', ''));
        $senderId = (string)self::getParamOrConfig('sms.sender_id', config('sms.sender_id', 'CarteApp'));

        // Si aucune passerelle SMS n'est configurée, le mode lien SMS direct est activé
        if ($gatewayUrl === '') {
            return [
                'sent' => false,
                'status' => 'ready',
                'message' => "Lien SMS natif prêt pour {$phone}",
            ];
        }

        try {
            $postData = [
                'to' => $phone,
                'message' => $message,
                'sender' => $senderId,
                'api_key' => $apiKey,
            ];

            $ch = curl_init($gatewayUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_TIMEOUT => 5,
            ]);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'sent' => true,
                    'status' => 'sent',
                    'message' => "SMS transmis via passerelle à {$phone}",
                ];
            } else {
                return [
                    'sent' => false,
                    'status' => 'ready',
                    'message' => "Passerelle SMS code {$httpCode}. Lien SMS disponible.",
                ];
            }
        } catch (Throwable $e) {
            return [
                'sent' => false,
                'status' => 'ready',
                'message' => "Erreur SMS API : {$e->getMessage()}. Lien SMS disponible.",
            ];
        }
    }

    /**
     * Normalise un numéro de téléphone pour WhatsApp (indicatif pays sans '+', sans espaces ni tirets).
     * Format Gabon par défaut (+241).
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if ($clean === null || $clean === '') {
            return '';
        }

        // Si commence par '+'
        if (str_starts_with($clean, '+')) {
            return ltrim($clean, '+');
        }

        // Si commence par '00'
        if (str_starts_with($clean, '00')) {
            return substr($clean, 2);
        }

        // Format Gabonais local (ex: 077..., 066..., 074..., 062..., 065...)
        // En enlevant le 0 initial ou en préfixant 241
        if (str_starts_with($clean, '0') && (strlen($clean) === 8 || strlen($clean) === 9)) {
            return '241' . substr($clean, 1);
        }

        // Si 7 ou 8 chiffres sans le zéro initial pour le Gabon (ex: 77123456 ou 66123456)
        if (strlen($clean) === 8 && in_array(substr($clean, 0, 2), ['77', '74', '66', '62', '65', '76', '07', '06'], true)) {
            return '241' . $clean;
        }

        if (strlen($clean) === 7 && in_array(substr($clean, 0, 1), ['7', '6', '1', '2', '4', '5'], true)) {
            return '241' . $clean;
        }

        return $clean;
    }

    /**
     * Message WhatsApp avec formatage Markdown WhatsApp (*gras*, _italique_)
     */
    public static function buildWhatsAppMessage(
        string $appName,
        string $fullName,
        string $email,
        string $password,
        string $role,
        string $loginUrl,
        ?string $etablissement = null
    ): string {
        $msg = "✨ *Bienvenue sur {$appName} !* 👋\n\n";
        $msg .= "Bonjour *{$fullName}*,\n";
        $msg .= "Votre compte utilisateur a été créé avec succès" . ($etablissement ? " pour l'établissement *{$etablissement}*" : "") . ".\n\n";
        $msg .= "🔐 *Vos identifiants de connexion :*\n";
        $msg .= "• *Identifiant (Email) :* {$email}\n";
        $msg .= "• *Mot de passe temporaire :* `{$password}`\n";
        $msg .= "• *Rôle assigné :* {$role}\n";
        $msg .= "• *Lien de connexion :* {$loginUrl}\n\n";
        $msg .= "⚠️ *Consigne de sécurité importante :*\n";
        $msg .= "Pour des raisons de sécurité, vous serez invité(e) à changer ce mot de passe temporaire dès votre première connexion.\n\n";
        $msg .= "— L'équipe d'administration {$appName}";

        return $msg;
    }

    /**
     * Message SMS court et percutant
     */
    public static function buildSmsMessage(
        string $appName,
        string $prenom,
        string $email,
        string $password,
        string $loginUrl
    ): string {
        return "{$appName}: Bonjour {$prenom}, votre compte a ete cree. Identifiant: {$email} | MDP temporaire: {$password} | Connexion: {$loginUrl}";
    }

    /**
     * Message texte complet pour copie dans le presse-papier
     */
    public static function buildPlainTextMessage(
        string $appName,
        string $fullName,
        string $email,
        string $password,
        string $role,
        string $loginUrl,
        ?string $etablissement = null
    ): string {
        $text = "============================================================\n";
        $text .= "BIENVENUE SUR " . strtoupper($appName) . "\n";
        $text .= "============================================================\n\n";
        $text .= "Bonjour {$fullName},\n\n";
        $text .= "Votre compte d'accès à la plateforme {$appName} a été créé avec succès" . ($etablissement ? " ({$etablissement})" : "") . ".\n\n";
        $text .= "VOS IDENTIFIANTS DE CONNEXION :\n";
        $text .= "------------------------------------------------------------\n";
        $text .= "• Identifiant (Email) : {$email}\n";
        $text .= "• Mot de passe temporaire : {$password}\n";
        $text .= "• Rôle assigné : {$role}\n";
        $text .= "• Lien de connexion : {$loginUrl}\n";
        $text .= "------------------------------------------------------------\n\n";
        $text .= "IMPORTANT : Pour votre sécurité, vous devrez modifier ce mot de passe dès votre première connexion.\n\n";
        $text .= "Cordialement,\n";
        $text .= "L'équipe d'administration {$appName}\n";

        return $text;
    }

    /**
     * Template d'email HTML moderne, responsive et sécurisé
     */
    public static function buildHtmlEmail(
        string $appName,
        string $fullName,
        string $email,
        string $password,
        string $role,
        string $loginUrl,
        ?string $etablissement = null
    ): string {
        $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeFullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $safeEtab = $etablissement ? htmlspecialchars($etablissement, ENT_QUOTES, 'UTF-8') : null;
        $etabPhrase = $safeEtab ? " pour l'établissement <strong>{$safeEtab}</strong>." : ".";
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos identifiants d'accès {$safeAppName}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 32px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 36px 32px;
        }
        .badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .credentials-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .credential-item {
            margin-bottom: 14px;
        }
        .credential-item:last-child {
            margin-bottom: 0;
        }
        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 4px;
        }
        .value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .password-value {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 18px;
            color: #2563eb;
            display: inline-block;
            letter-spacing: 1px;
        }
        .btn-connect {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            margin: 10px 0;
        }
        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 14px 18px;
            border-radius: 8px;
            margin-top: 24px;
            font-size: 13px;
            color: #92400e;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 30px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$safeAppName}</h1>
            <p>Gestion & Édition Sécurisée des Cartes d'Identité</p>
        </div>
        <div class="content">
            <span class="badge">Nouveau Compte</span>
            <h2 style="margin-top: 0; color: #0f172a; font-size: 20px;">Bonjour {$safeFullName},</h2>
            <p style="font-size: 15px; color: #334155;">
                Votre compte d'accès à la plateforme <strong>{$safeAppName}</strong> a été créé avec succès{$etabPhrase}
            </p>

            <div class="credentials-box">
                <div class="credential-item">
                    <div class="label">Identifiant de connexion (Email)</div>
                    <div class="value">{$safeEmail}</div>
                </div>
                <div class="credential-item">
                    <div class="label">Mot de passe temporaire</div>
                    <div class="password-value">{$safePassword}</div>
                </div>
                <div class="credential-item">
                    <div class="label">Rôle attribué</div>
                    <div class="value" style="color: #475569;">{$safeRole}</div>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{$safeLoginUrl}" class="btn-connect">
                    Se connecter à la plateforme &rarr;
                </a>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;">
                    Lien direct : <a href="{$safeLoginUrl}" style="color: #2563eb;">{$safeLoginUrl}</a>
                </div>
            </div>

            <div class="alert-box">
                <strong>⚠️ Obligation de sécurité :</strong> Pour garantir la confidentialité de vos données, vous serez invité(e) à changer ce mot de passe temporaire dès votre toute première connexion.
            </div>
        </div>
        <div class="footer">
            Cet email contient des identifiants confidentiels. Ne le transférez pas.<br>
            &copy; {$currentYear} {$safeAppName}. Tous droits réservés.
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Enregistre une simulation d'email dans storage/logs/mail_simulations.log
     */
    private static function logMailSimulation(string $to, string $subject, string $body, ?string $note = null): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mail_simulations.log';
        $entry = sprintf(
            "============================================================\n" .
            "[%s] SIMULATION EMAIL%s\n" .
            "Destinataire : %s\n" .
            "Objet        : %s\n" .
            "Contenu      :\n%s\n" .
            "============================================================\n\n",
            date('Y-m-d H:i:s'),
            $note ? " ({$note})" : "",
            $to,
            $subject,
            $body
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    /**
     * Journalise les notifications émises
     */
    private static function logNotification(string $type, ?int $userId, array $details): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/notifications.log';
        $entry = sprintf(
            "[%s] Type: %s | User: %s | Email: %s | WhatsApp: %s | SMS: %s\n",
            date('Y-m-d H:i:s'),
            $type,
            $userId ?? 'N/A',
            $details['email']['status'] ?? 'none',
            $details['whatsapp']['status'] ?? 'none',
            $details['sms']['status'] ?? 'none'
        );
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
