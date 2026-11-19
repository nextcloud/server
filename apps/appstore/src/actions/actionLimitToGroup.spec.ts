/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { describe, expect, test } from 'vitest'
import { actionLimitToGroup } from './actionLimitToGroup.ts'

describe('actionLimitToGroup', () => {
	test('is offered for an enabled app', () => {
		expect(actionLimitToGroup.enabled({ app_api: false, active: true, installed: true } as IAppstoreApp)).toBe(true)
	})

	test('is not offered for an ExApp', () => {
		expect(actionLimitToGroup.enabled({ app_api: true, active: true, installed: true } as IAppstoreExApp)).toBe(false)
	})
})
