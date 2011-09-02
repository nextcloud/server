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
$cal = $_GET["calid"];
$calendar = OC_Calendar_Calendar::findCalendar($cal);
if($calendar["userid"] != OC_User::getUser()){
	header("Location: " . OC_HELPER::linkTo("", "index.php"));
	exit;
}
$calobjects = OC_Calendar_Calendar::allCalendarObjects($cal);
header("Content-Type: text/Calendar");
header("Content-Disposition: inline; filename=calendar.ics"); 
for($i = 0;$i <= count($calobjects); $i++){
	echo $calobjects[$i]["calendardata"] . "\n";
}
?>