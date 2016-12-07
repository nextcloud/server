/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global moment, Handlebars */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

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

		/** @type {boolean} **/
		showLink: true,

		className: 'hidden',

		events: {
			'change .expirationCheckbox': '_onToggleExpiration',
			'change .datepicker': '_onChangeExpirationDate'
		},

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var view = this;
			this.configModel.on('change:isDefaultExpireDateEnforced', function() {
				view.render();
			});

			this.model.on('change:itemType', function() {
				view.render();
			});

			this.model.on('change:linkShare', function() {
				view.render();
			});
		},

		_onToggleExpiration: function(event) {
			var $checkbox = $(event.target);
			var state = $checkbox.prop('checked');
			// TODO: slide animation
			this.$el.find('.expirationDateContainer').toggleClass('hidden', !state);
			if (!state) {
				// discard expiration date
				this.model.get('linkShare').expiration = '';
				this.model.saveLinkShare({
					expireDate: ''
				});
			} else {
				this.$el.find('#expirationDate').focus();
			}
		},

		_onChangeExpirationDate: function(event) {
			var $target = $(event.target);
			$target.tooltip('hide');
			$target.removeClass('error');

			var expiration = moment($target.val(), 'DD-MM-YYYY').format('YYYY-MM-DD');
			this.model.get('linkShare').expiration = expiration;
			this.model.saveLinkShare({
				expiration: expiration
			}, {
				error: function(model, message) {
					if (!message) {
						$target.attr('title', t('core', 'Error setting expiration date'));
					} else {
						$target.attr('title', message);
					}
					$target.tooltip({gravity: 'n'});
					$target.tooltip('show');
					$target.addClass('error');
				}
			});
		},

		render: function() {
			var defaultExpireMessage = '';
			var defaultExpireDays = this.configModel.get('defaultExpireDate');
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');

			if(    (this.model.isFolder() || this.model.isFile())
				&& isExpirationEnforced) {
				defaultExpireMessage = t(
					'core',
					'The public link will expire no later than {days} days after it is created',
					{'days': defaultExpireDays }
				);
			}

			var isExpirationSet = !!this.model.get('linkShare').expiration || isExpirationEnforced;

			var expiration;
			if (isExpirationSet) {
				expiration = moment(this.model.get('linkShare').expiration, 'YYYY-MM-DD').format('DD-MM-YYYY');
			}

			this.$el.html(this.template({
				cid: this.cid,
				setExpirationLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				defaultExpireMessage: defaultExpireMessage,
				isLinkShare: this.model.get('linkShare').isLinkShare,
				isExpirationSet: isExpirationSet,
				isExpirationEnforced: isExpirationEnforced,
				disableCheckbox: isExpirationEnforced && isExpirationSet,
				expirationValue: expiration
			}));

			// what if there is another date picker on that page?
			var minDate = new Date();
			var maxDate = null;
			// min date should always be the next day
			minDate.setDate(minDate.getDate()+1);

			if(isExpirationSet) {
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
			}
			$.datepicker.setDefaults({
				minDate: minDate,
				maxDate: maxDate
			});

			this.$el.find('.datepicker').datepicker({dateFormat : 'dd-mm-yy'});

			this.delegateEvents();

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function (data) {
			return OC.Core.Templates.sharedialogexpirationview(data);
		}

	});

	OC.Share.ShareDialogExpirationView = ShareDialogExpirationView;

})();
