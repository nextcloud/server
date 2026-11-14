<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { n, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const props = defineProps<{
	/** Whether more accounts are being loaded */
	loading: boolean
	/** The accounts the list shows */
	filteredUsers: unknown[]
}>()

const userCount = computed(() => {
	if (props.loading) {
		return n(
			'settings',
			'{userCount} account …',
			'{userCount} accounts …',
			props.filteredUsers.length,
			{
				userCount: props.filteredUsers.length,
			},
		)
	}
	return n(
		'settings',
		'{userCount} account',
		'{userCount} accounts',
		props.filteredUsers.length,
		{
			userCount: props.filteredUsers.length,
		},
	)
})
</script>

<template>
	<tr class="footer">
		<th scope="row">
			<!-- TRANSLATORS Label for a table footer which summarizes the columns of the table -->
			<span class="hidden-visually">{{ t('settings', 'Total rows summary') }}</span>
		</th>
		<td class="footer__cell footer__cell--loading">
			<NcLoadingIcon
				v-if="loading"
				:name="t('settings', 'Loading accounts …')"
				:size="32" />
		</td>
		<td class="footer__cell footer__cell--count footer__cell--multiline">
			<span aria-describedby="user-count-desc">{{ userCount }}</span>
			<span
				id="user-count-desc"
				class="hidden-visually">
				{{ t('settings', 'Scroll to load more rows') }}
			</span>
		</td>
	</tr>
</template>

<style lang="scss" scoped>
@use './shared/styles.scss';

.footer {
	@include styles.row;
	@include styles.cell;

	&__cell {
		position: sticky;
		color: var(--color-text-maxcontrast);

		&--loading {
			inset-inline-start: 0;
			min-width: var(--avatar-cell-width);
			width: var(--avatar-cell-width);
			align-items: center;
			padding: 0;
		}

		&--count {
			inset-inline-start: var(--avatar-cell-width);
			min-width: var(--cell-width);
			width: var(--cell-width);
		}
	}
}
</style>
