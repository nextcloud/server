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
			// currently expiration is only effective for link share.
			// this is about to change in future. Therefore this is not included
			// in the LinkShareView to ease reusing it in future. Then,
			// modifications (getting rid of IDs) are still necessary.
			'{{#if isLinkShare}}' +
			'<input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" ' +
				'{{#if isExpirationSet}}checked="checked"{{/if}} {{#if disableCheckbox}}disabled="disabled"{{/if}} />' +
			'<label for="expirationCheckbox">{{setExpirationLabel}}</label>' +
			'    {{#if isExpirationSet}}' +
			'<label for="expirationDate" class="hidden-visually" value="{{expirationDate}}">{{expirationLabel}}</label>' +
			'<input id="expirationDate" class="datepicker" type="text" placeholder="{{expirationDatePlaceholder}}" />' +
			'    {{/if}}' +
			'    {{#if isExpirationEnforced}}' +
				// originally the expire message was shown when a default date was set, however it never had text
			'<em id="defaultExpireMessage">{{defaultExpireMessage}}</em>' +
			'    {{/if}}' +
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
			var defaultExpireDays = this.configModel.get('defaultExpireDate');
			if(    (this.model.isFolder() || this.model.isFile())
				&& this.configModel.get('isDefaultExpireDateEnforced')) {
				defaultExpireMessage = t(
					'core',
					'The public link will expire no later than {days} days after it is created',
					{'days': defaultExpireDays }
				);
			}

			var isExpirationSet = !!this.model.get('linkShare').expiration;
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');

			var expirationTemplate = this.template();
			this.$el.html(expirationTemplate({
				setExpirationLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				defaultExpireMessage: defaultExpireMessage,
				isLinkShare: this.model.get('linkShare').isLinkShare,
				isExpirationSet: isExpirationSet,
				isExpirationEnforced: isExpirationEnforced,
				disableCheckbox: isExpirationEnforced && isExpirationSet,
				expirationValue: this.model.get('linkShare').expiration
			}));

			if(isExpirationSet) {
				// what if there is another date picker on that page?
				var minDate = new Date();
				// min date should always be the next day
				minDate.setDate(minDate.getDate()+1);

				var maxDate = null;
				if(isExpirationEnforced) {
					// TODO: hack: backend returns string instead of integer
					var shareTime = this.model.get('linkShare').stime;
					if (_.isNumber(shareTime)) {
						shareTime = new Date(shareTime * 1000);
					}
					if (!shareTime) {
						shareTime = new Date(); // now
					}
					shareTime = OC.Util.stripTime(shareTime).getTime();
					maxDate = new Date(shareTime + defaultExpireDays * 24 * 3600 * 1000);
				}

				$.datepicker.setDefaults({
					minDate: minDate,
					maxDate: maxDate
				});
			}

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
