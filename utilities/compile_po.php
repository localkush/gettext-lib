<?php
/**
 * PO to MO File Compiler
 * 
 * A utility script to convert PO (Portable Object) translation files to
 * MO (Machine Object) format for use with the PHP-Gettext library.
 * 
 * Usage:
 * php compile_po.php <po_file> <mo_file>
 * 
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

/**
 * Compile a PO file to MO format
 * 
 * @param string $po_file Path to the PO file
 * @param string $mo_file Path where the MO file should be saved
 * @return bool True on success, false on failure
 */
function compile_po_to_mo(string $po_file, string $mo_file): bool {
    if (!file_exists($po_file)) {
        echo "Error: PO file not found: $po_file\n";
        return false;
    }
    
    $po_content = file_get_contents($po_file);
    if (!$po_content) {
        echo "Error: Failed to read PO file\n";
        return false;
    }
    
    $mo_content = generate_mo_content($po_content);
    if (!$mo_content) {
        echo "Error: Failed to generate MO content\n";
        return false;
    }
    
    $result = file_put_contents($mo_file, $mo_content);
    if (!$result) {
        echo "Error: Failed to write MO file\n";
        return false;
    }
    
    echo "Successfully created MO file: $mo_file\n";
    return true;
}

/**
 * Generate MO file content from PO file content
 * 
 * @param string $po_content The content of the PO file
 * @return string|false The content for the MO file, or false on failure
 */
function generate_mo_content(string $po_content) {
    // Parse PO content
    $entries = [];
    $headers = '';
    $header_extracted = false;
    
    // Extract messages
    preg_match_all('/msgid\s+"(.*)"\s+msgstr\s+"(.*)"/Us', $po_content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $msgid = stripcslashes($match[1]);
        $msgstr = stripcslashes($match[2]);
        
        if ($msgid === '') {
            // This is the header entry
            $headers = $msgstr;
            $header_extracted = true;
        } else {
            $entries[$msgid] = $msgstr;
        }
    }
    
    // Extract plural forms
    preg_match_all('/msgid\s+"(.*)"\s+msgid_plural\s+"(.*)"\s+msgstr\[0\]\s+"(.*)"\s+msgstr\[1\]\s+"(.*)"/Us', $po_content, $plural_matches, PREG_SET_ORDER);
    
    foreach ($plural_matches as $match) {
        $msgid = stripcslashes($match[1]);
        $msgid_plural = stripcslashes($match[2]);
        $msgstr0 = stripcslashes($match[3]);
        $msgstr1 = stripcslashes($match[4]);
        
        // Store with null byte separator for plural forms
        $entries[$msgid . chr(0) . $msgid_plural] = $msgstr0 . chr(0) . $msgstr1;
    }
    
    // Extract context-based translations
    preg_match_all('/msgctxt\s+"(.*)"\s+msgid\s+"(.*)"\s+msgstr\s+"(.*)"/Us', $po_content, $context_matches, PREG_SET_ORDER);
    
    foreach ($context_matches as $match) {
        $msgctxt = stripcslashes($match[1]);
        $msgid = stripcslashes($match[2]);
        $msgstr = stripcslashes($match[3]);
        
        // Store with context marker (EOT character)
        $entries[$msgctxt . chr(4) . $msgid] = $msgstr;
    }
    
    if (!$header_extracted) {
        echo "Warning: No header found in PO file, using default header\n";
        $headers = "Project-Id-Version: PHP-Gettext Library\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: nplurals=2; plural=(n != 1);\n";
    }
    
    // Create MO file content
    $num_entries = count($entries) + 1; // +1 for the header
    
    // MO file format constants
    $magic = "\x95\x04\x12\xde"; // Little-endian magic
    $revision = pack('V', 0);
    $num_strings = pack('V', $num_entries);
    
    // Calculate offsets
    $header_size = 28; // Fixed MO header size
    $table_size = $num_entries * 8 * 2; // Two tables of num_entries * 8 bytes each
    
    $offset = $header_size + $table_size;
    $originals_table = '';
    $translations_table = '';
    $strings_data = '';
    
    // Add header entry first
    $originals_table .= pack('VV', 0, $offset);
    $translations_table .= pack('VV', strlen($headers), $offset);
    $strings_data .= $headers;
    $offset += strlen($headers);
    
    // Add all other entries
    foreach ($entries as $original => $translation) {
        $length_original = strlen($original);
        $originals_table .= pack('VV', $length_original, $offset);
        $strings_data .= $original;
        $offset += $length_original;
        
        $length_translation = strlen($translation);
        $translations_table .= pack('VV', $length_translation, $offset);
        $strings_data .= $translation;
        $offset += $length_translation;
    }
    
    // Assemble the MO file content
    $mo = $magic . $revision . $num_strings;
    $mo .= pack('V', $header_size); // Offset of original strings table
    $mo .= pack('V', $header_size + $num_entries * 8); // Offset of translation strings table
    $mo .= pack('V', 0); // Size of hashing table (unused)
    $mo .= pack('V', 0); // Offset of hashing table (unused)
    $mo .= $originals_table . $translations_table . $strings_data;
    
    return $mo;
}

// Command line mode
if (PHP_SAPI === 'cli' && isset($argv)) {
    // Process command line arguments
    if (count($argv) < 3) {
        echo "Usage: php compile_po.php <po_file> <mo_file>\n";
        echo "Example: php compile_po.php ../languages/en_US/LC_MESSAGES/messages.po ../languages/en_US/LC_MESSAGES/messages.mo\n";
        exit(1);
    }
    
    $po_file = $argv[1];
    $mo_file = $argv[2];
    
    $result = compile_po_to_mo($po_file, $mo_file);
    exit($result ? 0 : 1);
} else if (count($_SERVER['argv']) > 0) {
    // Called from another script
    if (!isset($_SERVER['argv'][1]) || !isset($_SERVER['argv'][2])) {
        echo "Usage: php compile_po.php <po_file> <mo_file>\n";
        echo "Example: php compile_po.php ../languages/en_US/LC_MESSAGES/messages.po ../languages/en_US/LC_MESSAGES/messages.mo\n";
    } else {
        $po_file = $_SERVER['argv'][1];
        $mo_file = $_SERVER['argv'][2];
        compile_po_to_mo($po_file, $mo_file);
    }
}
?> 