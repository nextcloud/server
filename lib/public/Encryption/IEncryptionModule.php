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
	 * Returns the unique technical identifier for this encryption module.
	 * This ID must be globally unique among all modules and is used for internal reference.
	 *
	 * @return string The technical module ID.
	 * @since 8.1.0
	 */
	public function getId(): string;

	/**
	 * Returns a human-readable (and possibly translated) name for the encryption module.
	 * Intended for display in user interfaces and logs.
	 *
	 * @return string The display name of the module.
	 * @since 8.1.0
	 */
	public function getDisplayName(): string;

	/**
	 * Initializes the encryption or decryption process for a file.
	 * Called before chunked processing begins. Allows the module to prepare state,
	 * analyze headers, and determine access control.
	 *
	 * @param string $path The path to the file.
	 * @param string $user The user performing the operation (or null for public access).
	 * @param string $mode The PHP stream open mode (e.g., 'r', 'w').
	 * @param array $header Header data read from the file.
	 * @param array $accessList Access control list; must include keys 'users' and 'public'.
	 * @return array Key-value pairs for header data to write (if writing), or an empty array if not needed.
	 * @since 8.1.0
	 */
	public function begin(string $path, string $user, string $mode, array $header, array $accessList): array;

	/**
	 * Finalizes the encryption or decryption process for a file.
	 * Called after all chunks have been processed. Allows the module to flush buffers and perform cleanup.
	 *
	 * @param string $path The path to the file.
	 * @param string $position The identifier of the last block (e.g., "<Number>end").
	 * @return string Any remaining data to write at the end of a write operation (empty string if none).
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function end(string $path, string $position): string;

	/**
	 * Encrypts a chunk of file data.
	 *
	 * @param string $data The plaintext data to encrypt.
	 * @param string $position The position or identifier of the chunk/block (typically starts at '0').
	 * @return string The encrypted data for this chunk.
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function encrypt(string $data, string $position): string;

	/**
	 * Decrypts a chunk of file data.
	 *
	 * @param string $data The encrypted data to decrypt.
	 * @param int|string $position The position or identifier of the chunk/block.
	 * @return string The decrypted (plaintext) data for this chunk.
	 * @since 8.1.0
	 * @since 9.0.0 Parameter $position added.
	 */
	public function decrypt(string $data, int|string $position): string;

	/**
	 * Updates the encryption metadata for a file.
	 * For example, grants additional users access to the file or updates access lists.
	 *
	 * @param string $path Path to the file to update.
	 * @param string $uid The user performing the update.
	 * @param array $accessList Updated access control list; must include keys 'users' and 'public'.
	 * @return bool True on success, false otherwise.
	 * @since 8.1.0
	 */
	public function update(string $path, string $uid, array $accessList): bool;

	/**
	 * Checks whether the file at the given path should be encrypted by this module.
	 *
	 * @param string $path Path to the file.
	 * @return bool True if the file should be encrypted, false otherwise.
	 * @since 8.1.0
	 */
	public function shouldEncrypt(string $path): bool;

	/**
	 * Returns the size (in bytes) of each unencrypted block payload.
	 * Nextcloud reads/writes files using blocks of 8192 bytes.
	 *
	 * @param bool $signed True if the block is signed; affects the available payload size.
	 * @return int The size of the unencrypted block payload in bytes.
	 * @since 8.1.0 (optional parameter $signed added in 9.0.0)
	 */
	public function getUnencryptedBlockSize(bool $signed = false): int;

	/**
	 * Checks if this module can decrypt and read the given file for the specified user.
	 * For example, verifies that all necessary encryption keys exist for this user.
	 *
	 * @param string $path Path to the file.
	 * @param string $uid User for whom to check readability.
	 * @return bool True if the file can be read, false otherwise.
	 * @since 8.1.0
	 */
	public function isReadable(string $path, string $uid): bool;

	/**
	 * Performs initial encryption of all files (bulk operation).
	 * Used for server-side bulk encryption, typically from command-line tools.
	 *
	 * @param InputInterface $input Input interface.
	 * @param OutputInterface $output Output interface for status/progress information.
	 * @since 8.2.0
	 */
	public function encryptAll(InputInterface $input, OutputInterface $output): void;

	/**
	 * Prepares the encryption module for a bulk decrypt-all operation.
	 *
	 * @param InputInterface $input Input interface.
	 * @param OutputInterface $output Output interface for status/progress information.
	 * @param string $user (Optional) User for whom files should be decrypted. If omitted, decrypts for all users.
	 * @return bool True on success, false if the operation is not supported or failed.
	 * @since 8.2.0
	 */
	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool;

	/**
	 * Checks if the module is ready to be used by the specified user.
	 * For example, returns false if key pairs have not yet been generated for the user.
	 *
	 * @param string $user User to check.
	 * @return bool True if ready, false otherwise.
	 * @since 9.1.0
	 */
	public function isReadyForUser(string $user): bool;

	/**
	 * Indicates whether this module requires a detailed list of users with access to each file.
	 * For example, modules using per-user encryption keys may require this information.
	 *
	 * @return bool True if a detailed access list is required, false otherwise.
	 * @since 13.0.0
	 */
	public function needDetailedAccessList(): bool;
}
