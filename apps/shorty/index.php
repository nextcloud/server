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
 * @file index.php
 * This is the plugins central position
 * All requests to the plugin are handled by this file.
 * Exceptions: system settings, user preferences and relaying
 * @access public
 * @author Christian Reiner
 */

OCP\App::setActiveNavigationEntry ( 'shorty_index' );

OCP\Util::addStyle  ( 'shorty',  'shorty' );

OCP\Util::addScript ( 'shorty/3rdparty','jquery.tinysort.min' );
OCP\Util::addScript ( 'shorty',  'shorty' );
OCP\Util::addScript ( 'shorty',  'init' );
if ( OC_Log::DEBUG==OC_Config::getValue( "loglevel", OC_Log::WARN ) )
  OCP\Util::addScript ( 'shorty',  'debug' );

// strategy:
// - first: decide which action is requested
// - second: execute that action with an optional argument provided

// defaults:
$act = 'index';
$arg = NULL;
// we try to guess what the request indicates:
// - a (shorty) id to be looked up in the database resulting in a forwarding to the stored target
// - a (target) url to be added as a new shorty
// - none of the two, so just a plain list of existing shortys
foreach ($_GET as $key=>$val) // in case there are unexpected, additional arguments like a timestamp added by some stupid proxy
{
  switch ($key)
  {
    // this is the OC4 argument used to identify the app called, we ignore it:
    case 'app':
      break;
    // any recognizable argument key indicating a url to be added as new shorty ?
    case 'url':
    case 'uri':
    case 'target':
    case 'link':
      // example: http://.../shorty/index.php?url=http%...
      $act = 'acquire';
      $arg = OC_Shorty_Type::req_argument($key,OC_Shorty_Type::URL,FALSE);
      break 2; // skip switch AND foreach
    // no recognizable key but something else, hm...
    // this _might_ be some unexcepted argument, or:
    // it is an expected argument, but without recognizable key, so we try to guess by examining the content
    // we restrict this 'guessing' to cases where only a single argument is specified
    default:
      if (  (1==sizeof($_GET))  // only one single request argument
          &&( ! reset($_GET)) ) // no value, so maybe just an id
      {
        // use that source instead of $key, since $key contains replaced chars (php specific exceptions due to var name problems)
        $raw = urldecode($_SERVER['QUERY_STRING']);
        // now try to interpret its content
        if (NULL!==($value=OC_Shorty_Type::normalize($raw,OC_Shorty_Type::URL,FALSE)))
        {
          // the query string is a url, acquire it as a new shorty
          $act = 'acquire';
          $arg = $raw;
          break 2;
        }
        else
        {
          // no pattern recognized, so we assume an ordinary index action
          $act = 'index';
          break 2;
        }
      } // if
      $act='index';
      break 2;
  } // switch key
} // foreach key

// next, execute the "act" whilst considering the 'arg'
switch ($act)
{
  case 'acquire': // add url as new shorty
    // keep the url specified as referer, that is the one we want to store
    $_SESSION['shorty-referrer'] = $arg;
    OCP\Util::writeLog( 'shorty', sprintf("Detected an incoming Shortlet request for url '%s...'",substr($arg,0,80)), OC_Log::INFO );
    header ( sprintf('Location: %s', OCP\Util::linkTo('shorty','index.php')) );
    exit();
  // =====
  case 'index': // action 'index': list of shortys
  default:
    try
    {
      // is this a redirect from a call with a target url to be added ? 
      if ( isset($_SESSION['shorty-referrer']) )
      {
        // this takes care of handling the url on the client side
        OCP\Util::addScript ( 'shorty', 'add' );
        // add url taked from the session vars to anything contained in the query string
        $_SERVER['QUERY_STRING'] = implode('&',array_merge(array('url'=>$_SESSION['shorty-referrer']),explode('&',$_SERVER['QUERY_STRING'])));
      }
      else
      {
        // simple desktop initialization, no special actions contained
        OCP\Util::addScript ( 'shorty', 'list' );
      }
      $tmpl = new OCP\Template( 'shorty', 'tmpl_index', 'user' );
      // the (remote) base url of the qrcode generator
      $tmpl->assign ( 'qrcode-url', sprintf('%s?service=%s&url=',OCP\Util::linkToAbsolute("", "public.php"),'shorty_qrcode') );
      // available status (required for select filter in toolbox)
      $shorty_status['']=sprintf('- %s -',OC_Shorty_L10n::t('all'));
      foreach ( OC_Shorty_Type::$STATUS as $status )
        $shorty_status[$status] = OC_Shorty_L10n::t($status);
      $tmpl->assign ( 'shorty-status', $shorty_status );
      // any referrer we want to hand over to the browser ?
      if ( array_key_exists('shorty-referrer',$_SESSION) )
        $tmpl->assign ( 'shorty-referrer', $_SESSION['shorty-referrer'] );
      // is sending sms enabled in the personal preferences ?
      $tmpl->assign ( 'sms-control', OCP\Config::getUserValue(OCP\User::getUser(),'shorty','sms-control','disabled') );
      // clean up session var so that a browser reload does not trigger the same action again
      unset ( $_SESSION['shorty-referrer'] );
      $tmpl->printPage();
    } catch ( OC_Shorty_Exception $e ) { OCP\JSON::error ( array ( 'message'=>$e->getTranslation(), 'data'=>$result ) ); }
} // switch

?>
