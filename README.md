# CF Purge - Cloudflare cache purge service.

This module allows the programmatic purging of the Cloudflare cache.

## Getting started.

Add the following to the `repositories` section of your site's `composer.json` file

```javascript
    {
      "type": "vcs",
      "url": "https://github.com/teamdeeson/cf_purge.git"
    }
```

### Add the following config to settings

```php
<?php

/**
 * @file
 * Cloudflare configuration.
 *
 * If you use the cf_purge Cloudflare purge module, you should configure it here.
 */

$config['cf_purge.settings']['cloudflare_enable'] = isset($_ENV['CLOUDFLARE_PURGE_ENABLED']) ? $_ENV['CLOUDFLARE_PURGE_ENABLED'] : FALSE;
$config['cf_purge.settings']['cloudflare_purge_email'] = isset($_ENV['CLOUDFLARE_EMAIL']) ? $_ENV['CLOUDFLARE_EMAIL'] : '';
$config['cf_purge.settings']['cloudflare_purge_api_key'] = isset($_ENV['CLOUDFLARE_API_KEY']) ? $_ENV['CLOUDFLARE_API_KEY'] : '';
$config['cf_purge.settings']['cloudflare_purge_zone_id'] = isset($_ENV['CLOUDFLARE_ZONE_ID']) ? $_ENV['CLOUDFLARE_ZONE_ID'] : '';

// We disable the Cloudflare purging on local environments
if (SETTINGS_ENVIRONMENT === D_ENVIRONMENT_LOCAL) {
  $config['cf_purge.settings']['cloudflare_enable'] = FALSE;
}

```

You can then run `composer require teamdeeson/cf_purge` to download the module and `drush @docker en cf_purge` to enable the module.

## Purging by URL.

To purge a list of URLs from Cloudflare cache, there is this function available:

```php
    $urls = [
        'http://www.example.tld' // homepage
        'http://www.example.tld/page' // other pages
    ];

    $purge_service = \Drupal::service('cf_purge.service');
    $purge_service->purgeByUrl($urls);
```

## Purging everything.

To purge the whole Cloudflare cache, there is this function available:

```php
    $purge_service = \Drupal::service('cf_purge.service');
    $purge_service->purgeAll();
```

## Drush command utility.

There is a drush command file which allows you to purge the whole cache or purge a single url.

To purge the whole cache you can use:

`drush @docker cf-purge:purge-all`

The purge a url:

`drush @docker cf-purge:purge-url [url]`

Where:

* url is single URL.

e.g. the following drush command purges a URL from the Cloudflare cache.

`drush @docker cf-purge:purge-url http://www.example.tld`
