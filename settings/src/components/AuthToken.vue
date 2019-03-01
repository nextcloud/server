<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<tr :data-id="token.id">
		<td class="client">
			<div :class="iconName.icon"></div>
		</td>
		<td class="token-name">
			<input v-if="token.canRename && renaming"
				   type="text"
				   ref="input"
				   v-model="newName"
				   @keyup.enter="rename"
				   @blur="cancelRename"
				   @keyup.esc="cancelRename">
			<span v-else>{{iconName.name}}</span>
		</td>
		<td>
			<span class="last-activity" v-tooltip="lastActivity">{{lastActivityRelative}}</span>
		</td>
		<td class="more">
			<Action v-if="!token.current"
					:actions="actions"
					v-bind:open.sync="actionOpen"
					v-tooltip="{content: t('settings', 'Device settings'), container: 'body'}"
					tabindex="0"/>
		</td>
	</tr>
</template>

<script>
	import {Action} from 'nextcloud-vue';

	const userAgentMap = {
		ie: /(?:MSIE|Trident|Trident\/7.0; rv)[ :](\d+)/,
		// Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
		edge: /^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+ Edge\/[0-9.]+$/,
		// Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
		firefox: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) Gecko\/[0-9.]+ Firefox\/(\d+)(?:\.\d)?$/,
		// Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
		chrome: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/(\d+)[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+$/,
		// Safari User Agent from http://www.useragentstring.com/pages/Safari/
		safari: /^Mozilla\/5\.0 \([^)]*(Windows|OS X)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)(?: Version\/([0-9]+)[0-9.]+)? Safari\/[0-9.A-Z]+$/,
		// Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
		androidChrome: /Android.*(?:; (.*) Build\/).*Chrome\/(\d+)[0-9.]+/,
		iphone: / *CPU +iPhone +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
		ipad: /\(iPad\; *CPU +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
		iosClient: /^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/,
		androidClient: /^Mozilla\/5\.0 \(Android\) ownCloud\-android.*$/,
		iosTalkClient: /^Mozilla\/5\.0 \(iOS\) Nextcloud\-Talk.*$/,
		androidTalkClient: /^Mozilla\/5\.0 \(Android\) Nextcloud\-Talk.*$/,
		// DAVdroid/1.2 (2016/07/03; dav4android; okhttp3) Android/6.0.1
		davDroid: /DAV(droid|x5)\/([0-9.]+)/,
		// Mozilla/5.0 (U; Linux; Maemo; Jolla; Sailfish; like Android 4.3) AppleWebKit/538.1 (KHTML, like Gecko) WebPirate/2.0 like Mobile Safari/538.1 (compatible)
		webPirate: /(Sailfish).*WebPirate\/(\d+)/,
		// Mozilla/5.0 (Maemo; Linux; U; Jolla; Sailfish; Mobile; rv:31.0) Gecko/31.0 Firefox/31.0 SailfishBrowser/1.0
		sailfishBrowser: /(Sailfish).*SailfishBrowser\/(\d+)/
	};
	const nameMap = {
		ie: t('setting', 'Internet Explorer'),
		edge: t('setting', 'Edge'),
		firefox: t('setting', 'Firefox'),
		chrome: t('setting', 'Google Chrome'),
		safari: t('setting', 'Safari'),
		androidChrome: t('setting', 'Google Chrome for Android'),
		iphone: t('setting', 'iPhone'),
		ipad: t('setting', 'iPad'),
		iosClient: t('setting', 'Nextcloud iOS app'),
		androidClient: t('setting', 'Nextcloud Android app'),
		iosTalkClient: t('setting', 'Nextcloud Talk for iOS'),
		androidTalkClient: t('setting', 'Nextcloud Talk for Android'),
		davDroid: 'DAVdroid',
		webPirate: 'WebPirate',
		sailfishBrowser: 'SailfishBrowser'
	};
	const iconMap = {
		ie: 'icon-desktop',
		edge: 'icon-desktop',
		firefox: 'icon-desktop',
		chrome: 'icon-desktop',
		safari: 'icon-desktop',
		androidChrome: 'icon-phone',
		iphone: 'icon-phone',
		ipad: 'icon-tablet',
		iosClient: 'icon-phone',
		androidClient: 'icon-phone',
		iosTalkClient: 'icon-phone',
		androidTalkClient: 'icon-phone',
		davDroid: 'icon-phone',
		webPirate: 'icon-link',
		sailfishBrowser: 'icon-link'
	};

	export default {
		name: "AuthToken",
		components: {
			Action,
		},
		props: {
			token: {
				type: Object,
				required: true,
			}
		},
		computed: {
			actions () {
				const actions = [];

				if (this.token.type === 1) {
					// TODO: add text/longtext with some description
					actions.push({
						input: 'checkbox',
						action: () => this.$emit('toggleScope', this.token, 'filesystem', !this.token.scope.filesystem),
						model: this.token.scope.filesystem,
						text: t('settings', 'Allow filesystem access'),
					});
				}
				if (this.token.canRename) {
					// TODO: add text/longtext with some description
					actions.push({
						icon: 'icon-rename',
						action: () => this.startRename(),
						text: t('settings', 'Rename'),
					});
				}
				if (this.token.canDelete) {
					// TODO: add text/longtext with some description
					actions.push({
						icon: 'icon-delete',
						action: () => this.$emit('delete', this.token),
						text: t('settings', 'Revoke'),
					});
				}

				return actions;
			},
			lastActivityRelative () {
				return OC.Util.relativeModifiedDate(this.token.lastActivity * 1000);
			},
			lastActivity () {
				return OC.Util.formatDate(this.token.lastActivity * 1000, 'LLL');
			},
			iconName () {
				// pretty format sync client user agent
				let matches = this.token.name.match(/Mozilla\/5\.0 \((\w+)\) (?:mirall|csyncoC)\/(\d+\.\d+\.\d+)/);

				let icon = '';
				if (matches) {
					this.token.name = t('settings', 'Sync client - {os}', {
						os: matches[1],
						version: matches[2]
					});
					icon = 'icon-desktop';
				}

				// preserve title for cases where we format it further
				const title = this.token.name;
				let name = this.token.name;
				for (let client in userAgentMap) {
					if (matches = title.match(userAgentMap[client])) {
						if (matches[2] && matches[1]) { // version number and os
							name = nameMap[client] + ' ' + matches[2] + ' - ' + matches[1];
						} else if (matches[1]) { // only version number
							name = nameMap[client] + ' ' + matches[1];
						} else {
							name = nameMap[client];
						}

						icon = iconMap[client];
					}
				}
				if (this.token.current) {
					name = t('settings', 'This session');
				}

				return {
					icon,
					name,
				};
			},
		},
		data () {
			return {
				showMore: this.token.canScope || this.token.canDelete,
				renaming: false,
				newName: '',
				actionOpen: false,
			};
		},
		methods: {
			startRename () {
				// Close action (popover menu)
				this.actionOpen = false;

				this.newName = this.token.name;
				this.renaming = true;
				this.$nextTick(() => {
					this.$refs.input.select();
				});
			},
			cancelRename () {
				this.renaming = false;
			},
			rename () {
				this.renaming = false;
				this.$emit('rename', this.token, this.newName);
			},
		}
	}
</script>

<style lang="scss" scoped>
	td {
		border-top: 1px solid var(--color-border);
		max-width: 200px;
		white-space: normal;
		vertical-align: middle;
		position: relative;

		&%icon {
			overflow: visible;
			position: relative;
			width: 16px;
		}

		&.token-name {
			padding: 10px 6px;

			&.token-rename {
				padding: 0;
			}

			input {
				width: 100%;
				margin: 0;
			}
		}

		&.more {
			@extend %icon;
		}

		&.client {
			@extend %icon;

			div {
				opacity: 0.57;
				width: 44px;
				height: 44px;
			}
		}
	}
</style>
