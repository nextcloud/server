<!--
  - @copyright 2020 Kirill Dmitriev <dk1a@protonmail.com>
  -
  - @author 2020 Kirill Dmitriev <dk1a@protonmail.com>
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
	<div class="contacts-menu__contact">
		<Avatar
			:url="contact.avatar"
			:display-name="contact.fullName"
			:disable-tooltip="true" />

		<div class="contacts-menu__contact-body">
			<div class="contacts-menu__contact-full-name">
				{{ contact.fullName }}
			</div>
			<div class="contacts-menu__contact-last-message">
				{{ contact.lastMessage }}
			</div>
		</div>

		<a v-if="contact.topAction"
			v-tooltip.left="contact.topAction.title"
			class="contacts-menu__contact-action-link"
			:href="contact.topAction.hyperlink"
			:title="contact.topAction.title">
			<img :src="contact.topAction.icon"
				:alt="contact.topAction.title">
		</a>
		<a v-if="hasTwoActions"
			v-tooltip.left="secondAction.title"
			class="contacts-menu__contact-action-link"
			:href="secondAction.hyperlink"
			:title="secondAction.title">
			<img :src="secondAction.icon"
				:alt="secondAction.title">
		</a>

		<Actions v-if="hasManyActions" menu-align="right">
			<ActionLink v-for="({ hyperlink, icon, title }, index) in contact.actions"
				:key="index"
				:icon="icon"
				:href="hyperlink">
				{{ title }}
			</ActionLink>
		</Actions>
	</div>
</template>

<script>
import Vue from 'vue'
import VTooltip from 'v-tooltip'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'

// Show tooltips for top and second actions
Vue.use(VTooltip)

export default {
	components: {
		Avatar,
		Actions,
		ActionLink,
	},
	props: {
		contact: {
			type: Object,
			required: true,
		},
	},
	computed: {
		hasTwoActions() {
			return this.contact.actions.length === 1
		},
		secondAction() {
			return this.contact.actions[0]
		},
		hasManyActions() {
			return this.contact.actions.length > 1
		},
	},
}
</script>

<style lang="scss" scoped>
.contacts-menu__contact {
	display: flex;
	position: relative;
	align-items: center;
	padding: 3px 3px 3px 10px;
	border-bottom: 1px solid var(--color-border);

	&:last-of-type {
		border-bottom: none;
	}

	// name and message
	&-body {
		flex-grow: 1;
		padding-left: 8px;
	}
	&-full-name, &-last-message {
		position: relative;
		width: 100%;
		/* TODO: don't use fixed width */
		max-width: 204px;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	&-last-message {
		opacity: .5;
	}

	// standalone actions
	&-action-link {
		width: 16px;
		height: 16px;
		padding: 14px;
		opacity: .7;

		:hover {
			opacity: 1;
		}
	}

	// actions
	.action-item {
		margin-right: 13px;
	}
}

// dark theme icons
body.theme--dark {
	.contacts-menu__contact /deep/ a {
		& > img,
		& > .action-link__icon {
			filter: invert(100%);
		}
	}
}
</style>
