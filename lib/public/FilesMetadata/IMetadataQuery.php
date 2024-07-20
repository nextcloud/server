<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata;

use OCP\FilesMetadata\Model\IFilesMetadata;

/**
 * Model that help building queries with metadata and metadata indexes
 *
 * @since 28.0.0
 */
interface IMetadataQuery {
	/** @since 28.0.0 */
	public const EXTRA = 'metadata';

	/**
	 * Add metadata linked to file id to the query
	 *
	 * @see self::extractMetadata()
	 * @since 28.0.0
	 */
	public function retrieveMetadata(): void;

	/**
	 * extract metadata from a result row
	 *
	 * @param array $row result row
	 *
	 * @return IFilesMetadata metadata
	 * @see self::retrieveMetadata()
	 * @since 28.0.0
	 */
	public function extractMetadata(array $row): IFilesMetadata;

	/**
	 * join the metadata_index table, based on a metadataKey.
	 * This will prep the query for condition based on this specific metadataKey.
	 * If a link to the metadataKey already exists, returns known alias.
	 *
	 * TODO: investigate how to support a search done on multiple values for same key (AND).
	 *
	 * @param string $metadataKey metadata key
	 * @param bool $enforce limit the request only to existing metadata
	 *
	 * @return string generated table alias
	 * @since 28.0.0
	 */
	public function joinIndex(string $metadataKey, bool $enforce = false): string;

	/**
	 * returns the name of the field for metadata key to be used in query expressions
	 *
	 * @param string $metadataKey metadata key
	 *
	 * @return string table field
	 * @since 28.0.0
	 */
	public function getMetadataKeyField(string $metadataKey): string;

	/**
	 * returns the name of the field for metadata string value to be used in query expressions
	 *
	 * Note: this method loads lazy appconfig values.
	 *
	 * @param string $metadataKey metadata key
	 *
	 * @return string table field
	 * @since 28.0.0
	 */
	public function getMetadataValueField(string $metadataKey): string;
}
