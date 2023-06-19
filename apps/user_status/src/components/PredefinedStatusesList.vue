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
	<ul v-if="statusesHaveLoaded"
		class="predefined-statuses-list"
		role="radiogroup"
		:aria-label="t('user_status', 'Predefined statuses')">
		<PredefinedStatus v-for="status in predefinedStatuses"
			:key="status.id"
			:message-id="status.id"
			:icon="status.icon"
			:message="status.message"
			:clear-at="status.clearAt"
			:selected="!isCustomStatus && lastSelected === status.id"
			@select="selectStatus(status)" />
	</ul>
	<div v-else
		class="predefined-statuses-list">
		<div class="icon icon-loading-small" />
	</div>
</template>

<script>
import PredefinedStatus from './PredefinedStatus.vue'
import { mapGetters, mapState } from 'vuex'

export default {
	name: 'PredefinedStatusesList',
	components: {
		PredefinedStatus,
	},
	props: {
		/** If the current selected status is a custom one */
		isCustomStatus: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			lastSelected: null,
		}
	},
	computed: {
		...mapState({
			predefinedStatuses: state => state.predefinedStatuses.predefinedStatuses,
		}),
		...mapGetters(['statusesHaveLoaded']),
	},
	/**
	 * Loads all predefined statuses from the server
	 * when this component is mounted
	 */
	created() {
		this.$store.dispatch('loadAllPredefinedStatuses')
	},
	methods: {
		/**
		 * Emits an event when the user selects a status
		 *
		 * @param {object} status The selected status
		 */
		selectStatus(status) {
			this.lastSelected = status.id
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
