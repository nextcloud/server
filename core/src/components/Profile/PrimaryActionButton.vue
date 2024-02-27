<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
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
