<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OC\Support\Subscription;

use OCP\HintException;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Support\Subscription\IAssertion;
use OCP\Support\Subscription\IRegistry;

class Assertion implements IAssertion {
	private IRegistry $registry;
	private IFactory $l10nFactory;
	private IManager $notificationManager;

	public function __construct(IRegistry $registry, IFactory $l10nFactory, IManager $notificationManager) {
		$this->registry = $registry;
		$this->l10nFactory = $l10nFactory;
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @inheritDoc
	 */
	public function createUserIsLegit(): void {
		if ($this->registry->delegateIsHardUserLimitReached($this->notificationManager)) {
			$l = $this->l10nFactory->get('lib');
			throw new HintException($l->t('The user was not created because the user limit has been reached. Check your notifications to learn more.'));
		}
	}
}
