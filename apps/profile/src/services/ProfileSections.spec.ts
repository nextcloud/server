/*
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IProfileSection } from './ProfileSections.ts'

import { expect, test, vi } from 'vitest'
import ProfileSections from './ProfileSections.ts'

test('register profile section', () => {
	const profileSection: IProfileSection = {
		id: 'test-section',
		order: 1,
		tagName: 'test-element',
	}

	const sections = new ProfileSections()
	sections.registerSection(profileSection)

	expect(sections.getSections()).toHaveLength(1)
	expect(sections.getSections()[0]).toBe(profileSection)
})

test('register profile section twice', () => {
	const profileSection: IProfileSection = {
		id: 'test-section',
		order: 1,
		tagName: 'test-element',
	}

	const spy = vi.spyOn(console, 'warn').mockImplementation(() => {})

	const sections = new ProfileSections()
	sections.registerSection(profileSection)
	sections.registerSection(profileSection)

	expect(spy).toHaveBeenCalled()
	expect(sections.getSections()).toHaveLength(1)
	expect(sections.getSections()[0]).toBe(profileSection)
})
