<?php
/**
 * Advanced Usage Example for PHP-Gettext Library
 * 
 * This example demonstrates more advanced usage including:
 * - Language auto-detection
 * - Plural forms
 * - Context-based translations
 * - Web UI integration
 * 
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

// Include the required library files
require_once __DIR__ . '/../lib/gettext.php';
require_once __DIR__ . '/../lib/streams.php';

// Language detection with fallback
function detect_preferred_language(): string {
    $available_languages = ['en_US', 'fr_FR']; // Add your supported languages here
    $default_language = 'en_US';
    
    // Simple browser language detection
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browser_langs as $lang) {
            $lang = substr($lang, 0, 5); // Format like fr_FR or en_US
            if (in_array($lang, $available_languages)) {
                return $lang;
            }
            
            // Check just the primary language (e.g., 'en' from 'en_US')
            $primary_lang = substr($lang, 0, 2);
            foreach ($available_languages as $avail_lang) {
                if (strpos($avail_lang, $primary_lang) === 0) {
                    return $avail_lang;
                }
            }
        }
    }
    
    return $default_language;
}

/**
 * Initialize the gettext translation system
 * 
 * @param string|null $language The language code to use, or null to auto-detect
 * @return gettext_reader The initialized translation object
 */
function init_gettext(?string $language = null): gettext_reader {
    $language = $language ?? detect_preferred_language();
    
    $locale_lang = $language;
    $locale_file = new FileReader(__DIR__ . "/../languages/$locale_lang/LC_MESSAGES/messages.mo");
    
    if (isset($locale_file->error) && $locale_file->error) {
        // Fallback to default language if specified language file has an error
        $locale_lang = 'en_US';
        $locale_file = new FileReader(__DIR__ . "/../languages/$locale_lang/LC_MESSAGES/messages.mo");
    }
    
    return new gettext_reader($locale_file);
}

// Initialize with auto-detection
$translator = init_gettext();

/**
 * Translate a string
 * 
 * @param string $text Text to translate
 * @return string Translated text
 */
function __($text): string {
    global $translator;
    return $translator->translate($text);
}

/**
 * Translate and print a string
 * 
 * @param string $text Text to translate
 */
function _e($text): void {
    echo __($text);
}

/**
 * Translate plural forms
 * 
 * @param string $single Singular form
 * @param string $plural Plural form
 * @param int $count Number of items
 * @return string Translated text in correct plural form
 */
function _n($single, $plural, $count): string {
    global $translator;
    return $translator->ngettext($single, $plural, $count);
}

/**
 * Translate string with context
 * 
 * @param string $context Context for disambiguation
 * @param string $text Text to translate
 * @return string Translated text
 */
function _x($context, $text): string {
    global $translator;
    return $translator->pgettext($context, $text);
}

/**
 * Translate plural forms with context
 * 
 * @param string $context Context for disambiguation
 * @param string $single Singular form
 * @param string $plural Plural form
 * @param int $count Number of items
 * @return string Translated text in correct plural form
 */
function _nx($context, $single, $plural, $count): string {
    global $translator;
    return $translator->npgettext($context, $single, $plural, $count);
}

// Demo
$items_count = isset($_GET['count']) ? (int)$_GET['count'] : 1;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= __('PHP-Gettext Library Demo') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #3498db;
        }
        .example {
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= __('PHP-Gettext Library Demo') ?></h1>
        
        <div class="example">
            <h2><?= __('Basic Translation') ?></h2>
            <p><?= __('Hello World') ?></p>
            <pre>__('Hello World')</pre>
        </div>
        
        <div class="example">
            <h2><?= __('Plural Forms') ?></h2>
            <form method="get">
                <select name="count" onchange="this.form.submit()">
                    <?php for ($i = 0; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= ($items_count == $i) ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
            
            <p><?= _n('One item', 'Many items', $items_count) ?></p>
            <pre>_n('One item', 'Many items', <?= $items_count ?>)</pre>
        </div>
        
        <div class="example">
            <h2><?= __('Context-based Translation') ?></h2>
            <p><?= _x('menu', 'View') ?></p>
            <pre>_x('menu', 'View')</pre>
        </div>
        
        <div class="example">
            <h2><?= __('Context-based Plural Forms') ?></h2>
            <p><?= _nx('items', 'One item', 'Many items', $items_count) ?></p>
            <pre>_nx('items', 'One item', 'Many items', <?= $items_count ?>)</pre>
        </div>
    </div>
</body>
</html> 