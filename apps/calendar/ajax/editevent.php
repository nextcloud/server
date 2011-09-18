<?php
/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * License: GNU AFFERO GENERAL PUBLIC LICENSE     *
 *                                                *
 * <http://www.gnu.org/licenses/>                 *
 * If you are not able to view the License,       *
 * <http://www.gnu.org/licenses/>                 *
 * please write to the Free Software Foundation.  *
 * Address:                                       *
 * 59 Temple Place, Suite 330, Boston,            *
 * MA 02111-1307  USA                             *
 *************************************************/
require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}

$errarr = OC_Calendar_Object::validateRequest($_POST);
if($errarr){
	//show validate errors
	$errarr['status'] = 'error';
	echo json_encode($errarr);
	exit;
}else{
	$id = $_POST['id'];
	$cal = $_POST['calendar'];
	$data = OC_Calendar_Object::find($id);
	if (!$data)
	{
		echo json_encode(array('status'=>'error'));
		exit;
	}
	$calendar = OC_Calendar_Calendar::findCalendar($data['calendarid']);
	if($calendar['userid'] != OC_User::getUser()){
		echo json_encode(array('status'=>'error'));
		exit;
	}
	$vcalendar = Sabre_VObject_Reader::read($data['calendardata']);
	OC_Calendar_Object::updateVCalendarFromRequest($_POST, $vcalendar);
	$result = OC_Calendar_Object::edit($id, $vcalendar->serialize());
	if ($data['calendarid'] != $cal) {
		OC_Calendar_Object::moveToCalendar($id, $cal);
	}
	echo json_encode(array('status' => 'success'));
}
?> 
