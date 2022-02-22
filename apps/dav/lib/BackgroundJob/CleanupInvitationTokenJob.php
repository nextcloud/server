<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IDBConnection;

class CleanupInvitationTokenJob extends TimedJob {

	/** @var IDBConnection  */
	private $db;

	public function __construct(IDBConnection $db, ITimeFactory $time) {
		parent::__construct($time);
		$this->db = $db;

		// Run once a day at off-peak time
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		$query = $this->db->getQueryBuilder();
		$query->delete('calendar_invitations')
			->where($query->expr()->lt('expiration',
				$query->createNamedParameter($this->time->getTime())))
			->execute();
	}
}
