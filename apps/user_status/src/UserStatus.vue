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
	<component :is="elementTag">
		<div class="user-status-menu-item">
			<!-- Username display -->
			<a v-if="!inline"
				class="user-status-menu-item__header"
				:href="profilePageLink"
				@click.exact="loadProfilePage">
				<div class="user-status-menu-item__header-content">
					<div class="user-status-menu-item__header-content-displayname">{{ displayName }}</div>
					<div v-if="!loadingProfilePage" class="user-status-menu-item__header-content-placeholder" />
					<div v-else class="icon-loading-small" />
				</div>
				<div v-if="profileEnabled">
					{{ t('user_status', 'View profile') }}
				</div>
			</a>

			<!-- Status modal toggle -->
			<toggle :is="inline ? 'button' : 'a'"
				:class="{'user-status-menu-item__toggle--inline': inline}"
				class="user-status-menu-item__toggle"
				href="#"
				@click.prevent.stop="openModal">
				<span aria-hidden="true" :class="statusIcon" class="user-status-menu-item__toggle-icon" />
				{{ visibleMessage }}
			</toggle>
		</div>

		<!-- Status management modal -->
		<SetStatusModal v-if="isModalOpen"
			@close="closeModal" />
	</component>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import debounce from 'debounce'

import { sendHeartbeat } from './services/heartbeatService.js'
import OnlineStatusMixin from './mixins/OnlineStatusMixin.js'

const { profileEnabled } = loadState('user_status', 'profileEnabled', false)

export default {
	name: 'UserStatus',

	components: {
		SetStatusModal: () => import(/* webpackChunkName: 'user-status-modal' */'./components/SetStatusModal.vue'),
	},
	mixins: [OnlineStatusMixin],

	props: {
		inline: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			displayName: getCurrentUser().displayName,
			heartbeatInterval: null,
			isAway: false,
			isModalOpen: false,
			loadingProfilePage: false,
			mouseMoveListener: null,
			profileEnabled,
			setAwayTimeout: null,
		}
	},
	computed: {
		elementTag() {
			return this.inline ? 'div' : 'li'
		},
		/**
		 * The profile page link
		 *
		 * @return {string | null}
		 */
		profilePageLink() {
			if (this.profileEnabled) {
				return generateUrl('/u/{userId}', { userId: getCurrentUser().uid })
			}
			// Since an anchor element is used rather than a button,
			// this hack removes href if the profile is disabled so that disabling pointer-events is not needed to prevent a click from opening a page
			// and to allow the hover event for styling
			return null
		},
	},

	/**
	 * Loads the current user's status from initial state
	 * and stores it in Vuex
	 */
	mounted() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)

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
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		unsubscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		window.removeEventListener('mouseMove', this.mouseMoveListener)
		clearInterval(this.heartbeatInterval)
		unsubscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},

	methods: {
		handleDisplayNameUpdate(displayName) {
			this.displayName = displayName
		},

		handleProfileEnabledUpdate(profileEnabled) {
			this.profileEnabled = profileEnabled
		},

		loadProfilePage() {
			if (this.profileEnabled) {
				this.loadingProfilePage = true
			}
		},

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
	&__header {
		display: flex !important;
		flex-direction: column !important;
		width: auto !important;
		height: 44px * 1.5 !important;
		padding: 10px 12px 5px 12px !important;
		align-items: flex-start !important;
		color: var(--color-main-text) !important;

		&:not([href]) {
			height: var(--header-menu-item-height) !important;
			color: var(--color-text-maxcontrast) !important;
			cursor: default !important;

			& * {
				cursor: default !important;
			}

			&:hover {
				background-color: transparent !important;
			}
		}

		&-content {
			display: inline-flex !important;
			font-weight: bold !important;
			gap: 0 10px !important;
			width: auto;

			&-displayname {
				width: auto;
			}

			&-placeholder {
				width: 16px !important;
				height: 24px !important;
				margin-right: 10px !important;
				visibility: hidden !important;
			}
		}

		span {
			color: var(--color-text-maxcontrast) !important;
		}
	}

	&__toggle {
		&-icon {
			width: 16px;
			height: 16px;
			margin-right: 10px;
			opacity: 1 !important;
			background-size: 16px;
			vertical-align: middle !important;
		}

		// In dashboard
		&--inline {
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
			&:focus {
				background-color: var(--color-background-hover);
			}
			&:focus {
				box-shadow: 0 0 0 2px var(--color-main-text) !important;
			}
		}
	}
}

li {
	list-style-type: none;
}

</style>
