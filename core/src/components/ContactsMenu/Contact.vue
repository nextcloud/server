<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li class="contact">
		<NcAvatar class="contact__avatar"
			:size="44"
			:user="contact.isUser ? contact.uid : undefined"
			:is-no-user="!contact.isUser"
			:disable-menu="true"
			:display-name="contact.avatarLabel"
			:preloaded-user-status="preloadedUserStatus" />
		<a class="contact__body"
			:href="contact.profileUrl || contact.topAction?.hyperlink">
			<div class="contact__body__full-name">{{ contact.fullName }}</div>
			<div v-if="contact.lastMessage" class="contact__body__last-message">{{ contact.lastMessage }}</div>
			<div v-if="contact.statusMessage" class="contact__body__status-message">{{ contact.statusMessage }}</div>
			<div v-else class="contact__body__email-address">{{ contact.emailAddresses[0] }}</div>
		</a>
		<NcActions v-if="actions.length"
			:inline="contact.topAction ? 1 : 0">
			<template v-for="(action, idx) in actions">
				<NcActionLink v-if="action.hyperlink !== '#'"
					:key="`${idx}-link`"
					:href="action.hyperlink"
					class="other-actions">
					<template #icon>
						<img aria-hidden="true" class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionLink>
				<NcActionText v-else :key="`${idx}-text`" class="other-actions">
					<template #icon>
						<img aria-hidden="true" class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionText>
			</template>
			<NcActionButton v-for="action in jsActions"
				:key="action.id"
				:close-after-click="true"
				class="other-actions"
				@click="action.callback(contact)">
				<template #icon>
					<NcIconSvgWrapper class="contact__action__icon-svg"
						:svg="action.iconSvg(contact)" />
				</template>
				{{ action.displayName(contact) }}
			</NcActionButton>
		</NcActions>
	</li>
</template>

<script>
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { getEnabledContactsMenuActions } from '@nextcloud/vue/dist/Functions/contactsMenu.js'

export default {
	name: 'Contact',
	components: {
		NcActionLink,
		NcActionText,
		NcActionButton,
		NcActions,
		NcAvatar,
		NcIconSvgWrapper,
	},
	props: {
		contact: {
			required: true,
			type: Object,
		},
	},
	computed: {
		actions() {
			if (this.contact.topAction) {
				return [this.contact.topAction, ...this.contact.actions]
			}
			return this.contact.actions
		},
		jsActions() {
			return getEnabledContactsMenuActions(this.contact)
		},
		preloadedUserStatus() {
			if (this.contact.status) {
				return {
					status: this.contact.status,
					message: this.contact.statusMessage,
					icon: this.contact.statusIcon,
				}
			}
			return undefined
		},
	},
}
</script>

<style scoped lang="scss">
.contact {
	display: flex;
	position: relative;
	align-items: center;
	padding: 3px;
	padding-inline-start: 10px;

	&__action {
		&__icon {
			width: 20px;
			height: 20px;
			padding: 12px;
			filter: var(--background-invert-if-dark);
		}

		&__icon-svg {
			padding: 5px;
		}
	}

	&__avatar {
		display: inherit;
	}

	&__body {
		flex-grow: 1;
		padding-inline-start: 10px;
		margin-inline-start: 10px;
		min-width: 0;

		div {
			position: relative;
			width: 100%;
			overflow-x: hidden;
			text-overflow: ellipsis;
			margin: -1px 0;
		}
		div:first-of-type {
			margin-top: 0;
		}
		div:last-of-type {
			margin-bottom: 0;
		}

		&__last-message, &__status-message, &__email-address {
			color: var(--color-text-maxcontrast);
		}

		&:focus-visible {
			box-shadow: 0 0 0 4px var(--color-main-background) !important;
			outline: 2px solid var(--color-main-text) !important;
		}
	}

	.other-actions {
		width: 16px;
		height: 16px;
		cursor: pointer;

		img {
			filter: var(--background-invert-if-dark);
		}
	}

	button.other-actions {
		width: 44px;

		&:focus {
			border-color: transparent;
			box-shadow: 0 0 0 2px var(--color-main-text);
		}

		&:focus-visible {
			border-radius: var(--border-radius-pill);
		}
	}

	/* actions menu */
	.menu {
		top: 47px;
		margin-inline-end: 13px;
	}

	.popovermenu::after {
		inset-inline-end: 2px;
	}
}
</style>
