<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Address Object
 *
 * This object is used to define the address and label of a email address
 *
 * @since 30.0.0
 *
 */
class Address implements \OCP\Mail\Provider\IAddress {

	/**
	 * initialize the mail address object
	 *
	 * @since 30.0.0
	 *
	 * @param string|null $address mail address (e.g test@example.com)
	 * @param string|null $label mail address label/name
	 */
	public function __construct(
		protected ?string $address = null,
		protected ?string $label = null,
	) {
	}

	/**
	 * sets the mail address
	 *
	 * @since 30.0.0
	 *
	 * @param string $value mail address (e.g. test@example.com)
	 *
	 * @return self return this object for command chaining
	 */
	public function setAddress(string $value): self {
		$this->address = $value;
		return $this;
	}

	/**
	 * gets the mail address
	 *
	 * @since 30.0.0
	 *
	 * @return string|null returns the mail address or null if one is not set
	 */
	public function getAddress(): ?string {
		return $this->address;
	}

	/**
	 * sets the mail address label/name
	 *
	 * @since 30.0.0
	 *
	 * @param string $value mail address label/name
	 *
	 * @return self return this object for command chaining
	 */
	public function setLabel(string $value): self {
		$this->label = $value;
		return $this;
	}

	/**
	 * gets the mail address label/name
	 *
	 * @since 30.0.0
	 *
	 * @return string|null returns the mail address label/name or null if one is not set
	 */
	public function getLabel(): ?string {
		return $this->label;
	}

}
