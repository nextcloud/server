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
 * @file lib/tools.php
 * A collection of general utility routines
 * @author Christian Reiner
 */

/**
 * @class OC_Shorty_Tools
 * @brief Collection of a few practical routines, a tool box
 * @access public
 * @author Christian Reiner
 */
class OC_Shorty_Tools
{
  // internal flag indicating if output buffering should be used to prevent accidentially output during ajax requests
  static $ob_usage  = TRUE;
  // internal flag indicating if there is currently an output buffer active
  static $ob_active = FALSE;

  /**
   * @method OC_Shorty_Tools::ob_control
   * @param on (boolean) wether to activate or deactivate the buffer
   * @access public
   * @author Christian Reiner
   */
  static function ob_control ( $on=TRUE )
  {
    $output = NULL;
    if ( self::$ob_usage )
    {
      // attempt to use outpout buffering
      if ( $on )
      {
        // start buffering if possible and not yet started before
        if (   function_exists('ob_start')       // output buffers installed at all ?
            && ! self::$ob_active  )  // don't stack buffers (create buffer only, if not yet started)
        {
          ob_implicit_flush ( FALSE );
          ob_start ( );
          self::$ob_active = TRUE;
        }
      } // if $on==TRUE
      else
      {
        // end buffering _if_ it has been started before
        if ( self::$ob_active )
        {
          $output = ob_get_contents ( );
          ob_end_clean ( );
          self::$ob_active = FALSE;
        }
      } // if $on==FALSE
    } // if ob_usage
    return $output;
  } // function ob_control
  
  /**
   * @method OC_Shorty_Tools::db_escape
   * @brief escape a value for incusion in db statements
   * @param value (string) value to be escaped
   * @returns (string) escaped string value
   * @throws OC_Shorty_Exception in case of an unknown database engine
   * @access public
   * @author Christian Reiner
   * @todo use mdb2::quote() / mdb2:.escape() instead ?
   */
  static function db_escape ( $value )
  {
    $type = OCP\Config::getSystemValue ( 'dbtype', 'sqlite' );
    switch ( $type )
    {
      case 'sqlite':
      case 'sqlite3':
        return sqlite_escape_string     ( $value );
      case 'pgsql':
        return pg_escape_string         ( $value );
      case 'mysql':
        if (get_magic_quotes_gpc())
             return mysql_real_escape_string ( stripslashes($value) );
        else return mysql_real_escape_string ( $value );
    }
    throw new OC_Shorty_Exception ( "unknown database backend type '%1'", array($type) );
  } // function db_escape

  /**
   * @method OC_Shorty_Tools::db_timestamp
   * @brief current timestamp as required by db engine
   * @returns (string) current timestamp as required by db engine
   * @throws OC_Shorty_Exception in case of an unknown database engine
   * @access public
   * @author Christian Reiner
   * @todo not really required any more, we rely on CURRENT_TIMESTAMP instead
   */
  static function db_timestamp ( )
  {
    $type = OCP\Config::getSystemValue( "dbtype", "sqlite" );
    switch ( $type )
    {
      case 'sqlite':
      case 'sqlite3': return "strftime('%s','now')";
      case 'mysql':   return 'UNIX_TIMESTAMP()';
      case 'pgsql':   return "date_part('epoch',now())::integer";
    }
    throw new OC_Shorty_Exception ( "unknown database backend type '%1'", array($type) );
  } // function db_timestamp

  /**
   * @method OC_Shorty_Tools::shorty_id
   * @brief Creates a unique id to be used for a new shorty entry
   * @returns (string) valid and unique id
   * @access public
   * @author Christian Reiner
   */
  static function shorty_id ( )
  {
    // each shorty installation uses a (once self generated) 62 char alphabet
    $alphabet=OCP\Config::getAppValue('shorty','id-alphabet');
    if ( empty($alphabet) )
    {
      $alphabet = self::randomAlphabet(62);
      OCP\Config::setAppValue ( 'shorty', 'id-alphabet', $alphabet );
    }
    // use alphabet to generate a id being unique over time
    return self::convertToAlphabet ( str_replace(array(' ','.'),'',microtime()), $alphabet );
  } // function shorty_id

  /**
   *
   */
  static function randomAlphabet ($length)
  {
    if ( ! is_integer($length) )
      return FALSE;
    $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789";
    for($l=0;$l<$length;$l++) $s .= $c{rand(0,strlen($c))};
    return str_shuffle($s);
  } // function randomAlphabet

  /**
   * @method OC_Shorty_Tools::convertToAlphabet
   * @brief Converts a given decimal number into an arbitrary base (alphabet)
   * @param number decimal value to be converted
   * @returns (string) converted value in string notation
   * @access public
   * @author Christian Reiner
   */
  static function convertToAlphabet ( $number, $alphabet )
  {
    $alphabetLen = strlen($alphabet);
    $decVal = (int) $number;
    $number = FALSE;
    $nslen = 0;
    $pos = 1;
    while ($decVal > 0)
    {
      $valPerChar = pow($alphabetLen, $pos);
      $curChar = floor($decVal / $valPerChar);
      if ($curChar >= $alphabetLen)
      {
        $pos++;
      } else {
        $decVal -= ($curChar * $valPerChar);
        if ($number === FALSE)
        {
          $number = str_repeat($alphabet{1}, $pos);
          $nslen = $pos;
        }
        $number = substr($number, 0, ($nslen - $pos)) . $alphabet{$curChar} . substr($number, (($nslen - $pos) + 1));
        $pos--;
      }
    }
    if ($number === FALSE) $number = $alphabet{1};
    return $number;
  }

  /**
   * @method OC_Shorty_Tools::relayUrl
   * @brief Generates a relay url for a given id acting as a href target for all backends
   * @param id (string) shorty id as shorty identification
   * @returns (string) generated absolute relay url
   * @access public
   * @author Christian Reiner
   */
  static function relayUrl ($id)
  {
    return sprintf ( '%s?service=%s&id=%s', OCP\Util::linkToAbsolute("", "public.php"), 'shorty_relay', $id );
  } // function relayUrl

  /**
   * @method OC_Shorty_Tools::countShortys
   * @brief Returns the total number of entries and clicks from the database
   * @returns (array) two elements sum_shortys & sum_clicks holding an integer each
   * @access public
   * @author Christian Reiner
   */
  static function countShorties ()
  {
    $param = array
    (
      ':user'   => OCP\User::getUser ( ),
    );
    $query = OCP\DB::prepare ( OC_Shorty_Query::URL_COUNT );
    $result = $query->execute($param);
    $reply = $result->fetchAll();
    return $reply[0];
  } // function countShorties

} // class OC_Shorty_Tools
?>
