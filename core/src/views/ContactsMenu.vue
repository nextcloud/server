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
	<NcHeaderMenu id="contactsmenu"
		class="contactsmenu"
		:aria-label="t('core', 'Search contacts')"
		@open="handleOpen">
		<template #trigger>
			<Contacts :size="20" />
		</template>
		<div class="contactsmenu__menu">
			<label for="contactsmenu__menu__search">{{ t('core', 'Search contacts') }}</label>
			<input id="contactsmenu__menu__search"
				v-model="searchTerm"
				class="contactsmenu__menu__search"
				type="search"
				:placeholder="t('core', 'Search contacts …')"
				@input="onInputDebounced">
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
					<a :href="contactsAppURL">{{ t('core', 'Show all contacts …') }}</a>
				</div>
				<div v-else-if="canInstallApp" class="contactsmenu__menu__content__footer">
					<a :href="contactsAppMgmtURL">{{ t('core', 'Install the Contacts app') }}</a>
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
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcHeaderMenu from '@nextcloud/vue/dist/Components/NcHeaderMenu.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { translate as t } from '@nextcloud/l10n'

import Contact from '../components/ContactsMenu/Contact.vue'
import logger from '../logger.js'
import Nextcloud from '../mixins/Nextcloud.js'

export default {
	name: 'ContactsMenu',

	components: {
		Contact,
		Contacts,
		Magnify,
		NcEmptyContent,
		NcHeaderMenu,
		NcLoadingIcon,
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
	},
}
</script>

<style lang="scss" scoped>
.contactsmenu {
	&__menu {
		/* show 2.5 to 4.5 entries depending on the screen height */
		height: calc(100vh - 50px * 3);
		max-height: calc(50px * 6 + 2px + 26px);
		min-height: calc(50px * 3.5);

		label[for="contactsmenu__menu__search"] {
			font-weight: bold;
			font-size: 19px;
			margin-left: 13px;
		}

		&__search {
			width: 100%;
			height: 34px;
			margin: 8px 0;
		}

		&__content {
			/* fixed max height of the parent container without the search input */
			height: calc(100vh - 50px * 3 - 60px);
			max-height: calc(50px * 5);
			min-height: calc(50px * 3.5 - 50px);
			overflow-y: auto;

			&__footer {
				text-align: center;

				a {
					display: block;
					width: 100%;
					padding: 12px 0;
					opacity: .5;
				}
			}
		}

		a {
			&:focus-visible {
				box-shadow: inset 0 0 0 2px var(--color-main-text) !important; // override rule in core/css/headers.scss #header a:focus-visible
			}
		}
	}
}
</style>
