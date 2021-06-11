<?php

namespace Drupal\cf_purge\Service;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class PurgeService
 *
 * @package Drupal\cf_purge\Service
 */
class PurgeService {

  /**
   * @var boolean
   */
  protected $enabled;

  /**
   * @var string
   */
  protected $email;

  /**
   * @var string
   */
  protected $apiKey;

  /**
   * @var string
   */
  protected $zoneId;

  /**
   * @var array
   */
  protected $purgeSuccess;

  /**
   * @var array
   */
  protected $purgeError;

  /**
   * @var array
   */
  protected $errors;

  /**
   * @param ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory)
  {
    $cf_purge_settings = $configFactory->get('cf_purge.settings');

    $this->enabled = $cf_purge_settings->get('cloudflare_enable');
    $this->email = $cf_purge_settings->get('cloudflare_purge_email');
    $this->apiKey = $cf_purge_settings->get('cloudflare_purge_api_key');
    $this->zoneId = $cf_purge_settings->get('cloudflare_purge_zone_id');

    $this->validateConfig();
  }

  /**
   * Validates the config used for Cloudflare.
   */
  private function validateConfig() {
    if (!$this->enabled) {
      $this->errors[] = 'Cloudflare purging is not enabled';
    }

    if (empty($this->email)) {
      $this->errors[] = 'E-mail is empty';
    }
    else {
      if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
        $this->errors[] = 'E-mail is not valid';
      }
    }

    if (empty($this->apiKey)) {
      $this->errors[] = 'API Key is empty';
    }

    if (empty($this->zoneId)) {
      $this->errors[] = 'Zone Id is empty';
    }

    if (!empty($this->errors)) {
      $errorMessage = implode(', ', $this->errors);
      \Drupal::logger('cf_purge')->error('Config error: ' . $errorMessage);
    }
  }

  /**
   * Get an array of failed purges.
   */
  public function getPurgedErrors(): array
  {
    return $this->purgeError;
  }

  /**
   * Purges and array of urls from Cloudflare.
   *
   * @param array $urlList
   *   An array of absolute URls to purge
   */
  public function purgeByUrl(array $urlList) {
    if (!$this->enabled) {
      return;
    }

    if (!empty($this->errors)) {
      return;
    }

    if (empty($urlList)) {
      return;
    }

    try {
      $response = $this->processRequest(['files' => $urlList]);

      if ($response === TRUE) {
        \Drupal::logger('cf_purge')->notice('Purged url/s from cache');
      }
      else {
        \Drupal::logger('cf_purge')->error('Unable to purge url/s from cache', ['failed' => $this->getPurgedErrors()]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('cf_purge')->error('Unable to purge url/s from cache');
    }
  }

  /**
   * Purges all from Cloudflare.
   */
  public function purgeAll() {
    if (!$this->enabled) {
      return;
    }

    if (!empty($this->errors)) {
      return;
    }

    try {
      $response = $this->processRequest(['purge_everything' => true]);

      if ($response === TRUE) {
        \Drupal::logger('cf_purge')->notice('Purged everything from cache');
      }
      else {
        \Drupal::logger('cf_purge')->error('Unable to purge everything from cache');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('cf_purge')->error('Unable to purge everything from cache');
    }
  }

  /**
   * Prepares and sends request to Cloudflare API.
   * @param array $json
   * @return mixed
   */
  private function processRequest(array $json) {
    // Cloudflare API limits to 30 URLs when file purging so we batch the process in 30 URL chunks.
    if (array_key_exists('files', $json)) {
      $chunks = array_chunk($json['files'], 30);

      foreach ($chunks as $chunk) {
        $response = $this->sendRequestToAPI(['files' => $chunk]);

        if ($response->success === TRUE) {
          $this->purgeSuccess[] = [
            'message' => 'Purged url/s from cache',
          ];
        }
        else {
          $this->purgeError[] = [
            'message' => 'Unable to purge all url/s from cache',
            'urls' => [
              $chunk
            ]
          ];
        }
      }

      return (!empty($this->purgeError) ? FALSE : TRUE);
    }

    // Send response to the API if we don't need to batch process.
    $response = $this->sendRequestToAPI($json);
    $response = $response->success;

    return $response;
  }

  /**
   * Sends request to Cloudflare API.
   * @param array $json
   * @return mixed
   */
  private function sendRequestToAPI(array $json) {
    $client = \Drupal::httpClient();

    $url = 'https://api.cloudflare.com/client/v4/zones/' . $this->zoneId . '/purge_cache';

    $request = $client->post($url, [
      'headers' => [
        'X-Auth-Email' => $this->email,
        'X-Auth-Key' => $this->apiKey
      ],
      'json' => $json
    ]);

    $response = json_decode($request->getBody());

    return $response;
  }

}
