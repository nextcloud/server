<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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

use OC\Security\Bruteforce\Throttler;
use OCA\DAV\Db\DirectMapper;
use OCA\DAV\Direct\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use OCP\IRequest;

// no php execution timeout for webdav
if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

/** @var IRequest $request */
$request = \OC::$server->get(IRequest::class);

/** @var ServerFactory $serverFactory */
$serverFactory = \OC::$server->get(ServerFactory::class);
$server = $serverFactory->createServer(
	$baseuri,
	$request->getRequestUri(),
	\OC::$server->get(IRootFolder::class),
	\OC::$server->get(DirectMapper::class),
	\OC::$server->get(ITimeFactory::class),
	\OC::$server->get(Throttler::class),
	$request
);

$server->start();
