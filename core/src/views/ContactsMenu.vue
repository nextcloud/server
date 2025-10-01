<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcHeaderMenu
		id="contactsmenu"
		class="contactsmenu"
		:aria-label="t('core', 'Search contacts')"
		@open="handleOpen">
		<template #trigger>
			<NcIconSvgWrapper class="contactsmenu__trigger-icon" :path="mdiContacts" />
		</template>
		<div class="contactsmenu__menu">
			<div class="contactsmenu__menu__search-container">
				<div class="contactsmenu__menu__input-wrapper">
					<NcTextField
						id="contactsmenu__menu__search"
						ref="contactsMenuInput"
						v-model="searchTerm"
						trailing-button-icon="close"
						:label="t('core', 'Search contacts')"
						:trailing-button-label="t('core', 'Reset search')"
						:show-trailing-button="searchTerm !== ''"
						:placeholder="t('core', 'Search contacts …')"
						class="contactsmenu__menu__search"
						@input="onInputDebounced"
						@trailing-button-click="onReset" />
				</div>
				<NcButton
					v-for="action in actions"
					:key="action.id"
					:aria-label="action.label"
					:title="action.label"
					class="contactsmenu__menu__action"
					variant="tertiary-no-background"
					@click="action.onClick">
					<template #icon>
						<NcIconSvgWrapper :svg="action.icon" />
					</template>
				</NcButton>
			</div>
			<NcEmptyContent v-if="error" :name="t('core', 'Could not load your contacts')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadingText" :name="loadingText">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="contacts.length === 0" :name="t('core', 'No contacts found')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>
			<div v-else class="contactsmenu__menu__content">
				<div id="contactsmenu-contacts">
					<ul>
						<ContactMenuEntry v-for="contact in contacts" :key="contact.id" :contact="contact" />
					</ul>
				</div>
				<div v-if="contactsAppEnabled" class="contactsmenu__menu__content__footer">
					<NcButton variant="tertiary" :href="contactsAppURL">
						{{ t('core', 'Show all contacts') }}
					</NcButton>
				</div>
				<div v-else-if="canInstallApp" class="contactsmenu__menu__content__footer">
					<NcButton variant="tertiary" :href="contactsAppMgmtURL">
						{{ t('core', 'Install the Contacts app') }}
					</NcButton>
				</div>
			</div>
		</div>
	</NcHeaderMenu>
</template>

<script>
import { mdiContacts, mdiMagnify } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import debounce from 'debounce'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ContactMenuEntry from '../components/ContactsMenu/ContactMenuEntry.vue'
import logger from '../logger.js'
import Nextcloud from '../mixins/Nextcloud.js'

export default {
	name: 'ContactsMenu',

	components: {
		ContactMenuEntry,
		NcButton,
		NcEmptyContent,
		NcHeaderMenu,
		NcIconSvgWrapper,
		NcLoadingIcon,
		NcTextField,
	},

	mixins: [Nextcloud],

	setup() {
		return {
			mdiContacts,
			mdiMagnify,
		}
	},

	data() {
		const user = getCurrentUser()
		return {
			actions: window.OC?.ContactsMenu?.actions || [],
			contactsAppEnabled: false,
			contactsAppURL: generateUrl('/apps/contacts'),
			contactsAppMgmtURL: generateUrl('/settings/apps/social/contacts'),
			canInstallApp: user.isAdmin,
			contacts: [],
			loadingText: undefined,
			error: false,
			searchTerm: '',
		}
	},

	methods: {
		async handleOpen() {
			await this.getContacts('')
		},

		async getContacts(searchTerm) {
			if (searchTerm === '') {
				this.loadingText = t('core', 'Loading your contacts …')
			} else {
				this.loadingText = t('core', 'Looking for {term} …', {
					term: searchTerm,
				})
			}

			// Let the user try a different query if the previous one failed
			this.error = false

			try {
				const { data: { contacts, contactsAppEnabled } } = await axios.post(generateUrl('/contactsmenu/contacts'), {
					filter: searchTerm,
				})
				this.contacts = contacts
				this.contactsAppEnabled = contactsAppEnabled
				this.loadingText = undefined
			} catch (error) {
				logger.error('could not load contacts', {
					error,
					searchTerm,
				})
				this.error = true
			}
		},

		onInputDebounced: debounce(function() {
			this.getContacts(this.searchTerm)
		}, 500),

		/**
		 * Reset the search state
		 */
		onReset() {
			this.searchTerm = ''
			this.contacts = []
			this.focusInput()
		},

		/**
		 * Focus the search input on next tick
		 */
		focusInput() {
			this.$nextTick(() => {
				this.$refs.contactsMenuInput.focus()
				this.$refs.contactsMenuInput.select()
			})
		},

	},
}
</script>

<style lang="scss" scoped>
.contactsmenu {
	overflow-y: hidden;

	&__trigger-icon {
		color: var(--color-background-plain-text) !important;
	}

	&__menu {
		display: flex;
		flex-direction: column;
		overflow: hidden;
		height: calc(50px * 6 + 2px + 26px);
		max-height: inherit;

		label[for="contactsmenu__menu__search"] {
			font-weight: bold;
			font-size: 19px;
			margin-inline-start: 13px;
		}

		&__search-container {
			padding: 10px;
			display: flex;
			flex: row nowrap;
			column-gap: 10px;
		}

		&__input-wrapper {
			z-index: 2;
			top: 0;
			flex-grow: 1;
		}

		&__search {
			width: 100%;
			height: 34px;
			margin-top: 0!important;
		}

		&__content {
			overflow-y: auto;
			margin-top: 10px;
			flex: 1 1 auto;

			&__footer {
				display: flex;
				flex-direction: column;
				align-items: center;
			}
		}

		a {
			&:focus-visible {
				box-shadow: inset 0 0 0 2px var(--color-main-text) !important; // override rule in core/css/headers.scss #header a:focus-visible
			}
		}
	}

	:deep(.empty-content) {
		margin: 0 !important;
	}
}
</style>
