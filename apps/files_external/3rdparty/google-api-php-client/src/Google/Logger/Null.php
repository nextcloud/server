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
 * Null logger based on the PSR-3 standard.
 *
 * This logger simply discards all messages.
 */
class Google_Logger_Null extends Google_Logger_Abstract
{
  /**
   * {@inheritdoc}
   */
  public function shouldHandle($level)
  {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  protected function write($message, array $context = array())
  {
  }
}
