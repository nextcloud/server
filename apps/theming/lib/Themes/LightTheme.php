<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class LightTheme extends DefaultTheme implements ITheme {

	public function getId(): string {
		return 'light';
	}

	public function getTitle(): string {
		return $this->l->t('Light theme');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable the default light theme');
	}

	public function getDescription(): string {
		return $this->l->t('The default light appearance.');
	}

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: light)';
	}

	public function getMeta(): array {
		// https://html.spec.whatwg.org/multipage/semantics.html#meta-color-scheme
		return [[
			'name' => 'color-scheme',
			'content' => 'light',
		]];
	}
}
