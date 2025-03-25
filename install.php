<?php
/**
 * PHP-Gettext Library Installation Script
 * 
 * This script helps set up the PHP-Gettext library by creating the necessary
 * directory structure and sample language files.
 * 
 * Usage:
 * php install.php
 * 
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

// Define color codes for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RED', "\033[31m");
define('COLOR_RESET', "\033[0m");

/**
 * Show a colored message in the terminal
 * 
 * @param string $message The message to display
 * @param string $color The color code to use
 * @return void
 */
function colorize(string $message, string $color): string {
    // Only use colors if not on Windows or if using a modern terminal
    if (DIRECTORY_SEPARATOR === '\\' && !getenv('WT_SESSION') && !getenv('TERM')) {
        return $message;
    }
    return $color . $message . COLOR_RESET;
}

/**
 * Print a status message
 * 
 * @param string $message The message text
 * @param string $status The status (success, warning, error)
 * @return void
 */
function status(string $message, string $status = 'info'): void {
    $statusColor = match($status) {
        'success' => COLOR_GREEN,
        'warning' => COLOR_YELLOW,
        'error' => COLOR_RED,
        default => COLOR_RESET
    };
    
    echo $message . ' ' . colorize($status, $statusColor) . PHP_EOL;
}

/**
 * Create a directory if it doesn't exist
 * 
 * @param string $dir The directory path to create
 * @return bool True if the directory exists or was created, false otherwise
 */
function create_directory(string $dir): bool {
    if (is_dir($dir)) {
        status("Directory already exists: $dir", 'success');
        return true;
    }
    
    if (mkdir($dir, 0755, true)) {
        status("Created directory: $dir", 'success');
        return true;
    } else {
        status("Failed to create directory: $dir", 'error');
        return false;
    }
}

/**
 * Create a sample PO file
 * 
 * @param string $language The language code (e.g., 'en_US')
 * @return bool True if the file was created, false otherwise
 */
function create_sample_po_file(string $language): bool {
    $dir = __DIR__ . "/languages/$language/LC_MESSAGES";
    $file = "$dir/messages.po";
    
    if (!is_dir($dir) && !create_directory($dir)) {
        return false;
    }
    
    $content = 'msgid ""' . "\n";
    $content .= 'msgstr ""' . "\n";
    $content .= '"Project-Id-Version: PHP-Gettext Library Demo\n"' . "\n";
    $content .= '"POT-Creation-Date: ' . date('Y-m-d H:i:sO') . '\n"' . "\n";
    $content .= '"PO-Revision-Date: ' . date('Y-m-d H:i:sO') . '\n"' . "\n";
    $content .= '"Last-Translator: PHP-Gettext Installer <php-gettext@example.com>\n"' . "\n";
    $content .= '"Language-Team: ' . strtoupper(substr($language, 0, 2)) . '\n"' . "\n";
    $content .= '"Language: ' . $language . '\n"' . "\n";
    $content .= '"MIME-Version: 1.0\n"' . "\n";
    $content .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
    $content .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";
    $content .= '"Plural-Forms: nplurals=2; plural=(n != 1);\n"' . "\n\n";
    
    // Add some sample translations
    $content .= 'msgid "Hello World"' . "\n";
    $content .= 'msgstr "Hello World (' . $language . ')"' . "\n\n";
    
    $content .= 'msgid "One item"' . "\n";
    $content .= 'msgid_plural "Many items"' . "\n";
    $content .= 'msgstr[0] "One item (' . $language . ')"' . "\n";
    $content .= 'msgstr[1] "Many items (' . $language . ')"' . "\n\n";
    
    $content .= 'msgctxt "menu"' . "\n";
    $content .= 'msgid "View"' . "\n";
    $content .= 'msgstr "View (' . $language . ')"' . "\n\n";
    
    $content .= 'msgctxt "items"' . "\n";
    $content .= 'msgid "One item"' . "\n";
    $content .= 'msgid_plural "Many items"' . "\n";
    $content .= 'msgstr[0] "One item in context (' . $language . ')"' . "\n";
    $content .= 'msgstr[1] "Many items in context (' . $language . ')"' . "\n\n";
    
    if (file_put_contents($file, $content)) {
        status("Created sample PO file: $file", 'success');
        return true;
    } else {
        status("Failed to create sample PO file: $file", 'error');
        return false;
    }
}

/**
 * Compile a PO file to MO format
 * 
 * @param string $language The language code (e.g., 'en_US')
 * @return bool True if the compilation was successful, false otherwise
 */
function compile_po_to_mo(string $language): bool {
    $po_file = __DIR__ . "/languages/$language/LC_MESSAGES/messages.po";
    $mo_file = __DIR__ . "/languages/$language/LC_MESSAGES/messages.mo";
    
    if (!file_exists($po_file)) {
        status("PO file not found: $po_file", 'error');
        return false;
    }
    
    // Try to use the compiler utility if available
    $compiler = __DIR__ . '/utilities/compile_po.php';
    if (file_exists($compiler)) {
        status("Using compiler utility: $compiler", 'info');
        
        // Include the compiler script
        include_once $compiler;
        
        if (function_exists('compile_po_to_mo')) {
            return compile_po_to_mo($po_file, $mo_file);
        }
    }
    
    status("Compiler utility not available, skipping MO file creation", 'warning');
    return false;
}

// Main installation process
echo "PHP-Gettext Library Installation\n";
echo "==============================\n\n";

// Check PHP version
$php_version = phpversion();
status("Detected PHP version: $php_version", 'info');

if (version_compare($php_version, '8.0.0', '<')) {
    status("Warning: This library is optimized for PHP 8.0.0 or newer. You're using an older version which might not be fully compatible.", 'warning');
}

// Create languages directory structure
$languages = ['en_US', 'fr_FR', 'es_ES', 'de_DE'];

foreach ($languages as $language) {
    if (create_sample_po_file($language)) {
        compile_po_to_mo($language);
    }
}

// Create installation complete message
echo "\nPHP-Gettext Library installation completed!\n\n";
echo "Next steps:\n";
echo "1. Check the 'languages/' directory for sample translation files\n";
echo "2. See the 'examples/' directory for usage examples\n";
echo "3. Run tests with 'php utilities/test_gettext.php'\n\n";
echo "For more information, see the README.md file\n";

// Exit with success
exit(0); 