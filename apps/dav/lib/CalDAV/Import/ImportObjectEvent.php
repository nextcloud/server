<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Import;

final readonly class ImportObjectEvent implements ImportEvent {
	/**
	 * @param list<string> $errors
	 */
	public function __construct(
		public ?string $identifier,
		public ImportDisposition $disposition,
		public array $errors = [],
	) {
	}

	public function isError(): bool {
		return $this->disposition === ImportDisposition::Error;
	}

	/**
	 * @return array{type: 'object', identifier: ?string, disposition: string, errors: list<string>}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		$result = [
			'type' => 'object',
			'identifier' => $this->identifier,
			'disposition' => $this->disposition->value,
			'errors' => $this->errors,
		];

		return $result;
	}
}
