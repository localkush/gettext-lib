# gettext-lib
gettext library (corrected for PHP7+)

PHP-GetText Library (ver 2015) updated for new PHP7.x+.
Since the constructor doesn't work as before i have change the code for the new constructor using __construct.

Everything should work fine with these now using PHP 7.x or +.


## How to Use
```php
require_once 'lib/gettext.php';
require_once 'lib/streams.php';

$language = 'en_US';

$locale_lang = $language;
$locale_file = new FileReader("languages/$locale_lang/LC_MESSAGES/messages.mo");
$locale_fetch = new gettext_reader($locale_file);

function _e($text){
    global $locale_fetch;
    return $locale_fetch->translate($text);
}
```

**Than in your index.php use as follow :**
```php
echo _e('Hello World'); // this will be translated.
```
