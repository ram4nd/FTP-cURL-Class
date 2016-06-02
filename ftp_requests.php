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

  private $curl_handle;
  private $url;

  /**
   * Setup and init connection.
   */
  public function __construct($username, $password, $server, $initial_path = '/', $port = 21) {
    $this->username = $username;
    $this->password = $password;
    $this->server = $server;

    // Set host/initial path.
    $this->url = "ftp://{$server}{$initial_path}";

    // Setup connection.
    $this->curl_handle = curl_init();

    // Check for successful connection.
    if (!$this->curl_handle) {
      throw new Exception('Could not initialize cURL.');
    }

    // Connection options.
    $options = array(
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
    );

    // Set connection options, use foreach so useful errors can be caught
    // instead of a generic "cannot set options" error with curl_setopt_array().
    foreach ($options as $option_name => $option_value) {
      if (!curl_setopt($this->curl_handle, $option_name, $option_value)) {
        throw new Exception(sprintf('Could not set cURL option: %s', $option_name));
      }
    }

  }

  /**
   * @return array of file names
   * @throws Exception
   * @return array
   */
  public function ftpFileList() {
    if (!curl_setopt($this->curl_handle, CURLOPT_URL, $this->url)) {
      throw new Exception ("Could not set cURL directory: $this->url");
    }
    curl_setopt($this->curl_handle, CURLOPT_FTPLISTONLY, 1);
    $result = curl_exec($this->curl_handle) or die (curl_error($this->curl_handle));
    $files = explode("\n",trim($result));
    if (count($files)) {
      return $files;
    }
    else {
      return array();
    }
  }

  /**
   * Download remote file to the given location
   */
  public function download($file_name) {
    $file = tmpfile();
    curl_setopt($this->curl_handle, CURLOPT_URL, $this->url . $file_name);
    curl_setopt($this->curl_handle, CURLOPT_FTPLISTONLY, 0);
    curl_setopt($this->curl_handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->curl_handle, CURLOPT_FILE, $file);
    $result = curl_exec($this->curl_handle);
    $file_contents = file_get_contents(stream_get_meta_data($file)['uri']);
    fclose($file);
    return $file_contents;
  }

  /**
   * Attempt to close cURL handle.
   */
  public function __destruct() {
    @curl_close($this->curl_handle);
  }

}
