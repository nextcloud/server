<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
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
	<li>
		<!-- If item.href is set, a link will be directly used -->
		<a @click="item.action" v-if="item.href" :href="(item.href) ? item.href : '#' ">
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</a>
		<!-- If item.action is set instead, a button will be used -->
		<button @click="item.action" v-else-if="item.action">
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</button>
		<!-- If item.longtext is set AND the item does not have an action -->
		<span v-else>
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</span>
	</li>
</template>

<script>
export default {
	props: ['item']
};
</script>
