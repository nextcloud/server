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
		'{{{linkShare}}}';

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
			'    </div>'
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

		/** @type {unknown} **/
		_possiblePermissions: null,

		/** @type {string} **/
		tagName: 'div',

		initialize: function() {
			var view = this;
			this.model.on('change', function() {
				view.render();
			});

			this.model.on('fetchError', function() {
				OC.Notification.showTemporary(t('core', 'Share details could not be loaded for this item.'));
			});
		},

		render: function() {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);

			this.$el.html(baseTemplate({

				shareLabel: t('core', 'Share'),
				resharerInfo: this._renderResharerInfo(),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				remoteShareInfo: this._renderRemoteShareInfoPart(),
				linkShare: this._renderLinkSharePart()
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

		setPossiblePermissions: function(permissions) {
			//TODO: maybe move to model? Whatever this is.
			this._possiblePermissions = permissions;
		},

		_renderResharerInfo: function() {
			var resharerInfo = '';
			if (   this.model.hasReshare()
				&& this.model.getReshareOwner() !== OC.currentUser)
			{
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


				resharerInfo = reshareTemplate({
					avatarEnabled: oc_config.enable_avatars === true,
					sharedByText: sharedByText
				});
			}
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
			if(this._showLink && $('#allowShareWithLink').val() === 'yes') {
				var linkShareTemplate = this._getLinkShareTemplate();
				linkShare = linkShareTemplate({
					linkShareLabel: t('core', 'Share link'),
					urlLabel: t('core', 'Link'),
					enablePasswordLabel: t('core', 'Password protect'),
					passwordLabel: t('core', 'Password'),
					passwordPlaceholder: t('core', 'Choose a password for the public link')
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
