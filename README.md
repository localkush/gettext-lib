# PHP-Gettext Library for PHP 8.x+

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A modern PHP 8.x+ compatible implementation of PHP-Gettext that allows PHP applications to use gettext translation functionality without requiring the PHP gettext extension.

## Features

- No need for the PHP gettext extension
- Support for standard `.po` and `.mo` files
- Plural form handling
- Context-based translations
- Translation caching for performance
- Fully typed for modern PHP 8.x+ environments
- Secure processing of plural form expressions

## Installation

### Manual Installation

1. Download the latest release
2. Include the library files in your project:

```php
require_once 'lib/gettext.php';
require_once 'lib/streams.php';
```

## Updates for PHP 8.x+

The library has been modernized with several improvements:

- Replaced old constructor methods with `__construct()` for PHP 8.x compatibility
- Added type declarations for parameters and return types
- Protected/public property declarations for better encapsulation
- Improved handling of null values with nullable type hints
- Refactored the `select_string()` method to be safer and avoid unnecessary `eval()` calls
- Added robust error handling with try/catch blocks
- Added strict typing support
- Better class property visibility control

## Usage

### Folder Structure

Set up your translation files in this structure:

```
- /languages
   - /en_US
     - /LC_MESSAGES
        - messages.po
        - messages.mo
   - /fr_FR
     - /LC_MESSAGES
        - messages.po
        - messages.mo
   - /[other_locales]...
```

### Basic Usage

```php
<?php
declare(strict_types=1);

require_once 'lib/gettext.php';
require_once 'lib/streams.php';

// Define which language to use
$language = 'en_US';

// Set up the gettext reader
$locale_file = new FileReader("languages/$language/LC_MESSAGES/messages.mo");
$locale_fetch = new gettext_reader($locale_file);

/**
 * Helper function for translation
 */
function _e(string $text): string {
    global $locale_fetch;
    return $locale_fetch->translate($text);
}

// Usage:
echo _e('Hello World'); // This will be translated
```

### Advanced Usage

The library supports plural forms and context-based translations:

```php
// Plural forms
echo $locale_fetch->ngettext('One item', 'Many items', $count);

// Context-based translation
echo $locale_fetch->pgettext('menu', 'View');

// Context-based plural forms
echo $locale_fetch->npgettext('items', 'One item', 'Many items', $count);
```

### Auto-Detecting User Language

See `advanced_example.php` for a complete example of auto-detecting user language preferences.

## Security Improvements

The update includes security improvements, especially in handling plural forms evaluation. The `select_string()` method now:

1. Includes common plural form patterns hardcoded to avoid using `eval()`
2. Uses `eval()` only as a fallback for complex cases
3. Implements proper exception handling
4. Provides safe defaults if evaluation fails

## Utilities

The package includes several utility scripts:

- `compile_po.php`: A simple tool to compile `.po` files to `.mo` format
- `test_gettext.php`: Unit tests to verify the library works correctly
- `advanced_example.php`: A complete web example showing all features

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

PHP-gettext is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## Credits

- Original PHP-Gettext library by Danilo Segan and Nico Kaiser
- PHP 7.x+ updates by Charles Wilkin aka localkush@Github
- PHP 8.x+ modernization by Charles Wilkin aka localkush@Github
