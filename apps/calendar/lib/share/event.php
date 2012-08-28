<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Share_Backend_Event implements OCP\Share_Backend {

	const FORMAT_EVENT = 0;

	private static $event;

	public function isValidSource($itemSource, $uidOwner) {
		self::$event = OC_Calendar_Object::find($itemSource);
		if (self::$event) {
			return true;
		}
		return false;
	}
	
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		// TODO Get default calendar and check for conflicts
		return self::$event['summary'];
	}
	
	public function formatItems($items, $format, $parameters = null) {
		$events = array();
		if ($format == self::FORMAT_EVENT) {
			foreach ($items as $item) {
				$event = OC_Calendar_Object::find($item['item_source']);
				$event['summary'] = $item['item_target'];
				$events[] = $event;
			}
		}
		return $events;
	}

}
