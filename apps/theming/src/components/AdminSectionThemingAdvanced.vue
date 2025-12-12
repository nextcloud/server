<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AdminThemingInfo, AdminThemingParameters } from '../types.d.ts'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ColorPickerField from './admin/ColorPickerField.vue'
import FileInputField from './admin/FileInputField.vue'
import { useAdminThemingValue } from '../composables/useAdminThemingValue.ts'
import { logger } from '../utils/logger.ts'
import { refreshStyles } from '../utils/refreshStyles.ts'

const { defaultBackgroundColor } = loadState<AdminThemingInfo>('theming', 'adminThemingInfo')
const adminThemingParameters = loadState<AdminThemingParameters>('theming', 'adminThemingParameters')

const userThemingDisabled = ref(adminThemingParameters.disableUserTheming)
const { isSaving } = useAdminThemingValue('disableUserTheming', userThemingDisabled, false)

const isRemovingBackgroundImage = ref(false)
const removeBackgroundImage = ref(adminThemingParameters.backgroundMime === 'backgroundColor')
watch(removeBackgroundImage, toggleBackground)

/**
 * Remove the background image and set the background to backgroundColor
 *
 * @param value - Whether to remove the background image or restore it
 */
async function toggleBackground(value: boolean) {
	isRemovingBackgroundImage.value = true
	try {
		if (value) {
			await axios.post(generateUrl('/apps/theming/ajax/undoChanges'), {
				setting: 'background',
			})
			await axios.post(generateUrl('/apps/theming/ajax/updateStylesheet'), {
				setting: 'backgroundMime',
				value: 'backgroundColor',
			})
		} else {
			await axios.post(generateUrl('/apps/theming/ajax/undoChanges'), {
				setting: 'backgroundMime',
			})
		}
		await refreshStyles()
	} catch (error) {
		logger.error('Failed to remove background image', { error })
		if (isAxiosError(error) && error.response?.data?.data?.message) {
			showError(error.response.data.data.message)
			return
		}
		throw error
	} finally {
		isRemovingBackgroundImage.value = false
	}
}
</script>

<template>
	<NcSettingsSection :name="t('theming', 'Background and color')">
		<div :class="$style.adminSectionThemingAdvanced">
			<!-- primary color -->
			<ColorPickerField
				name="primaryColor"
				:label="t('theming', 'Primary color')"
				default-value="#00679e"
				@updated="refreshStyles">
				<template #description>
					{{ t('theming', 'Set the default primary color, used to highlight important elements.') }}
					{{ t('theming', 'The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.') }}
				</template>
			</ColorPickerField>
			<!-- background color -->
			<ColorPickerField
				name="backgroundColor"
				:label="t('theming', 'Background color')"
				:default-value="defaultBackgroundColor"
				@updated="refreshStyles">
				<template #description>
					{{ t('theming', 'When no background image is set the background color will be used.') }}
					{{ t('theming', 'Otherwise the background color is by default generated from the background image, but can be adjusted to fine tune the color of the navigation icons.') }}
				</template>
			</ColorPickerField>
			<!-- background and logo -->
			<NcCheckboxRadioSwitch
				v-model="removeBackgroundImage"
				type="switch"
				:loading="isRemovingBackgroundImage"
				:description="t('theming', 'Use a plain background color instead of a background image.')">
				{{ t('theming', 'Remove background image') }}
			</NcCheckboxRadioSwitch>
			<FileInputField
				name="background"
				:disabled="removeBackgroundImage"
				:label="t('theming', 'Background image')"
				@updated="refreshStyles" />
			<FileInputField
				name="favicon"
				:label="t('theming', 'Favicon')" />
			<FileInputField
				name="logo"
				:label="t('theming', 'Logo')"
				@updated="refreshStyles" />
			<FileInputField
				name="logoheader"
				:label="t('theming', 'Navigation bar logo')"
				@updated="refreshStyles" />
			<hr>
			<NcCheckboxRadioSwitch
				v-model="userThemingDisabled"
				type="switch"
				:loading="isSaving"
				:description="t('theming', 'Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.')">
				{{ t('theming', 'Disable user theming') }}
			</NcCheckboxRadioSwitch>
		</div>
	</NcSettingsSection>
</template>

<style module>
.adminSectionThemingAdvanced {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	max-width: 650px;
}
</style>
