<!--
  - @copyright 2023 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
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
	<NcHeaderMenu id="user-menu"
		class="user-menu"
		is-nav
		:aria-label="t('core', 'Settings menu')"
		:description="avatarDescription">
		<template #trigger>
			<NcAvatar v-if="!isLoadingUserStatus"
				class="user-menu__avatar"
				:disable-menu="true"
				:disable-tooltip="true"
				:user="userId"
				:preloaded-user-status="userStatus" />
		</template>
		<ul>
			<UserMenuEntry v-for="entry in settingsNavEntries"
				v-bind="entry"
				:key="entry.id" />
		</ul>
	</NcHeaderMenu>
</template>

<script>
import axios from '@nextcloud/axios'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcHeaderMenu from '@nextcloud/vue/dist/Components/NcHeaderMenu.js'

import { getAllStatusOptions } from '../../../apps/user_status/src/services/statusOptionsService.js'
import UserMenuEntry from '../components/UserMenu/UserMenuEntry.vue'

import logger from '../logger.js'

const settingsNavEntries = loadState('core', 'settingsNavEntries', [])

const translateStatus = (status) => {
	const statusMap = Object.fromEntries(
		getAllStatusOptions()
			.map(({ type, label }) => [type, label]),
	)
	if (statusMap[status]) {
		return statusMap[status]
	}
	return status
}

export default {
	name: 'UserMenu',

	components: {
		NcAvatar,
		NcHeaderMenu,
		UserMenuEntry,
	},

	data() {
		return {
			settingsNavEntries,
			displayName: getCurrentUser()?.displayName,
			userId: getCurrentUser()?.uid,
			isLoadingUserStatus: true,
			userStatus: {
				status: null,
				icon: null,
				message: null,
			},
		}
	},

	computed: {
		translatedUserStatus() {
			return {
				...this.userStatus,
				status: translateStatus(this.userStatus.status),
			}
		},

		avatarDescription() {
			const description = [
				t('core', 'Avatar of {displayName}', { displayName: this.displayName }),
				...Object.values(this.translatedUserStatus).filter(Boolean),
			].join(' — ')
			return description
		},
	},

	async created() {
		if (!getCapabilities()?.user_status?.enabled) {
			this.isLoadingUserStatus = false
			return
		}

		const url = generateOcsUrl('/apps/user_status/api/v1/user_status')
		try {
			const response = await axios.get(url)
			const { status, icon, message } = response.data.ocs.data
			this.userStatus = { status, icon, message }
		} catch (e) {
			logger.error('Failed to load user status')
		}
		this.isLoadingUserStatus = false
	},

	mounted() {
		subscribe('user_status:status.updated', this.handleUserStatusUpdated)
		emit('core:user-menu:mounted')
	},

	methods: {
		handleUserStatusUpdated(state) {
			if (this.userId === state.userId) {
				this.userStatus = {
					status: state.status,
					icon: state.icon,
					message: state.message,
				}
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.user-menu {
	margin-right: 12px;

	&:deep {
		.header-menu {
			&__trigger {
				opacity: 1 !important;
				&:focus-visible {
					.user-menu__avatar {
						border: 2px solid var(--color-primary-element);
					}
				}
			}

			&__carret {
				display: none !important;
			}

			&__content {
				width: fit-content !important;
			}
		}
	}

	&__avatar {
		&:active,
		&:focus,
		&:hover {
			border: 2px solid var(--color-primary-element-text);
		}
	}

	ul {
		display: flex;
		flex-direction: column;
		gap: 2px;

		&:deep {
			li {
				a,
				button {
					border-radius: 6px;
					display: inline-flex;
					align-items: center;
					height: var(--header-menu-item-height);
					color: var(--color-main-text);
					padding: 10px 8px;
					box-sizing: border-box;
					white-space: nowrap;
					position: relative;
					width: 100%;

					&:hover {
						background-color: var(--color-background-hover);
					}

					&:focus-visible {
						background-color: var(--color-background-hover) !important;
						box-shadow: inset 0 0 0 2px var(--color-primary-element) !important;
						outline: none !important;
					}

					&:active,
					&.active {
						background-color: var(--color-primary-element);
						color: var(--color-primary-element-text);
					}

					span {
						padding-bottom: 0;
						color: var(--color-main-text);
						white-space: nowrap;
						overflow: hidden;
						text-overflow: ellipsis;
						max-width: 110px;
					}

					img {
						width: 16px;
						height: 16px;
						margin-right: 10px;
					}

					img,
					svg {
						filter: var(--background-invert-if-dark);
					}
				}

				// Override global button styles
				button {
					background-color: transparent;
					border: none;
					font-weight: normal;
					margin: 0;
				}
			}
		}
	}
}
</style>
