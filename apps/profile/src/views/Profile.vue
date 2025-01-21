<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="profile">
		<NcAppContent>
			<div class="profile__header">
				<div class="profile__header__container">
					<div class="profile__header__container__placeholder" />
					<div class="profile__header__container__displayname">
						<h2>{{ displayname || userId }}</h2>
						<span v-if="pronouns">·</span>
						<span v-if="pronouns" class="profile__header__container__pronouns">{{ pronouns }}</span>
						<NcButton v-if="isCurrentUser"
							type="primary"
							:href="settingsUrl">
							<template #icon>
								<PencilIcon :size="20" />
							</template>
							{{ t('profile', 'Edit Profile') }}
						</NcButton>
					</div>
					<NcButton v-if="status.icon || status.message"
						:disabled="!isCurrentUser"
						:type="isCurrentUser ? 'tertiary' : 'tertiary-no-background'"
						@click="openStatusModal">
						{{ status.icon }} {{ status.message }}
					</NcButton>
				</div>
			</div>

			<div class="profile__wrapper">
				<div class="profile__content">
					<div class="profile__sidebar">
						<NcAvatar class="avatar"
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
							<NcButton v-if="primaryAction"
								type="primary"
								class="user-actions__primary"
								:href="primaryAction.target"
								:icon="primaryAction.icon"
								:target="primaryAction.id === 'phone' ? '_self' :'_blank'">
								<template #icon>
									<!-- Fix for https://github.com/nextcloud-libraries/nextcloud-vue/issues/2315 -->
									<img :src="primaryAction.icon" alt="" class="user-actions__primary__icon">
								</template>
								{{ primaryAction.title }}
							</NcButton>
							<NcActions class="user-actions__other" :inline="4">
								<NcActionLink v-for="action in otherActions"
									:key="action.id"
									:close-after-click="true"
									:href="action.target"
									:target="action.id === 'phone' ? '_self' :'_blank'">
									<template #icon>
										<!-- Fix for https://github.com/nextcloud-libraries/nextcloud-vue/issues/2315 -->
										<img :src="action.icon" alt="" class="user-actions__other__icon">
									</template>
									{{ action.title }}
								</NcActionLink>
							</NcActions>
						</div>
					</div>

					<div class="profile__blocks">
						<div v-if="organisation || role || address" class="profile__blocks-details">
							<div v-if="organisation || role" class="detail">
								<p>{{ organisation }} <span v-if="organisation && role">•</span> {{ role }}</p>
							</div>
							<div v-if="address" class="detail">
								<p>
									<MapMarkerIcon class="map-icon"
										:size="16" />
									{{ address }}
								</p>
							</div>
						</div>
						<template v-if="headline || biography || sections.length > 0">
							<h3 v-if="headline" class="profile__blocks-headline">
								{{ headline }}
							</h3>
							<NcRichText v-if="biography" :text="biography" use-extended-markdown />

							<!-- additional entries, use it with cautious -->
							<div v-for="(section, index) in sections"
								:ref="'section-' + index"
								:key="index"
								class="profile__additionalContent">
								<component :is="section($refs['section-'+index], userId)" :user-id="userId" />
							</div>
						</template>
						<NcEmptyContent v-else
							class="profile__blocks-empty-info"
							:name="emptyProfileMessage"
							:description="t('profile', 'The headline and about sections will show up here')">
							<template #icon>
								<AccountIcon :size="60" />
							</template>
						</NcEmptyContent>
					</div>
				</div>
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcRichText from '@nextcloud/vue/dist/Components/NcRichText.js'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'

interface IProfileAction {
	target: string
	icon: string
	id: string
	title: string
}

interface IStatus {
	icon: string,
	message: string,
	userId: string,
}

export default defineComponent({
	name: 'Profile',

	components: {
		AccountIcon,
		MapMarkerIcon,
		NcActionLink,
		NcActions,
		NcAppContent,
		NcAvatar,
		NcButton,
		NcContent,
		NcEmptyContent,
		NcRichText,
		PencilIcon,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		const profileParameters = loadState('profile', 'profileParameters', {
			userId: null as string|null,
			displayname: null as string|null,
			address: null as string|null,
			organisation: null as string|null,
			role: null as string|null,
			headline: null as string|null,
			biography: null as string|null,
			actions: [] as IProfileAction[],
			isUserAvatarVisible: false,
			pronouns: null as string|null,
		})

		return {
			...profileParameters,
			status: loadState<Partial<IStatus>>('profile', 'status', {}),
			sections: window.OCA.Core.ProfileSections.getSections(),
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

		otherActions() {
			if (this.allActions.length > 1) {
				return this.allActions.slice(1)
			}
			return []
		},

		settingsUrl() {
			return generateUrl('/settings/user')
		},

		emptyProfileMessage() {
			return this.isCurrentUser
				? t('profile', 'You have not added any info yet')
				: t('profile', '{user} has not added any info yet', { user: (this.displayname || this.userId || '') })
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
		handleStatusUpdate(status: IStatus) {
			if (this.isCurrentUser && status.userId === this.userId) {
				this.status = status
			}
		},

		openStatusModal() {
			const statusMenuItem = document.querySelector<HTMLButtonElement>('.user-status-menu-item')
			// Changing the user status is only enabled if you are the current user
			if (this.isCurrentUser) {
				if (statusMenuItem) {
					statusMenuItem.click()
				} else {
					showError(t('profile', 'Error opening the user status modal, try hard refreshing the page'))
				}
			}
		},
	},
})
</script>

<style lang="scss" scoped>
$profile-max-width: 1024px;
$content-max-width: 640px;

:deep(#app-content-vue) {
	background-color: unset;
}

.profile {
	width: 100%;
	overflow-y: auto;

	&__header {
		display: flex;
		position: sticky;
		height: 190px;
		top: -40px;
		background-color: var(--color-main-background-blur);
		backdrop-filter: var(--filter-background-blur);
		-webkit-backdrop-filter: var(--filter-background-blur);

		&__container {
			align-self: flex-end;
			width: 100%;
			max-width: $profile-max-width;
			margin: 8px auto;
			row-gap: 8px;
			display: grid;
			grid-template-rows: max-content max-content;
			grid-template-columns: 240px 1fr;
			justify-content: center;

			&__placeholder {
				grid-row: 1 / 3;
			}

			&__displayname {
				padding-inline: 16px; // same as the status text button, see NcButton
				width: $content-max-width;
				height: 45px;
				margin-block: 125px 0;
				display: flex;
				align-items: center;
				gap: 18px;

				h2 {
					font-size: 30px;
					margin: 0;
				}

				span {
					font-size: 20px;
				}
			}
		}
	}

	&__sidebar {
		position: sticky;
		top: 0;
		align-self: flex-start;
		padding-top: 20px;
		min-width: 220px;
		margin-block: -150px 0;
		margin-inline: 0 20px;

		// Specificity hack is needed to override Avatar component styles
		:deep(.avatar.avatardiv) {
			text-align: center;
			margin: auto;
			display: block;
			padding: 8px;

			&.interactive {
				.avatardiv__user-status {
					// Show that the status is interactive
					cursor: pointer;
				}
			}

			.avatardiv__user-status {
				inset-inline-end: 14px;
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
	}

	&__wrapper {
		background-color: var(--color-main-background);
		min-height: 100%;
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
			cursor: text;
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
			margin-inline: 0;
			margin-block: 10px 0;
			font-weight: bold;
			font-size: 20px;
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
				margin-bottom: 110px;

				&__displayname {
					margin: 80px 20px 0px 0px!important;
					width: unset;
					text-align: center;
					padding-inline: 12px;
				}

				&__edit-button {
					width: fit-content;
					display: block;
					margin: 60px auto;
				}

				&__status-text {
					margin: 4px auto;
				}
			}
		}

		&__content {
			display: block;

			 .avatar {
				// Overlap avatar to top header
				margin-top: -110px !important;
			 }
		}

		&__blocks {
			width: unset;
			max-width: 600px;
			margin: 0 auto;
			padding: 20px 50px 50px 50px;
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

		&__icon {
			filter: var(--primary-invert-if-dark);
		}
	}

	&__other {
		display: flex;
		justify-content: center;
		gap: 0 4px;

		&__icon {
			height: 20px;
			width: 20px;
			object-fit: contain;
			filter: var(--background-invert-if-dark);
			align-self: center;
			margin: 12px; // so we get 44px x 44px
		}
	}
}
</style>
