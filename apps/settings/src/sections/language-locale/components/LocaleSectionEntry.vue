<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LanguageOption } from '../../../components/Users/userFormUtils.ts'

import { getCanonicalLocale, t } from '@nextcloud/l10n'
import { useIntervalFn } from '@vueuse/core'
import { computed, ref } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

const props = defineProps<{
	/** Id of the select input, for the section's label */
	inputId?: string
	/** The user's current locale */
	locale: LanguageOption
	/** Locales matching the user's language, listed first */
	localesForLanguage: LanguageOption[]
	/** All other locales */
	otherLocales: LanguageOption[]
}>()

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

const example = ref(formatExample())
useIntervalFn(() => {
	example.value = formatExample()
}, 1000)

/**
 * All available locale, sorted like: current, common, other
 */
const allLocales = computed(() => {
	const common = props.localesForLanguage.filter((l) => l.code !== props.locale.code)
	const other = props.otherLocales.filter((l) => l.code !== props.locale.code)
	return [props.locale, ...common, ...other]
})

/**
 * @param newLocale The locale to save, the page reloads with it
 */
async function updateLocale(newLocale: LanguageOption) {
	try {
		await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE, newLocale.code)
		window.location.reload()
	} catch (error) {
		handleError(error, t('settings', 'Unable to update locale'))
	}
}
</script>

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
