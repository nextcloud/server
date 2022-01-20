<!--
  - @copyright Copyright (c) 2021 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
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
	<div class="profile">
		<div class="profile__header">
			<div class="profile__header__container">
				<div class="profile__header__container__placeholder" />
				<h2 class="profile__header__container__displayname">
					{{ displayname || userId }}
					<a v-if="isCurrentUser"
						class="primary profile__header__container__edit-button"
						:href="settingsUrl">
						<PencilIcon
							class="pencil-icon"
							decorative
							title=""
							:size="16" />
						{{ t('core', 'Edit Profile') }}
					</a>
				</h2>
				<div v-if="status.icon || status.message"
					class="profile__header__container__status-text"
					:class="{ interactive: isCurrentUser }"
					@click.prevent.stop="openStatusModal">
					{{ status.icon }} {{ status.message }}
				</div>
			</div>
		</div>

		<div class="profile__content">
			<div class="profile__sidebar">
				<Avatar
					class="avatar"
					:class="{ interactive: isCurrentUser }"
					:user="userId"
					:size="180"
					:show-user-status="true"
					:show-user-status-compact="false"
					:disable-menu="true"
					:disable-tooltip="true"
					:is-no-user="!isUserAvatarVisible"
					@click.native.prevent.stop="openStatusModal" />

				<div class="user-actions">
					<!-- When a tel: URL is opened with target="_blank", a blank new tab is opened which is inconsistent with the handling of other URLs so we set target="_self" for the phone action -->
					<PrimaryActionButton v-if="primaryAction"
						class="user-actions__primary"
						:href="primaryAction.target"
						:icon="primaryAction.icon"
						:target="primaryAction.id === 'phone' ? '_self' :'_blank'">
						{{ primaryAction.title }}
					</PrimaryActionButton>
					<div class="user-actions__other">
						<!-- FIXME Remove inline styles after https://github.com/nextcloud/nextcloud-vue/issues/2315 is fixed -->
						<Actions v-for="action in middleActions"
							:key="action.id"
							:default-icon="action.icon"
							style="
								background-position: 14px center;
								background-size: 16px;
								background-repeat: no-repeat;"
							:style="{
								backgroundImage: `url(${action.icon})`,
								...(colorMainBackground === '#181818' && { filter: 'invert(1)' })
							}">
							<ActionLink
								:close-after-click="true"
								:icon="action.icon"
								:href="action.target"
								:target="action.id === 'phone' ? '_self' :'_blank'">
								{{ action.title }}
							</ActionLink>
						</Actions>
						<template v-if="otherActions">
							<Actions
								:force-menu="true">
								<ActionLink v-for="action in otherActions"
									:key="action.id"
									:class="{ 'icon-invert': colorMainBackground === '#181818' }"
									:close-after-click="true"
									:icon="action.icon"
									:href="action.target"
									:target="action.id === 'phone' ? '_self' :'_blank'">
									{{ action.title }}
								</ActionLink>
							</Actions>
						</template>
					</div>
				</div>
			</div>

			<div class="profile__blocks">
				<div v-if="organisation || role || address" class="profile__blocks-details">
					<div v-if="organisation || role" class="detail">
						<p>{{ organisation }} <span v-if="organisation && role">•</span> {{ role }}</p>
					</div>
					<div v-if="address" class="detail">
						<p>
							<MapMarkerIcon
								class="map-icon"
								decorative
								title=""
								:size="16" />
							{{ address }}
						</p>
					</div>
				</div>
				<template v-if="headline || biography">
					<div v-if="headline" class="profile__blocks-headline">
						<h3>{{ headline }}</h3>
					</div>
					<div v-if="biography" class="profile__blocks-biography">
						<p>{{ biography }}</p>
					</div>
				</template>
				<template v-else>
					<div class="profile__blocks-empty-info">
						<AccountIcon
							decorative
							title=""
							fill-color="var(--color-text-maxcontrast)"
							:size="60" />
						<h3>{{ emptyProfileMessage }}</h3>
						<p>{{ t('core', 'The headline and about sections will show up here') }}</p>
					</div>
				</template>
			</div>
		</div>
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker'
import PencilIcon from 'vue-material-design-icons/Pencil'
import AccountIcon from 'vue-material-design-icons/Account'

import PrimaryActionButton from '../components/Profile/PrimaryActionButton'

const status = loadState('core', 'status', {})
const {
	userId,
	displayname,
	address,
	organisation,
	role,
	headline,
	biography,
	actions,
	isUserAvatarVisible,
} = loadState('core', 'profileParameters', {
	userId: null,
	displayname: null,
	address: null,
	organisation: null,
	role: null,
	headline: null,
	biography: null,
	actions: [],
	isUserAvatarVisible: false,
})

export default {
	name: 'Profile',

	components: {
		AccountIcon,
		ActionLink,
		Actions,
		Avatar,
		MapMarkerIcon,
		PencilIcon,
		PrimaryActionButton,
	},

	data() {
		return {
			status,
			userId,
			displayname,
			address,
			organisation,
			role,
			headline,
			biography,
			actions,
			isUserAvatarVisible,
		}
	},

	computed: {
		isCurrentUser() {
			return getCurrentUser()?.uid === this.userId
		},

		allActions() {
			return this.actions
		},

		primaryAction() {
			if (this.allActions.length) {
				return this.allActions[0]
			}
			return null
		},

		middleActions() {
			if (this.allActions.slice(1, 4).length) {
				return this.allActions.slice(1, 4)
			}
			return null
		},

		otherActions() {
			if (this.allActions.slice(4).length) {
				return this.allActions.slice(4)
			}
			return null
		},

		settingsUrl() {
			return generateUrl('/settings/user')
		},

		colorMainBackground() {
			// For some reason the returned string has prepended whitespace
			return getComputedStyle(document.body).getPropertyValue('--color-main-background').trim()
		},

		emptyProfileMessage() {
			return this.isCurrentUser
				? t('core', 'You have not added any info yet')
				: t('core', '{user} has not added any info yet', { user: (this.displayname || this.userId) })
		},
	},

	mounted() {
		// Set the user's displayname or userId in the page title and preserve the default title of "Nextcloud" at the end
		document.title = `${this.displayname || this.userId} - ${document.title}`
		subscribe('user_status:status.updated', this.handleStatusUpdate)
	},

	beforeDestroy() {
		unsubscribe('user_status:status.updated', this.handleStatusUpdate)
	},

	methods: {
		handleStatusUpdate(status) {
			if (this.isCurrentUser && status.userId === this.userId) {
				this.status = status
			}
		},

		openStatusModal() {
			const statusMenuItem = document.querySelector('.user-status-menu-item__toggle')
			// Changing the user status is only enabled if you are the current user
			if (this.isCurrentUser) {
				if (statusMenuItem) {
					statusMenuItem.click()
				} else {
					showError(t('core', 'Error opening the user status modal, try hard refreshing the page'))
				}
			}
		},
	},
}
</script>

<style lang="scss">
// Override header styles
#header {
	background-color: transparent !important;
	background-image: none !important;
}

#content {
	padding-top: 0px;
}
</style>

<style lang="scss" scoped>
$profile-max-width: 1024px;
$content-max-width: 640px;

.profile {
	width: 100%;

	&__header {
		position: sticky;
		height: 190px;
		top: -40px;

		&__container {
			align-self: flex-end;
			width: 100%;
			max-width: $profile-max-width;
			margin: 0 auto;
			display: grid;
			grid-template-rows: max-content max-content;
			grid-template-columns: 240px 1fr;
			justify-content: center;

			&__placeholder {
				grid-row: 1 / 3;
			}

			&__displayname, &__status-text {
				color: var(--color-primary-text);
			}

			&__displayname {
				width: $content-max-width;
				height: 45px;
				margin-top: 128px;
				// Override the global style declaration
				margin-bottom: 0;
				font-size: 30px;
				display: flex;
				align-items: center;
				cursor: text;

				&:not(:last-child) {
					margin-top: 100px;
					margin-bottom: 4px;
				}
			}

			&__edit-button {
				border: none;
				margin-left: 18px;
				margin-top: 2px;
				color: var(--color-primary-element);
				background-color: var(--color-primary-text);
				box-shadow: 0 0 0 2px var(--color-primary-text);
				border-radius: var(--border-radius-pill);
				padding: 0 18px;
				font-size: var(--default-font-size);
				height: 44px;
				line-height: 44px;
				font-weight: bold;

				&:hover,
				&:focus,
				&:active {
					color: var(--color-primary-text);
					background-color: var(--color-primary-element-light);
				}

				.pencil-icon {
					display: inline-block;
					vertical-align: middle;
					margin-top: 2px;
				}
			}

			&__status-text {
				width: max-content;
				max-width: $content-max-width;
				padding: 5px 10px;
				margin-left: -12px;
				margin-top: 2px;

				&.interactive {
					cursor: pointer;

					&:hover,
					&:focus,
					&:active {
						background-color: var(--color-main-background);
						color: var(--color-main-text);
						border-radius: var(--border-radius-pill);
						font-weight: bold;
						box-shadow: 0 3px 6px var(--color-box-shadow);
					}
				}
			}
		}
	}

	&__sidebar {
		position: sticky;
		top: var(--header-height);
		align-self: flex-start;
		padding-top: 20px;
		min-width: 220px;
		margin: -150px 20px 0 0;

		// Specificity hack is needed to override Avatar component styles
		&::v-deep .avatar.avatardiv, h2 {
			text-align: center;
			margin: auto;
			display: block;
			padding: 8px;
		}

		&::v-deep .avatar.avatardiv:not(.avatardiv--unknown) {
			background-color: var(--color-main-background) !important;
			box-shadow: none;
		}

		&::v-deep .avatar.avatardiv {
			.avatardiv__user-status {
				right: 14px;
				bottom: 14px;
				width: 34px;
				height: 34px;
				background-size: 28px;
				border: none;
				// Styles when custom status icon and status text are set
				background-color: var(--color-main-background);
				line-height: 34px;
				font-size: 20px;
			}
		}

		&::v-deep .avatar.interactive.avatardiv {
			.avatardiv__user-status {
				cursor: pointer;

				&:hover,
				&:focus,
				&:active {
					box-shadow: 0 3px 6px var(--color-box-shadow);
				}
			}
		}
	}

	&__content {
		max-width: $profile-max-width;
		margin: 0 auto;
		display: flex;
		width: 100%;
	}

	&__blocks {
		margin: 18px 0 80px 0;
		display: grid;
		gap: 16px 0;
		width: $content-max-width;

		p, h3 {
			overflow-wrap: anywhere;
		}

		&-details {
			display: flex;
			flex-direction: column;
			gap: 2px 0;

			.detail {
				display: inline-block;
				color: var(--color-text-maxcontrast);

				p .map-icon {
					display: inline-block;
					vertical-align: middle;
				}
			}
		}

		&-headline {
			margin-top: 10px;

			h3 {
				font-weight: bold;
				font-size: 20px;
				margin: 0;
			}
		}

		&-biography {
			white-space: pre-line;
		}

		h3, p {
			cursor: text;
		}

		&-empty-info {
			margin-top: 80px;
			margin-right: 100px;
			display: flex;
			flex-direction: column;
			text-align: center;

			h3 {
				font-weight: bold;
				font-size: 18px;
				margin: 8px 0;
			}
		}
	}
}

@media only screen and (max-width: 1024px) {
	.profile {
		&__header {
			height: 250px;
			position: unset;

			&__container {
				grid-template-columns: unset;

				&__displayname {
					margin: 100px 20px 0px;
					width: unset;
					display: unset;
					text-align: center;
				}

				&__edit-button {
					width: fit-content;
					display: block;
					margin: 30px auto;
				}
			}
		}

		&__content {
			display: block;
		}

		&__blocks {
			width: unset;
			max-width: 600px;
			margin: 0 auto;
			padding: 20px 50px 50px 50px;

			&-empty-info {
				margin: 0;
			}
		}

		&__sidebar {
			margin: unset;
			position: unset;
		}
	}
}

.user-actions {
	display: flex;
	flex-direction: column;
	gap: 8px 0;
	margin-top: 20px;

	&__primary {
		margin: 0 auto;
	}

	&__other {
		display: flex;
		justify-content: center;
		gap: 0 4px;
	}
}

.icon-invert {
	&::v-deep .action-link__icon {
		filter: invert(1);
	}
}
</style>
