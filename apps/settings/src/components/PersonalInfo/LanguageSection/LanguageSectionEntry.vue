<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="language">
		<NcSelect
			:aria-label-listbox="t('settings', 'Languages')"
			class="language__select"
			:clearable="false"
			:input-id="inputId"
			label="name"
			label-outside
			:options="allLanguages"
			:model-value="language"
			@option:selected="onLanguageChange" />

		<NcFormBoxButton
			:label="t('settings', 'Help translate')"
			href="https://explore.transifex.com/nextcloud/"
			target="_blank">
			<template #icon>
				<OpenInNew :size="20" />
			</template>
		</NcFormBoxButton>
	</div>
</template>

<script>
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'
import { validateLanguage } from '../../../utils/validate.js'

export default {
	name: 'LanguageSectionEntry',

	components: {
		NcFormBoxButton,
		NcSelect,
		OpenInNew,
	},

	props: {
		inputId: {
			type: String,
			default: null,
		},

		commonLanguages: {
			type: Array,
			required: true,
		},

		otherLanguages: {
			type: Array,
			required: true,
		},

		language: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			initialLanguage: this.language,
		}
	},

	computed: {
		/**
		 * All available languages, sorted like: current, common, other
		 */
		allLanguages() {
			const common = this.commonLanguages.filter((l) => l.code !== this.language.code)
			const other = this.otherLanguages.filter((l) => l.code !== this.language.code)
			return [this.language, ...common, ...other]
		},
	},

	methods: {
		async onLanguageChange(language) {
			this.$emit('update:language', language)

			if (validateLanguage(language)) {
				await this.updateLanguage(language)
			}
		},

		async updateLanguage(language) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE, language.code)
				this.handleResponse({
					language,
					status: responseData.ocs?.meta?.status,
				})
				window.location.reload()
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update language'),
					error: e,
				})
			}
		},

		handleResponse({ language, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				this.initialLanguage = language
			} else {
				handleError(error, errorMessage)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.language {
	display: flex;
	flex-direction: column;
	gap: 6px;
}
</style>
