<!--
  - @copyright 2020 Kirill Dmitriev <dk1a@protonmail.com>
  -
  - @author 2020 Kirill Dmitriev <dk1a@protonmail.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<HeaderMenu id="contacts-menu"
		class="contacts-menu"
		@open="onOpen"
		@close="onClose">
		<!-- Header icon -->
		<template #trigger>
			<div class="icon-contacts" />
		</template>

		<!-- Search input -->
		<div class="contacts-menu__input-wrapper">
			<input ref="input"
				v-model="query"
				class="contacts-menu__input"
				type="search"
				:placeholder="searchContactsText"
				@input="onInput">
		</div>

		<!-- Search results -->
		<div v-if="loadState === LoadStates.LOADED && contacts.length"
			class="contacts-menu__contacts-list"
			:aria-label="t('core', 'Contacts menu')">
			<Contact v-for="contact in contacts" :key="contact.id" :contact="contact" />
		</div>

		<!-- Loading placeholders -->
		<EmptyContent v-else-if="loadState === LoadStates.LOADED && !contacts.length" icon="icon-search">
			{{ t('core', 'No contacts found') }}
		</EmptyContent>

		<EmptyContent v-else-if="loadState === LoadStates.SHORT_QUERY" icon="icon-search">
			{{ t('core', 'Start typing to search') }}
			<template v-if="isShortQuery" #desc>
				{{ n('core',
					'Please enter {minSearchLength} character or more to search',
					'Please enter {minSearchLength} characters  or more to search',
					minSearchLength,
					{minSearchLength}) }}
			</template>
		</EmptyContent>

		<EmptyContent v-else-if="loadState === LoadStates.LOADING" icon="icon-loading">
			{{ t('core', loadingText) }}
		</EmptyContent>

		<EmptyContent v-else-if="loadState === LoadStates.ERROR" icon="icon-search">
			{{ t('core', 'Could not load your contacts') }}
		</EmptyContent>

		<!-- Footer -->
		<ContactsFooter v-if="loadState === LoadStates.LOADED" :contacts-app-enabled="contactsAppEnabled" />
	</HeaderMenu>
</template>

<script>
import debounce from 'debounce'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import OC from '../OC'

import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import HeaderMenu from '../components/HeaderMenu'

import Contact from '../components/ContactsMenu/Contact'
import ContactsFooter from '../components/ContactsMenu/ContactsFooter'

export default {
	components: {
		HeaderMenu,
		Contact,
		EmptyContent,
		ContactsFooter,
	},

	data() {
		return {
			searchContactsText: t('core', 'Search contacts …'),
			loadingText: undefined,

			query: '',
			LoadStates: {
				LOADING: 0,
				LOADED: 1,
				ERROR: 2,
				SHORT_QUERY: 3,
			},
			loadState: undefined,

			minSearchLength: parseInt(OC.config['sharing.minSearchStringLength']),

			contactsAppEnabled: undefined,
			contacts: undefined,
		}
	},

	computed: {
		isShortQuery() {
			return this.query.trim().length < this.minSearchLength
		},
	},

	methods: {
		onOpen() {
			this.focusInput()
			// load initial contacts list
			this.loadContacts()
		},
		onClose() {
			this.query = ''
		},

		onInput: debounce(function(query) {
			// load contacts on input
			this.loadContacts()
		}, 700),

		async loadContacts() {
			if (this.isShortQuery) {
				this.loadState = this.LoadStates.SHORT_QUERY
				return
			} else if (this.query.trim() === '') {
				this.loadingText = t('core', 'Loading your contacts …')
			} else {
				this.loadingText = t('core', 'Looking for {term} …', {
					term: this.query,
				})
			}
			this.loadState = this.LoadStates.LOADING

			const url = generateUrl('/contactsmenu/contacts')
			const data = {
				filter: this.query,
			}
			try {
				const request = await axios.post(url, data)
				this.contactsAppEnabled = request.data.contactsAppEnabled
				this.contacts = request.data.contacts
				this.loadState = this.LoadStates.LOADED
			} catch (e) {
				console.error('There was an error loading your contacts', e)
				this.loadState = this.LoadStates.ERROR
			}
		},

		/**
		 * Focus the search input on next tick
		 */
		focusInput() {
			this.$nextTick(() => {
				this.$refs.input.focus()
				this.$refs.input.select()
			})
		},
	},
}
</script>

<style lang="scss" scoped>
$margin: 10px;
$input-padding: 6px;

.contacts-menu {
	&__input-wrapper {
		position: sticky;
		// above search results
		z-index: 2;
		top: 0;
		background-color: var(--color-main-background);
	}

	&__input {
		// Minus margins
		width: calc(100% - 2 * #{$margin});
		height: 34px;
		margin: $margin;
		padding: $input-padding;
		&,
		&[placeholder],
		&::placeholder {
			overflow: hidden;
			text-overflow:ellipsis;
			white-space: nowrap;
		}

	}

	.empty-content {
		margin: 10vh 0;
	}
}
</style>
