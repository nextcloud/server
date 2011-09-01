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
require_once ("../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	header("Location: " . OC_HELPER::linkTo("", "index.php"));
	exit;
}
// Create default calendar ...
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
if( count($calendars) == 0){
	OC_Calendar_Calendar::addCalendar(OC_User::getUser(),'default','Default calendar');
	$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
}
OC_UTIL::addScript("calendar", "calendar");
OC_UTIL::addScript("calendar", "calendar_init");
OC_UTIL::addStyle("calendar", "style");
OC_APP::setActiveNavigationEntry("calendar_index");
$output = new OC_TEMPLATE("calendar", "calendar", "user");
$output -> printPage();
