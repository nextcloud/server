<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul
		v-if="statusesHaveLoaded"
		class="predefined-statuses-list"
		:aria-label="t('user_status', 'Predefined statuses')">
		<PredefinedStatus
			v-for="status in predefinedStatuses"
			:key="status.id"
			:message-id="status.id"
			:icon="status.icon"
			:message="status.message"
			:clear-at="status.clearAt"
			:selected="lastSelected === status.id"
			@select="selectStatus(status)" />
	</ul>
	<div
		v-else
		class="predefined-statuses-list">
		<div class="icon icon-loading-small" />
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { mapGetters, mapState } from 'vuex'
import PredefinedStatus from './PredefinedStatus.vue'

export default {
	name: 'PredefinedStatusesList',
	components: {
		PredefinedStatus,
	},

	emits: ['selectStatus'],

	data() {
		return {
			lastSelected: null,
		}
	},

	computed: {
		...mapState({
			predefinedStatuses: (state) => state.predefinedStatuses.predefinedStatuses,
			messageId: (state) => state.userStatus.messageId,
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
		t,

		/**
		 * Emits an event when the user selects a status
		 *
		 * @param {object} status The selected status
		 */
		selectStatus(status) {
			this.lastSelected = status.id
			this.$emit('selectStatus', status)
		},
	},
}
</script>

<style lang="scss" scoped>
.predefined-statuses-list {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	margin-block: 0 calc(2 * var(--default-grid-baseline));
}
</style>
