/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale, translate as t } from '@nextcloud/l10n'

/**
 * Plyr `i18n` option so the player controls and menus
 * (e.g. the settings/speed dropdown) are translated.
 *
 * Plyr ships English defaults only; passing this map makes it use the
 * Nextcloud translations instead.
 */
export const plyrTranslations: Record<string, string> = {
	restart: t('viewer', 'Restart'),
	rewind: t('viewer', 'Rewind {seektime}s'),
	play: t('viewer', 'Play'),
	pause: t('viewer', 'Pause'),
	fastForward: t('viewer', 'Forward {seektime}s'),
	seek: t('viewer', 'Seek'),
	seekLabel: t('viewer', '{currentTime} of {duration}'),
	played: t('viewer', 'Played'),
	buffered: t('viewer', 'Buffered'),
	currentTime: t('viewer', 'Current time'),
	duration: t('viewer', 'Duration'),
	volume: t('viewer', 'Volume'),
	mute: t('viewer', 'Mute'),
	unmute: t('viewer', 'Unmute'),
	enableCaptions: t('viewer', 'Enable captions'),
	disableCaptions: t('viewer', 'Disable captions'),
	download: t('viewer', 'Download'),
	enterFullscreen: t('viewer', 'Enter fullscreen'),
	exitFullscreen: t('viewer', 'Exit fullscreen'),
	frameTitle: t('viewer', 'Player for {title}'),
	captions: t('viewer', 'Captions'),
	settings: t('viewer', 'Settings'),
	pip: t('viewer', 'PIP'),
	menuBack: t('viewer', 'Go back to previous menu'),
	speed: t('viewer', 'Speed'),
	normal: t('viewer', 'Normal'),
	quality: t('viewer', 'Quality'),
	loop: t('viewer', 'Loop'),
	start: t('viewer', 'Start'),
	end: t('viewer', 'End'),
	all: t('viewer', 'All'),
	reset: t('viewer', 'Reset'),
	disabled: t('viewer', 'Disabled'),
	enabled: t('viewer', 'Enabled'),
	advertisement: t('viewer', 'Ad'),
}

/**
 * Plyr renders speed values via a plain template literal (`` `${speed}×` ``),
 * which always uses a `.` decimal separator regardless of locale — so German
 * shows `1.5×` instead of `1,5×`. Plyr exposes no hook for this, so we
 * re-format the rendered labels using the user's locale.
 *
 * The speed panel's id always ends in `-speed`. Each entry is a
 * `button[role="menuitemradio"]` carrying the numeric speed in its `value`
 * attribute (the source of truth). The home-pane `.plyr__menu__value` badge
 * shows the current speed as a bare `<number>×` string, which we match by
 * pattern so we never touch the quality/captions badges.
 *
 * @param root the Plyr root element to localize labels within
 */
export function localizeSpeedLabels(root: ParentNode): void {
	const formatter = new Intl.NumberFormat(getCanonicalLocale())
	const speedLabel = (value: number): string => value === 1 ? t('viewer', 'Normal') : `${formatter.format(value)}×`

	// Speed submenu radio items, scoped to the speed panel so we don't touch
	// quality (e.g. "1080") or captions entries.
	const items = root.querySelectorAll<HTMLButtonElement>('.plyr__menu__container [id$="-speed"] [role="menuitemradio"]')
	items.forEach((item) => {
		const value = Number.parseFloat(item.value)
		if (Number.isNaN(value)) {
			return
		}
		const label = item.querySelector('span')
		if (label) {
			label.textContent = speedLabel(value)
		}
	})

	// Home-pane badge showing the current speed (e.g. "Speed: 1,5×"). Match the
	// bare "<number>×" form so quality/captions badges are left untouched.
	root.querySelectorAll<HTMLElement>('.plyr__menu__value').forEach((badge) => {
		const match = /^(\d+(?:\.\d+)?)×$/.exec((badge.textContent ?? '').trim())
		if (match?.[1]) {
			badge.textContent = `${formatter.format(Number.parseFloat(match[1]))}×`
		}
	})
}
