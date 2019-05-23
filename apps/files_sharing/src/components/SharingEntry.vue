<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<div class="sharing-entry">
		<Avatar :user="user" :display-name="displayName" class="sharing-entry__avatar"></Avatar>
		<div class="sharing-entry__desc">
			<h4>{{ title }}</h4>
			<p v-if="subtitle">{{ subtitle }}</p>
		</div>
		<Actions v-if="$slots['default']" class="sharing-entry__actions"><slot /></Actions>
	</div>
</template>

<script>
import Avatar from 'nextcloud-vue/dist/Components/Avatar'
import Actions from 'nextcloud-vue/dist/Components/Avatar'

export default {
	name:  'SharingEntry',
	components: {
		Actions,
		Avatar
	},
	props: {
		user: {
			type: String,
			default: '',
			required: true
		},
		displayName: {
			type: String,
			default: '',
			required: true
		},
		title: {
			type: String,
			default: '',
			required: true
		},
		subtitle: {
			type: String,
			default: ''
		}
	}

}
</script>
  
<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	height: 44px;
	&__desc {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 8px;
		line-height: 1.2em;
	}
	&__actions {
		margin-left: auto;
	}
}
</style>
