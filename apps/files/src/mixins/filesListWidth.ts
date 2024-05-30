/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

export default Vue.extend({
	data() {
		return {
			filesListWidth: null as number | null,
		}
	},
	mounted() {
		const fileListEl = document.querySelector('#app-content-vue')
		this.filesListWidth = fileListEl?.clientWidth ?? null

		this.$resizeObserver = new ResizeObserver((entries) => {
			if (entries.length > 0 && entries[0].target === fileListEl) {
				this.filesListWidth = entries[0].contentRect.width
			}
		})
		this.$resizeObserver.observe(fileListEl as Element)
	},
	beforeDestroy() {
		this.$resizeObserver.disconnect()
	},
})
