<?php
/**
 * Basic Usage Example for PHP-Gettext Library
 * 
 * This example demonstrates how to set up and use the PHP-Gettext library
 * for basic string translation.
 * 
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

// Include the required library files
require_once __DIR__ . '/../lib/gettext.php';
require_once __DIR__ . '/../lib/streams.php';

// Define which language to use
$language = 'en_US';

// Set up the gettext reader
$locale_file = new FileReader(__DIR__ . "/../languages/$language/LC_MESSAGES/messages.mo");
$locale_fetch = new gettext_reader($locale_file);

/**
 * Helper function for translation
 * 
 * @param string $text Text to translate
 * @return string Translated text
 */
function _e(string $text): string {
    global $locale_fetch;
    return $locale_fetch->translate($text);
}

// Translation examples
echo "Basic translation example:\n";
echo "Original: 'Hello World'\n";
echo "Translated: '" . _e('Hello World') . "'\n\n";

// Exit with success
exit(0); 