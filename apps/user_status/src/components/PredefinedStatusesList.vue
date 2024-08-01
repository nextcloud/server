<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul v-if="statusesHaveLoaded"
		class="predefined-statuses-list"
		:aria-label="t('user_status', 'Predefined statuses')">
		<PredefinedStatus v-for="status in predefinedStatuses"
			:key="status.id"
			:message-id="status.id"
			:icon="status.icon"
			:message="status.message"
			:clear-at="status.clearAt"
			:selected="lastSelected === status.id"
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
	data() {
		return {
			lastSelected: null,
		}
	},
	computed: {
		...mapState({
			predefinedStatuses: state => state.predefinedStatuses.predefinedStatuses,
			messageId: state => state.userStatus.messageId,
		}),
		...mapGetters(['statusesHaveLoaded']),
	},

	watch: {
		messageId: {
		   immediate: true,
		   handler() {
			   this.lastSelected = this.messageId
		   },
	   },
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
