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
			'<ul id="shareWithList">' +
			'{{#each sharees}}' +
			'    {{#if isCollection}}' +
			'    <li data-collection="{{collectionID}}">{{text}}</li>' +
			'    {{/if}}' +
			'    {{#unless isCollection}}' +
			'    <li data-share-type="{{shareType}}" data-share-with="{{shareWith}}" title="{{shareWith}}">' +
			'        <a href="#" class="unshare"><img class="svg" alt="{{unshareLabel}}" title="{{unshareLabel}}" src="{{unshareImage}}" /></a>' +
			'        {{#if avatarEnabled}}' +
			'        <div class="avatar {{#if modSeed}}imageplaceholderseed{{/if}}" data-username="{{shareWith}}" {{#if modSeed}}data-seed="{{shareWith}} {{shareType}}"{{/if}}></div>' +
			'        {{/if}}' +
			'        <span class="username">{{shareWithDisplayName}}</span>' +
			'        {{#if mailPublicNotificationEnabled}} {{#unless isRemoteShare}}' +
			'        <label><input type="checkbox" name="mailNotification" class="mailNotification" {{#if wasMailSent}}checked="checked"{{/if}} />{{notifyByMailLabel}}</label>' +
			'        {{/unless}} {{/if}}' +
			'        {{#if isResharingAllowed}} {{#if sharePermissionPossible}}' +
			'        <label><input id="canShare-{{shareWith}}" type="checkbox" name="share" class="permissions" {{#if hasSharePermission}}checked="checked"{{/if}} data-permissions="{{sharePermission}}" />{{canShareLabel}}</label>' +
			'        {{/if}} {{/if}}' +
			'        {{#if editPermissionPossible}}' +
			'        <label><input id="canEdit-{{shareWith}}" type="checkbox" name="edit" class="permissions" {{#if hasEditPermission}}checked="checked"{{/if}} />{{canEditLabel}}</label>' +
			'        {{/if}}' +
			'        {{#unless isRemoteShare}}' +
			'        <a href="#" class="showCruds"><img class="svg" alt="{{crudsLabel}}" src="{{triangleSImage}}"/></a>' +
			'        <div class="cruds" class="hidden">' +
			'            {{#if createPermissionPossible}}' +
			'            <label><input id="canCreate-{{shareWith}}" type="checkbox" name="create" class="permissions" {{#if hasCreatePermission}}checked="checked"{{/if}} data-permissions="{{createPermission}}"/>{{createPermissionLabel}}</label>' +
			'            {{/if}}' +
			'            {{#if updatePermissionPossible}}' +
			'            <label><input id="canUpdate-{{shareWith}}" type="checkbox" name="update" class="permissions" {{#if hasUpdatePermission}}checked="checked"{{/if}} data-permissions="{{updatePermission}}"/>{{updatePermissionLabel}}</label>' +
			'            {{/if}}' +
			'            {{#if deletePermissionPossible}}' +
			'            <label><input id="canDelete-{{shareWith}}" type="checkbox" name="delete" class="permissions" {{#if hasDeletePermission}}checked="checked"{{/if}} data-permissions="{{deletePermission}}"/>{{deletePermissionLabel}}</label>' +
			'            {{/if}}' +
			'        </div>' +
			'        {{/unless}}' +
			'    </li>' +
			'    {{/unless}}' +
			'{{/each}}' +
			'</ul>'
		;

	/**
	 * @class OCA.Share.ShareDialogShareeListView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the sharee list part in the GUI of the share dialogue
	 *
	 */
	var ShareDialogShareeListView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkShare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var view = this;
			this.model.on('change:shares', function() {
				view.render();
			});
		},

		getCollectionObject: function(shareIndex) {
			var type = this.model.getCollectionType(shareIndex);
			var id = this.model.getCollectionPath(shareIndex);
			if(type !== 'file' && type !== 'folder') {
				id = this.model.getCollectionSource(shareIndex);
			}
			return {
				collectionID: id,
				text: t('core', 'Shared in {item} with {user}', {'item': id, user: this.model.getShareWithDisplayName(shareIndex)})
			}
		},

		/**
		 *
		 * @param {OC.Share.Types.ShareInfo} shareInfo
		 * @returns {object}
		 */
		getShareeObject: function(shareIndex) {
			var shareWith = this.model.getShareWith(shareIndex);
			var shareWithDisplayName = this.model.getShareWithDisplayName(shareIndex);
			var shareType = this.model.getShareType(shareIndex);

			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
			}


			return {
				hasSharePermission: this.model.hasSharePermission(shareIndex),
				hasEditPermission: this.model.hasEditPermission(shareIndex),
				hasCreatePermission: this.model.hasCreatePermission(shareIndex),
				hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
				hasDeletePermission: this.model.hasDeletePermission(shareIndex),
				wasMailSent: this.model.notificationMailWasSent(shareIndex),
				shareWith: shareWith,
				shareWithDisplayName: shareWithDisplayName,
				shareType: shareType,
				modSeed: shareType !== OC.Share.SHARE_TYPE_USER
			};
		},

		getShareeList: function() {
			var universal = {
				avatarEnabled: this.configModel.areAvatarsEnabled(),
				mailPublicNotificationEnabled: this.configModel.isMailPublicNotificationEnabled(),
				notifyByMailLabel: t('core', 'notify by email'),
				unshareLabel: t('core', 'Unshare'),
				unshareImage: OC.imagePath('core', 'actions/delete'),
				canShareLabel: t('core', 'can share'),
				canEditLabel: t('core', 'can edit'),
				createPermissionLabel: t('core', 'create'),
				updatePermissionLabel: t('core', 'change'),
				deletePermissionLabel: t('core', 'delete'),
				crudsLabel: t('core', 'access control'),
				triangleSImage: OC.imagePath('core', 'actions/triangle-s'),
				isResharingAllowed: this.configModel.isResharingAllowed(),
				sharePermissionPossible: this.model.sharePermissionPossible(),
				editPermissionPossible: this.model.editPermissionPossible(),
				createPermissionPossible: this.model.createPermissionPossible(),
				updatePermissionPossible: this.model.updatePermissionPossible(),
				deletePermissionPossible: this.model.deletePermissionPossible(),
				sharePermission: OC.PERMISSION_SHARE,
				createPermission: OC.PERMISSION_CREATE,
				updatePermission: OC.PERMISSION_UPDATE,
				deletePermission: OC.PERMISSION_DELETE
			};

			// TODO: sharess must have following attributes
			// isRemoteShare
			// isMailSent

			if(!this.model.hasShares()) {
				return [];
			}

			var list = [];
			for(var index in this.model.get('shares')) {
				if(this.model.isCollection(index)) {
					list.unshift(this.getCollectionObject(index));
				} else {
					list.push(_.extend(this.getShareeObject(index), universal))
				}
			}

			return list;
		},

		render: function() {
			var shareeListTemplate = this.template();
			var list = this.getShareeList();
			this.$el.html(shareeListTemplate({
				sharees: this.getShareeList()
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

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
