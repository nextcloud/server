<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for the viewer.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const SLIDESHOW_DELAY = 'slideshow_delay';
	public const VOLUME = 'volume';
	public const MUTED = 'muted';
	public const ORIGINAL_IMAGES = 'original_images';

	#[\Override]
	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	#[\Override]
	public function getAppConfigs(): array {
		return [];
	}

	#[\Override]
	public function getUserConfigs(): array {
		return [
			new Entry(
				self::SLIDESHOW_DELAY,
				ValueType::INT,
				defaultRaw: 5,
				definition: 'Seconds the slideshow shows each file for, from 1 to 60',
			),
			new Entry(
				self::VOLUME,
				ValueType::INT,
				defaultRaw: 100,
				definition: 'Volume of videos and audio, in percent',
			),
			new Entry(
				self::MUTED,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether videos and audio start muted',
			),
			new Entry(
				self::ORIGINAL_IMAGES,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether images are shown from the file itself rather than from a preview',
			),
		];
	}
}
