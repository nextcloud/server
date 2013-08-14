<?php
/*
 * Copyright 2008 Google Inc.
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

require_once "Google_FileCache.php";
require_once "Google_MemcacheCache.php";

/**
 * Abstract storage class
 *
 * @author Chris Chabot <chabotc@google.com>
 */
abstract class Google_Cache {

  /**
   * Retrieves the data for the given key, or false if they
   * key is unknown or expired
   *
   * @param String $key The key who's data to retrieve
   * @param boolean|int $expiration Expiration time in seconds
   *
   */
  abstract function get($key, $expiration = false);

  /**
   * Store the key => $value set. The $value is serialized
   * by this function so can be of any type
   *
   * @param string $key Key of the data
   * @param string $value data
   */
  abstract function set($key, $value);

  /**
   * Removes the key/data pair for the given $key
   *
   * @param String $key
   */
  abstract function delete($key);
}


