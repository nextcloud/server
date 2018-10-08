<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\WorkflowEngine\Check;

use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IRequest;

class FileName extends AbstractStringCheck {

	/** @var IRequest */
	protected $request;

	/** @var IStorage */
	protected $storage;

	/** @var string */
	protected $path;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 */
	public function __construct(IL10N $l, IRequest $request) {
		parent::__construct($l);
		$this->request = $request;
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 */
	public function setFileInfo(IStorage $storage, $path) {
		$this->storage = $storage;
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	protected function getActualValue(): string {
		return basename($this->path);
	}

	/**
	 * @param string $operator
	 * @param string $checkValue
	 * @param string $actualValue
	 * @return bool
	 */
	protected function executeStringCheck($operator, $checkValue, $actualValue): bool {
		if ($operator === 'is' || $operator === '!is') {
			$checkValue = mb_strtolower($checkValue);
			$actualValue = mb_strtolower($actualValue);
		}
		return parent::executeStringCheck($operator, $checkValue, $actualValue);
	}
}
