<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter;

/**
 * Generic OpenID providers adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *
 *       //  authenticate with Yahoo openid
 *       'openid_identifier' => 'https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds'
 *
 *       //  authenticate with stackexchange network openid
 *       // 'openid_identifier' => 'https://openid.stackexchange.com/',
 *
 *       //  authenticate with Steam openid
 *       // 'openid_identifier' => 'http://steamcommunity.com/openid',
 *
 *       // etc.
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\OpenID($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class OpenID extends Adapter\OpenID
{
}
