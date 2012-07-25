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
 * @file relay.php
 * This is the plugins central relaying feature
 * All relay requests are handled by this file.
 * @access public
 * @author Christian Reiner
 */

OCP\App::setActiveNavigationEntry ( 'shorty_index' );

$arg = NULL;
// we try to guess what the request indicates:
// - a (shorty) id to be looked up in the database resulting in a forwarding to the stored target
// - a (target) url to be added as a new shorty
// - none of the two, so just a plain list of existing shortys
foreach ($_GET as $key=>$val) // in case there are unexpected, additional arguments like a timestamp added by some stupid proxy
{
  switch ($key)
  {
    default:
      // unrecognized key, we ignore it
      break;
    case 'id':
    case 'shorty':
    case 'ref':
    case 'entry':
      // a recognized argument key indicating an id to be looked up
      $arg = OC_Shorty_Type::req_argument($key,OC_Shorty_Type::ID,FALSE);
      break 2; // skip switch AND foreach
  } // switch
} // foreach

// an id was specified, ordinary or special meaning ?
if ( '0000000000'==$arg )
{
  // this is a pseudo id, used to test the setup, so just return a positive message.
  // this is used to test the setup of the static backend, shorty calls itself from there
  OCP\Util::writeLog( 'shorty', "Positiv validation of static backend base url", OC_Log::INFO );
  OCP\JSON::success ( array ( ) );
  exit();
}

// now construct the target url and relay to it (if applicable)
try
{
  // detect requested shorty id from request
  $p_id = trim ( OC_Shorty_Type::normalize($arg,OC_Shorty_Type::ID) ) ;
  if ( $p_id )
  {
    $param = array
    (
      'id' => $p_id,
    );
    $query  = OCP\DB::prepare ( OC_Shorty_Query::URL_FORWARD );
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
    elseif ( (!array_key_exists(0,$result)) || (!is_array($result[0])) || (!array_key_exists('target',$result[0])) )
    {
      // invalid entry => 500: Internal Server Error
      throw new OC_Shorty_HttpException ( 500 );
    }
    elseif ( (!array_key_exists('target',$result[0])) || ('1'==$result[0]['expired']) )
    {
      // entry expired => 410: Gone
      throw new OC_Shorty_HttpException ( 410 );
    }
    // an usable target !
    $target = trim($result[0]['target']);
    // check status of matched entry
    switch (trim($result[0]['status']))
    {
      default:
      case 'blocked':
        // refuse forwarding => 403: Forbidden
        throw new OC_Shorty_HttpException ( 403 );
      case 'private':
        // check if user owns the Shorty, deny access if not
        if ( $result[0]['user']!=OCP\User::getUser() )
          // refuse forwarding => 403: Forbidden
          throw new OC_Shorty_HttpException ( 403 );
        // NO break; but fall through to the action in 'case public:'
      case 'shared':
        // check if we are a user, deny access if not
        if ( ! OCP\User::isLoggedIn() )
          // refuse forwarding => 403: Forbidden
          throw new OC_Shorty_HttpException ( 403 );
        // NO break; but fall through to the action in 'case public:'
      case 'public':
        // forward to target, regardless of who sends the request
        header("HTTP/1.0 301 Moved Permanently");
        // http forwarding header
        header ( sprintf('Location: %s', $target) );
    } // switch status
    // register click in shorty
    $query = OCP\DB::prepare ( OC_Shorty_Query::URL_CLICK );
    $query->execute ( $param );
    exit();
  } // if id
} catch ( OC_Shorty_Exception $e ) { header($e->getMessage()); }

?>
