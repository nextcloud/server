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
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
$calendar = array(
	'id' => 'new',
	'displayname' => 'Test',
	'description' => 'Test calendar',
	'calendarcolor' => 'black',
);
$tmpl = new OC_Template('calendar', 'part.editcalendar');
$tmpl->assign('new', true);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();
?>
