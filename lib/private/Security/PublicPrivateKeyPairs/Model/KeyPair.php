<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\PublicPrivateKeyPairs\Model;

use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;

/**
 * @inheritDoc
 *
 * @since 30.0.0
 */
class KeyPair implements IKeyPair {
	private string $publicKey = '';
	private string $privateKey = '';
	private array $options = [];

	public function __construct(
		private readonly string $app,
		private readonly string $name
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $publicKey
	 * @return IKeyPair
	 * @since 30.0.0
	 */
	public function setPublicKey(string $publicKey): self {
		$this->publicKey = $publicKey;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getPublicKey(): string {
		return $this->publicKey;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $privateKey
	 * @return IKeyPair
	 * @since 30.0.0
	 */
	public function setPrivateKey(string $privateKey): self {
		$this->privateKey = $privateKey;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getPrivateKey(): string {
		return $this->privateKey;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $options
	 * @return IKeyPair
	 * @since 30.0.0
	 */
	public function setOptions(array $options): self {
		$this->options = $options;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getOptions(): array {
		return $this->options;
	}
}
