/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files_sharing/src/additionalScripts.js":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/additionalScripts.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _share_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./share.js */ "./apps/files_sharing/src/share.js");
/* harmony import */ var _sharebreadcrumbview_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./sharebreadcrumbview.js */ "./apps/files_sharing/src/sharebreadcrumbview.js");
/* harmony import */ var _style_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style/sharebreadcrumb.scss */ "./apps/files_sharing/src/style/sharebreadcrumb.scss");
/* harmony import */ var _collaborationresourceshandler_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./collaborationresourceshandler.js */ "./apps/files_sharing/src/collaborationresourceshandler.js");
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






// eslint-disable-next-line camelcase
__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
window.OCA.Sharing = OCA.Sharing;

/***/ }),

/***/ "./apps/files_sharing/src/collaborationresourceshandler.js":
/*!*****************************************************************!*\
  !*** ./apps/files_sharing/src/collaborationresourceshandler.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


// eslint-disable-next-line camelcase
__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
window.OCP.Collaboration.registerType('file', {
  action: () => {
    return new Promise((resolve, reject) => {
      OC.dialogs.filepicker(t('files_sharing', 'Link to a file'), function (f) {
        const client = OC.Files.getClient();
        client.getFileInfo(f).then((status, fileInfo) => {
          resolve(fileInfo.id);
        }).fail(() => {
          reject(new Error('Cannot get fileinfo'));
        });
      }, false, null, false, OC.dialogs.FILEPICKER_TYPE_CHOOSE, '', {
        allowDirectoryChooser: true
      });
    });
  },
  typeString: t('files_sharing', 'Link to a file'),
  typeIconClass: 'icon-files-dark'
});

/***/ }),

/***/ "./apps/files_sharing/src/share.js":
/*!*****************************************!*\
  !*** ./apps/files_sharing/src/share.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2011-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */



(function () {
  _.extend(OC.Files.Client, {
    PROPERTY_SHARE_TYPES: '{' + OC.Files.Client.NS_OWNCLOUD + '}share-types',
    PROPERTY_OWNER_ID: '{' + OC.Files.Client.NS_OWNCLOUD + '}owner-id',
    PROPERTY_OWNER_DISPLAY_NAME: '{' + OC.Files.Client.NS_OWNCLOUD + '}owner-display-name'
  });
  if (!OCA.Sharing) {
    OCA.Sharing = {};
  }

  /**
   * @namespace
   */
  OCA.Sharing.Util = {
    /**
     * Regular expression for splitting parts of remote share owners:
     * "user@example.com/"
     * "user@example.com/path/to/owncloud"
     * "user@anotherexample.com@example.com/path/to/owncloud
     */
    _REMOTE_OWNER_REGEXP: new RegExp('^(([^@]*)@(([^@^/\\s]*)@)?)((https://)?[^[\\s/]*)([/](.*))?$'),
    /**
     * Initialize the sharing plugin.
     *
     * Registers the "Share" file action and adds additional
     * DOM attributes for the sharing file info.
     *
     * @param {OCA.Files.FileList} fileList file list to be extended
     */
    attach: function (fileList) {
      // core sharing is disabled/not loaded
      if (!(0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing?.api_enabled) {
        return;
      }
      if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
        return;
      }
      var fileActions = fileList.fileActions;
      var oldCreateRow = fileList._createRow;
      fileList._createRow = function (fileData) {
        var tr = oldCreateRow.apply(this, arguments);
        var sharePermissions = OCA.Sharing.Util.getSharePermissions(fileData);
        if (fileData.permissions === 0) {
          // no permission, disabling sidebar
          delete fileActions.actions.all.Comment;
          delete fileActions.actions.all.Details;
          delete fileActions.actions.all.Goto;
        }
        if (_.isFunction(fileData.canDownload) && !fileData.canDownload()) {
          delete fileActions.actions.all.Download;
          if ((fileData.permissions & OC.PERMISSION_UPDATE) === 0) {
            // neither move nor copy is allowed, remove the action completely
            delete fileActions.actions.all.MoveCopy;
          }
        }
        tr.attr('data-share-permissions', sharePermissions);
        tr.attr('data-share-attributes', JSON.stringify(fileData.shareAttributes));
        if (fileData.shareOwner) {
          tr.attr('data-share-owner', fileData.shareOwner);
          tr.attr('data-share-owner-id', fileData.shareOwnerId);
          // user should always be able to rename a mount point
          if (fileData.mountType === 'shared-root') {
            tr.attr('data-permissions', fileData.permissions | OC.PERMISSION_UPDATE);
          }
        }
        if (fileData.recipientData && !_.isEmpty(fileData.recipientData)) {
          tr.attr('data-share-recipient-data', JSON.stringify(fileData.recipientData));
        }
        if (fileData.shareTypes) {
          tr.attr('data-share-types', fileData.shareTypes.join(','));
        }
        return tr;
      };
      var oldElementToFile = fileList.elementToFile;
      fileList.elementToFile = function ($el) {
        var fileInfo = oldElementToFile.apply(this, arguments);
        fileInfo.shareAttributes = JSON.parse($el.attr('data-share-attributes') || '[]');
        fileInfo.sharePermissions = $el.attr('data-share-permissions') || undefined;
        fileInfo.shareOwner = $el.attr('data-share-owner') || undefined;
        fileInfo.shareOwnerId = $el.attr('data-share-owner-id') || undefined;
        if ($el.attr('data-share-types')) {
          fileInfo.shareTypes = $el.attr('data-share-types').split(',');
        }
        if ($el.attr('data-expiration')) {
          var expirationTimestamp = parseInt($el.attr('data-expiration'));
          fileInfo.shares = [];
          fileInfo.shares.push({
            expiration: expirationTimestamp
          });
        }
        return fileInfo;
      };
      var oldGetWebdavProperties = fileList._getWebdavProperties;
      fileList._getWebdavProperties = function () {
        var props = oldGetWebdavProperties.apply(this, arguments);
        props.push(OC.Files.Client.PROPERTY_OWNER_ID);
        props.push(OC.Files.Client.PROPERTY_OWNER_DISPLAY_NAME);
        props.push(OC.Files.Client.PROPERTY_SHARE_TYPES);
        return props;
      };
      fileList.filesClient.addFileInfoParser(function (response) {
        var data = {};
        var props = response.propStat[0].properties;
        var permissionsProp = props[OC.Files.Client.PROPERTY_PERMISSIONS];
        if (permissionsProp && permissionsProp.indexOf('S') >= 0) {
          data.shareOwner = props[OC.Files.Client.PROPERTY_OWNER_DISPLAY_NAME];
          data.shareOwnerId = props[OC.Files.Client.PROPERTY_OWNER_ID];
        }
        var shareTypesProp = props[OC.Files.Client.PROPERTY_SHARE_TYPES];
        if (shareTypesProp) {
          data.shareTypes = _.chain(shareTypesProp).filter(function (xmlvalue) {
            return xmlvalue.namespaceURI === OC.Files.Client.NS_OWNCLOUD && xmlvalue.nodeName.split(':')[1] === 'share-type';
          }).map(function (xmlvalue) {
            return parseInt(xmlvalue.textContent || xmlvalue.text, 10);
          }).value();
        }
        return data;
      });

      // use delegate to catch the case with multiple file lists
      fileList.$el.on('fileActionsReady', function (ev) {
        var $files = ev.$files;
        _.each($files, function (file) {
          var $tr = $(file);
          var shareTypesStr = $tr.attr('data-share-types') || '';
          var shareOwner = $tr.attr('data-share-owner');
          if (shareTypesStr || shareOwner) {
            var hasLink = false;
            var hasShares = false;
            _.each(shareTypesStr.split(',') || [], function (shareTypeStr) {
              let shareType = parseInt(shareTypeStr, 10);
              if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Link) {
                hasLink = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Email) {
                hasLink = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.User) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Group) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Remote) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.RemoteGroup) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Team) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Room) {
                hasShares = true;
              } else if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Deck) {
                hasShares = true;
              }
            });
            OCA.Sharing.Util._updateFileActionIcon($tr, hasShares, hasLink);
          }
        });
      });
      fileList.$el.on('changeDirectory', function () {
        OCA.Sharing.sharesLoaded = false;
      });
      fileActions.registerAction({
        name: 'Share',
        displayName: function (context) {
          if (context && context.$file) {
            var shareType = parseInt(context.$file.data('share-types'), 10);
            var shareOwner = context.$file.data('share-owner-id');
            if (shareType >= 0 || shareOwner) {
              return t('files_sharing', 'Shared');
            }
          }
          return t('files_sharing', 'Share');
        },
        altText: t('files_sharing', 'Share'),
        mime: 'all',
        order: -150,
        permissions: OC.PERMISSION_ALL,
        iconClass: function (fileName, context) {
          var shareType = parseInt(context.$file.data('share-types'), 10);
          if (shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Email || shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Link) {
            return 'icon-public';
          }
          return 'icon-shared';
        },
        icon: function (fileName, context) {
          var shareOwner = context.$file.data('share-owner-id');
          if (shareOwner) {
            return OC.generateUrl(`/avatar/${shareOwner}/32`);
          }
        },
        type: OCA.Files.FileActions.TYPE_INLINE,
        actionHandler: function (fileName, context) {
          // details view disabled in some share lists
          if (!fileList._detailsView) {
            return;
          }
          // do not open sidebar if permission is set and equal to 0
          var permissions = parseInt(context.$file.data('share-permissions'), 10);
          if (isNaN(permissions) || permissions > 0) {
            fileList.showDetailsView(fileName, 'sharing');
          }
        },
        render: function (actionSpec, isDefault, context) {
          var permissions = parseInt(context.$file.data('permissions'), 10);
          // if no share permissions but share owner exists, still show the link
          if ((permissions & OC.PERMISSION_SHARE) !== 0 || context.$file.attr('data-share-owner')) {
            return fileActions._defaultRenderAction.call(fileActions, actionSpec, isDefault, context);
          }
          // don't render anything
          return null;
        }
      });

      // register share breadcrumbs component
      var breadCrumbSharingDetailView = new OCA.Sharing.ShareBreadCrumbView();
      fileList.registerBreadCrumbDetailView(breadCrumbSharingDetailView);
    },
    /**
     * Update file list data attributes
     */
    _updateFileListDataAttributes: function (fileList, $tr, shareModel) {
      // files app current cannot show recipients on load, so we don't update the
      // icon when changed for consistency
      if (fileList.id === 'files') {
        return;
      }
      var recipients = _.pluck(shareModel.get('shares'), 'share_with_displayname');
      // note: we only update the data attribute because updateIcon()
      if (recipients.length) {
        var recipientData = _.mapObject(shareModel.get('shares'), function (share) {
          return {
            shareWith: share.share_with,
            shareWithDisplayName: share.share_with_displayname
          };
        });
        $tr.attr('data-share-recipient-data', JSON.stringify(recipientData));
      } else {
        $tr.removeAttr('data-share-recipient-data');
      }
    },
    /**
     * Update the file action share icon for the given file
     *
     * @param $tr file element of the file to update
     * @param {boolean} hasUserShares true if a user share exists
     * @param {boolean} hasLinkShares true if a link share exists
     *
     * @returns {boolean} true if the icon was set, false otherwise
     */
    _updateFileActionIcon: function ($tr, hasUserShares, hasLinkShares) {
      // if the statuses are loaded already, use them for the icon
      // (needed when scrolling to the next page)
      if (hasUserShares || hasLinkShares || $tr.attr('data-share-recipient-data') || $tr.attr('data-share-owner')) {
        OCA.Sharing.Util._markFileAsShared($tr, true, hasLinkShares);
        return true;
      }
      return false;
    },
    /**
     * Marks/unmarks a given file as shared by changing its action icon
     * and folder icon.
     *
     * @param $tr file element to mark as shared
     * @param hasShares whether shares are available
     * @param hasLink whether link share is available
     */
    _markFileAsShared: function ($tr, hasShares, hasLink) {
      var action = $tr.find('.fileactions .action[data-action="Share"]');
      var type = $tr.data('type');
      var icon = action.find('.icon');
      var message, recipients, avatars;
      var ownerId = $tr.attr('data-share-owner-id');
      var owner = $tr.attr('data-share-owner');
      var mountType = $tr.attr('data-mounttype');
      var shareFolderIcon;
      var iconClass = 'icon-shared';
      action.removeClass('shared-style');
      // update folder icon
      var isEncrypted = $tr.attr('data-e2eencrypted');
      if (type === 'dir' && isEncrypted === 'true') {
        shareFolderIcon = OC.MimeType.getIconUrl('dir-encrypted');
        $tr.attr('data-icon', shareFolderIcon);
      } else if (type === 'dir' && (hasShares || hasLink || ownerId)) {
        if (typeof mountType !== 'undefined' && mountType !== 'shared-root' && mountType !== 'shared') {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-' + mountType);
        } else if (hasLink) {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-public');
        } else {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-shared');
        }
        $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
        $tr.attr('data-icon', shareFolderIcon);
      } else if (type === 'dir') {
        // FIXME: duplicate of FileList._createRow logic for external folder,
        // need to refactor the icon logic into a single code path eventually
        if (mountType && mountType.indexOf('external') === 0) {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-external');
          $tr.attr('data-icon', shareFolderIcon);
        } else {
          shareFolderIcon = OC.MimeType.getIconUrl('dir');
          // back to default
          $tr.removeAttr('data-icon');
        }
        $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
      }
      // update share action text / icon
      if (hasShares || ownerId) {
        recipients = $tr.data('share-recipient-data');
        action.addClass('shared-style');
        avatars = '<span>' + t('files_sharing', 'Shared') + '</span>';
        // even if reshared, only show "Shared by"
        if (ownerId) {
          message = t('files_sharing', 'Shared by');
          avatars = OCA.Sharing.Util._formatRemoteShare(ownerId, owner, message);
        } else if (recipients) {
          avatars = OCA.Sharing.Util._formatShareList(recipients);
        }
        action.html(avatars).prepend(icon);
        if (ownerId || recipients) {
          var avatarElement = action.find('.avatar');
          avatarElement.each(function () {
            $(this).avatar($(this).data('username'), 32);
          });
        }
      } else {
        action.html('<span class="hidden-visually">' + t('files_sharing', 'Shared') + '</span>').prepend(icon);
      }
      if (hasLink) {
        iconClass = 'icon-public';
      }
      icon.removeClass('icon-shared icon-public').addClass(iconClass);
    },
    /**
     * Format a remote address
     *
     * @param {String} shareWith userid, full remote share, or whatever
     * @param {String} shareWithDisplayName
     * @param {String} message
     * @returns {String} HTML code to display
     */
    _formatRemoteShare: function (shareWith, shareWithDisplayName, message) {
      var parts = OCA.Sharing.Util._REMOTE_OWNER_REGEXP.exec(shareWith);
      if (!parts || !parts[7]) {
        // display avatar of the user
        var avatar = '<span class="avatar" data-username="' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(shareWith) + '" title="' + message + ' ' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(shareWithDisplayName) + '"></span>';
        var hidden = '<span class="hidden-visually">' + message + ' ' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(shareWithDisplayName) + '</span> ';
        return avatar + hidden;
      }
      var userName = parts[2];
      var userDomain = parts[4];
      var server = parts[5];
      var protocol = parts[6];
      var serverPath = parts[8] ? parts[7] : ''; // no trailing slash on root

      var tooltip = message + ' ' + userName;
      if (userDomain) {
        tooltip += '@' + userDomain;
      }
      if (server) {
        tooltip += '@' + server.replace(protocol, '') + serverPath;
      }
      var html = '<span class="remoteAddress" title="' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(tooltip) + '">';
      html += '<span class="username">' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(userName) + '</span>';
      if (userDomain) {
        html += '<span class="userDomain">@' + escape_html__WEBPACK_IMPORTED_MODULE_0___default()(userDomain) + '</span>';
      }
      html += '</span> ';
      return html;
    },
    /**
     * Loop over all recipients in the list and format them using
     * all kind of fancy magic.
     *
    * @param {Object} recipients array of all the recipients
    * @returns {String[]} modified list of recipients
    */
    _formatShareList: function (recipients) {
      var _parent = this;
      recipients = _.toArray(recipients);
      recipients.sort(function (a, b) {
        return a.shareWithDisplayName.localeCompare(b.shareWithDisplayName);
      });
      return $.map(recipients, function (recipient) {
        return _parent._formatRemoteShare(recipient.shareWith, recipient.shareWithDisplayName, t('files_sharing', 'Shared with'));
      });
    },
    /**
     * Marks/unmarks a given file as shared by changing its action icon
     * and folder icon.
     *
    * @param $tr file element to mark as shared
    * @param hasShares whether shares are available
    * @param hasLink whether link share is available
    */
    markFileAsShared: function ($tr, hasShares, hasLink) {
      var action = $tr.find('.fileactions .action[data-action="Share"]');
      var type = $tr.data('type');
      var icon = action.find('.icon');
      var message, recipients, avatars;
      var ownerId = $tr.attr('data-share-owner-id');
      var owner = $tr.attr('data-share-owner');
      var mountType = $tr.attr('data-mounttype');
      var shareFolderIcon;
      var iconClass = 'icon-shared';
      action.removeClass('shared-style');
      // update folder icon
      if (type === 'dir' && (hasShares || hasLink || ownerId)) {
        if (typeof mountType !== 'undefined' && mountType !== 'shared-root' && mountType !== 'shared') {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-' + mountType);
        } else if (hasLink) {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-public');
        } else {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-shared');
        }
        $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
        $tr.attr('data-icon', shareFolderIcon);
      } else if (type === 'dir') {
        var isEncrypted = $tr.attr('data-e2eencrypted');
        // FIXME: duplicate of FileList._createRow logic for external folder,
        // need to refactor the icon logic into a single code path eventually
        if (isEncrypted === 'true') {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-encrypted');
          $tr.attr('data-icon', shareFolderIcon);
        } else if (mountType && mountType.indexOf('external') === 0) {
          shareFolderIcon = OC.MimeType.getIconUrl('dir-external');
          $tr.attr('data-icon', shareFolderIcon);
        } else {
          shareFolderIcon = OC.MimeType.getIconUrl('dir');
          // back to default
          $tr.removeAttr('data-icon');
        }
        $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
      }
      // update share action text / icon
      if (hasShares || ownerId) {
        recipients = $tr.data('share-recipient-data');
        action.addClass('shared-style');
        avatars = '<span>' + t('files_sharing', 'Shared') + '</span>';
        // even if reshared, only show "Shared by"
        if (ownerId) {
          message = t('files_sharing', 'Shared by');
          avatars = this._formatRemoteShare(ownerId, owner, message);
        } else if (recipients) {
          avatars = this._formatShareList(recipients);
        }
        action.html(avatars).prepend(icon);
        if (ownerId || recipients) {
          var avatarElement = action.find('.avatar');
          avatarElement.each(function () {
            $(this).avatar($(this).data('username'), 32);
          });
        }
      } else {
        action.html('<span class="hidden-visually">' + t('files_sharing', 'Shared') + '</span>').prepend(icon);
      }
      if (hasLink) {
        iconClass = 'icon-public';
      }
      icon.removeClass('icon-shared icon-public').addClass(iconClass);
    },
    /**
     * @param {Array} fileData
     * @returns {String}
     */
    getSharePermissions: function (fileData) {
      return fileData.sharePermissions;
    }
  };
})();
OC.Plugins.register('OCA.Files.FileList', OCA.Sharing.Util);

/***/ }),

/***/ "./apps/files_sharing/src/sharebreadcrumbview.js":
/*!*******************************************************!*\
  !*** ./apps/files_sharing/src/sharebreadcrumbview.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


(function () {
  'use strict';

  const BreadCrumbView = OC.Backbone.View.extend({
    tagName: 'span',
    events: {
      click: '_onClick'
    },
    _dirInfo: undefined,
    render(data) {
      this._dirInfo = data.dirInfo || null;
      if (this._dirInfo !== null && (this._dirInfo.path !== '/' || this._dirInfo.name !== '')) {
        const isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0;
        this.$el.removeClass('shared icon-public icon-shared');
        if (isShared) {
          this.$el.addClass('shared');
          if (data.dirInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Link) !== -1) {
            this.$el.addClass('icon-public');
          } else {
            this.$el.addClass('icon-shared');
          }
        } else {
          this.$el.addClass('icon-shared');
        }
        this.$el.show();
        this.delegateEvents();
      } else {
        this.$el.removeClass('shared icon-public icon-shared');
        this.$el.hide();
      }
      return this;
    },
    _onClick(e) {
      e.preventDefault();
      e.stopPropagation();
      const fileInfoModel = new OCA.Files.FileInfoModel(this._dirInfo);
      const self = this;
      fileInfoModel.on('change', function () {
        self.render({
          dirInfo: self._dirInfo
        });
      });
      const path = fileInfoModel.attributes.path + '/' + fileInfoModel.attributes.name;
      OCA.Files.Sidebar.open(path);
      OCA.Files.Sidebar.setActiveTab('sharing');
    }
  });
  OCA.Sharing.ShareBreadCrumbView = BreadCrumbView;
})();

/***/ }),

/***/ "./apps/files_sharing/src/style/sharebreadcrumb.scss":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/style/sharebreadcrumb.scss ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./sharebreadcrumb.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/style/sharebreadcrumb.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/style/sharebreadcrumb.scss":
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/style/sharebreadcrumb.scss ***!
  \****************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/*!
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
li.crumb span.icon-shared,
li.crumb span.icon-public {
  display: inline-block;
  cursor: pointer;
  opacity: 0.2;
  margin-inline-end: 6px;
}

li.crumb span.icon-shared.shared,
li.crumb span.icon-public.shared {
  opacity: 0.7;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		// The chunk loading function for additional chunks
/******/ 		// Since all referenced chunks are already included
/******/ 		// in this file, this function is empty here.
/******/ 		__webpack_require__.e = () => (Promise.resolve());
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files_sharing-additionalScripts": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_sharing/src/additionalScripts.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_sharing-additionalScripts.js.map?v=917d82c303c9ba281ab1