<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Files_Sharing\Notification;

use OC\Share\Share;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Share\IShare;
use Symfony\Component\EventDispatcher\GenericEvent;

class Listener {

	/** @var IManager */
	protected $notificationManager;

	public function __construct(
		IManager $notificationManager
	) {
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @param GenericEvent $event
	 */
	public function shareNotification(GenericEvent $event): void {
		/** @var IShare $share */
		$share = $event->getSubject();
		$notification = $this->instantiateNotification($share);

		if ($share->getShareType() === Share::SHARE_TYPE_USER) {
			$notification->setSubject('incoming_user_share')
				->setUser($share->getSharedWith());
			$this->notificationManager->notify($notification);
		}
	}

	/**
	 * @param IShare $share
	 * @return INotification
	 */
	protected function instantiateNotification(IShare $share): INotification {
		$notification = $this->notificationManager->createNotification();
		$notification
			->setApp('files_sharing')
			->setObject('share', $share->getFullId())
			->setDateTime($share->getShareTime());

		return $notification;
	}
}
