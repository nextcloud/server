/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			// well that could go to linkShareView…
			'{{#if isLinkShare}}' +
			'<input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" />' +
			'<label for="expirationCheckbox">{{setExpirationLabel}}</label>' +
			'<label for="expirationDate" class="hidden-visually">{{expirationLabel}}</label>' +
			'<input id="expirationDate" type="text" placeholder="{{expirationDatePlaceholder}}" class="hidden" />' +
			'<em id="defaultExpireMessage">{{defaultExpireMessage}}</em>' +
			'{{/if}}'
		;

	/**
	 * @class OCA.Share.ShareDialogExpirationView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the expiration part in the GUI of the share dialogue
	 *
	 */
	var ShareDialogExpirationView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkShare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		className: 'hidden',

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}
		},

		render: function() {
			var defaultExpireMessage = '';
			if(    (this.model.isFolder() || this.model.isFile())
				&& this.configModel.isDefaultExpireDateEnforced()) {
				defaultExpireMessage = t(
					'core',
					'The public link will expire no later than {days} days after it is created',
					{'days': this.configModel.getDefaultExpireDate()}
				);
			}

			var expirationTemplate = this.template();
			this.$el.html(expirationTemplate({
				setExpirationLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				defaultExpireMessage: defaultExpireMessage,
				isLinkShare: this.model.get('linkShare').isLinkShare
			}));

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

	OC.Share.ShareDialogExpirationView = ShareDialogExpirationView;

})();
