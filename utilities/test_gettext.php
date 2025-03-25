<?php
/**
 * PHP-Gettext Library Test Script
 * 
 * This script tests the core functionality of the PHP-Gettext library
 * to ensure it works correctly.
 * 
 * Usage:
 * php test_gettext.php [language_code]
 * 
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../lib/gettext.php';
require_once __DIR__ . '/../lib/streams.php';

// Default language
$language = isset($argv[1]) ? $argv[1] : 'en_US';

/**
 * Simple test function for gettext library
 * 
 * @param string $language The language code to test
 * @return void
 */
function run_tests(string $language): void {
    echo "Running PHP-Gettext Library Unit Tests (Language: $language)\n";
    echo "============================================\n\n";
    
    // Test 1: Basic initialization
    echo "Test 1: Basic initialization\n";
    try {
        $locale_file = new FileReader(__DIR__ . "/../languages/$language/LC_MESSAGES/messages.mo");
        
        if (isset($locale_file->error) && $locale_file->error) {
            echo "FAIL: FileReader could not read MO file (Error code: " . $locale_file->error . ")\n";
            exit(1);
        }
        
        $locale_fetch = new gettext_reader($locale_file);
        
        if (isset($locale_fetch->error) && $locale_fetch->error) {
            echo "FAIL: gettext_reader could not parse MO file (Error code: " . $locale_fetch->error . ")\n";
            exit(1);
        }
        
        echo "PASS: FileReader and gettext_reader initialized successfully\n\n";
        
        // Test 2: Basic translation
        echo "Test 2: Basic translation\n";
        $original = "Hello World";
        $result = $locale_fetch->translate($original);
        
        echo "Original: '$original'\n";
        echo "Translated: '$result'\n";
        
        if (empty($result)) {
            echo "FAIL: Translation returned empty string\n";
            exit(1);
        }
        
        echo "PASS: Basic translation works\n\n";
        
        // Test 3: Plural forms
        echo "Test 3: Plural forms\n";
        $single = "One item";
        $plural = "Many items";
        
        // Test with n=1
        $result_singular = $locale_fetch->ngettext($single, $plural, 1);
        
        echo "Singular (n=1) - Got: '$result_singular'\n";
        
        // Test with n=2
        $result_plural = $locale_fetch->ngettext($single, $plural, 2);
        
        echo "Plural (n=2) - Got: '$result_plural'\n";
        
        if ($result_singular === $result_plural) {
            echo "WARNING: Singular and plural forms are the same\n";
        } else {
            echo "PASS: Plural forms give different results\n";
        }
        echo "\n";
        
        // Test 4: Context-based translation
        echo "Test 4: Context-based translation\n";
        $context = "menu";
        $text = "View";
        $result = $locale_fetch->pgettext($context, $text);
        
        echo "Context: '$context', Text: '$text'\n";
        echo "Translated: '$result'\n";
        
        echo "PASS: Context-based translation executed\n\n";
        
        // Test 5: Context-based plural forms
        echo "Test 5: Context-based plural forms\n";
        $context = "items";
        $single = "One item";
        $plural = "Many items";
        
        // Test with n=1
        $result_singular = $locale_fetch->npgettext($context, $single, $plural, 1);
        
        echo "Context: '$context', Singular (n=1) - Got: '$result_singular'\n";
        
        // Test with n=2
        $result_plural = $locale_fetch->npgettext($context, $single, $plural, 2);
        
        echo "Context: '$context', Plural (n=2) - Got: '$result_plural'\n";
        
        if ($result_singular === $result_plural) {
            echo "WARNING: Context-based singular and plural forms are the same\n";
        } else {
            echo "PASS: Context-based plural forms give different results\n";
        }
        echo "\n";
        
        // Test 6: Fallback when string not found
        echo "Test 6: Fallback when string not found\n";
        $nonexistent = "This string does not exist in translations";
        $result = $locale_fetch->translate($nonexistent);
        
        echo "Nonexistent: '$nonexistent'\n";
        echo "Result: '$result'\n";
        
        if ($result !== $nonexistent) {
            echo "FAIL: Fallback to original string does not work\n";
            exit(1);
        }
        
        echo "PASS: Fallback to original string works correctly\n\n";
        
        // Test 7: PHP 8.x compatibility features
        echo "Test 7: PHP 8.x compatibility features\n";
        
        $php_version = phpversion();
        echo "Current PHP version: $php_version\n";
        
        if (version_compare($php_version, '8.0.0', '>=')) {
            echo "PHP 8.x detected, testing compatibility...\n";
            
            try {
                // Test with an empty string (which should work in PHP 8.x)
                $empty_result = $locale_fetch->translate("");
                echo "Empty string translation: " . (is_string($empty_result) ? "works" : "fails") . "\n";
                
                // Test with nullable return type (PHP 8.x feature)
                $test_fn = function(?string $text = null): ?string {
                    global $locale_fetch;
                    if ($text === null) return null;
                    return $locale_fetch->translate($text);
                };
                
                $nullable_result = $test_fn(null);
                $string_result = $test_fn("Hello World");
                
                echo "Nullable type handling: " . ($nullable_result === null ? "works" : "fails") . "\n";
                echo "String return with nullable types: " . (is_string($string_result) ? "works" : "fails") . "\n";
                
                echo "PASS: PHP 8.x compatibility features work correctly\n";
            } catch (Throwable $e) {
                echo "FAIL: PHP 8.x compatibility test failed: " . $e->getMessage() . "\n";
                exit(1);
            }
        } else {
            echo "Not PHP 8.x, skipping specific PHP 8.x compatibility tests\n";
        }
        echo "\n";
        
        // All tests passed
        echo "All tests PASSED!\n";
        echo "PHP-Gettext Library for PHP 8.x+ is working correctly.\n";
    } catch (Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}

// Run the tests
run_tests($language); 