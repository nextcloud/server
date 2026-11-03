<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use NCU\Files\NodeAttributes\INodeAttributeProvider;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<INodeAttributeProvider>
 */
class NodeAttributeProviderRegistration extends ServiceRegistration {
	/**
	 * @param list<string> $attributes in Clark notation
	 */
	public function __construct(
		string $appId,
		string $service,
		private array $attributes,
	) {
		parent::__construct($appId, $service);
	}

	/**
	 * @return list<string>
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}
}
