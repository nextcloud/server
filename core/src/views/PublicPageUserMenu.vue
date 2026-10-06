<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<script setup lang="ts">
import type { NextcloudUser } from '@nextcloud/auth'

import { mdiAccountOutline } from '@mdi/js'
import { getGuestUser } from '@nextcloud/auth'
import { showGuestUserPrompt } from '@nextcloud/dialogs'
import { subscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import AccountMenuEntry from '../components/AccountMenu/AccountMenuEntry.vue'

import '@nextcloud/dialogs/style.css'

const avatarDescription = t('core', 'User menu')

const displayName = ref(getGuestUser().displayName)

const privacyNotice = computed(() => displayName.value
	? t('core', 'Your guest name: {user}', { user: displayName.value })
	: t('core', 'You are currently not identified.'))

onMounted(() => {
	subscribe('user:info:changed', (user: NextcloudUser) => {
		displayName.value = user.displayName || ''
	})
})

/**
 * Ask the guest for the name to be shown to others.
 */
function setNickname() {
	showGuestUserPrompt({
		nickname: displayName.value,
		cancellable: true,
	})
}
</script>

<template>
	<NcHeaderMenu
		id="public-page-user-menu"
		class="public-page-user-menu"
		isNav
		:aria-label="t('core', 'User menu')"
		:description="avatarDescription">
		<template #trigger>
			<NcAvatar
				class="public-page-user-menu__avatar"
				disableMenu
				disableTooltip
				isGuest
				:user="displayName || '?'" />
		</template>

		<!-- Privacy notice -->
		<NcNoteCard
			class="public-page-user-menu__list-note"
			:text="privacyNotice"
			type="info" />

		<ul class="public-page-user-menu__list">
			<!-- Nickname dialog -->
			<AccountMenuEntry
				id="set-nickname"
				:name="!displayName ? t('core', 'Set public name') : t('core', 'Change public name')"
				href="#"
				@click.prevent.stop="setNickname">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOutline" />
				</template>
			</AccountMenuEntry>
		</ul>
	</NcHeaderMenu>
</template>

<style scoped lang="scss">
.public-page-user-menu {
	&, * {
		box-sizing: border-box;
	}

	// Ensure we do not waste space, as the header menu sets a default width of 350px
	:deep(.header-menu__content) {
		width: fit-content !important;
	}

	&__list-note {
		padding-block: 5px !important;
		padding-inline: 5px !important;
		max-width: 300px;
		margin: 5px !important;
		margin-bottom: 0 !important;
	}

	&__list {
		display: inline-flex;
		flex-direction: column;
		padding-block: var(--default-grid-baseline) 0;
		width: 100%;

		> :deep(li) {
			box-sizing: border-box;
			// basically "fit-content"
			flex: 0 1;
		}
	}
}
</style>
