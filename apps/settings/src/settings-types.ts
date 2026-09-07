/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IRichObjectParameter {
	/**
	 * ID of the rich object
	 */
	id: string | number
	/**
	 * Name of the rich object
	 */
	name: string
	/**
	 * Type of the rich object
	 */
	type: string

	/**
	 * Additional rich object properties
	 */
	[key: string]: unknown
}

export type IRichObjectParameters = Record<string, IRichObjectParameter>

export interface ISetupCheck {
	name: string
	severity: 'success' | 'info' | 'warning' | 'error'
	description: string
	descriptionParameters: IRichObjectParameters
	linkToDoc?: string
}
