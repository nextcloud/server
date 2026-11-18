/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, describe, expect, it, vi } from 'vitest'

vi.mock('./UserStatus.vue', async () => {
	const { h } = await import('vue')
	return { default: { render: () => h('li', { class: 'user-status-entry' }) } }
})

describe('user status menu entry', () => {
	afterEach(() => {
		document.body.innerHTML = ''
		vi.resetModules()
	})

	it('replaces the whole account menu entry', async () => {
		document.body.innerHTML = `
			<ul id="list">
				<li id="user_status-menu-entry-account-menu-entry">
					<div class="list-item"><a id="user_status-menu-entry"></a></div>
				</li>
			</ul>`

		await import('./menu.js')

		const list = document.getElementById('list')!
		expect(document.getElementById('user_status-menu-entry-account-menu-entry')).toBeNull()
		expect(list.querySelector('li li')).toBeNull()
		expect(list.querySelector('.user-status-entry')).not.toBeNull()
	})
})
