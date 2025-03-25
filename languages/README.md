# PHP-Gettext Library Language Files

This directory contains the translation files used by the PHP-Gettext library.

## Directory Structure

The translation files are organized by language code and follow this structure:

```
- /[language_code]
   - /LC_MESSAGES
      - messages.po  (Source translation file)
      - messages.mo  (Compiled translation file)
```

Where `[language_code]` is a language code like `en_US`, `fr_FR`, etc.

## Creating New Translations

1. Create a new directory for your language (if it doesn't exist already)
2. Copy an existing PO file as a template
3. Translate the strings in the PO file
4. Compile the PO file to MO format using the utility:

```bash
php ../utilities/compile_po.php [language_code]/LC_MESSAGES/messages.po [language_code]/LC_MESSAGES/messages.mo
```

You can also run the installation script to automatically create sample files:

```bash
php ../install.php
```

## PO File Format

The PO file format is a standard format for translations. Each entry consists of:

- `msgid` - The original string
- `msgstr` - The translated string

For plural forms:

- `msgid` - The original singular form
- `msgid_plural` - The original plural form
- `msgstr[0]` - The translated singular form
- `msgstr[1]` - The translated plural form

For context-based translations:

- `msgctxt` - The context
- `msgid` - The original string
- `msgstr` - The translated string 