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

echo _e('Hello World'); // this will be translated.
