<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<section>
		<HeaderBar
			:account-property="accountProperty"
			label-for="headline"
			:scope.sync="primaryHeadline.scope" />

		<Headline
			:headline.sync="primaryHeadline.value"
			:scope.sync="primaryHeadline.scope" />
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import Headline from './Headline'
import HeaderBar from '../shared/HeaderBar'

import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'

const { headlineMap: { primaryHeadline } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'HeadlineSection',

	components: {
		Headline,
		HeaderBar,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE,
			primaryHeadline,
		}
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
