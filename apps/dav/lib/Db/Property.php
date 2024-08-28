<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserid()
 * @method string getPropertypath()
 * @method string getPropertyname()
 * @method string getPropertyvalue()
 */
class Property extends Entity {

	/** @var string|null */
	protected $userid;

	/** @var string|null */
	protected $propertypath;

	/** @var string|null */
	protected $propertyname;

	/** @var string|null */
	protected $propertyvalue;

	/** @var int|null */
	protected $valuetype;

}
