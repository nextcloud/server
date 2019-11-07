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

namespace OCA\Files_Sharing\Notification;

use OCP\Files\IRootFolder;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IManager */
	private $shareManager;

	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(IFactory $l10nFactory,
								IManager $shareManager,
								IRootFolder $rootFolder) {
		$this->l10nFactory = $l10nFactory;
		$this->shareManager = $shareManager;
		$this->rootFolder = $rootFolder;
	}

	public function getID(): string {
		return 'files_sharing';
	}

	public function getName(): string {
		return $this->l10nFactory->get('files_sharing')->t('Files sharing');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'files_sharing' ||
			$notification->getSubject() !== 'expiresTomorrow') {
			throw new \InvalidArgumentException('Unhandled app or subject');
		}

		$l = $this->l10nFactory->get('files_sharing', $languageCode);
		$attemptId = $notification->getObjectId();

		try {
			$share = $this->shareManager->getShareById($attemptId);
		} catch (ShareNotFound $e) {
			throw new AlreadyProcessedException();
		}

		$node = $share->getNode();
		$userFolder = $this->rootFolder->getUserFolder($notification->getUser());
		$path = $userFolder->getRelativePath($node->getPath());

		$notification
			->setParsedSubject($l->t('Share will expire tomorrow'))
			->setParsedMessage($l->t('One or more of your shares will expire tomorrow'))
			->setRichMessage(
				$l->t('Your share of {node} will expire tomorrow'),
				[
					'node' => [
						'type' => 'file',
						'id' => $node->getId(),
						'name' => $node->getName(),
						'path' => $path,
					],
				]
			);

		return $notification;
	}
}
