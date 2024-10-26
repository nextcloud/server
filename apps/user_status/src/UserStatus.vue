<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcListItem v-if="!inline"
			class="user-status-menu-item"
			compact
			:name="visibleMessage"
			@click.stop="openModal">
			<template #icon>
				<NcUserStatusIcon class="user-status-icon"
					:status="statusType"
					aria-hidden="true" />
			</template>
		</NcListItem>

		<div v-else>
			<!-- Dashboard Status -->
			<NcButton @click.stop="openModal">
				<template #icon>
					<NcUserStatusIcon class="user-status-icon"
						:status="statusType"
						aria-hidden="true" />
				</template>
				{{ visibleMessage }}
			</NcButton>
		</div>
		<!-- Status management modal -->
		<SetStatusModal v-if="isModalOpen"
			:inline="inline"
			@close="closeModal" />
	</Fragment>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { Fragment } from 'vue-frag'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcUserStatusIcon from '@nextcloud/vue/dist/Components/NcUserStatusIcon.js'
import debounce from 'debounce'

import { sendHeartbeat } from './services/heartbeatService.js'
import OnlineStatusMixin from './mixins/OnlineStatusMixin.js'

export default {
	name: 'UserStatus',

	components: {
		Fragment,
		NcButton,
		NcListItem,
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
			if (getCurrentUser()?.uid === state.userId) {
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
.user-status-icon {
	width: 20px;
	height: 20px;
	margin: calc((var(--default-clickable-area) - 20px) / 2); // 20px icon size
	opacity: 1 !important;
	background-size: 20px;
	vertical-align: middle !important;
}
</style>
