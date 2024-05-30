<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\Entity;

/**
 * Class to represent a tag.
 *
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getName()
 * @method void setName(string $name)
 */
class Tag extends Entity {
	protected $owner;
	protected $type;
	protected $name;

	/**
	 * Constructor.
	 *
	 * @param string $owner The tag's owner
	 * @param string $type The type of item this tag is used for
	 * @param string $name The tag's name
	 */
	public function __construct($owner = null, $type = null, $name = null) {
		$this->setOwner($owner);
		$this->setType($type);
		$this->setName($name);
	}

	/**
	 * Transform a database columnname to a property
	 *
	 * @param string $columnName the name of the column
	 * @return string the property name
	 * @todo migrate existing database columns to the correct names
	 * to be able to drop this direct mapping
	 */
	public function columnToProperty($columnName) {
		if ($columnName === 'category') {
			return 'name';
		} elseif ($columnName === 'uid') {
			return 'owner';
		} else {
			return parent::columnToProperty($columnName);
		}
	}

	/**
	 * Transform a property to a database column name
	 *
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	public function propertyToColumn($property) {
		if ($property === 'name') {
			return 'category';
		} elseif ($property === 'owner') {
			return 'uid';
		} else {
			return parent::propertyToColumn($property);
		}
	}
}
