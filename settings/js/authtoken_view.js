/* global Backbone, Handlebars */

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

(function(OC, _, Backbone, $, Handlebars) {
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
			list.removeClass('icon-loading');
			list.html('');

			tokens.forEach(function(token) {
				var html = _this.template(token.toJSON());
				list.append(html);
			});
		},
	});

	var AuthTokenView = Backbone.View.extend({
		collection: null,
		views
		: [],
		initialize: function(options) {
			this.collection = options.collection;

			var tokenTypes = [0, 1];
			var _this = this;
			_.each(tokenTypes, function(type) {
				_this.views.push(new SubView({
					el: type === 0 ? '#sessions' : '#devices',
					type: type,
					collection: _this.collection
				}));
			});
		},
		render: function() {
			_.each(this.views, function(view) {
				view.render();
			});
		},
		reload: function() {
			var loadingTokens = this.collection.fetch();

			var _this = this;
			$.when(loadingTokens).done(function() {
				_this.render();
			});
		}
	});

	OC.Settings.AuthTokenView = AuthTokenView;

})(OC, _, Backbone, $, Handlebars);
