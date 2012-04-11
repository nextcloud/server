<?php

/**
* ownCloud status page. usefull if you want to check from the outside if an owncloud installation exists
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

$RUNTIME_NOAPPS = TRUE; //no apps, yet

require_once('lib/base.php');

if(OC_Config::getValue('installed')==1) $installed='true'; else $installed='false';
$values=array('installed'=>$installed,'version'=>implode('.',OC_Util::getVersion()),'versionstring'=>OC_Util::getVersionString(),'edition'=>OC_Util::getEditionString());

echo(json_encode($values));


?>
