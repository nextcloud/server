<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcHeaderMenu id="public-page-user-menu"
		class="public-page-user-menu"
		is-nav
		:aria-label="t('core', 'User menu')"
		:description="avatarDescription">
		<template #trigger>
			<NcAvatar class="public-page-user-menu__avatar"
				disable-menu
				disable-tooltip
				is-guest
				:user="currentUser || '?'" />
		</template>
		<ul class="public-page-user-menu__list">
			<!-- Privacy notice -->
			<NcNoteCard class="public-page-user-menu__list-note"
				:text="privacyNotice"
				type="info" />

			<!-- Nickname dialog -->
			<AccountMenuEntry id="set-nickname"
				:name="!currentUser ? t('core', 'Set public name') : t('core', 'Change public name')"
				href="#"
				@click.capture.prevent.stop="setNickname">
				<template #icon>
					<IconAccount />
				</template>
			</AccountMenuEntry>
		</ul>
	</NcHeaderMenu>
</template>

<script lang="ts">
import { defineAsyncComponent, defineComponent } from 'vue'
import { getGuestNickname } from '@nextcloud/auth'
import { spawnDialog } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import IconAccount from 'vue-material-design-icons/Account.vue'

import AccountMenuEntry from '../components/AccountMenu/AccountMenuEntry.vue'

export default defineComponent({
	name: 'PublicPageUserMenu',
	components: {
		AccountMenuEntry,
		IconAccount,
		NcAvatar,
		NcHeaderMenu,
		NcNoteCard,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			currentUser: getGuestNickname(),
		}
	},

	computed: {
		avatarDescription(): string {
			return t('core', 'User menu')
		},

		privacyNotice(): string {
			return this.currentUser
				? t('core', 'You will be identified as {user} by the account owner.', { user: this.currentUser })
				: t('core', 'You are currently not identified.')
		},
	},

	methods: {
		setNickname() {
			spawnDialog(
				defineAsyncComponent(() => import('../../../apps/files_sharing/src/views/PublicAuthPrompt.vue')),
				{
					nickname: this.currentUser,
				},
				((newName: string) => { this.currentUser = newName }) as (...rest: unknown[]) => void,
			)
		},
	},
})
</script>

<style scoped lang="scss">
.public-page-user-menu {
	box-sizing: border-box;

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
		padding-inline: 0 var(--default-grid-baseline);

		> :deep(li) {
			box-sizing: border-box;
			// basically "fit-content"
			flex: 0 1;
		}
	}
}
</style>
