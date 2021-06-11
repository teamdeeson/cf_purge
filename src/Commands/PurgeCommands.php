<?php

namespace Drupal\cf_purge\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class PurgeCommands extends DrushCommands {

  /**
   * Purges everything from the Cloudflare cache.
   *
   * @command cf-purge:purge-all
   * @aliases cf-purge-all
   */
  public function purgeAll() {
    try {
      $purge_service = \Drupal::service('cf_purge.service');
      $purge_service->purgeAll();
    }
    catch (\Exception $e) {
      $this->logger->error('ok', $e->getMessage());
    }
  }

  /**
   * Purge by URL from the Cloudflare cache.
   *
   * @command cf-purge:purge-url
   * @aliases cf-purge-url
   * @param $urls
   */
  public function purgeByURL($urls) {
    try {
      $purge_service = \Drupal::service('cf_purge.service');
      $purge_service->purgeByUrl([$urls]);
    }
    catch (\Exception $e) {
      $this->logger->error('ok', $e->getMessage());
    }
  }

}
