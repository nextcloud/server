Google APIs Client Library for PHP
=====================================

== Description
The Google API Client Library enables you to work with Google APIs such as Google+, Drive, Tasks, or Latitude on your server.

Requirements:
  PHP 5.2.x or higher [http://www.php.net/]
  PHP Curl extension [http://www.php.net/manual/en/intro.curl.php]
  PHP JSON extension [http://php.net/manual/en/book.json.php]

Project page:
  http://code.google.com/p/google-api-php-client

OAuth 2 instructions:
  http://code.google.com/p/google-api-php-client/wiki/OAuth2

Report a defect or feature request here:
  http://code.google.com/p/google-api-php-client/issues/entry

Subscribe to project updates in your feed reader:
  http://code.google.com/feeds/p/google-api-php-client/updates/basic

Supported sample applications:
  http://code.google.com/p/google-api-php-client/wiki/Samples

== Basic Example
  <?php
  require_once 'path/to/src/Google_Client.php';
  require_once 'path/to/src/contrib/apiBooksService.php';

  $client = new Google_Client();
  $service = new Google_BooksService($client);

  $optParams = array('filter' => 'free-ebooks');
  $results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

  foreach ($results['items'] as $item) {
    print($item['volumeInfo']['title'] . '<br>');
  }
