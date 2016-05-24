/* global Backbone, Handlebars, moment */

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

(function(OC, _, Backbone, $, Handlebars, moment) {
	'use strict';

	OC.Settings = OC.Settings || {};

	var TEMPLATE_TOKEN =
		'<tr data-id="{{id}}">'
		+ '<td>{{name}}</td>'
		+ '<td><span class="last-activity" title="{{lastActivityTime}}">{{lastActivity}}</span></td>'
		+ '<td><a class="icon-delete" title="' + t('core', 'Disconnect') + '"></a></td>'
		+ '<tr>';

	var SubView = Backbone.View.extend({
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

		template: function(data) {
			if (_.isUndefined(this._template)) {
				this._template = Handlebars.compile(TEMPLATE_TOKEN);
			}

			return this._template(data);
		},

		initialize: function(options) {
			this.type = options.type;
			this.collection = options.collection;

			this.on(this.collection, 'change', this.render);
		},

		render: function() {
			var _this = this;

			var list = this.$('.token-list');
			var tokens = this.collection.filter(function(token) {
				return parseInt(token.get('type'), 10) === _this.type;
			});
			list.html('');

			// Show header only if there are tokens to show
			this._toggleHeader(tokens.length > 0);

			tokens.forEach(function(token) {
				var viewData = token.toJSON();
				var ts = viewData.lastActivity * 1000;
				viewData.lastActivity = OC.Util.relativeModifiedDate(ts);
				viewData.lastActivityTime = OC.Util.formatDate(ts, 'LLL');
				var html = _this.template(viewData);
				var $html = $(html);
				$html.find('.last-activity').tooltip();
				$html.find('.icon-delete').tooltip();
				list.append($html);
			});
		},

		toggleLoading: function(state) {
			this.$('.token-list').toggleClass('icon-loading', state);
		},

		_toggleHeader: function(show) {
			this.$('.hidden-when-empty').toggleClass('hidden', !show);
		}
	});

	var AuthTokenView = Backbone.View.extend({
		collection: null,

		_views: [],

		_form: undefined,

		_tokenName: undefined,

		_addTokenBtn: undefined,

		_result: undefined,

		_newToken: undefined,

		_hideTokenBtn: undefined,

		_addingToken: false,

		initialize: function(options) {
			this.collection = options.collection;

			var tokenTypes = [0, 1];
			var _this = this;
			_.each(tokenTypes, function(type) {
				var el = type === 0 ? '#sessions' : '#devices';
				_this._views.push(new SubView({
					el: el,
					type: type,
					collection: _this.collection
				}));

				var $el = $(el);
				$el.on('click', 'a.icon-delete', _.bind(_this._onDeleteToken, _this));
			});

			this._form = $('#device-token-form');
			this._tokenName = $('#device-token-name');
			this._addTokenBtn = $('#device-add-token');
			this._addTokenBtn.click(_.bind(this._addDeviceToken, this));

			this._result = $('#device-token-result');
			this._newToken = $('#device-new-token');
			this._newToken.on('focus', _.bind(this._onNewTokenFocus, this));
			this._hideTokenBtn = $('#device-token-hide');
			this._hideTokenBtn.click(_.bind(this._hideToken, this));
		},

		render: function() {
			_.each(this._views, function(view) {
				view.render();
				view.toggleLoading(false);
			});
		},

		reload: function() {
			var _this = this;

			_.each(this._views, function(view) {
				view.toggleLoading(true);
			});

			var loadingTokens = this.collection.fetch();

			$.when(loadingTokens).done(function() {
				_this.render();
			});
			$.when(loadingTokens).fail(function() {
				OC.Notification.showTemporary(t('core', 'Error while loading browser sessions and device tokens'));
			});
		},

		_addDeviceToken: function() {
			var _this = this;
			this._toggleAddingToken(true);

			var deviceName = this._tokenName.val();
			var creatingToken = $.ajax(OC.generateUrl('/settings/personal/authtokens'), {
				method: 'POST',
				data: {
					name: deviceName
				}
			});

			$.when(creatingToken).done(function(resp) {
				_this.collection.add(resp.deviceToken);
				_this.render();
				_this._newToken.val(resp.token);
				_this._toggleFormResult(false);
				_this._newToken.select();
				_this._tokenName.val('');
			});
			$.when(creatingToken).fail(function() {
				OC.Notification.showTemporary(t('core', 'Error while creating device token'));
			});
			$.when(creatingToken).always(function() {
				_this._toggleAddingToken(false);
			});
		},

		_onNewTokenFocus: function() {
			this._newToken.select();
		},

		_hideToken: function() {
			this._toggleFormResult(true);
		},

		_toggleAddingToken: function(state) {
			this._addingToken = state;
			this._addTokenBtn.toggleClass('icon-loading-small', state);
		},

		_onDeleteToken: function(event) {
			var $target = $(event.target);
			var $row = $target.closest('tr');
			var id = $row.data('id');

			var token = this.collection.get(id);
			if (_.isUndefined(token)) {
				// Ignore event
				return;
			}

			var destroyingToken = token.destroy();

			var _this = this;
			$.when(destroyingToken).fail(function() {
				OC.Notification.showTemporary(t('core', 'Error while deleting the token'));
			});
			$.when(destroyingToken).always(function() {
				_this.render();
			});
		},

		_toggleFormResult: function(showForm) {
			this._form.toggleClass('hidden', !showForm);
			this._result.toggleClass('hidden', showForm);
		}
	});

	OC.Settings.AuthTokenView = AuthTokenView;

})(OC, _, Backbone, $, Handlebars, moment);
