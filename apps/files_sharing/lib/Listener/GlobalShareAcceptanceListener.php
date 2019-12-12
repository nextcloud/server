<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\Files_Sharing\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

class GlobalShareAcceptanceListener implements IEventListener {

	/** @var IConfig */
	private $config;
	/** @var IManager */
	private $shareManager;

	public function __construct(IConfig $config, IManager $shareManager) {
		$this->config = $config;
		$this->shareManager = $shareManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)) {
			return;
		}

		if ($this->config->getSystemValueBool('sharing.interal_shares_accepted', false)) {
			$share = $event->getShare();

			if ($share->getShareType() === IShare::TYPE_USER || $share->getShareType() === IShare::TYPE_GROUP) {
				$share->setStatus(IShare::STATUS_ACCEPTED);
				$this->shareManager->updateShare($share);
			}
		}
	}

}
