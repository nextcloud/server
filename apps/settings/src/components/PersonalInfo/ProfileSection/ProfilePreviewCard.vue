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
	<a
		class="preview-card"
		:class="{ disabled }"
		:href="profilePageLink">
		<Avatar
			class="preview-card__avatar"
			:user="userId"
			:size="48"
			:show-user-status="true"
			:show-user-status-compact="false"
			:disable-menu="true"
			:disable-tooltip="true"
			@click.native.prevent.stop="openStatusModal" />
		<div class="preview-card__header">
			<span>{{ displayName }}</span>
		</div>
		<div class="preview-card__footer">
			<span>{{ organisation }}</span>
		</div>
	</a>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

import Avatar from '@nextcloud/vue/dist/Components/Avatar'

export default {
	name: 'ProfilePreviewCard',

	components: {
		Avatar,
	},

	props: {
		organisation: {
			type: String,
			required: true,
		},
		displayName: {
			type: String,
			required: true,
		},
		profileEnabled: {
			type: Boolean,
			required: true,
		},
		userId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
		}
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},

		profilePageLink() {
			if (this.profileEnabled) {
				return generateUrl('/u/{userId}', { userId: getCurrentUser().uid })
			}
			// Since an anchor element is used rather than a button for better UX,
			// this hack removes href if the profile is disabled so that disabling pointer-events is not needed to prevent a click from opening a page
			// and to allow the hover event (which disabling pointer-events wouldn't allow) for styling
			return null
		},
	},

	methods: {
	},
}
</script>

<style lang="scss" scoped>
.preview-card {
	display: flex;
	flex-direction: column;
	position: relative;
	width: 290px;
	height: 116px;
	margin: 14px auto;
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
	font-weight: bold;
	box-shadow: 0 2px 9px var(--color-box-shadow);

	&:hover {
		box-shadow: 0 2px 12px var(--color-box-shadow);
	}

	&.disabled {
		filter: grayscale(1);
		opacity: 0.5;
		cursor: default;
		box-shadow: 0 0 3px var(--color-box-shadow);

		& *,
		&::v-deep * {
			cursor: default;
		}
	}

	&__avatar {
		// Override Avatar component position to fix positioning on rerender
		position: absolute !important;
		top: 40px;
		left: 18px;
		z-index: 1;

		&:not(.avatardiv--unknown) {
			box-shadow: 0 0 0 3px var(--color-main-background) !important;
		}
	}

	&__header {
		position: relative !important;
		width: auto !important;
		height: 70px !important;
		border-radius: var(--border-radius-large) var(--border-radius-large) 0 0 !important;

		span {
			position: absolute;
			bottom: 0;
			left: 78px;
			color: var(--color-primary-text);
			font-size: 18px;
			font-weight: bold;
			margin-bottom: 8px;
		}
	}

	&__footer {
		position: relative;
		width: auto;
		height: 46px;

		span {
			position: absolute;
			top: 0;
			left: 78px;
			color: var(--color-text-maxcontrast);
			font-size: 14px;
			font-weight: normal;
			margin-top: 4px;
			line-height: 1.3;

			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;

			@supports (-webkit-line-clamp: 2) {
				overflow: hidden;
				white-space: initial;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
		}
	}
}
</style>
