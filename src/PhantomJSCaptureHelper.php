<?php

namespace Drupal\phantomjs_capture;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Drupal\phantomjs_capture\PhantomJSCaptureHelperInterface;
use Drupal\Core\Url;
use Imagick;

class PhantomJSCaptureHelper implements PhantomJSCaptureHelperInterface {

  /**
   * @var ConfigFactoryInterface;
   */
  private $configFactory;

  /**
   * @var LoggerChannelFactoryInterface
   */
  private $loggerFactory;

  /**
   * @var FileSystemInterface
   */
  private $fileSystem;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Imagick
   */
  private $imagick;

  /**
   * PhantomJSCaptureHelper constructor.
   * @param ConfigFactoryInterface $config_factory
   * @param FileSystemInterface $file_system
   * @param LoggerChannelFactoryInterface $logger
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $logger;
    $this->config = $this->configFactory->get('phantomjs_capture.settings');
  }

  /**
   * Check that the binary exists at the path that was given.
   * @param $path
   * @return bool
   */
  public function binaryExists($path) {
    if (is_null($path) || !file_exists($path)) {
      throw new FileNotFoundException($path);
    }

    return TRUE;
  }

  /**
   * Write the current users session to a tmp file.
   * @return boolean
   */
  public function writeSession($session_name, $session_id) {

    // Get the protected directory path
    $base_path = $_SERVER["DOCUMENT_ROOT"];
    $protected_dir = $this->config->get('protected_dir');

    if (isset($protected_dir) == TRUE) {

        // Create the filepath to the new file for the protected directory
        $protected_path = $base_path . $protected_dir;
        $filepath = $protected_path . $session_name;

        // If the file exists delete it and replace
        if (file_exists($filepath)) {
            try {
                // Write the session file
                unlink($filepath);
                \Drupal::logger('phantomjs_capture')->notice("Deleted old session file at " . $filepath);
                file_put_contents($filepath, $session_id);
                return true;
            } catch (Exception $e) {
                // Error
                \Drupal::logger('phantomjs_capture')->error("Unable to delete previous session file at " . $filepath);
                return false;
            }
        // Write the session file
        } else {
            file_put_contents($filepath, $session_id);
            return true;
        }

    // If the directory does not exist return false
    } else {
        return false;
    }
  }

  /**
   * Delete the current users session file.
   * @return boolean
   */
  public function deleteSession($session_name) {

    // Get the protected directory path
    $base_path = $_SERVER["DOCUMENT_ROOT"];
    $protected_dir = $this->config->get('protected_dir');

    if (isset($protected_dir) == TRUE) {

        // Create the filepath to the new file for the protected directory
        $protected_path = $base_path . $protected_dir;
        $filepath = $protected_path . $session_name;

        // If the file exists delete it and replace
        if (file_exists($filepath)) {
            try {
                // Write the session file
                unlink($filepath);
                return true;
            } catch (Exception $e) {
                // Error
                \Drupal::logger('phantomjs_capture')->error("Unable to delete previous session file at " . $filepath);
                return false;
            }
        // Write the session file
        } else {
            unlink($filepath);
            return true;
        }

    // If the directory does not exist return false
    } else {
        return false;
    }
  }

  /**
   * Return the version of PhantomJS binary on the server.
   * @return mixed
   */
  public function getVersion() {
    $binary = $this->config->get('binary');

    if ($this->binaryExists($binary)) {
      $output = [];
      exec($binary . ' -v', $output);
      return $output[0];
    }

    return FALSE;
  }

  /**
   * Captures a screen shot using PhantomJS by calling the program.
   *
   * @param Url $url
   *   An instance of Drupal\Core\Url, the address you want to capture.
   * @param string $destination
   *   The destination for the file (e.g. public://screenshot).
   * @param string $filename
   *   The filename to store the file as.
   * @param string $session
   *   The session name of the current user
   * @param string $host
   *   The hostname of the site
   * @param string $element
   *   The id of the DOM element to render in the document.
   *
   * @return bool
   *   Returns TRUE if the screen shot was taken else FALSE on error.
   */
  public function capture(Url $url, $destination, $filename, $session, $host, $element = NULL) {

    // Get the settings from the settings form
    $binary = $this->config->get('binary');
    $script = $this->fileSystem->realpath($this->config->get('script'));

    // If the binary does not exist
    if (!$this->binaryExists($binary)) {
      throw new FileNotFoundException($binary);
    }

    // Check that destination is writable.
    // @todo: would be nice to throw an exception instead of return FALSE
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $this->loggerFactory->get('phantomjs_capture')->error('The directory %directory for the file %filename could not be created or is not accessible.', ['%directory' => $destination, '%filename' => $filename]);
      return FALSE;
    }

    // Create the destination string
    $url = $url->toUriString();
    $destination = $this->fileSystem->realpath($destination . '/' . $filename);

    $output = [];

    // Run the command
    exec($binary . ' ' . $script . ' "' . $url . '" ' . $destination . ' ' . $session . ' ' . $host . ' ' . $element, $output);

    // Check that PhantomJS was able to load the page.
    if ($output[0] == '500') {
      $this->loggerFactory->get('phantomjs_capture')->error('PhantomJS could not capture the URL %url.', ['%url' => $url]);
      return FALSE;
    }

    return TRUE;
  }
}
