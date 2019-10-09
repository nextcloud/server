<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\AppFramework\Db;


use function lcfirst;
use function substr;

/**
 * @method integer getId()
 * @method void setId(integer $id)
 * @since 7.0.0
 */
abstract class Entity extends BaseEntity {

	/** @var int */
	public $id;

	/**
	 * @return array with attribute and type
	 * @since 7.0.0
	 */
	public function getFieldTypes(): array {
		$types = parent::getFieldTypes();
		$types['id'] = 'integer';
		return $types;
	}
}
