<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

use NCU\FullTextSearch\Model\Document;

interface IContentProvider {
	public function getId(): string;
	public function getConfiguration(): array;

//	public function setIndexOptions(IIndexOptions $options);
	public function getDocument(string $documentId): ?Document;
}
