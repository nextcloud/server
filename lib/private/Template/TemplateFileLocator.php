<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

use OCP\Template\TemplateNotFoundException;

class TemplateFileLocator {
	/**
	 * @param string[] $dirs
	 */
	public function __construct(
		private array $dirs,
	) {
	}

	/**
	 * @return array{string,string} Directory path and filename
	 * @throws TemplateNotFoundException
	 */
	public function find(string $template): array {
		if ($template === '') {
			throw new \InvalidArgumentException('Empty template name');
		}

		foreach ($this->dirs as $dir) {
			$file = $dir . $template . '.php';
			if (is_file($file)) {
				return [$dir,$file];
			}
		}
		throw new TemplateNotFoundException('template file not found: template:' . $template);
	}
}
