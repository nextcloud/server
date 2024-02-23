<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<NcButton type="primary"
		native-type="submit"
		:wide="true"
		@click="$emit('click')">
		{{ !loading ? value : valueLoading }}
		<template #icon>
			<div v-if="loading" class="submit-wrapper__icon icon-loading-small-dark" />
			<ArrowRight v-else class="submit-wrapper__icon" />
		</template>
	</NcButton>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'

export default {
	name: 'LoginButton',
	components: {
		ArrowRight,
		NcButton,
	},
	props: {
		value: {
			type: String,
			default: t('core', 'Log in'),
		},
		valueLoading: {
			type: String,
			default: t('core', 'Logging in …'),
		},
		loading: {
			type: Boolean,
			required: true,
		},
		invertedColors: {
			type: Boolean,
			default: false,
		},
	},
}
</script>

<style lang="scss" scoped>
.button-vue {
	margin-top: .5rem;
}
</style>
