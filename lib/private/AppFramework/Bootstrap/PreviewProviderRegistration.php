<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use OCP\Preview\IProviderV2;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<IProviderV2>
 */
class PreviewProviderRegistration extends ServiceRegistration {
	public function __construct(
		string $appId,
		string $service,
		private string $mimeTypeRegex,
	) {
		parent::__construct($appId, $service);
	}

	public function getMimeTypeRegex(): string {
		return $this->mimeTypeRegex;
	}
}
