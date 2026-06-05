<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div
		v-if="showConfig.showLanguages"
		class="user-form__item">
		<NcSelect
			v-model="formData.language"
			class="user-form__select"
			:inputLabel="t('settings', 'Language')"
			:placeholder="t('settings', 'Set default language')"
			:clearable="false"
			:selectable="option => !option.languages"
			:filterBy="languageFilterBy"
			:options="languages"
			label="name" />
	</div>
</template>

<script setup lang="ts">
import type { FormData } from './userFormUtils.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, inject } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useStore } from '../../store/index.js'
import { languageFilterBy } from './userFormUtils.ts'

const store = useStore()

/** Shared, reactive form state provided by the parent dialog */
const formData = inject<FormData>('formData')!

/** Per-admin UI flags from the store (controls language field visibility) */
const showConfig = computed(() => store.getters.getShowConfig)

/** Grouped options: a section header followed by its languages, twice */
const languages = computed(() => {
	const { commonLanguages, otherLanguages } = store.getters.getServerData.languages
	return [
		{ name: t('settings', 'Common languages'), languages: commonLanguages },
		...commonLanguages,
		{ name: t('settings', 'Other languages'), languages: otherLanguages },
		...otherLanguages,
	]
})
</script>
