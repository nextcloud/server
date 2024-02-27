<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<div id="security" class="section">
		<h2>{{ t('settings', 'Devices & sessions', {}, undefined, {sanitize: false}) }}</h2>
		<p class="settings-hint hidden-when-empty">
			{{ t('settings', 'Web, desktop and mobile clients currently logged in to your account.') }}
		</p>
		<AuthTokenList />
		<AuthTokenSetup v-if="canCreateToken" />
	</div>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import AuthTokenList from './AuthTokenList.vue'
import AuthTokenSetup from './AuthTokenSetup.vue'

export default defineComponent({
	name: 'AuthTokenSection',
	components: {
		AuthTokenList,
		AuthTokenSetup,
	},
	data() {
		return {
			canCreateToken: loadState('settings', 'can_create_app_token'),
		}
	},
	methods: {
		t,
	},
})
</script>
