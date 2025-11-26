<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import NcTimezonePicker from '@nextcloud/vue/components/NcTimezonePicker'
import HeaderBar from './shared/HeaderBar.vue'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService.js'

const { timezone: currentTimezone } = loadState<{ timezone: string }>('settings', 'personalInfoParameters')

const inputId = 'account-property-timezone'
const timezone = ref(currentTimezone)
watch(timezone, () => {
	savePrimaryAccountProperty('timezone', timezone.value)
})
</script>

<template>
	<section class="timezone-section">
		<HeaderBar
			:input-id="inputId"
			:readable="t('settings', 'Timezone')" />

		<NcTimezonePicker
			v-model="timezone"
			class="timezone-section__picker"
			:input-id="inputId" />
	</section>
</template>

<style scoped lang="scss">
.timezone-section {
	padding: 10px;

	&__picker {
		margin-top: 6px;
		width: 100%;
	}
}
</style>
