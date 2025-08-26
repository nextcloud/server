<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Address Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail address object
 *
 * @since 30.0.0
 *
 */
interface IAddress {

	/**
	 * sets the mail address
	 *
	 * @since 30.0.0
	 *
	 * @param string $value mail address (test@example.com)
	 *
	 * @return self return this object for command chaining
	 */
	public function setAddress(string $value): self;

	/**
	 * gets the mail address
	 *
	 * @since 30.0.0
	 *
	 * @return string returns the mail address
	 */
	public function getAddress(): ?string;

	/**
	 * sets the mail address label/name
	 *
	 * @since 30.0.0
	 *
	 * @param string $value mail address label/name
	 *
	 * @return self return this object for command chaining
	 */
	public function setLabel(string $value): self;

	/**
	 * gets the mail address label/name
	 *
	 * @since 30.0.0
	 *
	 * @return string returns the mail address label/name
	 */
	public function getLabel(): ?string;

}
