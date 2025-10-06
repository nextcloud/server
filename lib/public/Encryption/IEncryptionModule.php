<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Encryption;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for encryption modules for use by Nextcloud's Server-side Encryption (SSE).
 *
 * This interface allows Nextcloud SSE to support multiple interchangeable encryption modules.
 *
 * @since 8.1.0
 */
interface IEncryptionModule {
	/**
	 * Returns the technical unique ID of the encryption module.
	 *
	 * @return string Technical unique ID.
	 * @since 8.1.0
	 */
	public function getId(): string;

	/**
	 * Returns a human-readable (possibly translated) display name for the encryption module.
	 *
	 * @return string Display name.
	 * @since 8.1.0
	 */
	public function getDisplayName(): string;

	/**
	 * Initializes the encryption/decryption process for a file. This is the entry point for receiving file chunks.
	 *
	 * @param string $path Path to the file.
	 * @param string $user User reading or writing the file (null for public access).
	 * @param string $mode PHP stream open mode.
	 * @param array $header Header data read from the file.
	 * @param array $accessList List of users and public access; contains the keys 'users' and 'public'.
	 *
	 * @return array Header data as key-value pairs to be written to the header in case of a write operation,
	 *               or an empty array if no additional data is needed.
	 * @since 8.1.0
	 */
	public function begin(string $path, string $user, string $mode, array $header, array $accessList): array;

	/**
	 * Finalizes the encryption/decryption process. If there is remaining data in the buffer, it can be returned here.
	 *
	 * @param string $path Path to the file.
	 * @param string $position ID of the last block (format: "<Number>end").
	 *
	 * @return string Remaining data to be written to the file for write operations.
	 *
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function end(string $path, string $position): string;

	/**
	 * Encrypts data.
	 *
	 * @param string $data Data to encrypt.
	 * @param string $position Position of the block to encrypt (starts with '0').
	 *
	 * @return string Encrypted data.
	 *
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function encrypt(string $data, string $position): string;

	/**
	 * Decrypts data.
	 *
	 * @param string $data Data to decrypt.
	 * @param int|string $position Position of the block to decrypt.
	 *
	 * @return string Decrypted data.
	 *
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function decrypt(string $data, int|string $position): string;

	/**
	 * Updates the encrypted file, for example, granting additional users access.
	 *
	 * @param string $path Path to the file to update.
	 * @param string $uid User performing the operation.
	 * @param array $accessList List of users and public access; contains the keys 'users' and 'public'.
	 * @return bool True on success, false otherwise.
	 * @since 8.1.0
	 */
	public function update(string $path, string $uid, array $accessList): bool;

	/**
	 * Determines whether the file should be encrypted.
	 *
	 * @param string $path Path to the file.
	 * @return bool True if the file should be encrypted, false otherwise.
	 * @since 8.1.0
	 */
	public function shouldEncrypt(string $path): bool;

	/**
	 * Returns the size of the unencrypted payload per block.
	 * ownCloud reads/writes files with a block size of 8192 bytes.
	 *
	 * @param bool $signed Whether the block is signed.
	 * @return int Size of the unencrypted block.
	 * @since 8.1.0 Optional parameter $signed was added in 9.0.0.
	 */
	public function getUnencryptedBlockSize(bool $signed = false): int;

	/**
	 * Checks if the encryption module is able to read the file (e.g., if all encryption keys exist).
	 *
	 * @param string $path Path to the file.
	 * @param string $uid User for whom to check readability.
	 * @return bool True if readable, false otherwise.
	 * @since 8.1.0
	 */
	public function isReadable(string $path, string $uid): bool;

	/**
	 * Performs initial encryption of all files.
	 *
	 * @param InputInterface $input Input interface.
	 * @param OutputInterface $output Output interface for status information during encryption.
	 * @since 8.2.0
	 */
	public function encryptAll(InputInterface $input, OutputInterface $output): void;

	/**
	 * Prepares the encryption module to decrypt all files.
	 *
	 * @param InputInterface $input Input interface.
	 * @param OutputInterface $output Output interface for status information during decryption.
	 * @param string $user (optional) User for whom the files should be decrypted. If omitted, decrypts for all users.
	 * @return bool False on failure or if not supported by the module.
	 * @since 8.2.0
	 */
	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool;

	/**
	 * Checks if the module is ready to be used by the specified user.
	 * Returns false if key pairs have not been generated for the user.
	 *
	 * @param string $user User to check.
	 * @return bool True if ready, false otherwise.
	 * @since 9.1.0
	 */
	public function isReadyForUser(string $user): bool;

	/**
	 * Indicates whether the encryption module needs a detailed list of users with access to the file.
	 * For example, modules using per-user encryption keys may require this information.
	 *
	 * @return bool True if a detailed access list is required, false otherwise.
	 * @since 13.0.0
	 */
	public function needDetailedAccessList(): bool;
}
