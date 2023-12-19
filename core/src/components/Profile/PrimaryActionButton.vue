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

	.profile__primary-action-button {
		font-size: var(--default-font-size);
		font-weight: bold;
		width: 188px;
		height: 44px;
		padding: 0 16px;
		line-height: 44px;
		text-align: center;
		border-radius: var(--border-radius-pill);
		color: var(--color-primary-element-text);
		background-color: var(--color-primary-element);
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;

		.icon {
			display: inline-block;
			vertical-align: middle;
			margin-bottom: 2px;
			margin-inline-end: 4px;

			&.icon-invert {
				filter: invert(1);
			}
		}

		&:hover,
		&:focus,
		&:active {
			background-color: var(--color-primary-element-light);
		}
	}
</style>
