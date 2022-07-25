<?php
declare(strict_types=1);

/*
 * *
 *  * Dav App
 *  *
 *  * @copyright 2022 Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CalDAV\Schedule\ITip;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Broker as SabreBroker;
use Sabre\VObject\ITip\Message;

class Broker extends SabreBroker {

	protected function processMessageReply(Message $itipMessage, VCalendar $existingObject = null) {
		parent::processMessageReply($itipMessage, $existingObject);

		foreach($itipMessage->message->VEVENT->ATTENDEE as $attendee) {
			$guests = $attendee->{'X-NUM-GUESTS'};
			$comment = $attendee->{'X-RESPONSE-COMMENT'};
		}

		foreach ($existingObject->VEVENT as $vevent) {
			if (isset($vevent->ATTENDEE)) {
				foreach ($itipMessage->message->VEVENT->ATTENDEE as $attendee) {
//					$guests =  ->getValue() ?? null;
//					$comment =  $attendee->{'X-RESPONSE-COMMENT'}->getValue() ?? null;
					if($guests !== null) {
						$attendee['X-NUM-GUESTS'] = $guests;
					}
					if($comment !== null) {
						$attendee['X-RESPONSE-COMMENT'] = $comment;
					}
				}
			}
		}
		return $existingObject;
	}

}
