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
			closeAfterClick
			@click="(event) => action(event, { ...user })">
			{{ text }}
			<template v-if="isSvg(icon)" #icon>
				<NcIconSvgWrapper :svg="icon" aria-hidden="true" />
			</template>
		</NcActionButton>
	</NcActions>
</template>

<script setup lang="ts">
import SvgPencil from '@mdi/svg/svg/pencil-outline.svg?raw'
import { translate as t } from '@nextcloud/l10n'
import isSvg from 'is-svg'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

interface UserAction {
	action: (event: MouseEvent, user: Record<string, unknown>) => void
	enabled?: (user: Record<string, unknown>) => boolean
	icon: string
	text: string
}

const props = defineProps<{
	/** Row action descriptors; the optional `enabled` predicate filters them per user */
	actions: readonly UserAction[]
	/** Disables all actions (e.g. while a request is pending) */
	disabled: boolean
	/** The user the actions operate on */
	user: Record<string, unknown>
}>()

defineEmits<{
	'update:edit': [value: boolean]
}>()

/** Actions whose optional `enabled(user)` predicate passes for this user */
const enabledActions = computed<UserAction[]>(() => props.actions.filter((action) => typeof action.enabled === 'function' ? action.enabled(props.user) : true))
</script>
