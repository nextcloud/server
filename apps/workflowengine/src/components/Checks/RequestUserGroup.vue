<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<Multiselect v-model="newValue"
			:class="{'icon-loading-small': groups.length === 0}"
			:options="groups"
			:multiple="false"
			label="displayname"
			track-by="id"
			@input="setValue" />
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue/dist/Components/Multiselect'
import valueMixin from '../../mixins/valueMixin'
import axios from '@nextcloud/axios'
export default {
	name: 'RequestUserGroup',
	components: {
		Multiselect
	},
	mixins: [
		valueMixin
	],
	data() {
		return {
			groups: []
		}
	},
	beforeMount() {
		axios.get(OC.linkToOCS('cloud', 2) + 'groups').then((response) => {
			this.groups = response.data.ocs.data.groups.reduce((obj, item) => {
				obj.push({
					id: item,
					displayname: item
				})
				return obj
			}, [])
			this.updateInternalValue(this.value)
		}, (error) => {
			console.error('Error while loading group list', error.response)
		})
	},
	methods: {
		updateInternalValue() {
			this.newValue = this.groups.find(group => group.id === this.value) || null
		},
		setValue(value) {
			if (value !== null) {
				this.$emit('input', this.newValue.id)
			}
		}
	}
}
</script>
