/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type PreviewProviderRequirement = 'none' | 'imagick' | 'office' | 'ffmpeg' | 'imaginary'

export interface PreviewProvider {
	class: string
	name: string
	mimetypes: string
	requirement: PreviewProviderRequirement
	available: boolean
	enabled: boolean
}

/**
 * Number settings are `null` when unset, the built-in default then applies
 */
export interface PreviewLimits {
	maxX: number | null
	maxY: number | null
	maxMemory: number | null
	maxFilesizeImage: number | null
	jpegQuality: number | null
	webpQuality: number | null
	concurrencyNew: number | null
	concurrencyAll: number | null
	expirationDays: number | null
}

export interface PreviewSettings extends PreviewLimits {
	configIsReadOnly: boolean
	enabled: boolean
	providersConfigured: boolean
	providers: PreviewProvider[]
	dependencies: {
		imagick: boolean
		ffmpeg: string | null
		office: string | null
		imaginary: boolean
	}
}
