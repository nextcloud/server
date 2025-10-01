<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISetupCheck } from '../../settings-types.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import SettingsSetupChecksListItem from './SettingsSetupChecksListItem.vue'

const props = defineProps<{
	severity: 'info' | 'warning' | 'error'
	setupChecks: ISetupCheck[]
}>()

const ariaLabel = computed(() => {
	if (props.severity === 'error') {
		return t('settings', 'Setup errors')
	} else if (props.severity === 'warning') {
		return t('settings', 'Setup warnings')
	}
	return t('settings', 'Setup recommendations')
})

const shownChecks = computed(() => props.setupChecks.filter(({ severity }) => severity === props.severity))
</script>

<template>
	<ul class="settings-setup-checks-list" :aria-label="ariaLabel">
		<SettingsSetupChecksListItem
			v-for="(setupCheck, index) in shownChecks"
			:key="index"
			class="settings-setup-checks-list__item"
			:setup-check="setupCheck" />
	</ul>
</template>

<style scope lang="scss">
.settings-setup-checks-list {
	&:not(:first-of-type) {
		margin-top: calc(2 * var(--default-grid-baseline));
	}

	&__item:not(:last-of-type) {
		margin-bottom: var(--default-grid-baseline);
	}
}
</style>
