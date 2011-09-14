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

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$categories = array(
	$l10n->t('None'),
	$l10n->t('Birthday'),
	$l10n->t('Business'),
	$l10n->t('Call'),
	$l10n->t('Clients'),
	$l10n->t('Deliverer'),
	$l10n->t('Holidays'),
	$l10n->t('Ideas'),
	$l10n->t('Journey'),
	$l10n->t('Jubilee'),
	$l10n->t('Meeting'),
	$l10n->t('Other'),
	$l10n->t('Personal'),
	$l10n->t('Projects'),
	$l10n->t('Questions'),
	$l10n->t('Work'),
);
$repeat_options = array(
	'doesnotrepeat' => $l10n->t('Does not repeat'),
	'daily'         => $l10n->t('Daily'),
	'weekly'        => $l10n->t('Weekly'),
	'weekday'       => $l10n->t('Every Weekday'),
	'biweekly'      => $l10n->t('Bi-Weekly'),
	'monthly'       => $l10n->t('Monthly'),
	'yearly'        => $l10n->t('Yearly'),
);
$startday   = substr($_GET['d'], 0, 2);
$startmonth = substr($_GET['d'], 2, 2);
$startyear  = substr($_GET['d'], 4, 4);
$starttime  = $_GET['t'];
$allday = $starttime == 'allday';
if($starttime != 'undefined' && !is_nan($starttime) && !$allday){
	$startminutes = '00';
}elseif($allday){
	$starttime = '0';
	$startminutes = '00';
}else{
	$starttime = date('H');
	$startminutes = date('i');
}

$endday      = $startday;
$endmonth    = $startmonth;
$endyear     = $startyear;
$endtime     = $starttime;
$endminutes  = $startminutes;
if($endtime == 23) {
	$endday++;
	$endtime = 0;
} else {
	$endtime++;
}

$tmpl = new OC_Template('calendar', 'part.newevent');
$tmpl->assign('calendars', $calendars);
$tmpl->assign('categories', $categories);
$tmpl->assign('startdate', $startday . '-' . $startmonth . '-' . $startyear);
$tmpl->assign('starttime', ($starttime <= 9 ? '0' : '') . $starttime . ':' . $startminutes);
$tmpl->assign('enddate', $endday . '-' . $endmonth . '-' . $endyear);
$tmpl->assign('endtime', ($endtime <= 9 ? '0' : '') . $endtime . ':' . $endminutes);
$tmpl->assign('allday', $allday);
$tmpl->printpage();
?>
