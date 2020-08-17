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
	<div class="contacts-menu__footer">
		<a v-if="contactsAppEnabled" :href="contactsAppURL">
			{{ t('core', 'Show all contacts …') }}
		</a>
		<a v-else-if="canInstallApp" :href="contactsAppMgmtURL">
			{{ t('core', 'Install the Contacts app') }}
		</a>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

import OC from '../../OC/index'

export default {
	props: {
		contactsAppEnabled: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			contactsAppURL: generateUrl('/apps/contacts'),
			canInstallApp: OC.isUserAdmin(),
			contactsAppMgmtURL: generateUrl('/settings/apps/social/contacts'),
		}
	},
}
</script>

<style lang="scss" scoped>
.contacts-menu__footer {
	text-align: center;

	a {
		display: block;
		width: 100%;
		padding: 12px 0;
		opacity: .5;
	}
}
</style>
