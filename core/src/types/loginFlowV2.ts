/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface LoginFlowV2Context {
	userAgent: string
	serverUrl: string
	userId: string | null
	userDisplayName: string | null
	declineUrl: string
	securitySettingsUrl: string
	clientIp: string | null
	browserIp: string
	createdAt: number
	sameIp: boolean | null
}

export interface LoginFlowV2Auth {
	stateToken: string
	loginRedirectUrl: string
	appTokenUrl: string
	direct: boolean
}

export interface LoginFlowV2Grant {
	stateToken: string
	actionUrl: string
	direct: boolean
}
