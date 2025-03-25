<?php
/**
 * Provides a simple gettext replacement that works independently from
 * the system's gettext abilities.
 * It can read MO files and use them for translating strings.
 * The files are passed to gettext_reader as a Stream (see streams.php)
 *
 * This version has the ability to cache all strings and translations to
 * speed up the string lookup.
 * While the cache is enabled by default, it can be switched off with the
 * second parameter in the constructor (e.g. whenusing very large MO files
 * that you don't want to keep in memory)
 */
class gettext_reader {
  //public:
  public int $error = 0; // public variable that holds error code (0 if no error)

  //private:
  protected int $BYTEORDER = 0;        // 0: low endian, 1: big endian
  protected $STREAM = null;
  protected bool $short_circuit = false;
  protected bool $enable_cache = false;
  protected ?int $originals = null;      // offset of original table
  protected ?int $translations = null;    // offset of translation table
  protected ?string $pluralheader = null;    // cache header field for plural forms
  protected int $total = 0;          // total string count
  protected ?array $table_originals = null;  // table for original strings (offsets)
  protected ?array $table_translations = null;  // table for translated strings (offsets)
  protected ?array $cache_translations = null;  // original -> translation mapping


  /* Methods */


  /**
   * Reads a 32bit Integer from the Stream
   *
   * @access private
   * @return int Integer from the Stream
   */
  protected function readint(): int {
    if ($this->BYTEORDER == 0) {
      // low endian
      $input = unpack('V', $this->STREAM->read(4));
      return array_shift($input);
    } else {
      // big endian
      $input = unpack('N', $this->STREAM->read(4));
      return array_shift($input);
    }
  }

  /**
   * Reads bytes from the Stream
   * 
   * @param int $bytes Number of bytes to read
   * @return string Read data
   */
  protected function read(int $bytes): string {
    return $this->STREAM->read($bytes);
  }

  /**
   * Reads an array of Integers from the Stream
   *
   * @param int $count How many elements should be read
   * @return array Array of Integers
   */
  protected function readintarray(int $count): array {
    if ($this->BYTEORDER == 0) {
      // low endian
      return unpack('V'.$count, $this->STREAM->read(4 * $count));
    } else {
      // big endian
      return unpack('N'.$count, $this->STREAM->read(4 * $count));
    }
  }

  /**
   * Constructor
   *
   * @param object $Reader the StreamReader object
   * @param boolean $enable_cache Enable or disable caching of strings (default on)
   */
  function __construct($Reader, bool $enable_cache = true) {
    // If there isn't a StreamReader, turn on short circuit mode.
    if (!$Reader || (property_exists($Reader, 'error') && $Reader->error)) {
      $this->short_circuit = true;
      return;
    }

    // Caching can be turned off
    $this->enable_cache = $enable_cache;

    $MAGIC1 = "\x95\x04\x12\xde";
    $MAGIC2 = "\xde\x12\x04\x95";

    $this->STREAM = $Reader;
    $magic = $this->read(4);
    if ($magic == $MAGIC1) {
      $this->BYTEORDER = 1;
    } elseif ($magic == $MAGIC2) {
      $this->BYTEORDER = 0;
    } else {
      $this->error = 1; // not MO file
      return;
    }

    // FIXME: Do we care about revision? We should.
    $revision = $this->readint();

    $this->total = $this->readint();
    $this->originals = $this->readint();
    $this->translations = $this->readint();
  }

  /**
   * Loads the translation tables from the MO file into the cache
   * If caching is enabled, also loads all strings into a cache
   * to speed up translation lookups
   *
   * @access private
   */
  protected function load_tables(): void {
    if (is_array($this->cache_translations) &&
      is_array($this->table_originals) &&
      is_array($this->table_translations)) {
      return;
    }

    /* get original and translations tables */
    if (!is_array($this->table_originals)) {
      $this->STREAM->seekto($this->originals);
      $this->table_originals = $this->readintarray($this->total * 2);
    }
    if (!is_array($this->table_translations)) {
      $this->STREAM->seekto($this->translations);
      $this->table_translations = $this->readintarray($this->total * 2);
    }

    if ($this->enable_cache) {
      $this->cache_translations = array();
      /* read all strings in the cache */
      for ($i = 0; $i < $this->total; $i++) {
        $this->STREAM->seekto($this->table_originals[$i * 2 + 2]);
        $original = $this->STREAM->read($this->table_originals[$i * 2 + 1]);
        $this->STREAM->seekto($this->table_translations[$i * 2 + 2]);
        $translation = $this->STREAM->read($this->table_translations[$i * 2 + 1]);
        $this->cache_translations[$original] = $translation;
      }
    }
  }

  /**
   * Returns a string from the "originals" table
   *
   * @access private
   * @param int $num Offset number of original string
   * @return string Requested string if found, otherwise ''
   */
  protected function get_original_string(int $num): string {
    $length = $this->table_originals[$num * 2 + 1];
    $offset = $this->table_originals[$num * 2 + 2];
    if (!$length)
      return '';
    $this->STREAM->seekto($offset);
    $data = $this->STREAM->read($length);
    return (string)$data;
  }

  /**
   * Returns a string from the "translations" table
   *
   * @access private
   * @param int $num Offset number of original string
   * @return string Requested string if found, otherwise ''
   */
  protected function get_translation_string(int $num): string {
    $length = $this->table_translations[$num * 2 + 1];
    $offset = $this->table_translations[$num * 2 + 2];
    if (!$length)
      return '';
    $this->STREAM->seekto($offset);
    $data = $this->STREAM->read($length);
    return (string)$data;
  }

  /**
   * Binary search for string
   *
   * @access private
   * @param string $string String to find
   * @param int $start (internally used in recursive function)
   * @param int $end (internally used in recursive function)
   * @return int string number (offset in originals table)
   */
  protected function find_string(string $string, int $start = -1, int $end = -1): int {
    if (($start == -1) or ($end == -1)) {
      // find_string is called with only one parameter, set start end end
      $start = 0;
      $end = $this->total;
    }
    if (abs($start - $end) <= 1) {
      // We're done, now we either found the string, or it doesn't exist
      $txt = $this->get_original_string($start);
      if ($string == $txt)
        return $start;
      else
        return -1;
    } else if ($start > $end) {
      // start > end -> turn around and start over
      return $this->find_string($string, $end, $start);
    } else {
      // Divide table in two parts
      $half = (int)(($start + $end) / 2);
      $cmp = strcmp($string, $this->get_original_string($half));
      if ($cmp == 0)
        // string is exactly in the middle => return it
        return $half;
      else if ($cmp < 0)
        // The string is in the upper half
        return $this->find_string($string, $start, $half);
      else
        // The string is in the lower half
        return $this->find_string($string, $half, $end);
    }
  }

  /**
   * Translates a string
   *
   * @access public
   * @param string $string String to be translated
   * @return string translated string (or original, if not found)
   */
  public function translate(string $string): string {
    if ($this->short_circuit)
      return $string;
    $this->load_tables();

    if ($this->enable_cache) {
      // Caching enabled, get translated string from cache
      if (array_key_exists($string, $this->cache_translations))
        return $this->cache_translations[$string];
      else
        return $string;
    } else {
      // Caching not enabled, try to find string
      $num = $this->find_string($string);
      if ($num == -1)
        return $string;
      else
        return $this->get_translation_string($num);
    }
  }

  /**
   * Sanitize plural form expression for use in PHP eval call.
   *
   * @access private
   * @param string $expr Expression to sanitize
   * @return string sanitized plural form expression
   */
  protected function sanitize_plural_expression(string $expr): string {
    // Get rid of disallowed characters.
    $expr = preg_replace('@[^a-zA-Z0-9_:;\(\)\?\|\&=!<>+*/\%-]@', '', $expr);

    // Add parenthesis for tertiary '?' operator.
    $expr .= ';';
    $res = '';
    $p = 0;
    for ($i = 0; $i < strlen($expr); $i++) {
      $ch = $expr[$i];
      switch ($ch) {
      case '?':
        $res .= ' ? (';
        $p++;
        break;
      case ':':
        $res .= ') : (';
        break;
      case ';':
        $res .= str_repeat(')', $p) . ';';
        $p = 0;
        break;
      default:
        $res .= $ch;
      }
    }
    return $res;
  }

  /**
   * Parse full PO header and extract only plural forms line.
   *
   * @access private
   * @param string $header Header to parse
   * @return string verbatim plural form header field
   */
  protected function extract_plural_forms_header_from_po_header(string $header): string {
    if (preg_match("/(^|\n)plural-forms: ([^\n]*)\n/i", $header, $regs))
      $expr = $regs[2];
    else
      $expr = "nplurals=2; plural=n == 1 ? 0 : 1;";
    return $expr;
  }

  /**
   * Get possible plural forms from MO header
   *
   * @access private
   * @return string plural form header
   */
  protected function get_plural_forms(): string {
    // lets assume message number 0 is header
    // this is true, right?
    $this->load_tables();

    // cache header field for plural forms
    if (!is_string($this->pluralheader)) {
      if ($this->enable_cache) {
        $header = $this->cache_translations[""];
      } else {
        $header = $this->get_translation_string(0);
      }
      $expr = $this->extract_plural_forms_header_from_po_header($header);
      $this->pluralheader = $this->sanitize_plural_expression($expr);
    }
    return $this->pluralheader;
  }

  /**
   * Detects which plural form to take
   *
   * @access private
   * @param int $n Count
   * @return int array index of the right plural form
   */
  protected function select_string(int $n): int {
    $pluralforms = $this->get_plural_forms();
    
    // Extract nplurals value
    preg_match('/nplurals\s*=\s*(\d+)/', $pluralforms, $matches);
    $nplurals = isset($matches[1]) ? (int)$matches[1] : 2;
    
    // Extract plural formula
    preg_match('/plural\s*=\s*(.+?);/', $pluralforms, $matches);
    $formula = isset($matches[1]) ? $matches[1] : 'n != 1';
    
    // Replace 'n' with actual value in the formula
    $formula = str_replace('n', $n, $formula);
    
    // Safely evaluate the formula
    $plural = 0;
    
    // A safer alternative to eval for common plural formulas
    if ($formula === 'n != 1') {
        $plural = ($n != 1) ? 1 : 0;
    } else if ($formula === '(n != 1)') {
        $plural = ($n != 1) ? 1 : 0;
    } else if ($formula === '(n>1)') {
        $plural = ($n > 1) ? 1 : 0;
    } else if ($formula === 'n>1') {
        $plural = ($n > 1) ? 1 : 0;
    } else if ($formula === '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)') {
        $plural = ($n%10==1 && $n%100!=11) ? 0 : (($n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20)) ? 1 : 2);
    } else {
        // For complex formulas, we still need to use eval as a fallback
        // This is a security risk but necessary for complex plural forms
        $code = 'return ' . $formula . ';';
        try {
            $plural = (int)eval($code);
        } catch (\Throwable $e) {
            // If eval fails, default to simple English plural rule
            $plural = ($n != 1) ? 1 : 0;
        }
    }
    
    // Safety check - make sure plural doesn't exceed nplurals-1
    if ($plural >= $nplurals) {
        $plural = $nplurals - 1;
    }
    
    return $plural;
  }

  /**
   * Plural version of gettext
   *
   * @access public
   * @param string $single Single form
   * @param string $plural Plural form 
   * @param int $number Count
   * @return string translated plural form
   */
  public function ngettext(string $single, string $plural, int $number): string {
    if ($this->short_circuit) {
      if ($number != 1)
        return $plural;
      else
        return $single;
    }

    // find out the appropriate form
    $select = $this->select_string($number);

    // this should contains all strings separated by NULLs
    $key = $single . chr(0) . $plural;

    if ($this->enable_cache) {
      if (!array_key_exists($key, $this->cache_translations)) {
        return ($number != 1) ? $plural : $single;
      } else {
        $result = $this->cache_translations[$key];
        $list = explode(chr(0), $result);
        return $list[$select];
      }
    } else {
      $num = $this->find_string($key);
      if ($num == -1) {
        return ($number != 1) ? $plural : $single;
      } else {
        $result = $this->get_translation_string($num);
        $list = explode(chr(0), $result);
        return $list[$select];
      }
    }
  }

  /**
   * Context-aware gettext
   * 
   * @param string $context Context
   * @param string $msgid Message ID
   * @return string Translated string
   */
  public function pgettext(string $context, string $msgid): string {
    $key = $context . chr(4) . $msgid;
    $ret = $this->translate($key);
    if (strpos($ret, "\004") !== FALSE) {
      return $msgid;
    } else {
      return $ret;
    }
  }

  /**
   * Context-aware plural version of gettext
   * 
   * @param string $context Context
   * @param string $singular Singular form
   * @param string $plural Plural form
   * @param int $number Count
   * @return string Translated plural form
   */
  public function npgettext(string $context, string $singular, string $plural, int $number): string {
    $key = $context . chr(4) . $singular;
    $ret = $this->ngettext($key, $plural, $number);
    if (strpos($ret, "\004") !== FALSE) {
      return $singular;
    } else {
      return $ret;
    }
  }
}
?>
