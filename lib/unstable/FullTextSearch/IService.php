<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

interface IService {
	public function getLogger(): ILoggerService;
	public function requestIndex(string $providerId, string $documentId): void;
	public function deleteIndex(string $providerId, string $documentId): void;
}
