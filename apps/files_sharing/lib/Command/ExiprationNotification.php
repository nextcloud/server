<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Command;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\Notification\IManager as NotificationManager;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExiprationNotification extends Command {
	/** @var NotificationManager */
	private $notificationManager;
	/** @var IDBConnection */
	private $connection;
	/** @var ITimeFactory */
	private $time;
	/** @var ShareManager */
	private $shareManager;

	public function __construct(ITimeFactory $time,
		NotificationManager $notificationManager,
		IDBConnection $connection,
		ShareManager $shareManager) {
		parent::__construct();

		$this->notificationManager = $notificationManager;
		$this->connection = $connection;
		$this->time = $time;
		$this->shareManager = $shareManager;
	}

	protected function configure() {
		$this
			->setName('sharing:expiration-notification')
			->setDescription('Notify share initiators when a share will expire the next day.');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		//Current time
		$minTime = $this->time->getDateTime();
		$minTime->add(new \DateInterval('P1D'));
		$minTime->setTime(0, 0, 0);

		$maxTime = clone $minTime;
		$maxTime->setTime(23, 59, 59);

		$shares = $this->shareManager->getAllShares();

		$now = $this->time->getDateTime();

		/** @var IShare $share */
		foreach ($shares as $share) {
			if ($share->getExpirationDate() === null
				|| $share->getExpirationDate()->getTimestamp() < $minTime->getTimestamp()
				|| $share->getExpirationDate()->getTimestamp() > $maxTime->getTimestamp()) {
				continue;
			}

			$notification = $this->notificationManager->createNotification();
			$notification->setApp('files_sharing')
				->setDateTime($now)
				->setObject('share', $share->getFullId())
				->setSubject('expiresTomorrow');

			// Only send to initiator for now
			$notification->setUser($share->getSharedBy());
			$this->notificationManager->notify($notification);
		}
		return 0;
	}
}
