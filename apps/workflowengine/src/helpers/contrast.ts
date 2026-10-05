/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Color from 'color'

/** Text colour to use when no better one can be determined. */
export const DEFAULT_TEXT_COLOR = 'var(--color-main-text)'

/** Smallest contrast ratio that still counts as readable. */
const MINIMUM_CONTRAST = 4.5

/**
 * The text colour of a background that is known without resolving it first.
 *
 * Returns `null` for a background whose actual colour only the browser knows,
 * for instance a custom property an app registered its operation with.
 *
 * @param backgroundColor - The background as the operation declares it
 */
export function knownTextColor(backgroundColor: string): string | null {
	if (backgroundColor === 'transparent') {
		return DEFAULT_TEXT_COLOR
	}
	if (backgroundColor === 'var(--color-primary-element)') {
		return 'var(--color-primary-element-text)'
	}
	return null
}

/**
 * The text colour that reads on a resolved background colour.
 *
 * @param backgroundColor - A colour value, e.g. `#ff5900` or `rgb(255, 89, 0)`
 */
export function contrastingTextColor(backgroundColor: string): string {
	try {
		const contrast = Color(backgroundColor).contrast(Color('#ffffff'))
		return contrast > MINIMUM_CONTRAST ? '#ffffff' : '#000000'
	} catch {
		return DEFAULT_TEXT_COLOR
	}
}

/**
 * The filter that keeps a dark icon visible on its background.
 *
 * @param textColor - The text colour that was picked for the background
 */
export function iconFilterFor(textColor: string): string {
	return textColor === '#000000' ? 'invert(100%)' : 'none'
}
