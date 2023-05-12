<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Louis Chemineau <louis@chmn.me>
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
namespace OC\Metadata;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getGroupName()
 * @method void setGroupName(string $groupName)
 * @method string getValue()
 * @method void setValue(string $value)
 * @see \OC\Core\Migrations\Version240000Date20220404230027
 */
class FileMetadata extends Entity {
	protected ?string $groupName = null;
	protected ?string $value = null;

	public function __construct() {
		$this->addType('groupName', 'string');
		$this->addType('value', Types::STRING);
	}

	public function getDecodedValue(): array {
		return json_decode($this->getValue(), true) ?? [];
	}

	public function setArrayAsValue(array $value): void {
		$this->setValue(json_encode($value, JSON_THROW_ON_ERROR));
	}
}
