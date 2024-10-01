<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="language">
		<NcSelect :aria-label-listbox="t('settings', 'Languages')"
			class="language__select"
			:clearable="false"
			:input-id="inputId"
			label="name"
			label-outside
			:options="allLanguages"
			:value="language"
			@option:selected="onLanguageChange" />

		<a href="https://www.transifex.com/nextcloud/nextcloud/"
			target="_blank"
			rel="noreferrer noopener">
			<em>{{ t('settings', 'Help translate') }}</em>
		</a>
	</div>
</template>

<script>
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { validateLanguage } from '../../../utils/validate.js'
import { handleError } from '../../../utils/handlers.ts'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

export default {
	name: 'Language',

	components: {
		NcSelect,
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
			const common = this.commonLanguages.filter(l => l.code !== this.language.code)
			const other = this.otherLanguages.filter(l => l.code !== this.language.code)
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
	display: grid;

	#{&}__select {
		margin-top: 6px; // align with other inputs
	}

	a {
		text-decoration: none;
		width: max-content;
	}
}
</style>
