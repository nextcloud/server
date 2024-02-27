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
		<NcSelect :aria-label-listbox="t('settings', 'Locales')"
			class="locale__select"
			:clearable="false"
			:input-id="inputId"
			label="name"
			label-outside
			:options="allLocales"
			:value="locale"
			@option:selected="updateLocale" />

		<div class="example">
			<MapClock :size="20" />
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
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import MapClock from 'vue-material-design-icons/MapClock.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.js'

export default {
	name: 'Locale',

	components: {
		MapClock,
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

	data() {
		return {
			initialLocale: this.locale,
			intervalId: 0,
			example: {
				date: moment().format('L'),
				time: moment().format('LTS'),
				firstDayOfWeek: window.dayNames[window.firstDay],
			},
		}
	},

	computed: {
		/**
		 * All available locale, sorted like: current, common, other
		 */
		allLocales() {
			const common = this.localesForLanguage.filter(l => l.code !== this.locale.code)
			const other = this.otherLocales.filter(l => l.code !== this.locale.code)
			return [this.locale, ...common, ...other]
		},
	},

	mounted() {
		this.intervalId = window.setInterval(this.refreshExample, 1000)
	},

	beforeDestroy() {
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
			this.example = {
				date: moment().format('L'),
				time: moment().format('LTS'),
				firstDayOfWeek: window.dayNames[window.firstDay],
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.locale {
	display: grid;

	#{&}__select {
		margin-top: 6px; // align with other inputs
	}
}

.example {
	margin: 10px 0;
	display: flex;
	gap: 0 10px;
	color: var(--color-text-maxcontrast);

	&:deep(.material-design-icon) {
		align-self: flex-start;
		margin-top: 2px;
	}
}
</style>
