<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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

namespace OCA\Settings\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setAuthtokenid(int $authtokenid)
 * @method int getAuthtokenid()
 * @method void setDiagnostic(string $diagnostic)
 * @method string getDiagnostic()
 * @method \DateTime getTimestamp()
 * @method void setTimestamp(\DateTime $timestamp)
 */
class ClientDiagnostic extends Entity {
	public const TYPE_CONFLICT = 'conflict';
	public const TYPE_FAILED_UPLOAD = 'failed-upload';
	public const TYPES = [
		self::TYPE_CONFLICT,
		self::TYPE_FAILED_UPLOAD,
	];

	/** @var int */
	protected $authtokenid;

	/** @var string json-encoded*/
	protected $diagnostic;

	/** @var \DateTime */
	public $timestamp;

	public function __construct() {
		$this->addType('authtokenid', 'int');
		$this->addType('diagnostic', 'string');
		$this->addType('timestamp', 'datetime');
	}

	public function getDiagnosticAsArray(): array {
		$data = json_decode($this->diagnostic, true, JSON_THROW_ON_ERROR);
		return $data;
	}
}
