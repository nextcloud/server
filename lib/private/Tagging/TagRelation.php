<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\Entity;

/**
 *
 * @method int getObjid()
 * @method void setObjid(int $objid)
 * @method int getCategoryid()
 * @method void setCategoryid(int $categoryid)
 * @method string getType()
 * @method void setType(string $type)
 */
class TagRelation extends Entity {
	public function __construct(
		protected int $objid,
		protected int $categoryid,
		protected string $type,
	) {
		$this->addType('objid', 'integer');
		$this->addType('categoryid', 'integer');
		$this->addType('type', 'string');
	}
}
