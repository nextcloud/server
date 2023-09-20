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
		:aria-label="t('core', 'Search contacts')"
		@open="handleOpen">
		<template #trigger>
			<Contacts :size="20" />
		</template>
		<div id="contactsmenu-menu" />
	</NcHeaderMenu>
</template>

<script>
import NcHeaderMenu from '@nextcloud/vue/dist/Components/NcHeaderMenu.js'

import Contacts from 'vue-material-design-icons/Contacts.vue'

import OC from '../OC/index.js'

export default {
	name: 'ContactsMenu',

	components: {
		Contacts,
		NcHeaderMenu,
	},

	data() {
		return {
			contactsMenu: null,
		}
	},

	mounted() {
		// eslint-disable-next-line no-new
		this.contactsMenu = new OC.ContactsMenu({
			el: '#contactsmenu-menu',
		})
	},

	methods: {
		handleOpen() {
			this.contactsMenu?.loadContacts()
		},
	},
}
</script>

<style lang="scss" scoped>
#contactsmenu-menu {
	/* show 2.5 to 4.5 entries depending on the screen height */
	height: calc(100vh - 50px * 3);
	max-height: calc(50px * 6 + 2px + 26px);
	min-height: calc(50px * 3.5);
	width: 350px;

	&:deep {
		.emptycontent {
			margin-top: 5vh !important;
			margin-bottom: 1.5vh;
			.icon-loading,
			.icon-search {
				display: inline-block;
			}
		}

		label[for="contactsmenu-search"] {
			font-weight: bold;
			font-size: 19px;
			margin-left: 22px;
		}

		#contactsmenu-search {
			width: calc(100% - 16px);
			margin: 8px;
			height: 34px;
		}

		.content {
			/* fixed max height of the parent container without the search input */
			height: calc(100vh - 50px * 3 - 50px);
			max-height: calc(50px * 5);
			min-height: calc(50px * 3.5 - 50px);
			overflow-y: auto;

			.footer {
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
			padding: 2px;

			&:focus-visible {
				box-shadow: inset 0 0 0 2px var(--color-main-text) !important; // override rule in core/css/headers.scss #header a:focus-visible
			}
		}

		.contact {
			display: flex;
			position: relative;
			align-items: center;
			padding: 3px 3px 3px 10px;

			.avatar {
				height: 32px;
				width: 32px;
				display: inline-block;
			}

			.body {
				flex-grow: 1;
				padding-left: 8px;
				min-width: 0;

				div {
					position: relative;
					width: 100%;
					overflow-x: hidden;
					text-overflow: ellipsis;
				}

				.last-message, .email-address {
					color: var(--color-text-maxcontrast);
				}
			}

			.top-action, .second-action, .other-actions {
				width: 16px;
				height: 16px;
				opacity: .5;
				cursor: pointer;

				&:not(button) {
					padding: 14px;
				}
				img {
					filter: var(--background-invert-if-dark);
				}

				&:hover,
				&:active,
				&:focus {
					opacity: 1;
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
				margin-right: 13px;
			}
			.popovermenu::after {
				right: 2px;
			}
		}
	}
}
</style>
