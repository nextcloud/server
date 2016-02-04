<?php

namespace OCA\DAV\CalDAV;

class CalendarRoot extends \Sabre\CalDAV\CalendarRoot {

	function getChildForPrincipal(array $principal) {
		return new CalendarHome($this->caldavBackend, $principal);
	}
}