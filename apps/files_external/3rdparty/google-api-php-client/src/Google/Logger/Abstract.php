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
 * Abstract logging class based on the PSR-3 standard.
 *
 * NOTE: We don't implement `Psr\Log\LoggerInterface` because we need to
 * maintain PHP 5.2 support.
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 */
abstract class Google_Logger_Abstract
{
  /**
   * Default log format
   */
  const DEFAULT_LOG_FORMAT = "[%datetime%] %level%: %message% %context%\n";
  /**
   * Default date format
   *
   * Example: 16/Nov/2014:03:26:16 -0500
   */
  const DEFAULT_DATE_FORMAT = 'd/M/Y:H:i:s O';

  /**
   * System is unusable
   */
  const EMERGENCY = 'emergency';
  /**
   * Action must be taken immediately
   *
   * Example: Entire website down, database unavailable, etc. This should
   * trigger the SMS alerts and wake you up.
   */
  const ALERT = 'alert';
  /**
   * Critical conditions
   *
   * Example: Application component unavailable, unexpected exception.
   */
  const CRITICAL = 'critical';
  /**
   * Runtime errors that do not require immediate action but should typically
   * be logged and monitored.
   */
  const ERROR = 'error';
  /**
   * Exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   */
  const WARNING = 'warning';
  /**
   * Normal but significant events.
   */
  const NOTICE = 'notice';
  /**
   * Interesting events.
   *
   * Example: User logs in, SQL logs.
   */
  const INFO = 'info';
  /**
   * Detailed debug information.
   */
  const DEBUG = 'debug';

  /**
   * @var array $levels Logging levels
   */
  protected static $levels = array(
      self::EMERGENCY  => 600,
      self::ALERT => 550,
      self::CRITICAL => 500,
      self::ERROR => 400,
      self::WARNING => 300,
      self::NOTICE => 250,
      self::INFO => 200,
      self::DEBUG => 100,
  );

  /**
   * @var integer $level The minimum logging level
   */
  protected $level = self::DEBUG;

  /**
   * @var string $logFormat The current log format
   */
  protected $logFormat = self::DEFAULT_LOG_FORMAT;
  /**
   * @var string $dateFormat The current date format
   */
  protected $dateFormat = self::DEFAULT_DATE_FORMAT;

  /**
   * @var boolean $allowNewLines If newlines are allowed
   */
  protected $allowNewLines = false;

  /**
   * @param Google_Client $client  The current Google client
   */
  public function __construct(Google_Client $client)
  {
    $this->setLevel(
        $client->getClassConfig('Google_Logger_Abstract', 'level')
    );

    $format = $client->getClassConfig('Google_Logger_Abstract', 'log_format');
    $this->logFormat = $format ? $format : self::DEFAULT_LOG_FORMAT;

    $format = $client->getClassConfig('Google_Logger_Abstract', 'date_format');
    $this->dateFormat = $format ? $format : self::DEFAULT_DATE_FORMAT;

    $this->allowNewLines = (bool) $client->getClassConfig(
        'Google_Logger_Abstract',
        'allow_newlines'
    );
  }

  /**
   * Sets the minimum logging level that this logger handles.
   *
   * @param integer $level
   */
  public function setLevel($level)
  {
    $this->level = $this->normalizeLevel($level);
  }

  /**
   * Checks if the logger should handle messages at the provided level.
   *
   * @param  integer $level
   * @return boolean
   */
  public function shouldHandle($level)
  {
    return $this->normalizeLevel($level) >= $this->level;
  }

  /**
   * System is unusable.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function emergency($message, array $context = array())
  {
    $this->log(self::EMERGENCY, $message, $context);
  }

  /**
   * Action must be taken immediately.
   *
   * Example: Entire website down, database unavailable, etc. This should
   * trigger the SMS alerts and wake you up.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function alert($message, array $context = array())
  {
    $this->log(self::ALERT, $message, $context);
  }

  /**
   * Critical conditions.
   *
   * Example: Application component unavailable, unexpected exception.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function critical($message, array $context = array())
  {
    $this->log(self::CRITICAL, $message, $context);
  }

  /**
   * Runtime errors that do not require immediate action but should typically
   * be logged and monitored.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function error($message, array $context = array())
  {
    $this->log(self::ERROR, $message, $context);
  }

  /**
   * Exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function warning($message, array $context = array())
  {
    $this->log(self::WARNING, $message, $context);
  }

  /**
   * Normal but significant events.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function notice($message, array $context = array())
  {
    $this->log(self::NOTICE, $message, $context);
  }

  /**
   * Interesting events.
   *
   * Example: User logs in, SQL logs.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function info($message, array $context = array())
  {
    $this->log(self::INFO, $message, $context);
  }

  /**
   * Detailed debug information.
   *
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function debug($message, array $context = array())
  {
    $this->log(self::DEBUG, $message, $context);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level    The log level
   * @param string $message The log message
   * @param array $context  The log context
   */
  public function log($level, $message, array $context = array())
  {
    if (!$this->shouldHandle($level)) {
      return false;
    }

    $levelName = is_int($level) ? array_search($level, self::$levels) : $level;
    $message = $this->interpolate(
        array(
            'message' => $message,
            'context' => $context,
            'level' => strtoupper($levelName),
            'datetime' => new DateTime(),
        )
    );

    $this->write($message);
  }

  /**
   * Interpolates log variables into the defined log format.
   *
   * @param  array $variables The log variables.
   * @return string
   */
  protected function interpolate(array $variables = array())
  {
    $template = $this->logFormat;

    if (!$variables['context']) {
      $template = str_replace('%context%', '', $template);
      unset($variables['context']);
    } else {
      $this->reverseJsonInContext($variables['context']);
    }

    foreach ($variables as $key => $value) {
      if (strpos($template, '%'. $key .'%') !== false) {
        $template = str_replace(
            '%' . $key . '%',
            $this->export($value),
            $template
        );
      }
    }

    return $template;
  }

  /**
   * Reverses JSON encoded PHP arrays and objects so that they log better.
   *
   * @param array $context The log context
   */
  protected function reverseJsonInContext(array &$context)
  {
    if (!$context) {
      return;
    }

    foreach ($context as $key => $val) {
      if (!$val || !is_string($val) || !($val[0] == '{' || $val[0] == '[')) {
        continue;
      }

      $json = @json_decode($val);
      if (is_object($json) || is_array($json)) {
        $context[$key] = $json;
      }
    }
  }

  /**
   * Exports a PHP value for logging to a string.
   *
   * @param mixed $value The value to
   */
  protected function export($value)
  {
    if (is_string($value)) {
      if ($this->allowNewLines) {
        return $value;
      }

      return preg_replace('/[\r\n]+/', ' ', $value);
    }

    if (is_resource($value)) {
      return sprintf(
          'resource(%d) of type (%s)',
          $value,
          get_resource_type($value)
      );
    }

    if ($value instanceof DateTime) {
      return $value->format($this->dateFormat);
    }

    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

      if ($this->allowNewLines) {
        $options |= JSON_PRETTY_PRINT;
      }

      return @json_encode($value, $options);
    }

    return str_replace('\\/', '/', @json_encode($value));
  }

  /**
   * Converts a given log level to the integer form.
   *
   * @param  mixed $level   The logging level
   * @return integer $level The normalized level
   * @throws Google_Logger_Exception If $level is invalid
   */
  protected function normalizeLevel($level)
  {
    if (is_int($level) && array_search($level, self::$levels) !== false) {
      return $level;
    }

    if (is_string($level) && isset(self::$levels[$level])) {
      return self::$levels[$level];
    }

    throw new Google_Logger_Exception(
        sprintf("Unknown LogLevel: '%s'", $level)
    );
  }

  /**
   * Writes a message to the current log implementation.
   *
   * @param string $message The message
   */
  abstract protected function write($message);
}
