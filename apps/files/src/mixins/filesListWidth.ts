/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineComponent } from 'vue'

export default defineComponent({
	data() {
		return {
			filesListWidth: 0,
		}
	},

	mounted() {
		const fileListEl = document.querySelector('#app-content-vue')
		this.filesListWidth = fileListEl?.clientWidth ?? 0

		// @ts-expect-error The resize observer is just now attached to the object
		this.$resizeObserver = new ResizeObserver((entries) => {
			if (entries.length > 0 && entries[0].target === fileListEl) {
				this.filesListWidth = entries[0].contentRect.width
			}
		})
		// @ts-expect-error The resize observer was attached right before to the this object
		this.$resizeObserver.observe(fileListEl as Element)
	},

	beforeDestroy() {
		// @ts-expect-error mounted must have been called before the destroy, so the resize
		this.$resizeObserver.disconnect()
	},
})
