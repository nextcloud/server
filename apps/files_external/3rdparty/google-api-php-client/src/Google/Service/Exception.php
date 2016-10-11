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

class Google_Service_Exception extends Google_Exception implements Google_Task_Retryable
{
  /**
   * Optional list of errors returned in a JSON body of an HTTP error response.
   */
  protected $errors = array();

  /**
   * @var array $retryMap Map of errors with retry counts.
   */
  private $retryMap = array();

  /**
   * Override default constructor to add the ability to set $errors and a retry
   * map.
   *
   * @param string $message
   * @param int $code
   * @param Exception|null $previous
   * @param [{string, string}] errors List of errors returned in an HTTP
   * response.  Defaults to [].
   * @param array|null $retryMap Map of errors with retry counts.
   */
  public function __construct(
      $message,
      $code = 0,
      Exception $previous = null,
      $errors = array(),
      array $retryMap = null
  ) {
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      parent::__construct($message, $code, $previous);
    } else {
      parent::__construct($message, $code);
    }

    $this->errors = $errors;

    if (is_array($retryMap)) {
      $this->retryMap = $retryMap;
    }
  }

  /**
   * An example of the possible errors returned.
   *
   * {
   *   "domain": "global",
   *   "reason": "authError",
   *   "message": "Invalid Credentials",
   *   "locationType": "header",
   *   "location": "Authorization",
   * }
   *
   * @return [{string, string}] List of errors return in an HTTP response or [].
   */
  public function getErrors()
  {
    return $this->errors;
  }

  /**
   * Gets the number of times the associated task can be retried.
   *
   * NOTE: -1 is returned if the task can be retried indefinitely
   *
   * @return integer
   */
  public function allowedRetries()
  {
    if (isset($this->retryMap[$this->code])) {
      return $this->retryMap[$this->code];
    }

    $errors = $this->getErrors();

    if (!empty($errors) && isset($errors[0]['reason']) &&
        isset($this->retryMap[$errors[0]['reason']])) {
      return $this->retryMap[$errors[0]['reason']];
    }

    return 0;
  }
}
