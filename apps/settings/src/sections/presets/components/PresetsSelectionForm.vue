<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { PresetIds } from './models.ts'

import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import AccountGroupOutline from 'vue-material-design-icons/AccountGroupOutline.vue'
import AccountOutline from 'vue-material-design-icons/AccountOutline.vue'
import CloudCircleOutline from 'vue-material-design-icons/CloudCircleOutline.vue'
import Crowd from 'vue-material-design-icons/Crowd.vue'
import Domain from 'vue-material-design-icons/Domain.vue'
import MinusCircleOutline from 'vue-material-design-icons/MinusCircleOutline.vue'
import SchoolOutline from 'vue-material-design-icons/SchoolOutline.vue'

/** The selected preset */
const selectedPreset = defineModel<PresetIds>({ required: true })

const PresetNames = {
	// TRANSLATORS Large organization > Big organization > Small organization
	LARGE: t('settings', 'Large organization'),
	// TRANSLATORS Large organization > Big organization > Small organization
	MEDIUM: t('settings', 'Big organization'),
	SMALL: t('settings', 'Small organization'),
	SHARED: t('settings', 'Hosting company'),
	UNIVERSITY: t('settings', 'University'),
	SCHOOL: t('settings', 'School'),
	CLUB: t('settings', 'Club or association'),
	FAMILY: t('settings', 'Family'),
	PRIVATE: t('settings', 'Personal use'),
	NONE: t('settings', 'Default'),
}

const PresetsIcons = {
	LARGE: Domain,
	MEDIUM: Domain,
	SMALL: Domain,
	SHARED: CloudCircleOutline,
	UNIVERSITY: SchoolOutline,
	SCHOOL: SchoolOutline,
	CLUB: AccountGroupOutline,
	FAMILY: Crowd,
	PRIVATE: AccountOutline,
	NONE: MinusCircleOutline,
}

</script>

<template>
	<form :class="$style.form">
		<label
			v-for="(presetName, presetId) in PresetNames"
			:key="presetId"
			:class="$style.option">

			<component :is="PresetsIcons[presetId]" :size="32" />

			<NcCheckboxRadioSwitch
				type="radio"
				:modelValue="selectedPreset"
				:value="presetId"
				name="preset"
				@update:modelValue="selectedPreset = presetId" />

			<span :class="$style.optionName">{{ presetName }}</span>
		</label>
	</form>
</template>

<style module>
.form {
	display: flex;
	flex-wrap: wrap;
	gap: 24px;
	margin-top: 32px;
}

.option {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	width: 250px;
	min-height: 100px;
	padding: 16px;
	border-radius: var(--border-radius-large);
	background-color: var(--color-background-dark);
	font-size: 20px;
}

.option:hover {
	background-color: var(--color-background-darker);
}

.option:has(input[type=radio]:checked) {
	border: 2px solid var(--color-main-text);
	padding: 14px;
}

.optionName {
	flex-basis: 250px;
	margin-top: 8px;
}

.optionName,
.option :global(.material-design-icon) {
	cursor: pointer;
}
</style>
