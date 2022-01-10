<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
	<div
		v-if="hasLoaded"
		class="predefined-statuses-list">
		<PredefinedStatus
			v-for="status in predefinedStatuses"
			:key="status.id"
			:message-id="status.id"
			:icon="status.icon"
			:message="status.message"
			:clear-at="status.clearAt"
			@select="selectStatus(status)" />
	</div>
	<div
		v-else
		class="predefined-statuses-list">
		<div class="icon icon-loading-small" />
	</div>
</template>

<script>
import PredefinedStatus from './PredefinedStatus'
import { mapState } from 'vuex'

export default {
	name: 'PredefinedStatusesList',
	components: {
		PredefinedStatus,
	},
	computed: {
		...mapState({
			predefinedStatuses: state => state.predefinedStatuses.predefinedStatuses,
		}),
		/**
		 * Indicator whether the predefined statuses have already been loaded
		 *
		 * @return {boolean}
		 */
		hasLoaded() {
			return this.predefinedStatuses.length > 0
		},
	},
	/**
	 * Loads all predefined statuses from the server
	 * when this component is mounted
	 */
	mounted() {
		this.$store.dispatch('loadAllPredefinedStatuses')
	},
	methods: {
		/**
		 * Emits an event when the user selects a status
		 *
		 * @param {object} status The selected status
		 */
		selectStatus(status) {
			this.$emit('select-status', status)
		},
	},
}
</script>

<style lang="scss" scoped>
.predefined-statuses-list {
	display: flex;
	flex-direction: column;
	margin-bottom: 10px;
}
</style>
