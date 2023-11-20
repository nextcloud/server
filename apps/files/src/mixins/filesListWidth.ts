/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
