<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		['name' => 'birthday_calendar#enable', 'url' => '/enableBirthdayCalendar', 'verb' => 'POST'],
		['name' => 'birthday_calendar#disable', 'url' => '/disableBirthdayCalendar', 'verb' => 'POST'],
		['name' => 'invitation_response#accept', 'url' => '/invitation/accept/{token}', 'verb' => 'GET'],
		['name' => 'invitation_response#decline', 'url' => '/invitation/decline/{token}', 'verb' => 'GET'],
		['name' => 'invitation_response#options', 'url' => '/invitation/moreOptions/{token}', 'verb' => 'GET'],
		['name' => 'invitation_response#processMoreOptionsResult', 'url' => '/invitation/moreOptions/{token}', 'verb' => 'POST'],
	],
	'ocs' => [
		['name' => 'direct#getUrl', 'url' => '/api/v1/direct', 'verb' => 'POST'],
		['name' => 'upcoming_events#getEvents', 'url' => '/api/v1/events/upcoming', 'verb' => 'GET'],
		['name' => 'out_of_office#getCurrentOutOfOfficeData', 'url' => '/api/v1/outOfOffice/{userId}/now', 'verb' => 'GET'],
		['name' => 'out_of_office#getOutOfOffice', 'url' => '/api/v1/outOfOffice/{userId}', 'verb' => 'GET'],
		['name' => 'out_of_office#setOutOfOffice', 'url' => '/api/v1/outOfOffice/{userId}', 'verb' => 'POST'],
		['name' => 'out_of_office#clearOutOfOffice', 'url' => '/api/v1/outOfOffice/{userId}', 'verb' => 'DELETE'],
	],
];
