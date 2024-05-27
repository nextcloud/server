<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcButton type="primary"
		:href="href"
		alignment="center"
		:target="target"
		:disabled="disabled">
		<template #icon>
			<img class="icon"
				aria-hidden="true"
				:src="icon"
				alt="">
		</template>
		<slot />
	</NcButton>
</template>

<script>
import { defineComponent } from 'vue'
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default defineComponent({
	name: 'PrimaryActionButton',

	components: {
		NcButton,
	},

	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
		href: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		target: {
			type: String,
			required: true,
			validator: (value) => ['_self', '_blank', '_parent', '_top'].includes(value),
		},
	},

	methods: {
		t,
	},
})
</script>

<style lang="scss" scoped>
	.icon {
		filter: var(--primary-invert-if-dark);
	}
</style>
