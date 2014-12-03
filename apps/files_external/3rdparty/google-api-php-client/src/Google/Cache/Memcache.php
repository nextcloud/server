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

/**
 * A persistent storage class based on the memcache, which is not
 * really very persistent, as soon as you restart your memcache daemon
 * the storage will be wiped.
 *
 * Will use either the memcache or memcached extensions, preferring
 * memcached.
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class Google_Cache_Memcache extends Google_Cache_Abstract
{
  private $connection = false;
  private $mc = false;
  private $host;
  private $port;

  public function __construct(Google_Client $client)
  {
    if (!function_exists('memcache_connect') && !class_exists("Memcached")) {
      throw new Google_Cache_Exception("Memcache functions not available");
    }
    if ($client->isAppEngine()) {
      // No credentials needed for GAE.
      $this->mc = new Memcached();
      $this->connection = true;
    } else {
      $this->host = $client->getClassConfig($this, 'host');
      $this->port = $client->getClassConfig($this, 'port');
      if (empty($this->host) || (empty($this->port) && (string) $this->port != "0")) {
        throw new Google_Cache_Exception("You need to supply a valid memcache host and port");
      }
    }
  }
  
  /**
   * @inheritDoc
   */
  public function get($key, $expiration = false)
  {
    $this->connect();
    $ret = false;
    if ($this->mc) {
      $ret = $this->mc->get($key);
    } else {
      $ret = memcache_get($this->connection, $key);
    }
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
   * @param string $key
   * @param string $value
   * @throws Google_Cache_Exception
   */
  public function set($key, $value)
  {
    $this->connect();
    // we store it with the cache_time default expiration so objects will at
    // least get cleaned eventually.
    $data = array('time' => time(), 'data' => $value);
    $rc = false;
    if ($this->mc) {
      $rc = $this->mc->set($key, $data);
    } else {
      $rc = memcache_set($this->connection, $key, $data, false);
    }
    if ($rc == false) {
      throw new Google_Cache_Exception("Couldn't store data in cache");
    }
  }

  /**
   * @inheritDoc
   * @param String $key
   */
  public function delete($key)
  {
    $this->connect();
    if ($this->mc) {
      $this->mc->delete($key, 0);
    } else {
      memcache_delete($this->connection, $key, 0);
    }
  }

  /**
   * Lazy initialiser for memcache connection. Uses pconnect for to take 
   * advantage of the persistence pool where possible. 
   */
  private function connect()
  {
    if ($this->connection) {
      return;
    }

    if (class_exists("Memcached")) {
      $this->mc = new Memcached();
      $this->mc->addServer($this->host, $this->port);
       $this->connection = true;
    } else {
      $this->connection = memcache_pconnect($this->host, $this->port);
    }
    
    if (! $this->connection) {
      throw new Google_Cache_Exception("Couldn't connect to memcache server");
    }
  }
}
