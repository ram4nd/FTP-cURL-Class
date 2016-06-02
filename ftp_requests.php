<?php
/**
 * @file Simple wrapper for cURL functions to transfer an ASCII file over FTP with implicit TLS.
 *
 * @author Ra Mänd <ram4nd@gmail.com>
 * @link http://browse-tutorials.com/
 *
 * Example:
 * require_once 'ftp_requests.php'
 * $ftp = new ftp('username', 'password', 'server');
 */

class Ftp {
  private $username;
  private $password;
  private $server;
  private $path;
  private $options;

  private $ch;
  private $url;

  /**
   * Setup and init connection.
   *
   * @var string $username
   * @var string $password
   * @var string $path
   * @var number $port
   *
   * @throws Exception
   */
  public function __construct($username, $password, $server, $path = '/', $port = 21) {
    $this->username = $username;
    $this->password = $password;
    $this->server = $server;

    // Set host/initial path.
    $this->url = 'ftp://' . $server . $path;
    $this->path = $path;

    // Connection options.
    $this->options = array(
      CURLOPT_USERPWD        => $username . ':' . $password,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_INFILE         => null,
      CURLOPT_INFILESIZE     => -1,
      CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_TLS,
      CURLOPT_UPLOAD         => false,
      CURLOPT_PORT           => $port,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_HEADER         => false,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FTPLISTONLY    => 0,
    );
  }

  /**
   * Return array of file names.
   *
   * @throws Exception
   *
   * @return array
   */
  public function files() {
    $this->_init();
    if (!curl_setopt($this->ch, CURLOPT_URL, $this->url)) {
      throw new Exception ('Could not set cURL directory: ' . $this->url);
    }
    curl_setopt($this->ch, CURLOPT_FTPLISTONLY, 1);
    $result = curl_exec($this->ch) or die (curl_error($this->ch));
    $files = explode("\n",trim($result));
    if (count($files)) {
      return $files;
    }
    else {
      return array();
    }
  }

  /**
   * Download remote files content.
   *
   * @var string $file_name
   *
   * @throws Exception
   *
   * @return string
   */
  public function download($file_name) {
    $this->_init();
    $file = tmpfile();
    curl_setopt($this->ch, CURLOPT_URL, $this->url . $file_name);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->ch, CURLOPT_FILE, $file);
    $result = curl_exec($this->ch);

    if ($result === false) {
      throw new Exception(curl_error($this->ch));
    }

    $file_contents = file_get_contents(stream_get_meta_data($file)['uri']);
    fclose($file);
    return $file_contents;
  }

  /**
   * Delete remote file.
   *
   * @var string $file_name
   *
   * @throws Exception
   *
   * @return string
   */
  public function delete($file_name) {
    $this->_init();
    curl_setopt($this->ch, CURLOPT_URL, $this->url);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->ch, CURLOPT_QUOTE, array('DELE ' . $this->path . $file_name));
    $result = curl_exec($this->ch);

    if ($result === false) {
      throw new Exception(curl_error($this->ch));
    }

    return $result;
  }

  /**
   * Initialise cURL handle.
   */
  private function _init() {
    // Setup connection.
    $this->ch = curl_init();

    // Check for successful connection.
    if (!$this->ch) {
      throw new Exception('Could not initialize cURL.');
    }

    // Set connection options, use foreach so useful errors can be caught
    // instead of a generic "cannot set options" error with curl_setopt_array().
    foreach ($this->options as $option_name => $option_value) {
      if (!curl_setopt($this->ch, $option_name, $option_value)) {
        throw new Exception(sprintf('Could not set cURL option: %s', $option_name));
      }
    }
  }

  /**
   * Attempt to close cURL handle.
   */
  public function __destruct() {
    @curl_close($this->ch);
  }

}
