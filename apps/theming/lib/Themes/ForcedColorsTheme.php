<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;
use OCP\IL10N;

/**
 * Header actions (search, notifications, contacts) paint with
 * `--color-background-plain-text`, which the user agent does not remap
 * in forced-colors mode. Only that token is overridden so the rest of
 * the personal theme stays intact.
 */
class ForcedColorsTheme implements ITheme {
	public const ID = 'forced-colors';

	public function __construct(
		private IL10N $l,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getType(): int {
		return ITheme::TYPE_SUPPLEMENTARY;
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l->t('Forced colors');
	}

	#[\Override]
	public function getEnableLabel(): string {
		return $this->l->t('Enable forced colors');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Use the browser or operating system contrast colors so text and icons stay visible.');
	}

	#[\Override]
	public function getMediaQuery(): string {
		return '(forced-colors: active)';
	}

	#[\Override]
	public function getMeta(): array {
		return [];
	}

	#[\Override]
	public function getCSSVariables(): array {
		return [
			'--color-background-plain-text' => 'CanvasText',
		];
	}

	#[\Override]
	public function getCustomCss(): string {
		$variables = '';
		foreach ($this->getCSSVariables() as $variable => $value) {
			$variables .= "$variable:$value;";
		}

		// [data-theme-*] is set on body and would otherwise keep hex tokens
		// that the user agent does not remap. body[data-themes] is present
		// on every layout and beats those attribute selectors.
		return <<<CSS
			@media (forced-colors: active) {
				:root,
				body[data-themes] {
					$variables
				}
			}
		CSS;
	}
}
