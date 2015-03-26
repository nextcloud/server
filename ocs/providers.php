<?php
/**
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Stefan Herbrechtsmeier <stefan@herbrechtsmeier.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

require_once '../lib/base.php';

header('Content-type: application/xml');

$url=OCP\Util::getServerProtocol().'://'.substr(OCP\Util::getServerHost().OCP\Util::getRequestUri(), 0, -17).'ocs/v1.php/';

echo('
<providers>
<provider>
 <id>ownCloud</id>
 <location>'.$url.'</location>
 <name>ownCloud</name>
 <icon></icon>
 <termsofuse></termsofuse>
 <register></register>
 <services>
   <config ocsversion="1.7" />
   <activity ocsversion="1.7" />
   <cloud ocsversion="1.7" />
 </services>
</provider>
</providers>
');
