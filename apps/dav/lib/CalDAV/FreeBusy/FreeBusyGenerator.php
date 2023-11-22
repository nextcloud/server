<?php
declare(strict_types=1);
/*
 * *
 *  *
 *  * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

namespace OCA\DAV\CalDAV\FreeBusy;

use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FreeBusyGenerator extends \Sabre\VObject\FreeBusyGenerator {

	public function __construct() {
		parent::__construct();
	}

	public function getVCalendar(): VCalendar {
		return new VCalendar();
	}
}
