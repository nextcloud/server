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

if (!class_exists('Google_Client')) {
  require_once dirname(__FILE__) . '/../autoload.php';
}

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

  /**
   * @var Google_Client the current client
   */
  private $client;

  public function __construct(Google_Client $client)
  {
    $this->client = $client;
    $this->path = $this->client->getClassConfig($this, 'directory');
  }

  public function get($key, $expiration = false)
  {
    $storageFile = $this->getCacheFile($key);
    $data = false;

    if (!file_exists($storageFile)) {
      $this->client->getLogger()->debug(
          'File cache miss',
          array('key' => $key, 'file' => $storageFile)
      );
      return false;
    }

    if ($expiration) {
      $mtime = filemtime($storageFile);
      if ((time() - $mtime) >= $expiration) {
        $this->client->getLogger()->debug(
            'File cache miss (expired)',
            array('key' => $key, 'file' => $storageFile)
        );
        $this->delete($key);
        return false;
      }
    }

    if ($this->acquireReadLock($storageFile)) {
      if (filesize($storageFile) > 0) {
        $data = fread($this->fh, filesize($storageFile));
        $data =  unserialize($data);
      } else {
        $this->client->getLogger()->debug(
            'Cache file was empty',
            array('file' => $storageFile)
        );
      }
      $this->unlock($storageFile);
    }

    $this->client->getLogger()->debug(
        'File cache hit',
        array('key' => $key, 'file' => $storageFile, 'var' => $data)
    );

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

      $this->client->getLogger()->debug(
          'File cache set',
          array('key' => $key, 'file' => $storageFile, 'var' => $value)
      );
    } else {
      $this->client->getLogger()->notice(
          'File cache set failed',
          array('key' => $key, 'file' => $storageFile)
      );
    }
  }

  public function delete($key)
  {
    $file = $this->getCacheFile($key);
    if (file_exists($file) && !unlink($file)) {
      $this->client->getLogger()->error(
          'File cache delete failed',
          array('key' => $key, 'file' => $file)
      );
      throw new Google_Cache_Exception("Cache file could not be deleted");
    }

    $this->client->getLogger()->debug(
        'File cache delete',
        array('key' => $key, 'file' => $file)
    );
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
      if (! mkdir($storageDir, 0700, true)) {
        $this->client->getLogger()->error(
            'File cache creation failed',
            array('dir' => $storageDir)
        );
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
      $this->client->getLogger()->notice(
          'File cache write lock failed',
          array('file' => $storageFile)
      );
      $this->delete($storageFile);
    }
    return $rc;
  }

  private function acquireLock($type, $storageFile)
  {
    $mode = $type == LOCK_EX ? "w" : "r";
    $this->fh = fopen($storageFile, $mode);
    if (!$this->fh) {
      $this->client->getLogger()->error(
          'Failed to open file during lock acquisition',
          array('file' => $storageFile)
      );
      return false;
    }
    if ($type == LOCK_EX) {
      chmod($storageFile, 0600);
    }
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
