<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

namespace OCA\Viewer\AppInfo;

use OCA\Viewer\Event\LoadViewer;
use OCA\Viewer\Listener\LoadViewerScript;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;

class Application extends App {

	const APP_ID = 'viewer';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register() {
		$server = $this->getContainer()->getServer();

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $server->query(IEventDispatcher::class);

		// Watch Viewer load event
		$eventDispatcher->addServiceListener(LoadViewer::class, LoadViewerScript::class);
	}
}
