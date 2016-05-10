<?php
/*
 * Copyright 2014 Google Inc.
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
 * A blank storage class, for cases where caching is not
 * required.
 */
class Google_Cache_Null extends Google_Cache_Abstract
{
  public function __construct(Google_Client $client)
  {

  }

   /**
   * @inheritDoc
   */
  public function get($key, $expiration = false)
  {
    return false;
  }

  /**
   * @inheritDoc
   */
  public function set($key, $value)
  {
    // Nop.
  }

  /**
   * @inheritDoc
   * @param String $key
   */
  public function delete($key)
  {
    // Nop.
  }
}
