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
 * @file appinfo/app.php
 * @brief Basic registration of plugin at ownCloud
 * @author Christian Reiner
 */

OC::$CLASSPATH['OC_Shorty_Backend']       = 'apps/shorty/lib/backend.php';
OC::$CLASSPATH['OC_Shorty_Exception']     = 'apps/shorty/lib/exception.php';
OC::$CLASSPATH['OC_Shorty_HttpException'] = 'apps/shorty/lib/exception.php';
OC::$CLASSPATH['OC_Shorty_L10n']          = 'apps/shorty/lib/l10n.php';
OC::$CLASSPATH['OC_Shorty_Meta']          = 'apps/shorty/lib/meta.php';
OC::$CLASSPATH['OC_Shorty_Query']         = 'apps/shorty/lib/query.php';
OC::$CLASSPATH['OC_Shorty_Tools']         = 'apps/shorty/lib/tools.php';
OC::$CLASSPATH['OC_Shorty_Type']          = 'apps/shorty/lib/type.php';

OCP\App::addNavigationEntry ( array ( 'id' => 'shorty_index',
                                     'order' => 71,
                                     'href' => OCP\Util::linkTo   ( 'shorty', 'index.php' ),
                                     'icon' => OCP\Util::imagePath( 'shorty', 'shorty.svg' ),
                                     'name' => 'Shorty' ) );

OCP\App::register         ( array ( 'order' => 71, 'id' => 'shorty', 'name' => 'Shorty' ) );
OCP\App::registerAdmin    ( 'shorty', 'settings' );
OCP\App::registerPersonal ( 'shorty', 'preferences' );
OCP\Util::connectHook ( 'OC_User', 'post_deleteUser', 'OC_Shorty_Hooks', 'deleteUser');

?>
