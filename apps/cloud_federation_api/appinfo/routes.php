<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Maxence Lange <maxence@artificial-owl.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
return [
	'routes' => [
		[
			'name' => 'RequestHandler#addShare',
			'url' => '/shares',
			'verb' => 'POST',
			'root' => '/ocm',
		],
		[
			'name' => 'RequestHandler#receiveNotification',
			'url' => '/notifications',
			'verb' => 'POST',
			'root' => '/ocm',
		],
		//		[
		//			'name' => 'RequestHandler#inviteAccepted',
		//			'url' => '/invite-accepted',
		//			'verb' => 'POST',
		//			'root' => '/ocm',
		//		]
	],
];
