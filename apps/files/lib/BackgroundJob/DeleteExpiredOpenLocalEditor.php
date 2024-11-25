<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\BackgroundJob;

use OCA\Files\Db\OpenLocalEditorMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Delete all expired "Open local editor" token
 */
class DeleteExpiredOpenLocalEditor extends TimedJob {
	protected OpenLocalEditorMapper $mapper;

	public function __construct(
		ITimeFactory $time,
		OpenLocalEditorMapper $mapper
	) {
		parent::__construct($time);
		$this->mapper = $mapper;

		// Run every 12h
		$this->interval = 12 * 3600;
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument): void {
		$this->mapper->deleteExpiredTokens($this->time->getTime());
	}
}
