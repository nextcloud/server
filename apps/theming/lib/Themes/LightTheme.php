<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class LightTheme extends DefaultTheme implements ITheme {

	#[\Override]
	public function getId(): string {
		return 'light';
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l->t('Light theme');
	}

	#[\Override]
	public function getEnableLabel(): string {
		return $this->l->t('Enable the default light theme');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l->t('The default light appearance.');
	}

	#[\Override]
	public function getMediaQuery(): string {
		return '(prefers-color-scheme: light)';
	}

	#[\Override]
	public function getMeta(): array {
		// https://html.spec.whatwg.org/multipage/semantics.html#meta-color-scheme
		return [[
			'name' => 'color-scheme',
			'content' => 'light',
		]];
	}
}
