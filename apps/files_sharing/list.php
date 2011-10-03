<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2011 Michael Gapczynski GapczynskiM@gmail.com
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

require_once('../../lib/base.php');
require_once('lib_share.php');

OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('files_sharing');

OC_App::setActiveNavigationEntry("files_sharing_list");

OC_Util::addScript("files_sharing", "list");

$tmpl = new OC_Template("files_sharing", "list", "user");
$tmpl->assign("shared_items", OC_Share::getMySharedItems());
$tmpl->printPage();

?>
