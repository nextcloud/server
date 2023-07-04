<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<div class="locale">
		<select :id="inputId"
			:placeholder="t('settings', 'Locale')"
			@change="onLocaleChange">
			<option v-for="currentLocale in localesForLanguage"
				:key="currentLocale.code"
				:selected="locale.code === currentLocale.code"
				:value="currentLocale.code">
				{{ currentLocale.name }}
			</option>
			<option disabled>
				──────────
			</option>
			<option v-for="currentLocale in otherLocales"
				:key="currentLocale.code"
				:selected="locale.code === currentLocale.code"
				:value="currentLocale.code">
				{{ currentLocale.name }}
			</option>
		</select>

		<div class="example">
			<Web :size="20" />
			<div class="example__text">
				<p>
					<span>{{ example.date }}</span>
					<span>{{ example.time }}</span>
				</p>
				<p>
					{{ t('settings', 'Week starts on {firstDayOfWeek}', { firstDayOfWeek: example.firstDayOfWeek }) }}
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import Web from 'vue-material-design-icons/Web.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { validateLocale } from '../../../utils/validate.js'
import { handleError } from '../../../utils/handlers.js'

export default {
	name: 'Locale',

	components: {
		Web,
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

	data() {
		return {
			initialLocale: this.locale,
			example: {
				date: moment().format('L'),
				time: moment().format('LTS'),
				firstDayOfWeek: window.dayNames[window.firstDay],
			},
		}
	},

	computed: {
		allLocales() {
			return Object.freeze(
				[...this.localesForLanguage, ...this.otherLocales]
					.reduce((acc, { code, name }) => ({ ...acc, [code]: name }), {}),
			)
		},
	},

	created() {
		setInterval(this.refreshExample, 1000)
	},

	methods: {
		async onLocaleChange(e) {
			const locale = this.constructLocale(e.target.value)
			this.$emit('update:locale', locale)

			if (validateLocale(locale)) {
				await this.updateLocale(locale)
			}
		},

		async updateLocale(locale) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE, locale.code)
				this.handleResponse({
					locale,
					status: responseData.ocs?.meta?.status,
				})
				this.reloadPage()
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update locale'),
					error: e,
				})
			}
		},

		constructLocale(localeCode) {
			return {
				code: localeCode,
				name: this.allLocales[localeCode],
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
			this.example = {
				date: moment().format('L'),
				time: moment().format('LTS'),
				firstDayOfWeek: window.dayNames[window.firstDay],
			}
		},

		reloadPage() {
			location.reload()
		},
	},
}
</script>

<style lang="scss" scoped>
.locale {
	display: grid;

	select {
		width: 100%;
	}
}

.example {
	margin: 10px 0;
	display: flex;
	gap: 0 10px;
	color: var(--color-text-lighter);

	&::v-deep .material-design-icon {
		align-self: flex-start;
		margin-top: 2px;
	}
}
</style>
