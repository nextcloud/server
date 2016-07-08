/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* @global Handlebars */

(function() {
	var TEMPLATE =
		'<div>' +
		'<div class="dialogContainer"></div>' +
		'</div>';

	/**
	 * @memberof OCA.Sharing
	 */
	var ShareTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Sharing.ShareTabView.prototype */ {
		id: 'shareTabView',
		className: 'tab shareTabView',

		template: function(params) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(params);
		},

		getLabel: function() {
			return t('files_sharing', 'Sharing');
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			var self = this;
			if (this._dialog) {
				// remove/destroy older instance
				this._dialog.model.off();
				this._dialog.remove();
				this._dialog = null;
			}

			if (this.model) {
				this.$el.html(this.template());

				// TODO: the model should read these directly off the passed fileInfoModel
				var attributes = {
					itemType: this.model.isDirectory() ? 'folder' : 'file',
				   	itemSource: this.model.get('id'),
					possiblePermissions: this.model.get('sharePermissions')
				};
				var configModel = new OC.Share.ShareConfigModel();
				var shareModel = new OC.Share.ShareItemModel(attributes, {
					configModel: configModel,
					fileInfoModel: this.model
				});
				this._dialog = new OC.Share.ShareDialogView({
					configModel: configModel,
					model: shareModel
				});
				this.$el.find('.dialogContainer').append(this._dialog.$el);
				this._dialog.render();
				this._dialog.model.fetch();
				this._dialog.model.on('change', function() {
					self.trigger('sharesChanged', shareModel);
				});
			} else {
				this.$el.empty();
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Sharing.ShareTabView = ShareTabView;
})();

