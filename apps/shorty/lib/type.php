<?php
/**
* @package shorty an ownCloud url shortener plugin
* @category internet
* @author Christian Reiner
* @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
* @license GNU Affero General Public license (AGPL)
* @link information 
* @link repository https://svn.christian-reiner.info/svn/app/oc/shorty
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the license, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.
* If not, see <http://www.gnu.org/licenses/>.
*
*/

/**
 * @file lib/type.php
 * Type handling, recognition and verification routines
 * @author Christian Reiner
 */

/**
 * @class OC_Shorty_Type
 * @brief Static 'namespace' class offering routines and constants used to handle type recognition and value verification
 * @access public
 * @author Christian Reiner
 */
class OC_Shorty_Type
{
  // the 'types' of values we deal with, actually more something like flavours
  const ID          = 'id';
  const STATUS      = 'status';
  const SORTKEY     = 'sortkey';
  const SORTVAL     = 'sortval';
  const STRING      = 'string';
  const URL         = 'url';
  const INTEGER     = 'integer';
  const FLOAT       = 'float';
  const DATE        = 'date';
  const TIMESTAMP   = 'timestamp';
  // a list of all valid list sorting codes
  static $SORTING = array (
    ''  =>'created DESC', // default
    'aa'=>'accessed', 'ad'=>'accessed DESC',
    'ca'=>'created',  'cd'=>'created DESC',
    'da'=>'until',    'dd'=>'until DESC',
    'ha'=>'clicks',   'hd'=>'clicks DESC',
    'ka'=>'id',       'kd'=>'id DESC',
    'sa'=>'status',   'sd'=>'status DESC',
    'ta'=>'title',    'td'=>'title DESC',
    'ua'=>'target',   'ud'=>'target DESC' );
  // a list of all valid user preferences
  static $PREFERENCE = array (
    'backend-type'        => OC_Shorty_Type::STRING,
    'backend-static-base' => OC_Shorty_Type::URL,
    'backend-bitly-user'  => OC_Shorty_Type::STRING,
    'backend-bitly-key'   => OC_Shorty_Type::STRING,
    'backend-google-key'  => OC_Shorty_Type::STRING,
    'backend-tinycc-user' => OC_Shorty_Type::STRING,
    'backend-tinycc-key'  => OC_Shorty_Type::STRING,
    'sms-control'         => OC_Shorty_Type::STRING,
    'list-sort-code'      => OC_Shorty_Type::SORTKEY,
  );
  // valid status for entries
  static $STATUS = array (
    'blocked',
    'private',
    'shared',
    'public',
    'deleted',
  );
  // a list of implemented backends
  static $BACKENDS = array (
    'none'    => ' [ none ] ',
    'static'  => 'static backend',
//     'bitly'   => 'bitly.com service',
//     'cligs'   => 'cli.gs service',
    'isgd'    => 'is.gd service',
    'google'  => 'goo.gl service',
//     'tinycc'  => 'tiny.cc service',
    'tinyurl' => 'ti.ny service',
  );
  // a list of all valid system settings
  static $SETTING = array (
    'backend-static-base' => OC_Shorty_Type::URL,
  );
  static $HTTPCODE = array (
    200 => 'Ok',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => '(unused)',
    307 => 'Temporary Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
  );

  /**
   * @method OC_Shorty_Type::validate
   * @brief Validates a given value against a type specific regular expression
   * Validates a given value according to the claimed type of the value.
   * Validation is done by matching the value against a type specific regular expression. 
   * @param value the value to be verified according to the specified type
   * @param type the type the value is said to belong to, important for verification
   * @param strict flag indicating if the verification should be done strict, that is if an exception should be thrown in case of a failure
   * @returns the value itself in case of a positive validation, NULL or an exception in case of a failure, depending on the flag indication strict mode
   * @throws error indicating a failed validation in case of strict mode
   * @access public
   * @author Christian Reiner
   */
  static function validate ( $value, $type, $strict=FALSE )
  {
    switch ( $type )
    {
      case self::ID:
        if ( preg_match ( '/^[a-z0-9]{2,20}$/i', $value ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::STATUS:
        if ( in_array($value,OC_Shorty_Type::$STATUS) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::SORTKEY:
        if ( array_key_exists ( trim($value), self::$SORTING ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::SORTVAL:
        if ( in_array ( trim($value), self::$SORTING ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::STRING:
        if ( preg_match ( '/^.*$/x', str_replace("\n","\\n",$value) ) )
          return str_replace("\n","\\n",$value);
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::URL:
//        $pattern = '/^([a-zA-Z][a-zA-Z][a-zA-Z0-9]+)\:\/\/([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(\/($|[a-zA-Z0-9\.\;\:\,\@\?\'\\\+&amp;%\$#\=~_\-]+)?)*$/';
        $pattern = '/^([a-zA-Z][a-zA-Z][a-zA-Z0-9]+)\:\/\/([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(\/($|.+)?)*$/';
        if ( preg_match ( $pattern, $value ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::INTEGER:
        if ( preg_match ( '/^[0-9]+$/', $value ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::FLOAT:
        if ( preg_match ( '/^[0-9]+(\.[0-9]+)?$/', $value ) )
          return $value;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::TIMESTAMP:
        if ( preg_match ( '/^[0-9]{10}$/', $value ) )
          return $value;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
      case self::DATE:
        if (FALSE!==($time=strtotime($value)))
          return $time;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "invalid value '%s' for type '%s'", array( ((24<sizeof($value))?$value:substr($value,0,21).'…'),$type) );
    } // switch $type
    throw new OC_Shorty_Exception ( "unknown request argument type '%s'", array($type) );
  } // function is_valid

  /**
   * @method OC_Shorty_Type::normalize
   * @brief cleanup and formal normalization of a given value according to its type
   * Normalizes a given value according to its claimed type.
   * This typically means trimming of string values, but sometimes also more specific actions. 
   * @param value the value to be normalized
   * @param type the supposed type of the value
   * @param strict boolean flag indicating if the normalization should be done in a strict way
   * @returns the normalized value
   * @throws error indicating a parameter violation
   * @access public
   * @author Christian Reiner
   */
  static function normalize ( $value, $type, $strict=FALSE )
  {
    if (NULL===(self::validate($value,$type,$strict)))
    {
      if ( ! $strict)
        return NULL;
      else
        throw new OC_Shorty_Exception ( "invalid value '%1\$s' for type '%2\$s'", array($value,$type) );
    } // if
    switch ( $type )
    {
      case self::ID:        return trim ( $value );
      case self::STATUS:    return trim ( $value );
      case self::SORTKEY:   return trim ( $value );
      case self::SORTVAL:   return trim ( $value );
      case self::STRING:    return trim ( $value );
      case self::URL:       return trim ( $value );
      case self::INTEGER:   return sprintf ( '%d', $value );
      case self::FLOAT:     return sprintf ( '%f', $value );
      case self::TIMESTAMP: return trim ( $value );
      case self::DATE:      return date ( 'Y-m-d', self::validate($value,OC_Shorty_Type::DATE) );
    } // switch $type
    throw new OC_Shorty_Exception ( "unknown request argument type '%s'", array($type) );
  } // function normalize

  /**
   * @method OC_Shorty_Type::req_argument
   * @brief returns checked request argument or throws an error
   * @param arg (string) name of the request argument to get_argument
   * @param strict (bool) controls if an exception will be thrown upon a missing argument
   * @returns (string) checked and prepared value of request argument
   * @throws error indicating a parameter violation
   * @access public
   * @author Christian Reiner
   */
  static function req_argument ( $arg, $type, $strict=FALSE )
  {
    switch ( $_SERVER['REQUEST_METHOD'] )
    {
      case 'POST':
        if ( isset($_POST[$arg]) && !empty($_POST[$arg]) )
          return self::normalize ( urldecode($_POST[$arg]), $type ) ;
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "missing mandatory argument '%1s'", array($arg) );
      case 'GET':
        if ( isset($_GET[$arg]) && !empty($_GET[$arg]) )
          return self::normalize ( urldecode(trim($_GET[$arg])), $type, $strict );
        elseif ( ! $strict)
          return NULL;
        throw new OC_Shorty_Exception ( "missing mandatory argument '%1s'", array($arg) );
      default:
        throw new OC_Shorty_Exception ( "unexpected http request method '%1s'", array($_SERVER['REQUEST_METHOD']) );
    }
  } // function req_argument

} // class OC_Shorty_Query
?>
