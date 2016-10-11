<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!class_exists('Google_Client')) {
  require_once dirname(__FILE__) . '/../autoload.php';
}

/**
 * A persistent storage class based on the APC cache, which is not
 * really very persistent, as soon as you restart your web server
 * the storage will be wiped, however for debugging and/or speed
 * it can be useful, and cache is a lot cheaper then storage.
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class Google_Cache_Apc extends Google_Cache_Abstract
{
  /**
   * @var Google_Client the current client
   */
  private $client;

  public function __construct(Google_Client $client)
  {
    if (! function_exists('apc_add') ) {
      $error = "Apc functions not available";

      $client->getLogger()->error($error);
      throw new Google_Cache_Exception($error);
    }

    $this->client = $client;
  }

   /**
   * @inheritDoc
   */
  public function get($key, $expiration = false)
  {
    $ret = apc_fetch($key);
    if ($ret === false) {
      $this->client->getLogger()->debug(
          'APC cache miss',
          array('key' => $key)
      );
      return false;
    }
    if (is_numeric($expiration) && (time() - $ret['time'] > $expiration)) {
      $this->client->getLogger()->debug(
          'APC cache miss (expired)',
          array('key' => $key, 'var' => $ret)
      );
      $this->delete($key);
      return false;
    }

    $this->client->getLogger()->debug(
        'APC cache hit',
        array('key' => $key, 'var' => $ret)
    );

    return $ret['data'];
  }

  /**
   * @inheritDoc
   */
  public function set($key, $value)
  {
    $var = array('time' => time(), 'data' => $value);
    $rc = apc_store($key, $var);

    if ($rc == false) {
      $this->client->getLogger()->error(
          'APC cache set failed',
          array('key' => $key, 'var' => $var)
      );
      throw new Google_Cache_Exception("Couldn't store data");
    }

    $this->client->getLogger()->debug(
        'APC cache set',
        array('key' => $key, 'var' => $var)
    );
  }

  /**
   * @inheritDoc
   * @param String $key
   */
  public function delete($key)
  {
    $this->client->getLogger()->debug(
        'APC cache delete',
        array('key' => $key)
    );
    apc_delete($key);
  }
}
