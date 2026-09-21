<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache;

use OC\DB\QueryBuilder\ExtendedQueryBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;

/**
 * Query builder with commonly used helpers for filecache queries
 */
class CacheQueryBuilder extends ExtendedQueryBuilder {
	private ?string $alias = null;

	public function __construct(
		IQueryBuilder $queryBuilder,
		private IFilesMetadataManager $filesMetadataManager,
	) {
		parent::__construct($queryBuilder);
	}

	public function selectTagUsage(): self {
		$this
			->select('systemtag.name', 'systemtag.id', 'systemtag.visibility', 'systemtag.editable', 'systemtag.etag', 'systemtag.color')
			->selectAlias($this->func()->count('filecache.fileid'), 'number_files')
			->selectAlias($this->func()->max('filecache.fileid'), 'ref_file_id')
			->from('filecache', 'filecache')
			// Compare as strings (objectid = CAST(fileid AS CHAR)) so the systag_by_objectid
			// index on the string column objectid stays usable; casting objectid to int (or
			// the implicit string->number coercion against the bigint fileid) defeats it.
			->leftJoin('filecache', 'systemtag_object_mapping', 'systemtagmap', $this->expr()->andX(
				$this->expr()->eq('systemtagmap.objectid', $this->expr()->castColumn('filecache.fileid', IQueryBuilder::PARAM_STR)),
				$this->expr()->eq('systemtagmap.objecttype', $this->createNamedParameter('files'))
			))
			->leftJoin('systemtagmap', 'systemtag', 'systemtag', $this->expr()->andX(
				$this->expr()->eq('systemtag.id', 'systemtagmap.systemtagid'),
				$this->expr()->eq('systemtag.visibility', $this->createNamedParameter(true))
			))
			->groupBy('systemtag.name', 'systemtag.id', 'systemtag.visibility', 'systemtag.editable');

		return $this;
	}

	public function selectFileCache(?string $alias = null, bool $joinExtendedCache = true) {
		$name = $alias ?: 'filecache';
		$this->select("$name.fileid", 'storage', 'path', 'path_hash', "$name.parent", "$name.name", 'mimetype', 'mimepart', 'size', 'mtime',
			'storage_mtime', 'encrypted', "$name.etag", "$name.permissions", 'checksum', 'unencrypted_size')
			->from('filecache', $name);

		if ($joinExtendedCache) {
			$this->addSelect('metadata_etag', 'creation_time', 'upload_time');
			$this->leftJoin($name, 'filecache_extended', 'fe', $this->expr()->eq("$name.fileid", 'fe.fileid'));
		}

		$this->alias = $name;

		return $this;
	}

	public function whereStorageId(int $storageId) {
		$this->andWhere($this->expr()->eq('storage', $this->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));

		return $this;
	}

	public function whereFileId(int $fileId) {
		$alias = $this->alias;
		if ($alias) {
			$alias .= '.';
		} else {
			$alias = '';
		}

		$this->andWhere($this->expr()->eq("{$alias}fileid", $this->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));

		return $this;
	}

	public function wherePath(string $path) {
		$this->andWhere($this->expr()->eq('path_hash', $this->createNamedParameter(md5($path))));

		return $this;
	}

	public function whereParent(int $parent) {
		$alias = $this->alias;
		if ($alias) {
			$alias .= '.';
		} else {
			$alias = '';
		}

		$this->andWhere($this->expr()->eq("{$alias}parent", $this->createNamedParameter($parent, IQueryBuilder::PARAM_INT)));

		return $this;
	}

	public function whereParentInParameter(string $parameter) {
		$alias = $this->alias;
		if ($alias) {
			$alias .= '.';
		} else {
			$alias = '';
		}

		$this->andWhere($this->expr()->in("{$alias}parent", $this->createParameter($parameter)));

		return $this;
	}

	/**
	 * join metadata to current query builder and returns an helper
	 *
	 * @return IMetadataQuery
	 */
	public function selectMetadata(): IMetadataQuery {
		$metadataQuery = $this->filesMetadataManager->getMetadataQuery($this, $this->alias, 'fileid');
		$metadataQuery->retrieveMetadata();
		return $metadataQuery;
	}
}
