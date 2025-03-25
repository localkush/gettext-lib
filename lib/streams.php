<?php
  // Simple class to wrap file streams, string streams, etc.
  // seek is essential, and it should be byte stream
class StreamReader {
  // should return a string [FIXME: perhaps return array of bytes?]
  function read($bytes) {
    return false;
  }

  // should return new position
  function seekto($position) {
    return false;
  }

  // returns current position
  function currentpos() {
    return false;
  }

  // returns length of entire stream (limit for seekto()s)
  function length() {
    return false;
  }
};

class StringReader {
  protected $_pos;
  protected $_str;

  function __construct($str='') {
    $this->_str = $str;
    $this->_pos = 0;
  }

  function read($bytes) {
    $data = substr($this->_str, $this->_pos, $bytes);
    $this->_pos += $bytes;
    if (strlen($this->_str)<$this->_pos)
      $this->_pos = strlen($this->_str);

    return $data;
  }

  function seekto($pos) {
    $this->_pos = $pos;
    if (strlen($this->_str)<$this->_pos)
      $this->_pos = strlen($this->_str);
    return $this->_pos;
  }

  function currentpos() {
    return $this->_pos;
  }

  function length() {
    return strlen($this->_str);
  }

};


class FileReader {
  protected $_pos;
  protected $_fd;
  protected $_length;
  public $error;

  function __construct($filename) {
    if (file_exists($filename)) {

      $this->_length=filesize($filename);
      $this->_pos = 0;
      $this->_fd = fopen($filename,'rb');
      if (!$this->_fd) {
        $this->error = 3; // Cannot read file, probably permissions
        return;
      }
    } else {
      $this->error = 2; // File doesn't exist
      return;
    }
  }

  function read($bytes) {
    if ($bytes) {
      fseek($this->_fd, $this->_pos);

      // PHP 5.1.1 does not read more than 8192 bytes in one fread()
      // the discussions at PHP Bugs suggest it's the intended behaviour
      $data = '';
      while ($bytes > 0) {
        $chunk  = fread($this->_fd, $bytes);
        $data  .= $chunk;
        $bytes -= strlen($chunk);
      }
      $this->_pos = ftell($this->_fd);

      return $data;
    } else return '';
  }

  function seekto($pos) {
    fseek($this->_fd, $pos);
    $this->_pos = ftell($this->_fd);
    return $this->_pos;
  }

  function currentpos() {
    return $this->_pos;
  }

  function length() {
    return $this->_length;
  }

  function close() {
    fclose($this->_fd);
  }

};

// Preloads entire file in memory first, then creates a StringReader
// over it (it assumes knowledge of StringReader internals)
class CachedFileReader extends StringReader {
  public $error;
  
  function __construct($filename) {
    if (file_exists($filename)) {

      $length=filesize($filename);
      $fd = fopen($filename,'rb');

      if (!$fd) {
        $this->error = 3; // Cannot read file, probably permissions
        parent::__construct('');
        return;
      }
      $this->_str = fread($fd, $length);
      fclose($fd);
      $this->_pos = 0;

    } else {
      $this->error = 2; // File doesn't exist
      parent::__construct('');
      return;
    }
  }
};


?>
