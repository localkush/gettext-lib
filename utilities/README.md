# PHP-Gettext Library Utilities

This directory contains utility scripts for the PHP-Gettext library.

## Files

- `compile_po.php` - Utility for compiling PO files to MO format
- `test_gettext.php` - Unit tests to verify the library works correctly

## Using the Utilities

### PO to MO Compiler

Use this script to compile your PO files to MO format:

```bash
php compile_po.php <po_file> <mo_file>
```

Example:

```bash
php compile_po.php ../languages/en_US/LC_MESSAGES/messages.po ../languages/en_US/LC_MESSAGES/messages.mo
```

### Test Script

Run this script to verify the library is working correctly:

```bash
php test_gettext.php [language_code]
```

Example:

```bash
php test_gettext.php en_US
```

If no language code is provided, 'en_US' will be used as the default. 