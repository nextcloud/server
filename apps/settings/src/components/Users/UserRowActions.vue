<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions
		:aria-label="t('settings', 'Toggle account actions menu')"
		:disabled="disabled"
		:inline="1">
		<NcActionButton
			data-cy-user-list-action-edit
			:disabled="disabled"
			@click="$emit('update:edit', true)">
			{{ t('settings', 'Edit') }}
			<template #icon>
				<NcIconSvgWrapper :svg="SvgPencil" aria-hidden="true" />
			</template>
		</NcActionButton>
		<NcActionButton
			v-for="({ action, icon, text }, index) in enabledActions"
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

import SvgPencil from '@mdi/svg/svg/pencil-outline.svg?raw'
import isSvg from 'is-svg'
import { defineComponent } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

interface UserAction {
	action: (event: MouseEvent, user: Record<string, unknown>) => void
	enabled?: (user: Record<string, unknown>) => boolean
	icon: string
	text: string
}

export default defineComponent({
	components: {
		NcActionButton,
		NcActions,
		NcIconSvgWrapper,
	},

	props: {
		actions: {
			type: Array as PropType<readonly UserAction[]>,
			required: true,
		},

		disabled: {
			type: Boolean,
			required: true,
		},

		user: {
			type: Object,
			required: true,
		},
	},

	setup() {
		return { SvgPencil }
	},

	computed: {
		enabledActions(): UserAction[] {
			return this.actions.filter((action) => typeof action.enabled === 'function' ? action.enabled(this.user) : true)
		},
	},

	methods: {
		isSvg,
	},
})
</script>
