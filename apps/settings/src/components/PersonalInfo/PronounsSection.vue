<!--
	- @copyright Copyright (c) 2024 John Molakvoæ <skjnldsv@protonmail.com>
	-
	- @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<AccountPropertySection v-bind.sync="pronouns"
		autocomplete="organization-title"
		:placeholder="randomPronounsPlaceholder" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { pronouns } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'PronounsSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			pronouns: { ...pronouns, readable: NAME_READABLE_ENUM[pronouns.name] },
		}
	},

	computed: {
		randomPronounsPlaceholder() {
			const pronouns = [
				this.t('settings', 'she/her'),
				this.t('settings', 'he/him'),
				this.t('settings', 'they/them'),
			]
			const pronounsExample = pronouns[Math.floor(Math.random() * pronouns.length)]
			return this.t('settings', `Your pronouns. E.g. ${pronounsExample}`, { pronounsExample })
		},
	},
}
</script>
