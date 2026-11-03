<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="locale">
		<p class="locale__example">
			{{ t('settings', 'Example: {example}', { example: `${example.date} ${example.time}` }) }}
		</p>

		<NcSelect
			:aria-label-listbox="t('settings', 'Locales')"
			class="locale__select"
			:clearable="false"
			:inputId="inputId"
			label="name"
			labelOutside
			:options="allLocales"
			:modelValue="locale"
			@option:selected="updateLocale" />
	</div>
</template>

<script>
import { getCanonicalLocale, t } from '@nextcloud/l10n'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../utils/handlers.ts'

const dateFormat = new Intl.DateTimeFormat(getCanonicalLocale(), { year: 'numeric', month: '2-digit', day: '2-digit' })
const timeFormat = new Intl.DateTimeFormat(getCanonicalLocale(), { hour: 'numeric', minute: '2-digit', second: '2-digit' })

/**
 * Now, in the user's locale
 */
function formatExample() {
	const now = new Date()
	return {
		date: dateFormat.format(now),
		time: timeFormat.format(now),
	}
}

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

	emits: ['update:locale'],

	data() {
		return {
			initialLocale: this.locale,
			intervalId: 0,
			example: formatExample(),
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

	mounted() {
		this.intervalId = window.setInterval(this.refreshExample, 1000)
	},

	beforeUnmount() {
		window.clearInterval(this.intervalId)
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

		refreshExample() {
			this.example = formatExample()
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
