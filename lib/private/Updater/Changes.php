<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Updater;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class Changes
 *
 * @package OC\Updater
 * @method string getVersion()=1
 * @method void setVersion(string $version)
 * @method string getEtag()
 * @method void setEtag(string $etag)
 * @method int getLastCheck()
 * @method void setLastCheck(int $lastCheck)
 * @method string getData()
 * @method void setData(string $data)
 */
class Changes extends Entity {
	/** @var string */
	protected $version = '';

	/** @var string */
	protected $etag = '';

	/** @var int */
	protected $lastCheck = 0;

	/** @var string */
	protected $data = '';

	public function __construct() {
		$this->addType('version', 'string');
		$this->addType('etag', 'string');
		$this->addType('lastCheck', Types::INTEGER);
		$this->addType('data', 'string');
	}
}
