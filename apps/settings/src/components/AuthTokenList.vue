<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
