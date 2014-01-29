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
 
require_once "Google/Cache/Abstract.php";
require_once "Google/Cache/Exception.php";

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
  public function __construct(Google_Client $client)
  {
    if (! function_exists('apc_add') ) {
      throw new Google_Cache_Exception("Apc functions not available");
    }
  }

   /**
   * @inheritDoc
   */
  public function get($key, $expiration = false)
  {
    $ret = apc_fetch($key);
    if ($ret === false) {
      return false;
    }
    if (is_numeric($expiration) && (time() - $ret['time'] > $expiration)) {
      $this->delete($key);
      return false;
    }
    return $ret['data'];
  }

  /**
   * @inheritDoc
   */
  public function set($key, $value)
  {
    $rc = apc_store($key, array('time' => time(), 'data' => $value));
    if ($rc == false) {
      throw new Google_Cache_Exception("Couldn't store data");
    }
  }

  /**
   * @inheritDoc
   * @param String $key
   */
  public function delete($key)
  {
    apc_delete($key);
  }
}
