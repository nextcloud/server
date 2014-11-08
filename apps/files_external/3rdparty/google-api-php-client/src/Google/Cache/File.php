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

require_once "Google/Cache/Abstract.php";
require_once "Google/Cache/Exception.php";

/*
 * This class implements a basic on disk storage. While that does
 * work quite well it's not the most elegant and scalable solution.
 * It will also get you into a heap of trouble when you try to run
 * this in a clustered environment.
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class Google_Cache_File extends Google_Cache_Abstract
{
  const MAX_LOCK_RETRIES = 10;
  private $path;
  private $fh;

  public function __construct(Google_Client $client)
  {
    $this->path = $client->getClassConfig($this, 'directory');
  }
  
  public function get($key, $expiration = false)
  {
    $storageFile = $this->getCacheFile($key);
    $data = false;
    
    if (!file_exists($storageFile)) {
      return false;
    }

    if ($expiration) {
      $mtime = filemtime($storageFile);
      if ((time() - $mtime) >= $expiration) {
        $this->delete($key);
        return false;
      }
    }

    if ($this->acquireReadLock($storageFile)) {
      $data = fread($this->fh, filesize($storageFile));
      $data =  unserialize($data);
      $this->unlock($storageFile);
    }

    return $data;
  }

  public function set($key, $value)
  {
    $storageFile = $this->getWriteableCacheFile($key);
    if ($this->acquireWriteLock($storageFile)) {
      // We serialize the whole request object, since we don't only want the
      // responseContent but also the postBody used, headers, size, etc.
      $data = serialize($value);
      $result = fwrite($this->fh, $data);
      $this->unlock($storageFile);
    }
  }

  public function delete($key)
  {
    $file = $this->getCacheFile($key);
    if (file_exists($file) && !unlink($file)) {
      throw new Google_Cache_Exception("Cache file could not be deleted");
    }
  }
  
  private function getWriteableCacheFile($file)
  {
    return $this->getCacheFile($file, true);
  }

  private function getCacheFile($file, $forWrite = false)
  {
    return $this->getCacheDir($file, $forWrite) . '/' . md5($file);
  }
  
  private function getCacheDir($file, $forWrite)
  {
    // use the first 2 characters of the hash as a directory prefix
    // this should prevent slowdowns due to huge directory listings
    // and thus give some basic amount of scalability
    $storageDir = $this->path . '/' . substr(md5($file), 0, 2);
    if ($forWrite && ! is_dir($storageDir)) {
      if (! mkdir($storageDir, 0755, true)) {
        throw new Google_Cache_Exception("Could not create storage directory: $storageDir");
      }
    }
    return $storageDir;
  }
  
  private function acquireReadLock($storageFile)
  {
    return $this->acquireLock(LOCK_SH, $storageFile);
  }
  
  private function acquireWriteLock($storageFile)
  {
    $rc = $this->acquireLock(LOCK_EX, $storageFile);
    if (!$rc) {
      $this->delete($storageFile);
    }
    return $rc;
  }
  
  private function acquireLock($type, $storageFile)
  {
    $mode = $type == LOCK_EX ? "w" : "r";
    $this->fh = fopen($storageFile, $mode);
    $count = 0;
    while (!flock($this->fh, $type | LOCK_NB)) {
      // Sleep for 10ms.
      usleep(10000);
      if (++$count < self::MAX_LOCK_RETRIES) {
        return false;
      }
    }
    return true;
  }
  
  public function unlock($storageFile)
  {
    if ($this->fh) {
      flock($this->fh, LOCK_UN);
    }
  }
}
