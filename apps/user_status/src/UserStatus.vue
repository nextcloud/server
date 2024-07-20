<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<component :is="inline ? 'div' : 'li'">
		<!-- User Status = Status modal toggle -->
		<button v-if="!inline"
			class="user-status-menu-item"
			@click.stop="openModal">
			<NcUserStatusIcon class="user-status-icon"
				:status="statusType"
				aria-hidden="true" />
			{{ visibleMessage }}
		</button>

		<!-- Dashboard Status -->
		<NcButton v-else
			@click.stop="openModal">
			<template #icon>
				<NcUserStatusIcon class="user-status-icon"
					:status="statusType"
					aria-hidden="true" />
			</template>
			{{ visibleMessage }}
		</NcButton>

		<!-- Status management modal -->
		<SetStatusModal v-if="isModalOpen"
			:inline="inline"
			@close="closeModal" />
	</component>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcUserStatusIcon from '@nextcloud/vue/dist/Components/NcUserStatusIcon.js'
import debounce from 'debounce'

import { sendHeartbeat } from './services/heartbeatService.js'
import OnlineStatusMixin from './mixins/OnlineStatusMixin.js'

export default {
	name: 'UserStatus',

	components: {
		NcButton,
		NcUserStatusIcon,
		SetStatusModal: () => import(/* webpackChunkName: 'user-status-modal' */'./components/SetStatusModal.vue'),
	},
	mixins: [OnlineStatusMixin],

	props: {
		/**
		 * Whether the component should be rendered as a Dashboard Status or a User Menu Entries
		 * true = Dashboard Status
		 * false = User Menu Entries
		 */
		inline: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			heartbeatInterval: null,
			isAway: false,
			isModalOpen: false,
			mouseMoveListener: null,
			setAwayTimeout: null,
		}
	},

	/**
	 * Loads the current user's status from initial state
	 * and stores it in Vuex
	 */
	mounted() {
		this.$store.dispatch('loadStatusFromInitialState')

		if (OC.config.session_keepalive) {
			// Send the latest status to the server every 5 minutes
			this.heartbeatInterval = setInterval(this._backgroundHeartbeat.bind(this), 1000 * 60 * 5)
			this.setAwayTimeout = () => {
				this.isAway = true
			}
			// Catch mouse movements, but debounce to once every 30 seconds
			this.mouseMoveListener = debounce(() => {
				const wasAway = this.isAway
				this.isAway = false
				// Reset the two minute counter
				clearTimeout(this.setAwayTimeout)
				// If the user did not move the mouse within two minutes,
				// mark them as away
				setTimeout(this.setAwayTimeout, 1000 * 60 * 2)

				if (wasAway) {
					this._backgroundHeartbeat()
				}
			}, 1000 * 2, true)
			window.addEventListener('mousemove', this.mouseMoveListener, {
				capture: true,
				passive: true,
			})

			this._backgroundHeartbeat()
		}
		subscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},

	/**
	 * Some housekeeping before destroying the component
	 */
	beforeDestroy() {
		window.removeEventListener('mouseMove', this.mouseMoveListener)
		clearInterval(this.heartbeatInterval)
		unsubscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},

	methods: {
		/**
		 * Opens the modal to set a custom status
		 */
		openModal() {
			this.isModalOpen = true
		},
		/**
		 * Closes the modal
		 */
		closeModal() {
			this.isModalOpen = false
		},

		/**
		 * Sends the status heartbeat to the server
		 *
		 * @return {Promise<void>}
		 * @private
		 */
		async _backgroundHeartbeat() {
			try {
				const status = await sendHeartbeat(this.isAway)
				if (status?.userId) {
					this.$store.dispatch('setStatusFromHeartbeat', status)
				} else {
					await this.$store.dispatch('reFetchStatusFromServer')
				}
			} catch (error) {
				console.debug('Failed sending heartbeat, got: ' + error.response?.status)
			}
		},
		handleUserStatusUpdated(state) {
			if (OC.getCurrentUser().uid === state.userId) {
				this.$store.dispatch('setStatusFromObject', {
					status: state.status,
					icon: state.icon,
					message: state.message,
				})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.user-status-menu-item {
	// Ensure the maxcontrast color is set for the background
	--color-text-maxcontrast: var(--color-text-maxcontrast-background-blur, var(--color-main-text));

	width: auto;
	min-width: 44px;
	height: 44px;
	margin: 0;
	border: 0;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-main-background-blur);
	font-size: inherit;
	font-weight: normal;

	-webkit-backdrop-filter: var(--background-blur);
	backdrop-filter: var(--background-blur);

	&:active,
	&:hover,
	&:focus-visible {
		background-color: var(--color-background-hover);
	}
	&:focus-visible {
		box-shadow: 0 0 0 4px var(--color-main-background) !important;
		outline: 2px solid var(--color-main-text) !important;
	}
}

.user-status-icon {
	width: 16px;
	height: 16px;
	margin-right: 10px;
	opacity: 1 !important;
	background-size: 16px;
	vertical-align: middle !important;
}
</style>
