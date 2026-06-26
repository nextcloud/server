/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IRichObjectParameter {
	[index: string]: string
	type: string
}

export type IRichObjectParameters = Record<string, IRichObjectParameter>

export interface ISetupCheck {
	name: string
	severity: 'success' | 'info' | 'warning' | 'error'
	description: string
	descriptionParameters: IRichObjectParameters
	linkToDoc?: string
}
