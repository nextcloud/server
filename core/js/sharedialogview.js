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
		'<div id="{{containerID}}" {{{containerClasses}}}>' +
		'    {{{resharerInfo}}}' +
		'    <label for="shareWith" class="hidden-visually">{{shareLabel}}</label>' +
		'    <div class="oneline">' +
		'        <input id="shareWith" type="text" placeholder="{{sharePlaceholder}}" />' +
		'        <span class="shareWithLoading icon-loading-small hidden"></span>'+
		'    </div>' +
			// FIXME: find a good position for remoteShareInfo
		'    {{{remoteShareInfo}}}' +
		'    <ul id="shareWithList">' +
		'    </ul>' +
		'    {{{linkShare}}}' +
		'</div>';

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
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogView = function(id) {
		this.initialize(id);
	};

	/**
	 * @memberof OCA.Sharing
	 */
	ShareDialogView.prototype = {
		/** @member {OC.Share.ShareItemModel} **/
		_itemModel: null,

		/** @var {string} **/
		_id: null,

		/** @var {Object} **/
		_templates: {},

		/** @var {string} **/
		_containerClasses: '',

		/** @var {boolean} **/
		_showLink: true,

		/** @var {unknown} **/
		_possiblePermissions: null,

		initialize: function (id) {
			this._id = id;
		},

		render: function() {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);

			var $dialog = $(baseTemplate({
				containerID: this._id,
				containerClasses: this._renderContainerClasses(),
				shareLabel: t('core', 'Share'),
				resharerInfo: this._renderResharerInfo(),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				remoteShareInfo: this._renderRemoteShareInfoPart(),
				linkShare: this._renderLinkSharePart()
			}));

			return $dialog;
		},

		setItemModel: function(model) {
			if(model instanceof OC.Share.ShareItemModel) {
				this._itemModel = model;
			} else {
				console.warn('model is not an instance of OC.Share.ShareItemModel');
			}
		},

		/**
		 * sets the classes the main container should get additionally
		 * TODO:: figure out whether this is really necessary
		 *
		 * @param {string} classes whitespace seperated
		 */
		setContainerClasses: function(classes) {
			this._containerClasses = classes;
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
			if (   this._itemModel.hasReshare()
				&& this._itemModel.getReshareOwner() !== OC.currentUser)
			{
				var reshareTemplate = this._getReshareTemplate();
				var sharedByText = '';
				if (this._itemModel.getReshareType() === OC.Share.SHARE_TYPE_GROUP) {
					sharedByText = t(
						'core',
						'Shared with you and the group {group} by {owner}',
						{
							group: this._itemModel.getReshareWith(),
							owner: this._itemModel.getReshareOwnerDisplayname()
						}
					);
				}  else {
					sharedByText = t(
						'core',
						'Shared with you by {owner}',
						{ owner: this._itemModel.getReshareOwnerDisplayname() }
					);
				}


				resharerInfo = reshareTemplate({
					avatarEnabled: oc_config.enable_avatars === true,
					sharedByText: sharedByText
				});
			}
		},

		_renderContainerClasses: function() {
			var classes = '';
			if(this._containerClasses) {
				classes = 'class="' + this._containerClasses + '"';
			}
			return classes;
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

		_getTemplate: function (key, template) {
			if (!this._templates[key]) {
				this._templates[key] = Handlebars.compile(template);
			}
			return this._templates[key];
		},

		/**
		 * returns the info template for remote sharing
		 *
		 * @returns {Handlebars}
		 * @private
		 */
		_getRemoteShareInfoTemplate: function() {
			return this._getTemplate('remoteShareInfo', TEMPLATE_REMOTE_SHARE_INFO);
		},

		/**
		 * returns the info template for link sharing
		 *
		 * @returns {Handlebars}
		 * @private
		 */
		_getLinkShareTemplate: function() {
			return this._getTemplate('linkShare', TEMPLATE_LINK_SHARE);
		},

		_getReshareTemplate: function() {
			return this._getTemplate('reshare', TEMPLATE_RESHARER_INFO);
		},
	};

	OC.Share.ShareDialogView = ShareDialogView;

})();
