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
	<table id="app-tokens-table" class="token-list">
		<thead>
			<tr>
				<th class="token-list__header-device">
					{{ t('settings', 'Device') }}
				</th>
				<th class="toke-list__header-activity">
					{{ t('settings', 'Last activity') }}
				</th>
				<th>
					<span class="hidden-visually">
						{{ t('settings', 'Actions') }}
					</span>
				</th>
			</tr>
		</thead>
		<tbody class="token-list__body">
			<AuthToken v-for="token in sortedTokens"
				:key="token.id"
				:token="token" />
		</tbody>
	</table>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import { useAuthTokenStore } from '../store/authtoken'

import AuthToken from './AuthToken.vue'

export default defineComponent({
	name: 'AuthTokenList',
	components: {
		AuthToken,
	},
	setup() {
		const authTokenStore = useAuthTokenStore()
		return { authTokenStore }
	},
	computed: {
		sortedTokens() {
			return [...this.authTokenStore.tokens].sort((t1, t2) => t2.lastActivity - t1.lastActivity)
		},
	},
	methods: {
		t,
	},
})
</script>

<style lang="scss" scoped>
.token-list {
	width: 100%;
	min-height: 50px;
	padding-top: 5px;
	max-width: fit-content;

	th {
		padding-block: 10px;
		padding-inline-start: 10px;
	}

	#{&}__header-device {
		padding-inline-start: 50px; // 44px icon + 6px padding
	}
	&__header-activity {
		text-align: end;
	}
}
</style>
