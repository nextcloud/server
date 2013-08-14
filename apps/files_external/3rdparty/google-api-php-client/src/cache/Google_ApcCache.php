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

/**
 * A persistent storage class based on the APC cache, which is not
 * really very persistent, as soon as you restart your web server
 * the storage will be wiped, however for debugging and/or speed
 * it can be useful, kinda, and cache is a lot cheaper then storage.
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class googleApcCache extends Google_Cache {

  public function __construct() {
    if (! function_exists('apc_add')) {
      throw new Google_CacheException("Apc functions not available");
    }
  }

  private function isLocked($key) {
    if ((@apc_fetch($key . '.lock')) === false) {
      return false;
    }
    return true;
  }

  private function createLock($key) {
    // the interesting thing is that this could fail if the lock was created in the meantime..
    // but we'll ignore that out of convenience
    @apc_add($key . '.lock', '', 5);
  }

  private function removeLock($key) {
    // suppress all warnings, if some other process removed it that's ok too
    @apc_delete($key . '.lock');
  }

  private function waitForLock($key) {
    // 20 x 250 = 5 seconds
    $tries = 20;
    $cnt = 0;
    do {
      // 250 ms is a long time to sleep, but it does stop the server from burning all resources on polling locks..
      usleep(250);
      $cnt ++;
    } while ($cnt <= $tries && $this->isLocked($key));
    if ($this->isLocked($key)) {
      // 5 seconds passed, assume the owning process died off and remove it
      $this->removeLock($key);
    }
  }

   /**
   * @inheritDoc
   */
  public function get($key, $expiration = false) {

    if (($ret = @apc_fetch($key)) === false) {
      return false;
    }
    if (!$expiration || (time() - $ret['time'] > $expiration)) {
      $this->delete($key);
      return false;
    }
    return unserialize($ret['data']);
  }

  /**
   * @inheritDoc
   */
  public function set($key, $value) {
    if (@apc_store($key, array('time' => time(), 'data' => serialize($value))) == false) {
      throw new Google_CacheException("Couldn't store data");
    }
  }

  /**
   * @inheritDoc
   * @param String $key
   */
  public function delete($key) {
    @apc_delete($key);
  }
}
