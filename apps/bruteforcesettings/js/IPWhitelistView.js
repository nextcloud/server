/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function () {

	OCA.BruteForceSettings = OCA.BruteForceSettings || {};

	var TEMPLATE_WHITELIST =
		'<tr data-id="{{id}}">'
		+ '<td><span>{{ip}}/{{mask}}</span></td>'
		+ '<td class="action-column"><span><a class="icon-delete has-tooltip" title="' + t('bruteforcesettings', 'Delete') + '"></a></span></td>'
		+ '</tr>';

	OCA.BruteForceSettings.WhitelistView = OC.Backbone.View.extend({
		collection: null,

		ipInput: undefined,
		maskInput: undefined,
		submit: undefined,

		list: undefined,
		listHeader: undefined,

		initialize: function(options) {
			this.collection = options.collection;

			this.ipInput = $('#whitelist_ip');
			this.maskInput = $('#whitelist_mask');
			this.submit = $('#whitelist_submit');
			this.submit.click(_.bind(this._addWhitelist, this));

			this.list = $('#whitelist-list');
			this.listHeader = $('#whitelist-list-header');

			this.list.on('click', 'a.icon-delete', _.bind(this._onDeleteRetention, this));
			this.listenTo(this.collection, 'sync', this.render);
		},



		reload: function() {
			var _this = this;
			var loadingWhitelists = this.collection.fetch();

			$.when(loadingWhitelists).done(function () {
				_this.render();
			});
			$.when(loadingWhitelists).fail(function () {
				OC.Notification.showTemporary(t('bruteforcesettings', 'Error while whitelists.'));
			});
		},

		template: function (data) {
			if (_.isUndefined(this._template)) {
				this._template = Handlebars.compile(TEMPLATE_WHITELIST);
			}

			return this._template(data);
		},

		render: function () {
			var _this = this;
			this.list.html('');

			this.collection.forEach(function (model) {
				var data = {
					id: model.attributes.id,
					ip: model.attributes.ip,
					mask: model.attributes.mask
				};
				var html = _this.template(data);
				var $html = $(html);
				_this.list.append($html);
			});
		},

		_onDeleteRetention: function(event) {
			var $target = $(event.target);
			var $row = $target.closest('tr');
			var id = $row.data('id');

			var whitelist = this.collection.get(id);

			if (_.isUndefined(whitelist)) {
				// Ignore event
				return;
			}

			var destroyingRetention = whitelist.destroy();

			$row.find('.icon-delete').tooltip('hide');

			var _this = this;
			$.when(destroyingRetention).fail(function () {
				OC.Notification.showTemporary(t('bruteforcesettings', 'Error while deleting a whitelist'));
			});
			$.when(destroyingRetention).always(function () {
				_this.render();
			});
		},

		_addWhitelist: function() {
			this.collection.create({
				ip: this.ipInput.val(),
				mask: this.maskInput.val()
			});
		}
	});
})();
