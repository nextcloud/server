<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\Attribute\Column;
use OCP\AppFramework\Db\Attribute\Table;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class to represent a tag.
 *
 * @method string getId()
 * @method void setId(string $id)
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getName()
 * @method void setName(string $name)
 */
#[Table(name: 'vcategory', useSnowflakeId: true)]
class Tag extends Entity {
	#[Column(name: 'uid', type: Types::STRING, length: 64, nullable: false)]
	protected ?string $owner = null;

	#[Column(name: 'type', type: Types::STRING, length: 64, nullable: false)]
	protected ?string $type = null;

	#[Column(name: 'category', type: Types::STRING, length: 255, nullable: false)]
	protected ?string $name = null;

	/**
	 * Constructor.
	 *
	 * @param ?string $owner The tag's owner
	 * @param ?string $type The type of item this tag is used for
	 * @param ?string $name The tag's name
	 */
	public function __construct(?string $owner = null, ?string $type = null, ?string $name = null) {
		parent::__construct();

		$this->setOwner($owner);
		$this->setType($type);
		$this->setName($name);
	}
}
