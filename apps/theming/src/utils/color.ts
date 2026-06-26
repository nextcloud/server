/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Get the text color for a given background color
 *
 * @param color - The hex color
 */
export function getTextColor(color: string) {
	return calculateLuma(color) > 0.6
		? '#000000'
		: '#ffffff'
}

/**
 * Calculate luminance of provided hex color
 *
 * @param color - The hex color
 */
function calculateLuma(color: string) {
	const [red, green, blue] = hexToRGB(color)
	return (0.2126 * red + 0.7152 * green + 0.0722 * blue) / 255
}

/**
 * Convert hex color to RGB
 *
 * @param hex - The hex color
 */
function hexToRGB(hex: string): [number, number, number] {
	if (hex.length < 6) {
		// handle shorthand hex colors like #FFF
		const result = /^#?([a-f\d])([a-f\d])([a-f\d])/i.exec(hex)
		if (result) {
			hex = `#${result[1]!.repeat(2)}${result[2]!.repeat(2)}${result[3]!.repeat(2)}`
		}
	}

	const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
	return result
		? [parseInt(result[1]!, 16), parseInt(result[2]!, 16), parseInt(result[3]!, 16)]
		: [0, 0, 0]
}
