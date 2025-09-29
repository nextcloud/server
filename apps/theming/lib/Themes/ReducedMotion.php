<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;
use OCP\IL10N;

class ReducedMotion implements ITheme {

	public function __construct(
		private IL10N $l,
	) {
	}

	#[\Override]
	public function getCustomCss(): string
	{
		return '';
	}

	#[\Override]
	public function getMeta(): array
	{
		return [];
	}

	#[\Override]
	public function getId(): string {
		return 'reduced-motion';
	}

	#[\Override]
	public function getType(): int {
		return ITheme::TYPE_FONT;
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l->t('Reduced motion');
	}

	#[\Override]
	public function getEnableLabel(): string {
		return $this->l->t('Motion sickness reduction');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Prevents animations, such as scaling or panning large objects, that can trigger discomfort for those with vestibular motion disorders.');
	}

	#[\Override]
	public function getCSSVariables(): array {
		$variables = [
			'--animation-quick' => '0',
			'--animation-slow' => '0',
		];

		return $variables;
	}

	#[\Override]
	public function getMediaQuery(): string {
		return '(prefers-reduced-motion: reduce)';
	}
}
