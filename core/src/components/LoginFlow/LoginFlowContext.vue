<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LoginFlowV2Context } from '../../types/loginFlowV2.ts'

import { getLanguage, t } from '@nextcloud/l10n'
import { useIntervalFn, useTimestamp } from '@vueuse/core'
import { computed } from 'vue'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import LoginFlowHelp from './LoginFlowHelp.vue'

const props = defineProps<{ context: LoginFlowV2Context }>()
const now = useTimestamp({ scheduler: (update) => useIntervalFn(update, 60_000) })
const formatter = new Intl.RelativeTimeFormat(getLanguage(), { numeric: 'always' })
const requestAge = computed(() => formatter.format(-Math.max(1, Math.floor((now.value - props.context.createdAt * 1000) / 60_000)), 'minute'))
</script>

<template>
	<dl :class="$style.details">
		<dt>{{ t('core', 'App user agent') }}</dt>
		<dd>
			<div :class="$style.identity">
				<span :class="$style.selfReported">{{ t('core', 'Self-reported') }}</span>
				<LoginFlowHelp :label="t('core', 'About the app user agent')">
					{{ t('core', 'The requesting app chooses this text. Anyone can claim to be a trusted app; Nextcloud has not verified this identity.') }}
				</LoginFlowHelp>
			</div>
			<bdi :class="$style.userAgent">{{ context.userAgent || t('core', 'Unavailable') }}</bdi>
		</dd>
		<dt>{{ t('core', 'App IP address') }}</dt>
		<dd><bdi>{{ context.clientIp || t('core', 'Unavailable') }}</bdi></dd>
		<dt>{{ t('core', 'Your browser’s IP address') }}</dt>
		<dd><bdi>{{ context.browserIp || t('core', 'Unavailable') }}</bdi></dd>
		<dt>{{ t('core', 'Request started') }}</dt>
		<dd :class="$style.label">
			<time :datetime="new Date(context.createdAt * 1000).toISOString()">{{ requestAge }}</time>
			<LoginFlowHelp :label="t('core', 'Exact request time')">
				<NcDateTime
					:timestamp="context.createdAt * 1000"
					:relativeTime="false"
					:format="{ dateStyle: 'long', timeStyle: 'long' }" />
			</LoginFlowHelp>
		</dd>
	</dl>
</template>

<style module>
.details {
	display: grid;
	grid-template-columns: auto minmax(0, 1fr);
	gap: calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 4);
	align-items: baseline;
	padding-block: calc(var(--default-grid-baseline) * 5);
	border-block: 1px solid var(--color-border);

	dt,
	dd {
		width: auto;
		padding: 0;
		white-space: normal;
		text-align: start;
	}

	dt {
		font-weight: bold;
	}

	dd {
		min-width: 0;
		overflow-wrap: anywhere;
	}
}

.identity,
.label {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);
}

.selfReported {
	font-size: 0.875rem;
	color: var(--color-warning-text);
}

.userAgent {
	display: block;
	font-family: ui-monospace, monospace;
	font-size: 0.875rem;
}

@media (width <= 480px) {
	.details {
		grid-template-columns: minmax(0, 1fr);
		gap: var(--default-grid-baseline);

		dd {
			margin-bottom: calc(var(--default-grid-baseline) * 2);
		}
	}
}
</style>
