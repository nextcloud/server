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
 * @file qrcode.php
 * Generates qr code barcodes cading a specified url
 * @access public
 * @author Christian Reiner
 */

require_once ( '3rdparty/php/phpqrcode.php' );

$source = NULL;
// we try to guess what the request indicates:
// - a (source) url to be looked up in the database
foreach ($_GET as $key=>$val) // in case there are unexpected, additional arguments like a timestamp added by some stupid proxy
{
  switch ($key)
  {
    default:
      // unrecognized key, we ignore it
      break;
    case 'url':
    case 'uri':
    case 'ref':
    case 'source':
    case 'target':
      // a recognized argument key indicating an id to be looked up
      $source = OC_Shorty_Type::req_argument($key,OC_Shorty_Type::URL,FALSE);
      break 2; // skip switch AND foreach
  } // switch
} // foreach

// generate qr code for the specified url, IF it exists and is usable in the database
try
{
  if ( $source )
  {
    $param = array ( 'source' => OC_Shorty_Type::normalize($source,OC_Shorty_Type::URL) );
    $query  = OCP\DB::prepare ( OC_Shorty_Query::URL_SOURCE );
    $result = $query->execute($param)->FetchAll();

    if ( FALSE===$result )
      throw new OC_Shorty_HttpException ( 500 );
    elseif ( ! is_array($result) )
      throw new OC_Shorty_HttpException ( 500 );
    elseif ( 0==sizeof($result) )
    {
      // no entry found => 404: Not Found
      throw new OC_Shorty_HttpException ( 404 );
    }
    elseif ( 1<sizeof($result) )
    {
      // multiple matches => 409: Conflict
      throw new OC_Shorty_HttpException ( 409 );
    }
    elseif ( (!array_key_exists(0,$result)) || (!is_array($result[0])) || (!array_key_exists('source',$result[0])) )
    {
      // invalid entry => 500: Internal Server Error
      throw new OC_Shorty_HttpException ( 500 );
    }
    elseif ( (!array_key_exists('source',$result[0])) || ('1'==$result[0]['expired']) )
    {
      // entry expired => 410: Gone
      throw new OC_Shorty_HttpException ( 410 );
    }
    // generate qrcode, regardless of who sends the request
    QRcode::png ( $source );
  } // if $source
  else
  {
    // refuse forwarding => 403: Forbidden
    throw new OC_Shorty_HttpException ( 403 );
  }
} catch ( OC_Shorty_Exception $e ) { header($e->getMessage()); }

?>
