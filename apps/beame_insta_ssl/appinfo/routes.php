<?php
/**
 * @copyright Copyright (c) 2017 Beame.io LTD.
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

return [
	'routes' => [
		['name' => 'settings#getCreds', 'url' => '/settings/get-creds', 'verb' => 'POST'],
		['name' => 'settings#start',    'url' => '/settings/start',     'verb' => 'POST'],
		['name' => 'settings#stop',     'url' => '/settings/stop',      'verb' => 'POST'],
		['name' => 'settings#cleanup',  'url' => '/settings/cleanup',   'verb' => 'POST'],
	],
];
