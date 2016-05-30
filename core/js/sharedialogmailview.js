/*
 * Copyright (c) 2016
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
			'{{#if shareAllowed}}' +
			'    {{#if mailPublicNotificationEnabled}}' +
			'<form id="emailPrivateLink" class="emailPrivateLinkForm">' +
			'    <input id="email" class="emailField" value="{{email}}" placeholder="{{mailPrivatePlaceholder}}" type="text" />' +
			'    <input id="emailButton" class="emailButton" type="submit" value="{{mailButtonText}}" />' +
			'</form>' +
			'    {{/if}}' +
			'{{/if}}'
		;
	
	/**
	 * @class OCA.Share.ShareDialogMailView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogMailView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogMailView',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		events: {
			'submit .emailPrivateLinkForm': '_onEmailPrivateLink'
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

			_.bindAll(
				this,
				'_onEmailPrivateLink'
			);
		},

		_onEmailPrivateLink: function(event) {
			event.preventDefault();

			var $emailField = this.$el.find('.emailField');
			var $emailButton = this.$el.find('.emailButton');
			var email = $emailField.val();
			if (email !== '') {
				$emailField.prop('disabled', true);
				$emailButton.prop('disabled', true);
				$emailField.val(t('core', 'Sending ...'));
				this.model.sendEmailPrivateLink(email).done(function() {
					$emailField.css('font-weight', 'bold').val(t('core','Email sent'));
					setTimeout(function() {
						$emailField.val('');
						$emailField.css('font-weight', 'normal');
						$emailField.prop('disabled', false);
						$emailButton.prop('disabled', false);
					}, 2000);
				}).fail(function() {
					$emailField.val(email);
					$emailField.css('font-weight', 'normal');
					$emailField.prop('disabled', false);
					$emailButton.prop('disabled', false);
				});
			}
			return false;
		},

		render: function() {
			var linkShareTemplate = this.template();
			var resharingAllowed = this.model.sharePermissionPossible();
			var email = this.$el.find('.emailField').val();

			if(!resharingAllowed
				|| !this.showLink
				|| !this.configModel.isShareWithLinkAllowed())
			{
				var templateData = {shareAllowed: false};
				if (!resharingAllowed) {
					// add message
					templateData.noSharingPlaceholder = t('core', 'Resharing is not allowed');
				}
				this.$el.html(linkShareTemplate(templateData));
				return this;
			}
			
			var isLinkShare = this.model.get('linkShare').isLinkShare;

			this.$el.html(linkShareTemplate({
				cid: this.cid,
				shareAllowed: true,
				mailPublicNotificationEnabled: isLinkShare && this.configModel.isMailPublicNotificationEnabled(),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send link via email'),
				email: email
			}));

			var $emailField = this.$el.find('.emailField');
			if (isLinkShare && $emailField.length !== 0) {
				$emailField.autocomplete({
					minLength: 1,
					source: function (search, response) {
						$.get(
							OC.generateUrl('core/ajax/share.php'), {
								fetch: 'getShareWithEmail',
								search: search.term
							}, function(result) {
								if (result.status == 'success' && result.data.length > 0) {
									response(result.data);
								}
							});
						},
					select: function( event, item ) {
						$emailField.val(item.item.email);
						return false;
					}
				})
				.data("ui-autocomplete")._renderItem = function( ul, item ) {
					return $('<li>')
						.append('<a>' + escapeHTML(item.displayname) + "<br>" + escapeHTML(item.email) + '</a>' )
						.appendTo( ul );
				};
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

	OC.Share.ShareDialogMailView = ShareDialogMailView;

})();