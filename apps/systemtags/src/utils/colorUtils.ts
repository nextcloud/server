/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Color from 'color'

type hexColor = `#${string & (
  `${string}${string}${string}` |
  `${string}${string}${string}${string}${string}${string}`
)}`;

/**
 * Is the current theme dark?
 */
export function isDarkModeEnabled() {
	const darkModePreference = window?.matchMedia?.('(prefers-color-scheme: dark)')?.matches
	const darkModeSetting = document.body.getAttribute('data-themes')?.includes('dark')
	return darkModeSetting || darkModePreference || false
}

/**
 * Is the current theme high contrast?
 */
export function isHighContrastModeEnabled() {
	const highContrastPreference = window?.matchMedia?.('(forced-colors: active)')?.matches
	const highContrastSetting = document.body.getAttribute('data-themes')?.includes('highcontrast')
	return highContrastSetting || highContrastPreference || false
}

/**
 * Should we invert the text on this background color?
 * @param color RGB color value as a hex string
 * @return boolean
 */
export function invertTextColor(color: hexColor): boolean {
	return colorContrast(color, '#ffffff') < 4.5
}

/**
 * Is this color too bright?
 * @param color RGB color value as a hex string
 * @return boolean
 */
export function isBrightColor(color: hexColor): boolean {
	return calculateLuma(color) > 0.6
}

/**
 * Get color for on-page elements
 * theme color by default, grey if theme color is too bright.
 * @param color the color to contrast against, e.g. #ffffff
 * @param backgroundColor the background color to contrast against, e.g. #000000
 */
export function elementColor(
	color: hexColor,
	backgroundColor: hexColor,
): hexColor {
	const brightBackground = isBrightColor(backgroundColor)
	const blurredBackground = mix(
		backgroundColor,
		brightBackground ? color : '#ffffff',
		66,
	)

	let contrast = colorContrast(color, blurredBackground)
	const minContrast = isHighContrastModeEnabled() ? 5.6 : 3.2

	let iteration = 0
	let result = color
	const epsilon = (brightBackground ? -100 : 100) / 255
	while (contrast < minContrast && iteration++ < 100) {
		const hsl = hexToHSL(result)
		const l = Math.max(
			0,
			Math.min(255, hsl.l + epsilon),
		)
		result = hslToHex({ h: hsl.h, s: hsl.s, l })
		contrast = colorContrast(result, blurredBackground)
	}

	return result
}

/**
 * Get color for on-page text:
 * black if background is bright, white if background is dark.
 * @param color1 the color to contrast against, e.g. #ffffff
 * @param color2 the background color to contrast against, e.g. #000000
 * @param factor the factor to mix the colors between -100 and 100, e.g. 66
 */
export function mix(color1: hexColor, color2: hexColor, factor: number): hexColor {
	if (factor < -100 || factor > 100) {
		throw new RangeError('Factor must be between -100 and 100')
	}
	return new Color(color2).mix(new Color(color1), (factor + 100) / 200).hex()
}

/**
 * Lighten a color by a factor
 * @param color the color to lighten, e.g. #000000
 * @param factor the factor to lighten the color by between -100 and 100, e.g. -41
 */
export function lighten(color: hexColor, factor: number): hexColor {
	if (factor < -100 || factor > 100) {
		throw new RangeError('Factor must be between -100 and 100')
	}
	return new Color(color).lighten((factor + 100) / 200).hex()
}

/**
 * Darken a color by a factor
 * @param color the color to darken, e.g. #ffffff
 * @param factor the factor to darken the color by between -100 and 100, e.g. 32
 */
export function darken(color: hexColor, factor: number): hexColor {
	if (factor < -100 || factor > 100) {
		throw new RangeError('Factor must be between -100 and 100')
	}
	return new Color(color).darken((factor + 100) / 200).hex()
}

/**
 * Calculate the luminance of a color
 * @param color the color to calculate the luminance of, e.g. #ffffff
 */
export function calculateLuminance(color: hexColor): number {
	return hexToHSL(color).l
}

/**
 * Calculate the luma of a color
 * @param color the color to calculate the luma of, e.g. #ffffff
 */
export function calculateLuma(color: hexColor): number {
	const rgb = hexToRGB(color).map((value) => {
		value /= 255
		return value <= 0.03928
			? value / 12.92
			: Math.pow((value + 0.055) / 1.055, 2.4)
	})
	const [red, green, blue] = rgb
	return 0.2126 * red + 0.7152 * green + 0.0722 * blue
}

/**
 * Calculate the contrast between two colors
 * @param color1 the first color to calculate the contrast of, e.g. #ffffff
 * @param color2 the second color to calculate the contrast of, e.g. #000000
 */
export function colorContrast(color1: hexColor, color2: hexColor): number {
	const luminance1 = calculateLuma(color1) + 0.05
	const luminance2 = calculateLuma(color2) + 0.05
	return Math.max(luminance1, luminance2) / Math.min(luminance1, luminance2)
}

/**
 * Convert hex color to RGB
 * @param color RGB color value as a hex string
 */
export function hexToRGB(color: hexColor): [number, number, number] {
	return new Color(color).rgb().array()
}

/**
 * Convert RGB color to hex
 * @param color RGB color value as a hex string
 */
export function hexToHSL(color: hexColor): { h: number; s: number; l: number } {
	const hsl = new Color(color).hsl()
	return { h: hsl.color[0], s: hsl.color[1], l: hsl.color[2] }
}

/**
 * Convert HSL color to hex
 * @param hsl HSL color value as an object
 * @param hsl.h hue
 * @param hsl.s saturation
 * @param hsl.l lightness
 */
export function hslToHex(hsl: { h: number; s: number; l: number }): hexColor {
	return new Color(hsl).hex()
}

/**
 * Convert RGB color to hex
 * @param r red
 * @param g green
 * @param b blue
 */
export function rgbToHex(r: number, g: number, b: number): hexColor {
	const hex = ((1 << 24) | (r << 16) | (g << 8) | b).toString(16).slice(1)
	return `#${hex}`
}
