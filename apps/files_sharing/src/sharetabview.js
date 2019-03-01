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
		'<div id="collaborationResources"></div>' +
		'</div>';

	/**
	 * @memberof OCA.Sharing
	 */
	var ShareTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Sharing.ShareTabView.prototype */ {
		id: 'shareTabView',
		className: 'tab shareTabView',

		initialize: function(name, options) {
			OCA.Files.DetailTabView.prototype.initialize.call(this, name, options);
			OC.Plugins.attach('OCA.Sharing.ShareTabView', this);
		},

		template: function(params) {
			return 	TEMPLATE;
		},

		getLabel: function() {
			return t('files_sharing', 'Sharing');
		},

		getIcon: function() {
			return 'icon-shared';
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

				if (_.isUndefined(this.model.get('sharePermissions'))) {
					this.model.set('sharePermissions', OCA.Sharing.Util.getSharePermissions(this.model.attributes));
				}

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

				import('./collaborationresources').then((Resources) => {
					var vm = new Resources.Vue({
						el: '#collaborationResources',
						render: h => h(Resources.View),
						data: {
							model: this.model.toJSON()
						},
					});
					this.model.on('change', () => { vm.data = this.model.toJSON() })

				})

			} else {
				this.$el.empty();
				// TODO: render placeholder text?
			}
			this.trigger('rendered');
		}
	});

	OCA.Sharing.ShareTabView = ShareTabView;
})();

