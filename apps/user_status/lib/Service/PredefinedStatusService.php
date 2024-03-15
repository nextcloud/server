<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\Service;

use OCP\IL10N;
use OCP\UserStatus\IUserStatus;

/**
 * Class DefaultStatusService
 *
 * We are offering a set of default statuses, so we can
 * translate them into different languages.
 *
 * @package OCA\UserStatus\Service
 */
class PredefinedStatusService {
	private const MEETING = 'meeting';
	private const COMMUTING = 'commuting';
	private const SICK_LEAVE = 'sick-leave';
	private const VACATIONING = 'vacationing';
	private const REMOTE_WORK = 'remote-work';
	/**
	 * @deprecated See \OCP\UserStatus\IUserStatus::MESSAGE_CALL
	 */
	public const CALL = 'call';
	public const OUT_OF_OFFICE = 'out-of-office';

	/** @var IL10N */
	private $l10n;

	/**
	 * DefaultStatusService constructor.
	 *
	 * @param IL10N $l10n
	 */
	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * @return array
	 */
	public function getDefaultStatuses(): array {
		return [
			[
				'id' => self::MEETING,
				'icon' => '📅',
				'message' => $this->getTranslatedStatusForId(self::MEETING),
				'clearAt' => [
					'type' => 'period',
					'time' => 3600,
				],
			],
			[
				'id' => self::COMMUTING,
				'icon' => '🚌',
				'message' => $this->getTranslatedStatusForId(self::COMMUTING),
				'clearAt' => [
					'type' => 'period',
					'time' => 1800,
				],
			],
			[
				'id' => self::REMOTE_WORK,
				'icon' => '🏡',
				'message' => $this->getTranslatedStatusForId(self::REMOTE_WORK),
				'clearAt' => [
					'type' => 'end-of',
					'time' => 'day',
				],
			],
			[
				'id' => self::SICK_LEAVE,
				'icon' => '🤒',
				'message' => $this->getTranslatedStatusForId(self::SICK_LEAVE),
				'clearAt' => [
					'type' => 'end-of',
					'time' => 'day',
				],
			],
			[
				'id' => self::VACATIONING,
				'icon' => '🌴',
				'message' => $this->getTranslatedStatusForId(self::VACATIONING),
				'clearAt' => null,
			],
			[
				'id' => self::CALL,
				'icon' => '💬',
				'message' => $this->getTranslatedStatusForId(self::CALL),
				'clearAt' => null,
				'visible' => false,
			],
			[
				'id' => self::OUT_OF_OFFICE,
				'icon' => '🛑',
				'message' => $this->getTranslatedStatusForId(self::OUT_OF_OFFICE),
				'clearAt' => null,
				'visible' => false,
			],
		];
	}

	/**
	 * @param string $id
	 * @return array|null
	 */
	public function getDefaultStatusById(string $id): ?array {
		foreach ($this->getDefaultStatuses() as $status) {
			if ($status['id'] === $id) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * @param string $id
	 * @return string|null
	 */
	public function getIconForId(string $id): ?string {
		switch ($id) {
			case self::MEETING:
				return '📅';

			case self::COMMUTING:
				return '🚌';

			case self::SICK_LEAVE:
				return '🤒';

			case self::VACATIONING:
				return '🌴';

			case self::OUT_OF_OFFICE:
				return '🛑';

			case self::REMOTE_WORK:
				return '🏡';

			case self::CALL:
				return '💬';

			default:
				return null;
		}
	}

	/**
	 * @param string $lang
	 * @param string $id
	 * @return string|null
	 */
	public function getTranslatedStatusForId(string $id): ?string {
		switch ($id) {
			case self::MEETING:
				return $this->l10n->t('In a meeting');

			case self::COMMUTING:
				return $this->l10n->t('Commuting');

			case self::SICK_LEAVE:
				return $this->l10n->t('Out sick');

			case self::VACATIONING:
				return $this->l10n->t('Vacationing');

			case self::OUT_OF_OFFICE:
				return $this->l10n->t('Out of office');

			case self::REMOTE_WORK:
				return $this->l10n->t('Working remotely');

			case self::CALL:
				return $this->l10n->t('In a call');

			default:
				return null;
		}
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function isValidId(string $id): bool {
		return \in_array($id, [
			self::MEETING,
			self::COMMUTING,
			self::SICK_LEAVE,
			self::VACATIONING,
			self::OUT_OF_OFFICE,
			self::REMOTE_WORK,
			IUserStatus::MESSAGE_CALL,
			IUserStatus::MESSAGE_AVAILABILITY,
			IUserStatus::MESSAGE_VACATION,
			IUserStatus::MESSAGE_CALENDAR_BUSY,
			IUserStatus::MESSAGE_CALENDAR_BUSY_TENTATIVE,
		], true);
	}
}
