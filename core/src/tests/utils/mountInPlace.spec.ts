/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, describe, expect, it } from 'vitest'
import { createApp, defineComponent, h, nextTick, onMounted, ref } from 'vue'
import { mountInPlace } from '../../utils/mountInPlace.ts'

const count = ref(0)
let connectedOnMount: boolean | undefined

const Counter = defineComponent({
	setup(_, { expose }) {
		const root = ref<HTMLElement>()
		onMounted(() => {
			connectedOnMount = root.value?.isConnected
		})
		expose({ increment: () => count.value++ })
		return () => h('nav', { id: 'placeholder', ref: root }, String(count.value))
	},
})

describe('mountInPlace', () => {
	afterEach(() => {
		document.body.innerHTML = ''
		count.value = 0
		connectedOnMount = undefined
	})

	it('replaces the placeholder with the root element', () => {
		document.body.innerHTML = '<header><nav id="placeholder"></nav></header>'

		mountInPlace(createApp(Counter), document.getElementById('placeholder')!)

		expect(document.querySelectorAll('#placeholder')).toHaveLength(1)
		expect(document.querySelector('header')!.innerHTML).toBe('<nav id="placeholder">0</nav>')
	})

	it('mounts while the placeholder is part of the document', () => {
		document.body.innerHTML = '<div id="placeholder"></div>'

		mountInPlace(createApp(Counter), document.getElementById('placeholder')!)

		expect(connectedOnMount).toBe(true)
	})

	it('keeps updating and unmounting the moved root', async () => {
		document.body.innerHTML = '<div id="placeholder"></div>'
		const app = createApp(Counter)
		const instance = mountInPlace(app, document.getElementById('placeholder')!) as unknown as { increment: () => void }

		instance.increment()
		await nextTick()
		expect(document.body.innerHTML).toBe('<nav id="placeholder">1</nav>')

		app.unmount()
		expect(document.body.innerHTML).toBe('')
	})
})
