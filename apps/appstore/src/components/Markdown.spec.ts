/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it } from 'vitest'
import Markdown from './Markdown.vue'

describe('Markdown component', () => {
	beforeEach(cleanup)

	it('renders links', () => {
		const component = render(Markdown, {
			props: {
				text: 'This is [a link](http://example.com)!',
			},
		})

		const link = component.getByRole('link')
		expect(link).toBeInstanceOf(HTMLAnchorElement)
		expect(link.getAttribute('href')).toBe('http://example.com')
		expect(link.textContent).toBe('a link')
	})

	it('renders headings', () => {
		const component = render(Markdown, {
			props: {
				text: '# level 1\nText\n## level 2\nText\n### level 3\nText\n#### level 4\nText\n##### level 5\nText\n###### level 6\nText\n',
			},
		})

		for (let level = 1; level <= 6; level++) {
			const heading = component.getByRole('heading', { level })
			expect(heading.textContent).toBe(`level ${level}`)
		}
	})

	it('can limit headings', async () => {
		const component = render(Markdown, {
			props: {
				text: '# level 1\nText\n## level 2\nText\n### level 3\nText\n#### level 4\nText\n##### level 5\nText\n###### level 6\nText\n',
				minHeading: 4,
			},
		})

		await expect(component.findByRole('heading', { level: 1 })).rejects.toThrow()
		await expect(component.findByRole('heading', { level: 2 })).rejects.toThrow()
		await expect(component.findByRole('heading', { level: 3 })).rejects.toThrow()

		expect(component.getByRole('heading', { level: 4 }).textContent).toBe('level 1')
		expect(component.getByRole('heading', { level: 5 }).textContent).toBe('level 2')
		await expect(component.findByRole('heading', { level: 6, name: 'level 3' })).resolves.not.toThrow()
		await expect(component.findByRole('heading', { level: 6, name: 'level 4' })).resolves.not.toThrow()
		await expect(component.findByRole('heading', { level: 6, name: 'level 5' })).resolves.not.toThrow()
		await expect(component.findByRole('heading', { level: 6, name: 'level 6' })).resolves.not.toThrow()
	})
})
