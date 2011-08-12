<?php
/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Georg Ehrke                 *
 * author: Georg Ehrke                            *
 * email: ownclouddev at georgswebsite dot de     *
 * homepage: ownclouddev.georgswebsite.de         *
 * manual: ownclouddev.georgswebsite.de/manual    *
 * License: GNU AFFERO GENERAL PUBLIC LICENSE     *
 *                                                *
 * If you are not able to view the License,       *
 * <http://www.gnu.org/licenses/>                 *
 * <http://ownclouddev.georgswebsite.de/license/> *
 * please write to the Free Software Foundation.  *
 * Address:                                       *
 * 59 Temple Place, Suite 330, Boston,            *
 * MA 02111-1307  USA                             *
 *************************************************/
require_once ("../../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	die("0");
}
$currentview = $_GET["v"];
OC_Preferences::setValue(OC_USER::getUser(), "calendar", "currentview", $currentview);
if(OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview") == $currentview){
	die("1");
}else{
	die("0");
}
?>