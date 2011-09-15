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
 * <http://www.gnu.org/licenses/>                 *
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
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
//short variables
$title = $_POST["title"];
$location = $_POST["location"];
$cat = $_POST["cat"];
$cal = str_replace("option_","", $_POST["cal"]);
$allday = $_POST["allday"];
$from = $_POST["from"];
$fromtime = $_POST["fromtime"];
$to  = $_POST["to"];
$totime = $_POST["totime"];
$description = $_POST["description"];
//$repeat = $_POST["repeat"];
/*switch($_POST["repeatfreq"]){
	case "DAILY":
		$repeatfreq = "DAILY";
	case "WEEKLY":
		$repeatfreq = "WEEKLY";
	case "WEEKDAY":
		$repeatfreq = "DAILY;BYDAY=MO,TU,WE,TH,FR"; //load weeksdayss from userconfig when weekdays are choosable
	case "":
		$repeatfreq = "";
	case "":
		$repeatfreq = "";
	case "":
		$repeatfreq = "";
	default:
		$repeat = "false";
}*/
$repeat = "false";
//validate variables
$errnum = 0;
$errarr = array("title"=>"false", "cal"=>"false", "from"=>"false", "fromtime"=>"false", "to"=>"false", "totime"=>"false", "endbeforestart"=>"false");
if($title == ""){
	$errarr["title"] = "true";
	$errnum++;
}
$calendar = OC_Calendar_Calendar::findCalendar($cal);
if($calendar["userid"] != OC_User::getUser()){
	$errarr["cal"] = "true";
	$errnum++;
}
$fromday = substr($_POST["from"], 0, 2);
$frommonth = substr($_POST["from"], 3, 2);
$fromyear = substr($_POST["from"], 6, 4);
if(!checkdate($frommonth, $fromday, $fromyear)){
	$errarr["from"] = "true";
	$errnum++;
}
$fromhours = substr($_POST["fromtime"], 0, 2);
$fromminutes = substr($_POST["fromtime"], 3, 2);
if($fromhours > 24 || $fromminutes > 60 || $fromtime == ""){
	$errarr["fromtime"] = "true";
	$errnum++;
}

$today = substr($_POST["to"], 0, 2);
$tomonth = substr($_POST["to"], 3, 2);
$toyear = substr($_POST["to"], 6, 4);
if(!checkdate($tomonth, $today, $toyear)){
	$errarr["to"] = "true";
	$errnum++;
}
$tohours = substr($_POST["totime"], 0, 2);
$tominutes = substr($_POST["totime"], 3, 2);
if($tohours > 24 || $tominutes > 60 || $totime == ""){
	$errarr["totime"] = "true";
	$errnum++;
}
if($today < $fromday && $frommonth == $tomonth && $fromyear == $toyear){
	$errarr["endbeforestart"] = "true";
	$errnum++;
}
if($today == $fromday && $frommonth > $tomonth && $fromyear == $toyear){
	$errarr["endbeforestart"] = "true";
	$errnum++;
}
if($today == $fromday && $frommonth == $tomonth && $fromyear > $toyear){
	$errarr["endbeforestart"] = "true";
	$errnum++;
}
if($fromday == $today && $frommonth == $tomonth && $fromyear == $toyear){
	if($tohours < $fromhours){
		$errarr["endbeforestart"] = "true";
		$errnum++;
	}
	if($tohours == $fromhours && $tominutes < $fromminutes){
		$errarr["endbeforestart"] = "true";
		$errnum++;
	}
}
if($errnum != 0){
	//show validate errors
	$errarr["error"] = "true";
	echo json_encode($errarr);
	exit;
}else{
	$data = "BEGIN:VCALENDAR\nPRODID:ownCloud Calendar\nVERSION:2.0\n";
	$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
	$created = date("Ymd") . "T" . date("His");
	$data .= "BEGIN:VEVENT\n";
	$data .= "CREATED:" . $created . "\nLAST-MODIFIED:" . $created . "\nDTSTAMP:" . $created . "\n";
	$data .= "SUMMARY:" . $title . "\n";
	if($allday == "true"){
		$start = $fromyear . $frommonth . $fromday;
		$unixend = mktime(0,0,0,$tomonth, $today, $toyear) + (24 * 60 * 60);
		$end = date("Ymd", $unixend);
		$data .= "DTSTART;VALUE=DATE:" . $start . "\n";
		$data .= "DTEND;VALUE=DATE:" . $end . "\n";
	}else{
		$start = $fromyear . $frommonth . $fromday . "T" . $fromhours . $fromminutes . "00";
		$end = $toyear . $tomonth . $today . "T" . $tohours . $tominutes . "00";
		$data .= "DTSTART;TZID=" . $timezone . ":" . $start . "\n";
		$data .= "DTEND;TZID=" . $timezone . ":" . $end . "\n";
	}
	if($location != ""){
		$data .= "LOCATION:" . $location . "\n";
	}
	if($description != ""){
		$des = str_replace("\n","\\n", $description);
		$data .= "DESCRIPTION:" . $des . "\n";
	}
	/*if($cat != $l->t("None")){
		$data .= "CATEGORIES:" . $cat . "\n";
	}*/
	/*if($repeat == "true"){
		$data .= "RRULE:" . $repeat . "\n";
	}*/
	$data .= "END:VEVENT\nEND:VCALENDAR";
	$result = OC_Calendar_Object::add($cal, $data);
	echo json_encode(array("success"=>"true"));
}
?>
