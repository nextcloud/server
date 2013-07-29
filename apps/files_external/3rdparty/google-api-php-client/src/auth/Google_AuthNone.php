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
 * Do-nothing authentication implementation, use this if you want to make un-authenticated calls
 * @author Chris Chabot <chabotc@google.com>
 * @author Chirag Shah <chirags@google.com>
 */
class Google_AuthNone extends Google_Auth {
  public $key = null;

  public function __construct() {
    global $apiConfig;
    if (!empty($apiConfig['developer_key'])) {
      $this->setDeveloperKey($apiConfig['developer_key']);
    }
  }

  public function setDeveloperKey($key) {$this->key = $key;}
  public function authenticate($service) {/*noop*/}
  public function setAccessToken($accessToken) {/* noop*/}
  public function getAccessToken() {return null;}
  public function createAuthUrl($scope) {return null;}
  public function refreshToken($refreshToken) {/* noop*/}
  public function revokeToken() {/* noop*/}

  public function sign(Google_HttpRequest $request) {
    if ($this->key) {
      $request->setUrl($request->getUrl() . ((strpos($request->getUrl(), '?') === false) ? '?' : '&')
          . 'key='.urlencode($this->key));
    }
    return $request;
  }
}
