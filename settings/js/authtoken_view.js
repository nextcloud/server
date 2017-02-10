/* global Handlebars, moment */

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

(function (OC, _, $, Handlebars, moment) {
	'use strict';

	OC.Settings = OC.Settings || {};

	var TEMPLATE_TOKEN =
		'<tr data-id="{{id}}">'
		+ '<td class="has-tooltip" title="{{title}}"><span class="token-name">{{name}}</span></td>'
		+ '<td><span class="last-activity has-tooltip" title="{{lastActivityTime}}">{{lastActivity}}</span></td>'
		+ '{{#if canDelete}}'
		+ '<td><a class="icon-delete has-tooltip" title="' + t('core', 'Disconnect') + '"></a></td>'
		+ '{{else}}'
		+ '<td></td>'
		+ '{{/if}}'
		+ '<tr>';

	var SubView = OC.Backbone.View.extend({
		collection: null,

		/**
		 * token type
		 * - 0: browser
		 * - 1: device
		 *
		 * @see OC\Authentication\Token\IToken
		 */
		type: 0,

		_template: undefined,

		template: function (data) {
			if (_.isUndefined(this._template)) {
				this._template = Handlebars.compile(TEMPLATE_TOKEN);
			}

			return this._template(data);
		},

		initialize: function (options) {
			this.type = options.type;
			this.collection = options.collection;

			this.on(this.collection, 'change', this.render);
		},

		render: function () {
			var _this = this;

			var list = this.$('.token-list');
			var tokens = this.collection.filter(function (token) {
				return parseInt(token.get('type'), 10) === _this.type;
			});
			list.html('');

			// Show header only if there are tokens to show
			this._toggleHeader(tokens.length > 0);

			tokens.forEach(function (token) {
				var viewData = this._formatViewData(token.toJSON());
				var html = _this.template(viewData);
				var $html = $(html);
				$html.find('.has-tooltip').tooltip({container: 'body'});
				list.append($html);
			}.bind(this));
		},

		toggleLoading: function (state) {
			this.$('.token-list').toggleClass('icon-loading', state);
		},

		_toggleHeader: function (show) {
			this.$('.hidden-when-empty').toggleClass('hidden', !show);
		},

		_formatViewData: function (viewData) {
			var ts = viewData.lastActivity * 1000;
			viewData.lastActivity = OC.Util.relativeModifiedDate(ts);
			viewData.lastActivityTime = OC.Util.formatDate(ts, 'LLL');

			// preserve title for cases where we format it further
			viewData.title = viewData.name;

			// pretty format sync client user agent
			var matches = viewData.name.match(/Mozilla\/5\.0 \((\w+)\) (?:mirall|csyncoC)\/(\d+\.\d+\.\d+)/);

			var userAgentMap = {
				ie: /(?:MSIE|Trident) (\d+)/,
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
				androidClient:/^Mozilla\/5\.0 \(Android\) ownCloud\-android.*$/,
				// DAVdroid/1.2 (2016/07/03; dav4android; okhttp3) Android/6.0.1
				davDroid: /DAVdroid\/([0-9.]+)/,
				// Mozilla/5.0 (U; Linux; Maemo; Jolla; Sailfish; like Android 4.3) AppleWebKit/538.1 (KHTML, like Gecko) WebPirate/2.0 like Mobile Safari/538.1 (compatible)
				webPirate: /(Sailfish).*WebPirate\/(\d+)/,
				// Mozilla/5.0 (Maemo; Linux; U; Jolla; Sailfish; Mobile; rv:31.0) Gecko/31.0 Firefox/31.0 SailfishBrowser/1.0
				sailfishBrowser: /(Sailfish).*SailfishBrowser\/(\d+)/
			};
			var nameMap = {
				ie: t('setting', 'Internet Explorer'),
				edge: t('setting', 'Edge'),
				firefox: t('setting', 'Firefox'),
				chrome: t('setting', 'Google Chrome'),
				safari: t('setting', 'Safari'),
				androidChrome: t('setting', 'Google Chrome for Android'),
				iphone: t('setting', 'iPhone iOS'),
				ipad: t('setting', 'iPad iOS'),
				iosClient: t('setting', 'iOS Client'),
				androidClient: t('setting', 'Android Client'),
				davDroid: 'DAVdroid',
				webPirate: 'WebPirate',
				sailfishBrowser: 'SailfishBrowser'
			};

			if (matches) {
				viewData.name = t('settings', 'Sync client - {os}', {
					os: matches[1],
					version: matches[2]
				});
			}
			for (var client in userAgentMap) {
				if (matches = viewData.title.match(userAgentMap[client])) {
					if (matches[2] && matches[1]) { // version number and os
						viewData.name = nameMap[client] + ' ' + matches[2] + ' - ' + matches[1];
					}else if (matches[1]) { // only version number
						viewData.name = nameMap[client] + ' ' + matches[1];
					} else {
						viewData.name = nameMap[client];
					}
				}
			}
			if (viewData.current) {
				viewData.name = t('settings', 'This session');
			}
			return viewData;
		}
	});

	var AuthTokenView = OC.Backbone.View.extend({
		collection: null,

		_views: [],

		_form: undefined,

		_tokenName: undefined,

		_addAppPasswordBtn: undefined,

		_result: undefined,

		_newAppLoginName: undefined,

		_newAppPassword: undefined,

		_newAppId: undefined,

		_hideAppPasswordBtn: undefined,

		_addingToken: false,

		initialize: function (options) {
			this.collection = options.collection;

			var tokenTypes = [0, 1];
			var _this = this;
			_.each(tokenTypes, function (type) {
				var el = type === 0 ? '#sessions' : '#apppasswords';
				_this._views.push(new SubView({
					el: el,
					type: type,
					collection: _this.collection
				}));

				var $el = $(el);
				$el.on('click', 'a.icon-delete', _.bind(_this._onDeleteToken, _this));
			});

			this._form = $('#app-password-form');
			this._tokenName = $('#app-password-name');
			this._addAppPasswordBtn = $('#add-app-password');
			this._addAppPasswordBtn.click(_.bind(this._addAppPassword, this));

			this._result = $('#app-password-result');
			this._newAppLoginName = $('#new-app-login-name');
			this._newAppLoginName.on('focus', _.bind(this._onNewTokenLoginNameFocus, this));
			this._newAppPassword = $('#new-app-password');
			this._newAppPassword.on('focus', _.bind(this._onNewTokenFocus, this));
			this._hideAppPasswordBtn = $('#app-password-hide');
			this._hideAppPasswordBtn.click(_.bind(this._hideToken, this));

			// Clipboard!
			var clipboard = new Clipboard('.clipboardButton');
			clipboard.on('success', function(e) {
				var $input = $(e.trigger);
				$input.tooltip({placement: 'bottom', trigger: 'manual', title: t('core', 'Copied!')});
				$input.tooltip('show');
				_.delay(function() {
					$input.tooltip('hide');
				}, 3000);
			});
			clipboard.on('error', function (e) {
				var $input = $(e.trigger);
				var actionMsg = '';
				if (/iPhone|iPad/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Not supported!');
				} else if (/Mac/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Press ⌘-C to copy.');
				} else {
					actionMsg = t('core', 'Press Ctrl-C to copy.');
				}

				$input.tooltip({
					placement: 'bottom',
					trigger: 'manual',
					title: actionMsg
				});
				$input.tooltip('show');
				_.delay(function () {
					$input.tooltip('hide');
				}, 3000);
			});
		},

		render: function () {
			_.each(this._views, function (view) {
				view.render();
				view.toggleLoading(false);
			});
		},

		reload: function () {
			var _this = this;

			_.each(this._views, function (view) {
				view.toggleLoading(true);
			});

			var loadingTokens = this.collection.fetch();

			$.when(loadingTokens).done(function () {
				_this.render();
			});
			$.when(loadingTokens).fail(function () {
				OC.Notification.showTemporary(t('core', 'Error while loading browser sessions and device tokens'));
			});
		},

		_addAppPassword: function () {
			var _this = this;
			this._toggleAddingToken(true);

			var deviceName = this._tokenName.val();
			var creatingToken = $.ajax(OC.generateUrl('/settings/personal/authtokens'), {
				method: 'POST',
				data: {
					name: deviceName
				}
			});

			$.when(creatingToken).done(function (resp) {
				// We can delete token we add
				resp.deviceToken.canDelete = true;
				_this.collection.add(resp.deviceToken);
				_this.render();
				_this._newAppLoginName.val(resp.loginName);
				_this._newAppPassword.val(resp.token);
				_this._newAppId = resp.deviceToken.id;
				_this._toggleFormResult(false);
				_this._newAppPassword.select();
				_this._tokenName.val('');
			});
			$.when(creatingToken).fail(function () {
				OC.Notification.showTemporary(t('core', 'Error while creating device token'));
			});
			$.when(creatingToken).always(function () {
				_this._toggleAddingToken(false);
			});
		},

		_onNewTokenLoginNameFocus: function () {
			this._newAppLoginName.select();
		},

		_onNewTokenFocus: function () {
			this._newAppPassword.select();
		},

		_hideToken: function () {
			this._toggleFormResult(true);
		},

		_toggleAddingToken: function (state) {
			this._addingToken = state;
			this._addAppPasswordBtn.toggleClass('icon-loading-small', state);
		},

		_onDeleteToken: function (event) {
			var $target = $(event.target);
			var $row = $target.closest('tr');
			var id = $row.data('id');

			if (id === this._newAppId) {
				this._toggleFormResult(true);
			}

			var token = this.collection.get(id);
			if (_.isUndefined(token)) {
				// Ignore event
				return;
			}

			var destroyingToken = token.destroy();

			$row.find('.icon-delete').tooltip('hide');

			var _this = this;
			$.when(destroyingToken).fail(function () {
				OC.Notification.showTemporary(t('core', 'Error while deleting the token'));
			});
			$.when(destroyingToken).always(function () {
				_this.render();
			});
		},

		_toggleFormResult: function (showForm) {
			if (showForm) {
				this._result.slideUp();
				this._form.slideDown();
			} else {
				this._form.slideUp();
				this._result.slideDown();
			}
		}
	});

	OC.Settings.AuthTokenView = AuthTokenView;

})(OC, _, $, Handlebars, moment);
