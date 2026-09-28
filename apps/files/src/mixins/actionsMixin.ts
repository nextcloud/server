/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileAction } from '@nextcloud/files'
import { defineComponent } from 'vue'

export default defineComponent({

	data() {
		return {
			openedSubmenu: null as FileAction|null,
		}
	},

	computed: {
		enabledSubmenuActions(): Record<string, FileAction[]> {
			return (this.enabledFileActions as FileAction[])
				.reduce((record, action) => {
					if (action.parent !== undefined) {
						if (!record[action.parent]) {
							record[action.parent] = []
						}

						record[action.parent].push(action)
					}
					return record
				}, {} as Record<string, FileAction[]>)
		},
	},

	methods: {
		/**
		 * Check if a menu is valid, meaning it is
		 * defined and has at least one action
		 *
		 * @param action The action to check
		 */
		isValidMenu(action: FileAction): boolean {
			return this.enabledSubmenuActions[action.id]?.length > 0
		},

		async onBackToMenuClick(action: FileAction|null) {
			if (!action) {
				return
			}

			this.openedSubmenu = null
			// Wait for first render
			await this.$nextTick()

			// Focus the previous menu action button
			this.$nextTick(() => {
				// Focus the action button, test both batch and single action references
				// as this mixin is used in both single and batch actions.
				const menuAction = this.$refs[`action-batch-${action.id}`]?.[0]
					|| this.$refs[`action-${action.id}`]?.[0]
				if (menuAction) {
					menuAction.$el.querySelector('button')?.focus()
				}
			})
		},
	},
})
