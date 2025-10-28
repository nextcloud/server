<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

interface ILoggerService {
	public function info(string $entry): void;
	public function action(string $entry): void;
	public function warning(string $entry, array $data = []): void;
	public function error(string $entry, array $data = []): void;
}
