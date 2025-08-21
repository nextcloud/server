/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

type PresetAppConfigEntry = {
	key: string
	type: 'ARRAY' | 'BOOL' | 'FLOAT' | 'INT' | 'MIXED' | 'STRING'
	definition: string
	note: string
	lazy: boolean
	deprecated: boolean
}

type PresetIds = 'LARGE' | 'MEDIUM' | 'SMALL' | 'SHARED' | 'UNIVERSITY' | 'SCHOOL' | 'CLUB' | 'FAMILY' | 'PRIVATE' | 'NONE'

type PresetAppConfig = {
	entry: PresetAppConfigEntry
	defaults: Record<PresetIds, string>
	value?: unknown
}

export type PresetAppConfigs = Record<string, PresetAppConfig[]>
