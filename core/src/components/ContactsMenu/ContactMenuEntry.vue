<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ContactsMenuEntry } from '@nextcloud/vue/functions/contactsMenu'

import { getEnabledContactsMenuActions } from '@nextcloud/vue/functions/contactsMenu'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

interface ContactAction {
	title: string
	icon: string
	hyperlink: string
}

/** A contact as returned by the contacts menu API */
type Contact = Omit<ContactsMenuEntry, 'topAction' | 'actions'> & {
	topAction: ContactAction | null
	actions: ContactAction[]
}

const props = defineProps<{
	contact: Contact
}>()

const actions = computed(() => props.contact.topAction
	? [props.contact.topAction, ...props.contact.actions]
	: props.contact.actions)

const jsActions = computed(() => getEnabledContactsMenuActions(props.contact))

const preloadedUserStatus = computed(() => props.contact.status
	? {
			status: props.contact.status,
			message: props.contact.statusMessage,
			icon: props.contact.statusIcon,
		}
	: undefined)
</script>

<template>
	<li class="contact">
		<NcAvatar
			class="contact__avatar"
			:user="contact.isUser ? contact.uid ?? undefined : undefined"
			:isNoUser="!contact.isUser"
			disableMenu
			:displayName="contact.fullName"
			:url="contact.isUser ? undefined : contact.avatar ?? undefined"
			:preloadedUserStatus="preloadedUserStatus" />
		<a
			class="contact__body"
			:href="contact.profileUrl || contact.topAction?.hyperlink">
			<div class="contact__body__full-name">{{ contact.fullName }}</div>
			<div v-if="contact.lastMessage" class="contact__body__last-message">{{ contact.lastMessage }}</div>
			<div v-if="contact.statusMessage" class="contact__body__status-message">{{ contact.statusMessage }}</div>
			<div v-else class="contact__body__email-address">{{ contact.emailAddresses[0] }}</div>
		</a>
		<NcActions
			v-if="actions.length"
			:inline="contact.topAction ? 1 : 0">
			<template v-for="(action, idx) in actions" :key="idx">
				<NcActionLink
					v-if="action.hyperlink !== '#'"
					:href="action.hyperlink"
					class="other-actions">
					<template #icon>
						<img aria-hidden="true" class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionLink>
				<NcActionText v-else class="other-actions">
					<template #icon>
						<img aria-hidden="true" class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionText>
			</template>
			<NcActionButton
				v-for="action in jsActions"
				:key="action.id"
				closeAfterClick
				class="other-actions"
				@click="action.callback(contact)">
				<template #icon>
					<NcIconSvgWrapper :svg="action.iconSvg(contact)" />
				</template>
				{{ action.displayName(contact) }}
			</NcActionButton>
		</NcActions>
	</li>
</template>

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
			padding: calc((var(--default-clickable-area) - 20px) / 2);
			filter: var(--background-invert-if-dark);
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
