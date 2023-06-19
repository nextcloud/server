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
	<AccountPropertySection v-bind.sync="website"
		:placeholder="t('settings', 'Your website')"
		autocomplete="url"
		type="url"
		:on-validate="onValidate" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'
import { validateUrl } from '../../utils/validate.js'

const { website } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'WebsiteSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			website: { ...website, readable: NAME_READABLE_ENUM[website.name] },
		}
	},

	methods: {
		onValidate(value) {
			return validateUrl(value)
		},
	},
}
</script>
