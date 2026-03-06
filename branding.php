<?php
/**
 * DS-Tracks - Branding & Customization Configuration
 * Version 2.0
 *
 * This file allows radio stations to customize the appearance
 * without modifying core code.
 */

if (!defined('DS_TRACKS')) {
    define('DS_TRACKS', true);
}

class Branding {

    // Station Information
    public static $stationName = "My Station";
    public static $stationShortName = "DS";
    public static $stationWebsite = "";

    // Logo Configuration
    public static $logoPath = "images/station-logo.png";
    public static $tracksLogoPath = "images/tracks-logo.png";
    public static $faviconPath = "images/favicon.ico";

    // Color Scheme (CSS Custom Properties)
    public static $colors = [
        // Primary brand colors
        'primary-color' => '#1a7a7a',           // Teal/turquoise (main brand)
        'primary-dark' => '#145a5a',            // Darker teal
        'primary-light' => '#4da6a6',           // Lighter teal

        // Accent colors
        'accent-color' => '#d32f2f',            // Red accent
        'accent-light' => '#ff6659',            // Light red

        // Background colors
        'background-main' => '#1a7a7a',         // Main background
        'background-secondary' => '#f5f5f5',    // Secondary background
        'background-card' => '#ffffff',         // Card/panel background

        // Text colors
        'text-primary' => '#ffffff',            // Primary text (on dark bg)
        'text-secondary' => '#333333',          // Secondary text (on light bg)
        'text-muted' => '#666666',              // Muted text

        // Button colors
        'button-primary' => '#1a7a7a',
        'button-primary-hover' => '#145a5a',
        'button-secondary' => '#4da6a6',
        'button-danger' => '#d32f2f',

        // UI Element colors
        'border-color' => '#ddd',
        'shadow-color' => 'rgba(0, 0, 0, 0.1)',
        'active-track' => '#ff6659',
    ];

    // Typography
    public static $fonts = [
        'primary-font' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
        'heading-font' => "'Arial', sans-serif",
    ];

    // Application Settings
    public static $settings = [
        'show-station-website-link' => true,
        'enable-reports' => true,
        'show-powered-by' => true,
        'custom-footer-text' => '',
    ];

    /**
     * Get CSS custom properties as a string
     */
    public static function getCSSVariables() {
        $css = ":root {\n";

        // Add color variables
        foreach (self::$colors as $name => $value) {
            $css .= "  --{$name}: {$value};\n";
        }

        // Add font variables
        foreach (self::$fonts as $name => $value) {
            $css .= "  --{$name}: {$value};\n";
        }

        $css .= "}\n";
        return $css;
    }

    /**
     * Get station logo HTML
     */
    public static function getLogoHTML($class = '') {
        $alt = htmlspecialchars(self::$stationName, ENT_QUOTES, 'UTF-8');
        $src = htmlspecialchars(self::$logoPath, ENT_QUOTES, 'UTF-8');
        $classAttr = $class ? " class=\"{$class}\"" : '';

        return "<img src=\"{$src}\" alt=\"{$alt}\"{$classAttr}>";
    }

    /**
     * Get tracks logo HTML
     */
    public static function getTracksLogoHTML($class = '') {
        $src = htmlspecialchars(self::$tracksLogoPath, ENT_QUOTES, 'UTF-8');
        $classAttr = $class ? " class=\"{$class}\"" : '';

        return "<img src=\"{$src}\" alt=\"Tracks Logo\"{$classAttr}>";
    }

    /**
     * Get page title
     */
    public static function getPageTitle($pageTitle = '') {
        if ($pageTitle) {
            return htmlspecialchars($pageTitle . ' - ' . self::$stationShortName . ' Tracks', ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars(self::$stationShortName . ' Tracks', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get footer HTML
     */
    public static function getFooterHTML() {
        $html = '<div class="branding-footer">';

        if (self::$settings['custom-footer-text']) {
            $html .= '<p>' . htmlspecialchars(self::$settings['custom-footer-text'], ENT_QUOTES, 'UTF-8') . '</p>';
        }

        if (self::$settings['show-powered-by']) {
            $html .= '<p class="powered-by">Powered by DS-Tracks v2.0</p>';
        }

        if (self::$settings['show-station-website-link'] && self::$stationWebsite) {
            $website = htmlspecialchars(self::$stationWebsite, ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars(self::$stationName, ENT_QUOTES, 'UTF-8');
            $html .= "<p><a href=\"{$website}\" target=\"_blank\">{$name}</a></p>";
        }

        $html .= '</div>';
        return $html;
    }
}

?>
