<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/lib/Application.php';
require_once __DIR__ . '/lib/ApplicationUI.php';

function h($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

function header_html(string $title): void {
    ApplicationUI::headerHtml($title);
}

function footer_html(): void {
    ApplicationUI::footerHtml();
}

function format_phone(?string $phone): ?string {
    if (!$phone) return null;
    
    $phone = trim($phone);
    if ($phone === '') return null;
    
    // Remove all non-digit characters except +
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    
    // US number with country code: +1XXXXXXXXXX (11 digits starting with +1)
    if (preg_match('/^\+1(\d{10})$/', $cleaned, $matches)) {
        return '+1 ' . substr($matches[1], 0, 3) . '-' . 
               substr($matches[1], 3, 3) . '-' . substr($matches[1], 6);
    }
    
    // US number without country code: XXXXXXXXXX (10 digits)
    if (preg_match('/^(\d{10})$/', $cleaned, $matches)) {
        return substr($matches[1], 0, 3) . '-' . 
               substr($matches[1], 3, 3) . '-' . substr($matches[1], 6);
    }
    
    // International number (starts with +, not +1)
    if (preg_match('/^\+(\d+)$/', $cleaned, $matches)) {
        $digits = $matches[1];
        // Add spaces for readability: country code + groups of 3-4 digits
        // This is a simple approach that works reasonably well for most formats
        if (strlen($digits) <= 4) {
            return '+' . $digits;
        } elseif (strlen($digits) <= 7) {
            return '+' . substr($digits, 0, strlen($digits) - 4) . ' ' . substr($digits, -4);
        } else {
            // Format as: +CC XXXX XXXX... (country code, then groups of 4)
            $countryCode = substr($digits, 0, min(3, strlen($digits) - 8));
            $remaining = substr($digits, strlen($countryCode));
            $formatted = '+' . $countryCode;
            while (strlen($remaining) > 0) {
                $formatted .= ' ' . substr($remaining, 0, 4);
                $remaining = substr($remaining, 4);
            }
            return trim($formatted);
        }
    }
    
    // Return original if no pattern matches
    return $phone;
}
