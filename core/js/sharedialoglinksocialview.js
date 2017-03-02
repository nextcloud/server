/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
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

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			'<button class="icon {{iconClass}} pop-up hasTooltip"' +
			'	title="{{shareToolTip}}"' +
			'	data-url="{{url}}"></button>'
		;

	/**
	 * @class OCA.Share.ShareDialogLinkSocialView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogLinkSocialView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkSocialView',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		events: {
			'click .pop-up': 'onPopUpClick'
		},

		initialize: function(options) {
			var view = this;

			this.model.on('change:linkShare', function() {
				view.render();
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}
		},

		onPopUpClick: function(event) {
			var url = $(event.target).data('url');
			$(event.target).tooltip('hide');
			if (url) {
				var width = 600;
				var height = 400;
				var left = (screen.width/2)-(width/2);
				var top = (screen.height/2)-(height/2);

				window.open(url, 'name', 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
			}
		},

		render: function() {
			var isLinkShare = this.model.get('linkShare').isLinkShare;
			if (isLinkShare && OC.Share.Social.Collection.size() > 0) {
				var linkShareTemplate = this.template();
				var link = this.model.get('linkShare').link;

				var html = '';

				OC.Share.Social.Collection.each(function(model) {
					var url = model.get('url');
					url = url.replace('{{reference}}', link);

					html += linkShareTemplate({
						url: url,
						shareToolTip: t('core', 'Share to {name}', {name: model.get('name')}),
						iconClass: model.get('iconClass')
					});
				});

				this.$el.html(html);
				this.$el.show();
			} else {
				this.$el.hide();
			}

			this.delegateEvents();

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template;
		}

	});

	OC.Share.ShareDialogLinkSocialView = ShareDialogLinkSocialView;
})();
