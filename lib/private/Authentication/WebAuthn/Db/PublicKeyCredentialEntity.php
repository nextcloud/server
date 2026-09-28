<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\WebAuthn\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use Webauthn\PublicKeyCredentialSource;

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
 *
 * @since 30.0.0 Add userVerification attribute
 * @method bool|null getUserVerification();
 * @method void setUserVerification(bool $userVerification);
 */
class PublicKeyCredentialEntity extends Entity implements JsonSerializable {
	/** @var string */
	protected $name;

	/** @var string */
	protected $uid;

	/** @var string */
	protected $publicKeyCredentialId;

	/** @var string */
	protected $data;

	/** @var bool|null */
	protected $userVerification;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('uid', 'string');
		$this->addType('publicKeyCredentialId', 'string');
		$this->addType('data', 'string');
		$this->addType('userVerification', 'boolean');
	}

	public static function fromPublicKeyCrendentialSource(string $name, PublicKeyCredentialSource $publicKeyCredentialSource, bool $userVerification): PublicKeyCredentialEntity {
		$publicKeyCredentialEntity = new self();

		$publicKeyCredentialEntity->setName($name);
		$publicKeyCredentialEntity->setUid($publicKeyCredentialSource->getUserHandle());
		$publicKeyCredentialEntity->setPublicKeyCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
		$publicKeyCredentialEntity->setData(json_encode($publicKeyCredentialSource));
		$publicKeyCredentialEntity->setUserVerification($userVerification);

		return $publicKeyCredentialEntity;
	}

	public function toPublicKeyCredentialSource(): PublicKeyCredentialSource {
		return PublicKeyCredentialSource::createFromArray(
			json_decode($this->getData(), true)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}
}
