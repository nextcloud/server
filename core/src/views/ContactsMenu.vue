<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcHeaderMenu id="contactsmenu"
		class="contactsmenu"
		:aria-label="t('core', 'Search contacts')"
		@open="handleOpen">
		<template #trigger>
			<Contacts class="contactsmenu__trigger-icon" :size="20" />
		</template>
		<div class="contactsmenu__menu">
			<div class="contactsmenu__menu__input-wrapper">
				<NcTextField id="contactsmenu__menu__search"
					ref="contactsMenuInput"
					:value.sync="searchTerm"
					trailing-button-icon="close"
					:label="t('core', 'Search contacts')"
					:trailing-button-label="t('core','Reset search')"
					:show-trailing-button="searchTerm !== ''"
					:placeholder="t('core', 'Search contacts …')"
					class="contactsmenu__menu__search"
					@input="onInputDebounced"
					@trailing-button-click="onReset" />
			</div>
			<NcEmptyContent v-if="error" :name="t('core', 'Could not load your contacts')">
				<template #icon>
					<Magnify />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadingText" :name="loadingText">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="contacts.length === 0" :name="t('core', 'No contacts found')">
				<template #icon>
					<Magnify />
				</template>
			</NcEmptyContent>
			<div v-else class="contactsmenu__menu__content">
				<div id="contactsmenu-contacts">
					<ul>
						<Contact v-for="contact in contacts" :key="contact.id" :contact="contact" />
					</ul>
				</div>
				<div v-if="contactsAppEnabled" class="contactsmenu__menu__content__footer">
					<NcButton type="tertiary" :href="contactsAppURL">
						{{ t('core', 'Show all contacts') }}
					</NcButton>
				</div>
				<div v-else-if="canInstallApp" class="contactsmenu__menu__content__footer">
					<NcButton type="tertiary" :href="contactsAppMgmtURL">
						{{ t('core', 'Install the Contacts app') }}
					</NcButton>
				</div>
			</div>
		</div>
	</NcHeaderMenu>
</template>

<script>
import axios from '@nextcloud/axios'
import Contacts from 'vue-material-design-icons/Contacts.vue'
import debounce from 'debounce'
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcHeaderMenu from '@nextcloud/vue/dist/Components/NcHeaderMenu.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { translate as t } from '@nextcloud/l10n'

import Contact from '../components/ContactsMenu/Contact.vue'
import logger from '../logger.js'
import Nextcloud from '../mixins/Nextcloud.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default {
	name: 'ContactsMenu',

	components: {
		Contact,
		Contacts,
		Magnify,
		NcButton,
		NcEmptyContent,
		NcHeaderMenu,
		NcLoadingIcon,
		NcTextField,
	},

	mixins: [Nextcloud],

	data() {
		const user = getCurrentUser()
		return {
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
				this.loadingText = t('core', 'Loading your contacts …')
			} else {
				this.loadingText = t('core', 'Looking for {term} …', {
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

		&__input-wrapper {
			padding: 10px;
			z-index: 2;
			top: 0;
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
