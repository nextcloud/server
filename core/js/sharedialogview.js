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
	if(!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE_BASE =
		'{{{resharerInfo}}}' +
		'<label for="shareWith" class="hidden-visually">{{shareLabel}}</label>' +
		'<div class="oneline">' +
		'    <input id="shareWith" type="text" placeholder="{{sharePlaceholder}}" />' +
		'    <span class="shareWithLoading icon-loading-small hidden"></span>'+
		'</div>' +
			// FIXME: find a good position for remoteShareInfo
		'{{{remoteShareInfo}}}' +
		'<ul id="shareWithList">' +
		'</ul>' +
		'{{#if shareAllowed}}' +
		'{{{linkShare}}}' +
		'{{else}}' +
		'{{{noSharing}}}' +
		'{{/if}}' +
		'{{{expiration}}}'
		;

	var TEMPLATE_RESHARER_INFO =
		'<span class="reshare">' +
		'    {{#if avatarEnabled}}' +
		'    <div class="avatar"></div>' +
		'    {{/if}}' +
		'    {{sharedByText}}' +
		'</span><br />';

	var TEMPLATE_REMOTE_SHARE_INFO =
		'<a target="_blank" class="icon-info svg shareWithRemoteInfo" href="{{docLink}}" ' +
		'title="{{tooltip}}"></a>';

	var TEMPLATE_LINK_SHARE =
			'<div id="link" class="linkShare">' +
			'    <span class="icon-loading-small hidden"></span>' +
			'    <input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" /><label for="linkCheckbox">{{linkShareLabel}}</label>' +
			'    <br />' +
			'    <label for="linkText" class="hidden-visually">{{urlLabel}}</label>' +
			'    <input id="linkText" type="text" readonly="readonly" />' +
			'    <input type="checkbox" name="showPassword" id="showPassword" value="1" class="hidden" /><label for="showPassword" class="hidden-visually">{{enablePasswordLabel}}</label>' +
			'    <div id="linkPass">' +
			'        <label for="linkPassText" class="hidden-visually">{{passwordLabel}}</label>' +
			'        <input id="linkPassText" type="password" placeholder="passwordPlaceholder" />' +
			'        <span class="icon-loading-small hidden"></span>' +
			'    </div>' +
			'    {{#if publicUpload}}' +
			'    <div id="allowPublicUploadWrapper" class="hidden">' +
			'        <span class="icon-loading-small hidden"></span>' +
			'        <input type="checkbox" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload" {{{publicUploadChecked}}} />' +
			'        <label for="sharingDialogAllowPublicUpload">{{publicUploadLabel}}</label>' +
			'    </div>' +
			'    {{/if}}' +
			'    {{#if mailPublicNotificationEnabled}}' +
			'    <form id="emailPrivateLink">' +
			'        <input id="email" class="hidden" value="" placeholder="{{mailPrivatePlaceholder}}" type="text" />' +
			'        <input id="emailButton" class="hidden" type="submit" value="{{mailButtonText}}" />' +
			'    </form>' +
			'    {{/if}}' +
			'</div>'
		;

	var TEMPLATE_NO_SHARING =
		'<input id="shareWith" type="text" placeholder="{{placeholder}}" disabled="disabled"/>'
	;

	var TEMPLATE_EXPIRATION =
		'<div id="expiration">' +
		'    <input type="checkbox" name="expirationCheckbox" id="expirationCheckbox" value="1" />' +
		'    <label for="expirationCheckbox">{{setExpirationLabel}}</label>' +
		'    <label for="expirationDate" class="hidden-visually">{{expirationLabel}}</label>' +
		'    <input id="expirationDate" type="text" placeholder="{{expirationDatePlaceholder}}" class="hidden" />' +
		'    <em id="defaultExpireMessage">{{defaultExpireMessage}}</em>' +
		'</div>'
	;

	/**
	 * @class OCA.Share.ShareDialogView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogView = OC.Backbone.View.extend({
		/** @type {Object} **/
		_templates: {},

		/** @type {boolean} **/
		_showLink: true,

		/** @type {string} **/
		tagName: 'div',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		initialize: function(options) {
			var view = this;
			this.model.on('change', function() {
				view.render();
			});

			this.model.on('fetchError', function() {
				OC.Notification.showTemporary(t('core', 'Share details could not be loaded for this item.'));
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				console.warn('missing OC.Share.ShareConfigModel');
			}
		},

		render: function() {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);

			this.$el.html(baseTemplate({
				shareLabel: t('core', 'Share'),
				resharerInfo: this._renderResharerInfo(),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				remoteShareInfo: this._renderRemoteShareInfoPart(),
				linkShare: this._renderLinkSharePart(),
				shareAllowed: this.model.hasSharePermission(),
				noSharing: this._renderNoSharing(),
				expiration: this._renderExpirationPart()
			}));

			return this;
		},

		/**
		 * sets whether share by link should be displayed or not. Default is
		 * true.
		 *
		 * @param {bool} showLink
		 */
		setShowLink: function(showLink) {
			this._showLink = (typeof showLink === 'boolean') ? showLink : true;
		},

		_renderResharerInfo: function() {
			var resharerInfo = '';
			if (   !this.model.hasReshare()
				|| !this.model.getReshareOwner() !== OC.currentUser)
			{
				return '';
			}

			var reshareTemplate = this._getReshareTemplate();
			var sharedByText = '';
			if (this.model.getReshareType() === OC.Share.SHARE_TYPE_GROUP) {
				sharedByText = t(
					'core',
					'Shared with you and the group {group} by {owner}',
					{
						group: this.model.getReshareWith(),
						owner: this.model.getReshareOwnerDisplayname()
					}
				);
			}  else {
				sharedByText = t(
					'core',
					'Shared with you by {owner}',
					{ owner: this.model.getReshareOwnerDisplayname() }
				);
			}

			return reshareTemplate({
				avatarEnabled: oc_config.enable_avatars === true,
				sharedByText: sharedByText
			});
		},

		_renderRemoteShareInfoPart: function() {
			var remoteShareInfo = '';
			if(oc_appconfig.core.remoteShareAllowed) {
				var infoTemplate = this._getRemoteShareInfoTemplate();
				remoteShareInfo = infoTemplate({
					docLink: oc_appconfig.core.federatedCloudShareDoc,
					tooltip: t('core', 'Share with people on other ownClouds using the syntax username@example.com/owncloud')
				});
			}
			return remoteShareInfo;
		},

		_renderLinkSharePart: function() {
			var linkShare = '';
			if(    this.model.hasSharePermission()
				&& this._showLink
				&& $('#allowShareWithLink').val() === 'yes')
			{
				var linkShareTemplate = this._getLinkShareTemplate();

				var publicUpload =
					   this.model.isFolder()
					&& this.model.hasCreatePermission()
					&& this.configModel.isPublicUploadEnabled();

				var publicUploadChecked = '';
				if(this.model.isPublicUploadAllowed) {
					publicUploadChecked = 'checked="checked"';
				}

				linkShare = linkShareTemplate({
					linkShareLabel: t('core', 'Share link'),
					urlLabel: t('core', 'Link'),
					enablePasswordLabel: t('core', 'Password protect'),
					passwordLabel: t('core', 'Password'),
					passwordPlaceholder: t('core', 'Choose a password for the public link'),
					publicUpload: publicUpload,
					publicUploadChecked: publicUploadChecked,
					publicUploadLabel: t('core', 'Allow editing'),
					mailPublicNotificationEnabled: this.configModel.isMailPublicNotificationEnabled(),
					mailPrivatePlaceholder: t('core', 'Email link to person'),
					mailButtonText: t('core', 'Send')
				});
			}
			return linkShare;
		},

		_renderSharePlaceholderPart: function () {
			var sharePlaceholder = t('core', 'Share with users or groups …');
			if (oc_appconfig.core.remoteShareAllowed) {
				sharePlaceholder = t('core', 'Share with users, groups or remote users …');
			}
			return sharePlaceholder;
		},

		_renderNoSharing: function () {
			var noSharing = '';
			if(!this.model.hasSharePermission()) {
				var noSharingTemplate = this._getTemplate('noSharing', TEMPLATE_NO_SHARING);
				noSharing = noSharingTemplate({
					placeholder: t('core', 'Resharing is not allowed')
				});
			}
			return noSharing;
		},

		_renderExpirationPart: function() {
			var expirationTemplate = this._getTemplate('expiration', TEMPLATE_EXPIRATION);

			var defaultExpireMessage = '';
			if((   this.model.isFolder() || this.model.isFile())
				&& this.configModel.isDefaultExpireDateEnforced()) {
				defaultExpireMessage = t(
						'core',
						'The public link will expire no later than {days} days after it is created',
						{'days': this.configModel.getDefaultExpireDate()}
				);
			}

			var expiration = expirationTemplate({
				setExpirationLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				defaultExpireMessage: defaultExpireMessage
			});

			return expiration;
		},

		/**
		 *
		 * @param {string} key - an identifier for the template
		 * @param {string} template - the HTML to be compiled by Handlebars
		 * @returns {Function} from Handlebars
		 * @private
		 */
		_getTemplate: function (key, template) {
			if (!this._templates[key]) {
				this._templates[key] = Handlebars.compile(template);
			}
			return this._templates[key];
		},

		/**
		 * returns the info template for remote sharing
		 *
		 * @returns {Function}
		 * @private
		 */
		_getRemoteShareInfoTemplate: function() {
			return this._getTemplate('remoteShareInfo', TEMPLATE_REMOTE_SHARE_INFO);
		},

		/**
		 * returns the info template for link sharing
		 *
		 * @returns {Function}
		 * @private
		 */
		_getLinkShareTemplate: function() {
			return this._getTemplate('linkShare', TEMPLATE_LINK_SHARE);
		},

		_getReshareTemplate: function() {
			return this._getTemplate('reshare', TEMPLATE_RESHARER_INFO);
		}
	});

	OC.Share.ShareDialogView = ShareDialogView;

})();
