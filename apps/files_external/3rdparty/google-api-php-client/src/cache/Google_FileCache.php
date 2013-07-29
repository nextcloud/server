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

/*
 * This class implements a basic on disk storage. While that does
 * work quite well it's not the most elegant and scalable solution.
 * It will also get you into a heap of trouble when you try to run
 * this in a clustered environment. In those cases please use the
 * MySql back-end
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class Google_FileCache extends Google_Cache {
  private $path;

  public function __construct() {
    global $apiConfig;
    $this->path = $apiConfig['ioFileCache_directory'];
  }

  private function isLocked($storageFile) {
    // our lock file convention is simple: /the/file/path.lock
    return file_exists($storageFile . '.lock');
  }

  private function createLock($storageFile) {
    $storageDir = dirname($storageFile);
    if (! is_dir($storageDir)) {
      // @codeCoverageIgnoreStart
      if (! @mkdir($storageDir, 0755, true)) {
        // make sure the failure isn't because of a concurrency issue
        if (! is_dir($storageDir)) {
          throw new Google_CacheException("Could not create storage directory: $storageDir");
        }
      }
      // @codeCoverageIgnoreEnd
    }
    @touch($storageFile . '.lock');
  }

  private function removeLock($storageFile) {
    // suppress all warnings, if some other process removed it that's ok too
    @unlink($storageFile . '.lock');
  }

  private function waitForLock($storageFile) {
    // 20 x 250 = 5 seconds
    $tries = 20;
    $cnt = 0;
    do {
      // make sure PHP picks up on file changes. This is an expensive action but really can't be avoided
      clearstatcache();
      // 250 ms is a long time to sleep, but it does stop the server from burning all resources on polling locks..
      usleep(250);
      $cnt ++;
    } while ($cnt <= $tries && $this->isLocked($storageFile));
    if ($this->isLocked($storageFile)) {
      // 5 seconds passed, assume the owning process died off and remove it
      $this->removeLock($storageFile);
    }
  }

  private function getCacheDir($hash) {
    // use the first 2 characters of the hash as a directory prefix
    // this should prevent slowdowns due to huge directory listings
    // and thus give some basic amount of scalability
    return $this->path . '/' . substr($hash, 0, 2);
  }

  private function getCacheFile($hash) {
    return $this->getCacheDir($hash) . '/' . $hash;
  }

  public function get($key, $expiration = false) {
    $storageFile = $this->getCacheFile(md5($key));
    // See if this storage file is locked, if so we wait up to 5 seconds for the lock owning process to
    // complete it's work. If the lock is not released within that time frame, it's cleaned up.
    // This should give us a fair amount of 'Cache Stampeding' protection
    if ($this->isLocked($storageFile)) {
      $this->waitForLock($storageFile);
    }
    if (file_exists($storageFile) && is_readable($storageFile)) {
      $now = time();
      if (! $expiration || (($mtime = @filemtime($storageFile)) !== false && ($now - $mtime) < $expiration)) {
        if (($data = @file_get_contents($storageFile)) !== false) {
          $data = unserialize($data);
          return $data;
        }
      }
    }
    return false;
  }

  public function set($key, $value) {
    $storageDir = $this->getCacheDir(md5($key));
    $storageFile = $this->getCacheFile(md5($key));
    if ($this->isLocked($storageFile)) {
      // some other process is writing to this file too, wait until it's done to prevent hiccups
      $this->waitForLock($storageFile);
    }
    if (! is_dir($storageDir)) {
      if (! @mkdir($storageDir, 0755, true)) {
        throw new Google_CacheException("Could not create storage directory: $storageDir");
      }
    }
    // we serialize the whole request object, since we don't only want the
    // responseContent but also the postBody used, headers, size, etc
    $data = serialize($value);
    $this->createLock($storageFile);
    if (! @file_put_contents($storageFile, $data)) {
      $this->removeLock($storageFile);
      throw new Google_CacheException("Could not store data in the file");
    }
    $this->removeLock($storageFile);
  }

  public function delete($key) {
    $file = $this->getCacheFile(md5($key));
    if (! @unlink($file)) {
      throw new Google_CacheException("Cache file could not be deleted");
    }
  }
}
