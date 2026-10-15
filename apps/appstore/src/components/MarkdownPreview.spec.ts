/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it } from 'vitest'
import MarkdownPreview from './MarkdownPreview.vue'

describe('MarkdownPreview component', () => {
	beforeEach(cleanup)

	it('renders', () => {
		const component = render(MarkdownPreview, {
			props: {
				minHeadingLevel: 2,
				text: `# Heading one
This is [a link](http://example.com)!
## Heading two
> This is a block quote

![](http://example.com/image.jpg "Title")`,
			},
		})

		expect(component.getByRole('heading', { level: 2, name: 'Heading one' })).toBeTruthy()
		expect(component.getByRole('heading', { level: 3, name: 'Heading two' })).toBeTruthy()
		expect(component.getByText('This is a block quote')).toBeInstanceOf(HTMLQuoteElement)
		expect(component.getByRole('link', { name: 'a link' })).toBeInstanceOf(HTMLAnchorElement)
		expect(component.getByRole('link', { name: 'a link' }).getAttribute('href')).toBe('http://example.com')
		expect(() => component.getByRole('img')).toThrow() // its a text
	})
})
