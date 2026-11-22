/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Modifiable parameters for the admin theming settings.
 */
export interface AdminThemingParameters {
	backgroundMime: string
	backgroundURL: string
	backgroundColor: string
	faviconMime: string
	legalNoticeUrl: string
	logoheaderMime: string
	logoMime: string
	name: string
	primaryColor: string
	privacyPolicyUrl: string
	slogan: string
	url: string
	disableUserTheming: boolean
	defaultApps: string[]
}

/**
 * Admin theming information.
 */
export interface AdminThemingInfo {
	isThemeable: boolean
	canThemeIcons: boolean

	notThemeableErrorMessage: string
	defaultBackgroundURL: string
	defaultBackgroundColor: string
	docUrl: string
	docUrlIcons: string
}
