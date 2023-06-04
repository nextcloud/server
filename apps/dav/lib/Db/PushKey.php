<?php

declare(strict_types=1);

/**
 * @copyright 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
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
namespace OCA\DAV\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getPrincipalUri()
 * @method void setPrincipalUri(string $principalUri)
 * @method string getUri()
 * @method void setUri(string $principalUri)
 * @method string getPushKey()
 * @method void setPushKey(string $pushKey)
 */
class PushKey extends Entity {
	protected ?string $principalUri = null;
	protected ?string $uri = null;
	protected ?string $pushKey = null;

	public function __construct() {
		$this->addType('principalUri', 'string');
		$this->addType('uri', 'string');
		$this->addType('pushKey', 'string');
	}
}
