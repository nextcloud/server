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
 * @file lib/hooks.php
 * Static class providing routines to populate hooks called by other parts of ownCloud
 * @author Christian Reiner
 */

/**
 * @class OC_Shorty_Hooks
 * @brief Static 'namespace' class for api hook population
 * ownCloud propagates to use static classes as namespaces instead of OOP.
 * This 'namespace' defines routines to populate hooks called by other parts of ownCloud
 * @access public
 * @author Christian Reiner
 */
class OC_Shorty_Hooks
{
  /**
   * @brief Deletes all Shortys and preferences of a certain user
   * @param paramters (array) parameters from postDeleteUser-Hook
   * @return bool
   */
  public static function deleteUser ( $parameters )
  {
    OCP\Util::writeLog ( 'user post delete','wiping all users Shortys', OCP\Util::INFO );
    $result = TRUE;
    $param  = array ( 'user' => OCP\User::getUser() );
    // wipe shortys
    $query = OCP\DB::prepare ( OC_Shorty_Query::WIPE_SHORTYS );
    if ( FALSE===$query->execute($param) )
      $result = FALSE;
    // wipe preferences
    $query = OCP\DB::prepare ( OC_Shorty_Query::WIPE_PREFERENCES );
    if ( FALSE===$query->execute($param) )
      $result = FALSE;
    // report completion success
    return $result;
  }
}
