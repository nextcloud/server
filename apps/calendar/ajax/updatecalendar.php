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
require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => $l->t("Authentication error") )));
	exit();
}

$calendarid = $_POST['id'];
OC_Calendar_Calendar::editCalendar($calendarid, $_POST['name'], $_POST['description'], null, null, null, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, $_POST['active']);
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
$tmpl = new OC_Template('calendar', 'part.calendar.row');
$tmpl->assign('calendar', $calendar);
echo json_encode( array( "status" => "success", "data" => $tmpl->fetchPage() ));
