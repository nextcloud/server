<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;
use OCP\IL10N;

class MotionSickness implements ITheme {

	public function __construct(
		private IL10N $l,
	) {
	}

	public function getCustomCss(): string
	{
		return '';
	}

	public function getMeta(): array
	{
		return [];
	}

	public function getId(): string {
		return 'motionSickness';
	}

	public function getType(): int {
		return ITheme::TYPE_FONT;
	}

	public function getTitle(): string {
		return $this->l->t('Motion sickness');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Motion sickness reduction');
	}

	public function getDescription(): string {
		return $this->l->t('Prevents animations, such as scaling or panning large objects, that can trigger discomfort for those with vestibular motion disorders.');
	}

	public function getCSSVariables(): array {
		$variables = [
			'--animation-quick' => '0',
			'--animation-slow' => '0',
		];

		return $variables;
	}

	public function getMediaQuery(): string {
		return '(prefers-reduced-motion: reduce)';
	}
}
