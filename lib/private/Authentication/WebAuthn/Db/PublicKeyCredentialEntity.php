<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\WebAuthn\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @since 19.0.0
 *
 * @method string getUid();
 * @method void setUid(string $uid)
 * @method string getName();
 * @method void setName(string $name);
 * @method string getPublicKeyCredentialId();
 * @method void setPublicKeyCredentialId(string $id);
 * @method string getData();
 * @method void setData(string $data);
 * @method bool|null getUserVerification();
 * @method void setUserVerification(bool $userVerification);
 *
 * @since 30.0.0 Add userVerification attribute
 */
class PublicKeyCredentialEntity extends Entity implements JsonSerializable {
	protected ?string $name = null;

	protected ?string $uid = null;

	protected ?string $publicKeyCredentialId = null;

	protected ?string $data = null;

	protected ?bool $userVerification = null;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('uid', 'string');
		$this->addType('publicKeyCredentialId', 'string');
		$this->addType('data', 'string');
		$this->addType('userVerification', 'boolean');
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}
}
