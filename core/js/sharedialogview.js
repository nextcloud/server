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
		'<div class="resharerInfo"></div>' +
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
		'<div class="linkShare"></div>' +
		'{{else}}' +
		'{{{noSharing}}}' +
		'{{/if}}' +
		'{{{expiration}}}'
		;

	var TEMPLATE_REMOTE_SHARE_INFO =
		'<a target="_blank" class="icon-info svg shareWithRemoteInfo hasTooltip" href="{{docLink}}" ' +
		'title="{{tooltip}}"></a>';

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

		/** @type {object} **/
		resharerInfoView: undefined,

		/** @type {object} **/
		linkShareView: undefined,

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

			var subViewOptions = {
				model: this.model,
				configModel: this.configModel
			};

			this.resharerInfoView = _.isUndefined(options.resharerInfoView)
				? new OC.Share.ShareDialogResharerInfoView(subViewOptions)
				: options.resharerInfoView;

			this.linkShareView = _.isUndefined(options.linkShareView)
				? new OC.Share.ShareDialogLinkShareView(subViewOptions)
				: options.linkShareView;

		},

		render: function() {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);

			this.$el.html(baseTemplate({
				shareLabel: t('core', 'Share'),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				remoteShareInfo: this._renderRemoteShareInfoPart(),
				shareAllowed: this.model.hasSharePermission(),
				noSharing: this._renderNoSharing(),
				expiration: this._renderExpirationPart()
			}));

			this.resharerInfoView.$el = this.$el.find('.resharerInfo');
			this.resharerInfoView.render();

			if(this.model.hasSharePermission()) {
				this.linkShareView.$el = this.$el.find('.linkShare');
				this.linkShareView.render();
			}

			this.$el.find('.hasTooltip').tooltip();
			if(this.configModel.areAvatarsEnabled()) {
				this.$el.find('.avatar').avatar(this.model.getReshareOwner, 32);
			}

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
			this.linkShareView.showLink = this._showLink;
		},

		_renderRemoteShareInfoPart: function() {
			var remoteShareInfo = '';
			if(this.configModel.isRemoteShareAllowed()) {
				var infoTemplate = this._getRemoteShareInfoTemplate();
				remoteShareInfo = infoTemplate({
					docLink: this.configModel.getFederatedShareDocLink(),
					tooltip: t('core', 'Share with people on other ownClouds using the syntax username@example.com/owncloud')
				});
			}

			return remoteShareInfo;
		},

		_renderSharePlaceholderPart: function () {
			var sharePlaceholder = t('core', 'Share with users or groups …');
			if (this.configModel.isRemoteShareAllowed()) {
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
		}
	});

	OC.Share.ShareDialogView = ShareDialogView;

})();
