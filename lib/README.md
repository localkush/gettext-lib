# PHP-Gettext Library Core Files

This directory contains the core files of the PHP-Gettext library.

## Files

- `gettext.php` - The main gettext implementation with translation functionality
- `streams.php` - File and stream handling classes for reading MO files

## How the Library Works

The library consists of two main components:

1. **Stream Readers** (in `streams.php`):
   - `StreamReader` - Base abstract class for all stream readers
   - `StringReader` - Reads from a string in memory
   - `FileReader` - Reads from a file on disk
   - `CachedFileReader` - Loads a file into memory and reads from there

2. **Gettext Reader** (in `gettext.php`):
   - `gettext_reader` - Main class that reads MO files and provides translation functions
   
The basic flow is:

1. Create a reader for your MO file (`FileReader` or `CachedFileReader`)
2. Create a `gettext_reader` with that reader
3. Use the `translate()`, `ngettext()`, `pgettext()`, or `npgettext()` methods to translate strings

## PHP 8.x+ Features

The library has been updated for PHP 8.x+ with:

- Type declarations for parameters and return types
- Constructor method updates
- Property visibility improvements
- Nullable type handling
- Security improvements in plural form evaluation 