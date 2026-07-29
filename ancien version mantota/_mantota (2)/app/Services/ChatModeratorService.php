<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\FlaggedMessageNotification;
use Illuminate\Support\Facades\Notification;

class ChatModeratorService
{
    /**
     * Analyse et modere un message.
     *
     * @return array{text: string, is_flagged: bool, original_message: string|null}
     */
    public function moderate(string $text, string $source = '', string $senderName = ''): array
    {
        $original  = $text;
        $isFlagged = false;

        // 1. Masquer les numeros de telephone (8-14 chiffres)
        $text = $this->maskPhoneNumbers($text);

        // 2. Masquer les emails
        $text = $this->maskEmails($text);

        // 3. Masquer les liens web et reseaux sociaux
        $text = $this->maskLinks($text);

        // 4. Detection de ruse (mots-cles evasifs)
        $ruseResult = $this->detectRuse($text);
        if ($ruseResult['flagged']) {
            $text      = $ruseResult['text'];
            $isFlagged = true;
        }

        // Si le texte a ete modifie (masquage simple) mais pas flag total
        $wasModified = ($text !== $original) && !$isFlagged;

        // Notifier les admins si le message est flague
        if ($isFlagged && $source !== '') {
            $admins = User::where('role', UserRole::ADMIN)->get();
            Notification::send($admins, new FlaggedMessageNotification($original, $source, $senderName));
        }

        return [
            'text'             => $text,
            'is_flagged'       => $isFlagged,
            'original_message' => ($isFlagged || $wasModified) ? $original : null,
        ];
    }

    /**
     * Masque les numeros de telephone.
     */
    private function maskPhoneNumbers(string $text): string
    {
        return (string) preg_replace(
            '/(?:\+\d{1,3}[\s\-]?)?(?:\(?\d{2,4}\)?[\s\-]?)?(?:\d[\s\-]?){6,12}\d/',
            '[NUMERO MASQUE]',
            $text
        );
    }

    /**
     * Masque les adresses email.
     */
    private function maskEmails(string $text): string
    {
        return (string) preg_replace(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            '[EMAIL MASQUE]',
            $text
        );
    }

    /**
     * Masque les liens web et reseaux sociaux.
     */
    private function maskLinks(string $text): string
    {
        // URLs classiques (http, https, www)
        $text = (string) preg_replace(
            '#(?:https?://|www\.)\S+#i',
            '[LIEN MASQUE]',
            $text
        );

        // Domaines reseaux sociaux sans http/www
        $text = (string) preg_replace(
            '#\b(?:facebook\.com|fb\.com|instagram\.com|ig\.com|tiktok\.com|twitter\.com|x\.com|snapchat\.com|telegram\.me|t\.me|wa\.me|linkedin\.com|youtube\.com|youtu\.be)\S*#i',
            '[LIEN MASQUE]',
            $text
        );

        return $text;
    }

    /**
     * Detection de ruse par mots-cles evasifs.
     * Si plus de 2 mots-cles detectes dans le message, flag total.
     *
     * @return array{flagged: bool, text: string}
     */
    private function detectRuse(string $text): array
    {
        $keywords = [
            'arobase', 'arobas', 'arrobase',
            'gmail', 'hotmail', 'yahoo', 'outlook',
            'whatsapp', 'whats app', 'watsap', 'whatapp',
            'wa\.me',
            'insta', 'instagram', 'ig',
            'tiktok', 'tik tok',
            'facebook', 'fb',
            'telegram', 'snap', 'snapchat',
            'z[eé]ro', 'un(?:e)?', 'deux', 'trois', 'quatre',
            'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
            'appelle[- ]?moi', 'contacte[- ]?moi', 'ecris[- ]?moi',
            'mon num[eé]ro', 'mon numero', 'mon contact',
            'hors plateforme', 'en priv[eé]',
        ];

        // Ajouter les mots interdits configurés par l'admin
        $bannedKeywords = mantota_setting('banned_keywords', []);
        if (is_string($bannedKeywords)) {
            $bannedKeywords = json_decode($bannedKeywords, true) ?: [];
        }

        // Les mots admin sont strictement interdits : 1 seul match = flag
        $customPatterns = [];
        foreach ($bannedKeywords as $word) {
            $word = trim($word);
            if ($word !== '') {
                $customPatterns[] = preg_quote($word, '/');
            }
        }

        if ($customPatterns !== []) {
            $customRegex = '/\b(?:' . implode('|', $customPatterns) . ')\b/iu';
            if (preg_match($customRegex, $text)) {
                return [
                    'flagged' => true,
                    'text'    => '[MESSAGE SUSPECT MASQUE POUR VERIFICATION]',
                ];
            }
        }

        // Mots de ruse intégrés : flag si > 2 détectés
        $pattern = '/\b(?:' . implode('|', $keywords) . ')\b/iu';

        if (preg_match_all($pattern, $text, $matches)) {
            $count = count($matches[0]);
            if ($count > 2) {
                return [
                    'flagged' => true,
                    'text'    => '[MESSAGE SUSPECT MASQUE POUR VERIFICATION]',
                ];
            }
        }

        return ['flagged' => false, 'text' => $text];
    }
}
