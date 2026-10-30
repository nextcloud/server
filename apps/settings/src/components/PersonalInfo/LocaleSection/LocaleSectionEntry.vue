<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="locale">
		<p class="locale__example">
			{{ t('settings', 'Example: {example}', { example }) }}

		</p>

		<NcSelect
			:aria-label-listbox="t('settings', 'Locales')"
			class="locale__select"
			:clearable="false"
			:input-id="inputId"
			label="name"
			label-outside
			:options="allLocales"
			:model-value="locale"
			@option:selected="updateLocale" />
	</div>
</template>

<script>
import { useNow } from '@vueuse/core'
import { useFormatTime } from '@nextcloud/vue/composables/useFormatDateTime'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

export default {
	name: 'LocaleSectionEntry',

	components: {
		NcSelect,
	},

	props: {
		inputId: {
			type: String,
			default: null,
		},

		locale: {
			type: Object,
			required: true,
		},

		localesForLanguage: {
			type: Array,
			required: true,
		},

		otherLocales: {
			type: Array,
			required: true,
		},
	},

	setup() {
		const now = useNow({ interval: 1000 })

		return {
			example: useFormatTime(now, {
				format: {
					dateStyle: 'short',
					timeStyle: 'medium',
				},
			}),
		}
	},

	data() {
		return {
			initialLocale: this.locale,
		}
	},

	computed: {
		/**
		 * All available locale, sorted like: current, common, other
		 */
		allLocales() {
			const common = this.localesForLanguage.filter((l) => l.code !== this.locale.code)
			const other = this.otherLocales.filter((l) => l.code !== this.locale.code)
			return [this.locale, ...common, ...other]
		},
	},

	methods: {
		async updateLocale(locale) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE, locale.code)
				this.handleResponse({
					locale,
					status: responseData.ocs?.meta?.status,
				})
				window.location.reload()
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update locale'),
					error: e,
				})
			}
		},

		handleResponse({ locale, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialLocale = locale
			} else {
				this.$emit('update:locale', this.initialLocale)
				handleError(error, errorMessage)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.locale {
	display: flex;
	flex-direction: column;
	gap: 6px;

	&__example {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}
}
</style>
