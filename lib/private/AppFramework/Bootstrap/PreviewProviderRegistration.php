<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<\OCP\Preview\IProviderV2>
 */
class PreviewProviderRegistration extends ServiceRegistration {
	/** @var string */
	private $mimeTypeRegex;

	public function __construct(string $appId,
		string $service,
		string $mimeTypeRegex) {
		parent::__construct($appId, $service);
		$this->mimeTypeRegex = $mimeTypeRegex;
	}

	public function getMimeTypeRegex(): string {
		return $this->mimeTypeRegex;
	}
}
