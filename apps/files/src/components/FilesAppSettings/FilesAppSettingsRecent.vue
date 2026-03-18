<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts" setup>
import { t } from '@nextcloud/l10n'
import { NcFormBoxSwitch, NcInputField, NcSelect } from '@nextcloud/vue'
import debounce from 'debounce'
import { ref, watch } from 'vue'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import { useUserConfigStore } from '../../store/userconfig.ts'

const store = useUserConfigStore()

const availableMimetypes = [
	{ id: 'image/png', label: 'PNG' },
	{ id: 'image/jpeg', label: 'JPEG' },
	{ id: 'image/gif', label: 'GIF' },
	{ id: 'image/webp', label: 'WebP' },
	{ id: 'image/avif', label: 'AVIF' },
	{ id: 'image/heic', label: 'HEIC' },
	{ id: 'image/heif', label: 'HEIF' },
]

const storedMimetypes = store.userConfig.recent_files_group_mimetypes
const initialMimetypes = Array.isArray(storedMimetypes)
	? availableMimetypes.filter((m) => storedMimetypes.includes(m.id))
	: []

const selectedMimetypes = ref(initialMimetypes)

const debouncedUpdateMimetypes = debounce((value) => {
	store.update('recent_files_group_mimetypes', JSON.stringify(value.map((v) => v.id)))
}, 500)

watch(selectedMimetypes, (value) => {
	debouncedUpdateMimetypes(value)
})

const debouncedUpdateTimespan = debounce((value: number) => {
	store.update('recent_files_group_timespan_minutes', value)
}, 500)
</script>

<template>
	<NcAppSettingsSection id="recent" :name="t('files', 'Recent view')">
		<NcFormBox>
			<NcFormBoxSwitch
				v-model="store.userConfig.group_recent_files_images"
				:label="t('files', 'Group image files')"
				@update:modelValue="store.update('group_recent_files_images', $event)" />
			<label>{{ t('files', 'Group these image types together') }}</label>
			<NcSelect
				v-model="selectedMimetypes"
				:options="availableMimetypes"
				labelOutside
				multiple />
			<NcInputField
				v-model="store.userConfig.recent_files_group_timespan_minutes"
				type="number"
				:min="1"
				:max="999"
				:label="t('files', 'Time window in minutes to group files uploaded close together')"
				@update:modelValue="debouncedUpdateTimespan(Number($event))" />
		</NcFormBox>
	</NcAppSettingsSection>
</template>
