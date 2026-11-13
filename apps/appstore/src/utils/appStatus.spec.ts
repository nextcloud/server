/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp } from '../apps.d.ts'

import { describe, expect, test } from 'vitest'
import { canUninstall, canUpdate } from './appStatus.ts'

describe('canUpdate', () => {
	test('is true when an update is available', () => {
		expect(canUpdate({ update: '2.0.0' } as IAppstoreExApp)).toBe(true)
	})

	test('is false for an ExApp that is listed without an update', () => {
		// ExApps that are not listed in the app store come with `update: null`
		expect(canUpdate({ update: null } as IAppstoreExApp)).toBe(false)
		expect(canUpdate({} as IAppstoreExApp)).toBe(false)
	})
})

describe('canUninstall', () => {
	const exApp = { app_api: true, installed: true, removable: true, active: false } as IAppstoreExApp

	test('is true for a disabled ExApp', () => {
		expect(canUninstall({ ...exApp, status: { type: '', action: '', deploy: 100, init: 100 } })).toBe(true)
	})

	test('is false while the ExApp is being deployed', () => {
		expect(canUninstall({ ...exApp, status: { type: 'install', action: 'deploy', deploy: 50, init: 0 } })).toBe(false)
	})

	test('is false for an enabled ExApp', () => {
		expect(canUninstall({ ...exApp, active: true, status: { type: '', action: '', deploy: 100, init: 100 } })).toBe(false)
	})

	test('is true when the deployment failed', () => {
		expect(canUninstall({ ...exApp, status: { type: 'install', action: 'deploy', deploy: 50, init: 0, error: 'Image pull failed' } })).toBe(true)
	})

	test('is true for an enabled ExApp whose initialization failed', () => {
		expect(canUninstall({ ...exApp, active: true, status: { type: 'install', action: 'init', deploy: 100, init: 30, error: 'Init failed' } })).toBe(true)
	})
})
