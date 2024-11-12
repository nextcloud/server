<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\PublicPrivateKeyPairs\Model;

/**
 * simple model that store key pair, its name, its origin (app)
 * and the options used during its creation
 *
 * @experimental 31.0.0
 * @since 31.0.0
 */
interface IKeyPair {
	/**
	 * returns id of the app owning the key pair
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getApp(): string;

	/**
	 * returns name of the key pair
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getName(): string;

	/**
	 * set public key
	 *
	 * @param string $publicKey
	 * @return IKeyPair
	 * @since 31.0.0
	 */
	public function setPublicKey(string $publicKey): IKeyPair;

	/**
	 * returns public key
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getPublicKey(): string;

	/**
	 * set private key
	 *
	 * @param string $privateKey
	 * @return IKeyPair
	 * @since 31.0.0
	 */
	public function setPrivateKey(string $privateKey): IKeyPair;

	/**
	 * returns private key
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getPrivateKey(): string;

	/**
	 * set options
	 *
	 * @param array $options
	 * @return IKeyPair
	 * @since 31.0.0
	 */
	public function setOptions(array $options): IKeyPair;

	/**
	 * returns options
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getOptions(): array;
}
