/*
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { render } from '@testing-library/vue'
import { expect, test, vi } from 'vitest'
import ProfileSection from './ProfileSection.vue'

window.customElements.define('test-element', class extends HTMLElement {
	user?: string
	callback?: (user?: string) => void

	connectedCallback() {
		this.callback?.(this.user)
	}
})

test('can render section component', async () => {
	const callback = vi.fn()
	const result = render(ProfileSection, {
		props: {
			userId: 'testuser',
			section: {
				id: 'test-section',
				order: 1,
				tagName: 'test-element',
				params: {
					callback,
				},
			},
		},
	})

	// this basically covers everything we need to test:
	// 1. The custom element is rendered
	// 2. The custom params are passed to the custom element
	// 3. The user id is passed to the custom element
	expect(result.baseElement.querySelector('test-element')).toBeTruthy()
	expect(callback).toHaveBeenCalledWith('testuser')
})
