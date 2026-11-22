/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { afterEach, expect, test } from 'vitest'
import SharedResourcesSection from './SharedResourcesSection.vue'

afterEach(() => {
	cleanup()
})

test('renders You & user title and shared resources', async () => {
	const { findByRole, findByText } = render(SharedResourcesSection, {
		props: {
			displayName: 'Alice',
			resources: [
				{
					label: 'Team notes',
					text: 'yesterday',
					href: 'https://example.com/f/1',
					img: 'https://example.com/preview/1',
				},
			],
		},
	})

	expect(await findByRole('heading', { name: 'You & Alice' })).toBeTruthy()
	expect(await findByText('Team notes')).toBeTruthy()
	expect(await findByText('yesterday')).toBeTruthy()

	const link = await findByRole('link', { name: /Team notes/i })
	expect(link.getAttribute('href')).toBe('https://example.com/f/1')
	expect(link.getAttribute('target')).toBe('_self')
})
