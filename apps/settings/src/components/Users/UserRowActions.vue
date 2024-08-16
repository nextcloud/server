<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions :aria-label="t('settings', 'Toggle account actions menu')"
		:disabled="disabled"
		:inline="1">
		<NcActionButton :data-cy-user-list-action-toggle-edit="`${edit}`"
			:disabled="disabled"
			@click="toggleEdit">
			{{ edit ? t('settings', 'Done') : t('settings', 'Edit') }}
			<template #icon>
				<NcIconSvgWrapper :key="editSvg" :svg="editSvg" aria-hidden="true" />
			</template>
		</NcActionButton>
		<NcActionButton v-for="({ action, icon, text }, index) in enabledActions"
			:key="index"
			:disabled="disabled"
			:aria-label="text"
			:icon="icon"
			close-after-click
			@click="(event) => action(event, { ...user })">
			{{ text }}
			<template v-if="isSvg(icon)" #icon>
				<NcIconSvgWrapper :svg="icon" aria-hidden="true" />
			</template>
		</NcActionButton>
	</NcActions>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { defineComponent } from 'vue'
import isSvg from 'is-svg'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import SvgCheck from '@mdi/svg/svg/check.svg?raw'
import SvgPencil from '@mdi/svg/svg/pencil.svg?raw'

interface UserAction {
	action: (event: MouseEvent, user: Record<string, unknown>) => void,
	enabled?: (user: Record<string, unknown>) => boolean,
	icon: string,
	text: string,
}

export default defineComponent({
	components: {
		NcActionButton,
		NcActions,
		NcIconSvgWrapper,
	},

	props: {
		/**
		 * Array of user actions
		 */
		actions: {
			type: Array as PropType<readonly UserAction[]>,
			required: true,
		},

		/**
		 * The state whether the row is currently disabled
		 */
		disabled: {
			type: Boolean,
			required: true,
		},

		/**
		 * The state whether the row is currently edited
		 */
		edit: {
			type: Boolean,
			required: true,
		},

		/**
		 * Target of this actions
		 */
		user: {
			type: Object,
			required: true,
		},
	},

	computed: {
		/**
		 * Current MDI logo to show for edit toggle
		 */
		editSvg(): string {
			return this.edit ? SvgCheck : SvgPencil
		},

		/**
		 * Enabled user row actions
		 */
		enabledActions(): UserAction[] {
			return this.actions.filter(action => typeof action.enabled === 'function' ? action.enabled(this.user) : true)
		},
	},

	methods: {
		isSvg,

		/**
		 * Toggle edit mode by emitting the update event
		 */
		toggleEdit() {
			this.$emit('update:edit', !this.edit)
		},
	},
})
</script>
