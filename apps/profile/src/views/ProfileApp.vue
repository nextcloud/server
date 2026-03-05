<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IProfileSection } from '../services/ProfileSections.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref } from 'vue'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import AccountIcon from 'vue-material-design-icons/AccountOutline.vue'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker.vue'
import PencilIcon from 'vue-material-design-icons/PencilOutline.vue'
import ProfileSection from '../components/ProfileSection.vue'

interface IProfileAction {
	target: string
	icon: string
	id: string
	title: string
}

interface IStatus {
	icon: string
	message: string
	userId: string
}

const profileParameters = loadState('profile', 'profileParameters', {
	userId: undefined as string | undefined,
	displayname: undefined as string | undefined,
	address: undefined as string | undefined,
	organisation: undefined as string | undefined,
	role: undefined as string | undefined,
	headline: undefined as string | undefined,
	biography: undefined as string | undefined,
	actions: [] as IProfileAction[],
	isUserAvatarVisible: false,
	pronouns: undefined as string | undefined,
})

const userStatus = ref(loadState<Partial<IStatus>>('profile', 'status', {}))
const sections = ref<IProfileSection[]>([])
const sortedSections = computed(() => [...sections.value].sort((a, b) => b.order - a.order))
onBeforeMount(() => {
	sections.value = window.OCA.Profile.ProfileSections.getSections()
})

const isCurrentUser = getCurrentUser()?.uid === profileParameters.userId

const primaryAction = profileParameters.actions[0]
const otherActions = profileParameters.actions.slice(1)

const settingsUrl = generateUrl('/settings/user')
const emptyProfileMessage = isCurrentUser
	? t('profile', 'You have not added any info yet')
	: t('profile', '{user} has not added any info yet', { user: (profileParameters.displayname || profileParameters.userId || '') })

onMounted(() => {
	// Set the user's displayname or userId in the page title and preserve the default title of "Nextcloud" at the end
	document.title = `${profileParameters.displayname || profileParameters.userId} - ${document.title}`
	subscribe('user_status:status.updated', handleStatusUpdate)
})

onBeforeUnmount(() => {
	unsubscribe('user_status:status.updated', handleStatusUpdate)
})

/**
 * Update the user status
 *
 * @param status - The new status
 */
function handleStatusUpdate(status: IStatus) {
	if (isCurrentUser && status.userId === profileParameters.userId) {
		userStatus.value = status
	}
}

/**
 * Open the user status modal by simulating a click on the hidden status menu item in the avatar menu
 */
function openStatusModal() {
	// Changing the user status is only enabled if you are the current user
	if (!isCurrentUser) {
		return
	}

	const statusMenuItem = document.querySelector<HTMLButtonElement>('.user-status-menu-item')
	if (statusMenuItem) {
		statusMenuItem.click()
	} else {
		showError(t('profile', 'Error opening the user status modal, try hard refreshing the page'))
	}
}
</script>

<template>
	<NcContent appName="profile">
		<NcAppContent>
			<div class="profile__header">
				<div class="profile__header__container">
					<div class="profile__header__container__placeholder" />
					<div class="profile__header__container__displayname">
						<h2>{{ profileParameters.displayname || profileParameters.userId }}</h2>
						<span v-if="profileParameters.pronouns">·</span>
						<span v-if="profileParameters.pronouns" class="profile__header__container__pronouns">{{ profileParameters.pronouns }}</span>
						<NcButton
							v-if="isCurrentUser"
							variant="primary"
							:href="settingsUrl">
							<template #icon>
								<PencilIcon :size="20" />
							</template>
							{{ t('profile', 'Edit Profile') }}
						</NcButton>
					</div>
					<NcButton
						v-if="userStatus.icon || userStatus.message"
						:disabled="!isCurrentUser"
						:variant="isCurrentUser ? 'tertiary' : 'tertiary-no-background'"
						@click="openStatusModal">
						{{ userStatus.icon }} {{ userStatus.message }}
					</NcButton>
				</div>
			</div>

			<div class="profile__wrapper">
				<div class="profile__content">
					<div class="profile__sidebar">
						<NcAvatar
							class="avatar"
							:class="{ interactive: isCurrentUser }"
							:user="profileParameters.userId"
							:size="180"
							:disableMenu="true"
							:disableTooltip="true"
							:isNoUser="!profileParameters.isUserAvatarVisible"
							@click.prevent.stop="openStatusModal" />

						<div class="user-actions">
							<!-- When a tel: URL is opened with target="_blank", a blank new tab is opened which is inconsistent with the handling of other URLs so we set target="_self" for the phone action -->
							<NcButton
								v-if="primaryAction"
								variant="primary"
								class="user-actions__primary"
								:href="primaryAction.target"
								:icon="primaryAction.icon"
								:target="primaryAction.id === 'phone' ? '_self' : '_blank'">
								<template #icon>
									<!-- Fix for https://github.com/nextcloud-libraries/nextcloud-vue/issues/2315 -->
									<img :src="primaryAction.icon" alt="" class="user-actions__primary__icon">
								</template>
								{{ primaryAction.title }}
							</NcButton>
							<NcActions class="user-actions__other" :inline="4">
								<NcActionLink
									v-for="action in otherActions"
									:key="action.id"
									:closeAfterClick="true"
									:href="action.target"
									:target="action.id === 'phone' ? '_self' : '_blank'">
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
						<div v-if="profileParameters.organisation || profileParameters.role || profileParameters.address" class="profile__blocks-details">
							<div v-if="profileParameters.organisation || profileParameters.role" class="detail">
								<p>{{ profileParameters.organisation }} <span v-if="profileParameters.organisation && profileParameters.role">•</span> {{ profileParameters.role }}</p>
							</div>
							<div v-if="profileParameters.address" class="detail">
								<p>
									<MapMarkerIcon
										class="map-icon"
										:size="16" />
									{{ profileParameters.address }}
								</p>
							</div>
						</div>
						<template v-if="profileParameters.headline || profileParameters.biography || sections.length > 0">
							<h3 v-if="profileParameters.headline" class="profile__blocks-headline">
								{{ profileParameters.headline }}
							</h3>
							<NcRichText v-if="profileParameters.biography" :text="profileParameters.biography" useExtendedMarkdown />

							<!-- additional entries, use it with cautious -->
							<ProfileSection
								v-for="section in sortedSections"
								:key="section.id"
								:section="section"
								:userId="profileParameters.userId" />
						</template>
						<NcEmptyContent
							v-else
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
	max-width: 300px;

	&__primary {
		margin: 0 auto;
		max-width: 100%;

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
