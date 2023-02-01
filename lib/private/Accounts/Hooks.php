<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Accounts;

use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserChangedEvent>
 */
class Hooks implements IEventListener {
	/** @var IAccountManager */
	private $accountManager;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger, IAccountManager $accountManager) {
		$this->logger = $logger;
		$this->accountManager = $accountManager;
	}

	/**
	 * update accounts table if email address or display name was changed from outside
	 */
	public function changeUserHook(IUser $user, string $feature, $newValue): void {
		$account = $this->accountManager->getAccount($user);

		try {
			switch ($feature) {
				case 'eMailAddress':
					$property = $account->getProperty(IAccountManager::PROPERTY_EMAIL);
					break;
				case 'displayName':
					$property = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
					break;
			}
		} catch (PropertyDoesNotExistException $e) {
			$this->logger->debug($e->getMessage(), ['exception' => $e]);
			return;
		}

		if (isset($property) && $property->getValue() !== (string)$newValue) {
			$property->setValue($newValue);
			$this->accountManager->updateAccount($account);
		}
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserChangedEvent) {
			return;
		}
		$this->changeUserHook($event->getUser(), $event->getFeature(), $event->getValue());
	}
}
