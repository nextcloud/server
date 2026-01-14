<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Encryption;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface IEncryptionModule
 *
 * @since 8.1.0
 */
interface IEncryptionModule {
	/**
	 * @return string defining the technical unique id
	 * @since 8.1.0
	 */
	public function getId(): string;

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getDisplayName(): string;

	/**
	 * start receiving chunks from a file. This is the place where you can
	 * perform some initial step before starting encrypting/decrypting the
	 * chunks
	 *
	 * @param string $path to the file
	 * @param string|null $user who read/write the file (null for public access)
	 * @param string $mode php stream open mode
	 * @param array $header contains the header data read from the file
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 *
	 * @return array $header contain data as key-value pairs which should be
	 *               written to the header, in case of a write operation
	 *               or if no additional data is needed return an empty array
	 * @since 8.1.0
	 */
	public function begin(string $path, ?string $user, string $mode, array $header, array $accessList): array;

	/**
	 * last chunk received. This is the place where you can perform some final
	 * operation and return some remaining data if something is left in your
	 * buffer.
	 *
	 * @param string $path to the file
	 * @param string $position id of the last block (looks like "<Number>end")
	 *
	 * @return string|null remained data which should be written to the file in case
	 *                of a write operation, or null
	 *
	 * @since 8.1.0
	 * @since 9.0.0 parameter $position added
	 */
	public function end(string $path, string $position): ?string;

	/**
	 * encrypt data
	 *
	 * @param string $data you want to encrypt
	 * @param string|int $position position of the block we want to encrypt (starts with '0')
	 *
	 * @return string encrypted data
	 *
	 * @since 8.1.0
	 * @since 9.0.0 parameter $position added
	 */
	public function encrypt(string $data, string|int $position): string;

	/**
	 * decrypt data
	 *
	 * @param string $data you want to decrypt
	 * @param string|int $position position of the block we want to decrypt
	 *
	 * @return string decrypted data
	 *
	 * @since 8.1.0
	 * @since 9.0.0 parameter $position added
	 */
	public function decrypt(string $data, string|int $position): string;

	/**
	 * update encrypted file, e.g. give additional users access to the file
	 *
	 * @param string $path path to the file which should be updated
	 * @param string $uid of the user who performs the operation
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 * @return bool
	 * @since 8.1.0
	 */
	public function update(string $path, string $uid, array $accessList): bool;

	/**
	 * should the file be encrypted or not
	 *
	 * @param string $path
	 * @return bool
	 * @since 8.1.0
	 */
	public function shouldEncrypt(string $path): bool;

	/**
	 * get size of the unencrypted payload per block.
	 * ownCloud read/write files with a block size of 8192 byte
	 *
	 * @param bool $signed
	 * @return int
	 * @since 8.1.0 optional parameter $signed was added in 9.0.0
	 */
	public function getUnencryptedBlockSize(bool $signed = false): int;

	/**
	 * check if the encryption module is able to read the file,
	 * e.g. if all encryption keys exists
	 *
	 * @param string $path
	 * @param string $uid user for whom we want to check if they can read the file
	 * @return bool
	 * @since 8.1.0
	 */
	public function isReadable(string $path, string $uid): bool;

	/**
	 * Initial encryption of all files
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output write some status information to the terminal during encryption
	 * @since 8.2.0
	 */
	public function encryptAll(InputInterface $input, OutputInterface $output): void;

	/**
	 * prepare encryption module to decrypt all files
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output write some status information to the terminal during encryption
	 * @param string $user (optional) for which the files should be decrypted, default = all users
	 * @return bool return false on failure or if it isn't supported by the module
	 * @since 8.2.0
	 */
	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool;

	/**
	 * Check if the module is ready to be used by that specific user.
	 * In case a module is not ready - because e.g. key pairs have not been generated
	 * upon login this method can return false before any operation starts and might
	 * cause issues during operations.
	 *
	 * @param string $user
	 * @return bool
	 * @since 9.1.0
	 */
	public function isReadyForUser(string $user): bool;

	/**
	 * Does the encryption module needs a detailed list of users with access to the file?
	 * For example if the encryption module uses per-user encryption keys and needs to know
	 * the users with access to the file to encrypt/decrypt it.
	 *
	 * @since 13.0.0
	 * @return bool
	 */
	public function needDetailedAccessList(): bool;
}
