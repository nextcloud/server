<?php
/**
* ownCloud
* @author Christian Reiner
* @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
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
 * @file core/ajax/requesttoken.php
 * @brief Ajax method to retrieve a fresh request protection token for ajax calls
 * @return json: success/error state indicator including a fresh request token
 * @author Christian Reiner
 */
require_once '../../lib/base.php';

// don't load apps or filesystem for this task
$RUNTIME_NOAPPS    = TRUE;
$RUNTIME_NOSETUPFS = TRUE;

// Sanity checks
// using OCP\JSON::callCheck() below protects the token refreshing itself.
//OCP\JSON::callCheck ( );
OCP\JSON::checkLoggedIn ( );
// hand out a fresh token
OCP\JSON::success ( array ( 'token'   => OCP\Util::callRegister() ) );
?>
