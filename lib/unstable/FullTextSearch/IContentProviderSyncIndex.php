<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

use Generator;
use OCP\FullTextSearch\IFullTextSearchManager;

/**
 * this interface needs to be implemented to re-generate a full sync
 * if not implemented, the only source of documents to be indexed are via the API
 *
 * @see IFullTextSearchManager::requestIndex()
 */
interface IContentProviderSyncIndex {
	/*
	 * if $qh is ignored, indexes are compared lately in the process
	 * if the IIndexQueryHelper is useless but returned documents are to be indexed, you must
	 * initiated the method with a call to $qh->notNeeded()
	 *
	 * @return Generator<UnindexedDocument|[UnindexedDocument]>
	 */
	public function getUnindexedDocuments(IIndexQueryHelper $qh): Generator;
}
