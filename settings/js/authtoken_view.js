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
		'<tr>'
		+ '<td>{{name}}</td>'
		+ '<td>{{lastActivity}}</td>'
		+ '<tr>';

	var SubView = Backbone.View.extend({
		collection: null,
		type: 0,
		template: Handlebars.compile(TEMPLATE_TOKEN),
		initialize: function(options) {
			this.type = options.type;
			this.collection = options.collection;
		},
		render: function() {
			var _this = this;

			var list = this.$el.find('.token-list');
			var tokens = this.collection.filter(function(token) {
				return parseInt(token.get('type')) === _this.type;
			});
			list.html('');

			tokens.forEach(function(token) {
				var viewData = token.toJSON();
				viewData.lastActivity = moment(viewData.lastActivity, 'X').
					format('LLL');
				var html = _this.template(viewData);
				list.append(html);
			});
		},
		toggleLoading: function(state) {
			this.$el.find('.token-list').toggleClass('icon-loading', state);
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
				_this._views.push(new SubView({
					el: type === 0 ? '#sessions' : '#devices',
					type: type,
					collection: _this.collection
				}));
			});

			this._form = $('#device-token-form');
			this._tokenName = $('#device-token-name');
			this._addTokenBtn = $('#device-add-token');
			this._addTokenBtn.click(_.bind(this._addDeviceToken, this));

			this._result = $('#device-token-result');
			this._newToken = $('#device-new-token');
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
				_this._newToken.text(resp.token);
				_this._toggleFormResult(false);
				_this._tokenName.val('');
			});
			$.when(creatingToken).fail(function() {
				OC.Notification.showTemporary(t('core', 'Error while creating device token'));
			});
			$.when(creatingToken).always(function() {
				_this._toggleAddingToken(false);
			});
		},
		_hideToken: function() {
			this._toggleFormResult(true);
		},
		_toggleAddingToken: function(state) {
			this._addingToken = state;
			this._addTokenBtn.toggleClass('icon-loading-small', state);
		},
		_toggleFormResult: function(showForm) {
			this._form.toggleClass('hidden', !showForm);
			this._result.toggleClass('hidden', showForm);
		}
	});

	OC.Settings.AuthTokenView = AuthTokenView;

})(OC, _, Backbone, $, Handlebars, moment);
