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
			:input-label="t('settings', 'Language')"
			:placeholder="t('settings', 'Set default language')"
			:clearable="false"
			:selectable="option => !option.languages"
			:filter-by="languageFilterBy"
			:options="languages"
			label="name" />
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'

export default {
	name: 'UserFormLanguage',

	components: {
		NcSelect,
	},

	props: {
		formData: {
			type: Object,
			required: true,
		},
	},

	computed: {
		showConfig() {
			return this.$store.getters.getShowConfig
		},

		languages() {
			const { commonLanguages, otherLanguages } = this.$store.getters.getServerData.languages
			return [
				{ name: t('settings', 'Common languages'), languages: commonLanguages },
				...commonLanguages,
				{ name: t('settings', 'Other languages'), languages: otherLanguages },
				...otherLanguages,
			]
		},
	},

	methods: {
		languageFilterBy(option, label, search) {
			if (option.languages) {
				return option.languages.some(({ name }) => name.toLocaleLowerCase().includes(search.toLocaleLowerCase()))
			}
			return (label || '').toLocaleLowerCase().includes(search.toLocaleLowerCase())
		},
	},
}
</script>
