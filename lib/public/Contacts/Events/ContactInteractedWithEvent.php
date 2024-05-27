<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * An event that allows apps to notify other components about an interaction
 * between two users. This can be used to build better recommendations and
 * suggestions in user interfaces.
 *
 * Emitters should add at least one identifier (uid, email, federated cloud ID)
 * of the recipient of the interaction.
 *
 * @since 19.0.0
 */
class ContactInteractedWithEvent extends Event {
	/** @var IUser */
	private $actor;

	/** @var string|null */
	private $uid;

	/** @var string|null */
	private $email;

	/** @var string|null */
	private $federatedCloudId;

	/**
	 * @param IUser $actor the user who started the interaction
	 *
	 * @since 19.0.0
	 */
	public function __construct(IUser $actor) {
		parent::__construct();
		$this->actor = $actor;
	}

	/**
	 * @return IUser
	 * @since 19.0.0
	 */
	public function getActor(): IUser {
		return $this->actor;
	}

	/**
	 * @return string|null
	 * @since 19.0.0
	 */
	public function getUid(): ?string {
		return $this->uid;
	}

	/**
	 * Set the uid of the person interacted with, if known
	 *
	 * @param string $uid
	 *
	 * @return self
	 * @since 19.0.0
	 */
	public function setUid(string $uid): self {
		$this->uid = $uid;
		return $this;
	}

	/**
	 * @return string|null
	 * @since 19.0.0
	 */
	public function getEmail(): ?string {
		return $this->email;
	}

	/**
	 * Set the email of the person interacted with, if known
	 *
	 * @param string $email
	 *
	 * @return self
	 * @since 19.0.0
	 */
	public function setEmail(string $email): self {
		$this->email = $email;
		return $this;
	}

	/**
	 * @return string|null
	 * @since 19.0.0
	 */
	public function getFederatedCloudId(): ?string {
		return $this->federatedCloudId;
	}

	/**
	 * Set the federated cloud of the person interacted with, if known
	 *
	 * @param string $federatedCloudId
	 *
	 * @return self
	 * @since 19.0.0
	 */
	public function setFederatedCloudId(string $federatedCloudId): self {
		$this->federatedCloudId = $federatedCloudId;
		return $this;
	}
}
