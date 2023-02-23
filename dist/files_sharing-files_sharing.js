/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files_sharing/js/app.js":
/*!**************************************!*\
  !*** ./apps/files_sharing/js/app.js ***!
  \**************************************/
/***/ (function() {

/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

if (!OCA.Sharing) {
  /**
   * @namespace OCA.Sharing
   */
  OCA.Sharing = {};
}

/**
 * @namespace
 */
OCA.Sharing.App = {
  _inFileList: null,
  _outFileList: null,
  _overviewFileList: null,
  _pendingFileList: null,
  initSharingIn: function initSharingIn($el) {
    if (this._inFileList) {
      return this._inFileList;
    }
    this._inFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.self',
      sharedWithUser: true,
      fileActions: this._createFileActions(),
      config: OCA.Files.App.getFilesConfig(),
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._inFileList);
    this._inFileList.appName = t('files_sharing', 'Shared with you');
    this._inFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-shared"></div>' + '<h2>' + t('files_sharing', 'Nothing shared with you yet') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders others share with you will show up here') + '</p>');
    return this._inFileList;
  },
  initSharingOut: function initSharingOut($el) {
    if (this._outFileList) {
      return this._outFileList;
    }
    this._outFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.others',
      sharedWithUser: false,
      fileActions: this._createFileActions(),
      config: OCA.Files.App.getFilesConfig(),
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._outFileList);
    this._outFileList.appName = t('files_sharing', 'Shared with others');
    this._outFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-shared"></div>' + '<h2>' + t('files_sharing', 'Nothing shared yet') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders you share will show up here') + '</p>');
    return this._outFileList;
  },
  initSharingLinks: function initSharingLinks($el) {
    if (this._linkFileList) {
      return this._linkFileList;
    }
    this._linkFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.link',
      linksOnly: true,
      fileActions: this._createFileActions(),
      config: OCA.Files.App.getFilesConfig(),
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._linkFileList);
    this._linkFileList.appName = t('files_sharing', 'Shared by link');
    this._linkFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-public"></div>' + '<h2>' + t('files_sharing', 'No shared links') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders you share by link will show up here') + '</p>');
    return this._linkFileList;
  },
  initSharingDeleted: function initSharingDeleted($el) {
    if (this._deletedFileList) {
      return this._deletedFileList;
    }
    this._deletedFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.deleted',
      defaultFileActionsDisabled: true,
      showDeleted: true,
      sharedWithUser: true,
      fileActions: this._restoreShareAction(),
      config: OCA.Files.App.getFilesConfig(),
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._deletedFileList);
    this._deletedFileList.appName = t('files_sharing', 'Deleted shares');
    this._deletedFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-share"></div>' + '<h2>' + t('files_sharing', 'No deleted shares') + '</h2>' + '<p>' + t('files_sharing', 'Shares you deleted will show up here') + '</p>');
    return this._deletedFileList;
  },
  initSharingPening: function initSharingPening($el) {
    if (this._pendingFileList) {
      return this._pendingFileList;
    }
    this._pendingFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.pending',
      showPending: true,
      detailsViewEnabled: false,
      defaultFileActionsDisabled: true,
      sharedWithUser: true,
      fileActions: this._acceptShareAction(),
      config: OCA.Files.App.getFilesConfig(),
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._pendingFileList);
    this._pendingFileList.appName = t('files_sharing', 'Pending shares');
    this._pendingFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-share"></div>' + '<h2>' + t('files_sharing', 'No pending shares') + '</h2>' + '<p>' + t('files_sharing', 'Shares you have received but not confirmed will show up here') + '</p>');
    return this._pendingFileList;
  },
  initShareingOverview: function initShareingOverview($el) {
    if (this._overviewFileList) {
      return this._overviewFileList;
    }
    this._overviewFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.overview',
      fileActions: this._createFileActions(),
      config: OCA.Files.App.getFilesConfig(),
      isOverview: true,
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
    this._extendFileList(this._overviewFileList);
    this._overviewFileList.appName = t('files_sharing', 'Shares');
    this._overviewFileList.$el.find('.emptyfilelist.emptycontent').html('<div class="icon-share"></div>' + '<h2>' + t('files_sharing', 'No shares') + '</h2>' + '<p>' + t('files_sharing', 'Shares will show up here') + '</p>');
    return this._overviewFileList;
  },
  removeSharingIn: function removeSharingIn() {
    if (this._inFileList) {
      this._inFileList.$fileList.empty();
    }
  },
  removeSharingOut: function removeSharingOut() {
    if (this._outFileList) {
      this._outFileList.$fileList.empty();
    }
  },
  removeSharingLinks: function removeSharingLinks() {
    if (this._linkFileList) {
      this._linkFileList.$fileList.empty();
    }
  },
  removeSharingDeleted: function removeSharingDeleted() {
    if (this._deletedFileList) {
      this._deletedFileList.$fileList.empty();
    }
  },
  removeSharingPending: function removeSharingPending() {
    if (this._pendingFileList) {
      this._pendingFileList.$fileList.empty();
    }
  },
  removeSharingOverview: function removeSharingOverview() {
    if (this._overviewFileList) {
      this._overviewFileList.$fileList.empty();
    }
  },
  /**
   * Destroy the app
   */
  destroy: function destroy() {
    OCA.Files.fileActions.off('setDefault.app-sharing', this._onActionsUpdated);
    OCA.Files.fileActions.off('registerAction.app-sharing', this._onActionsUpdated);
    this.removeSharingIn();
    this.removeSharingOut();
    this.removeSharingLinks();
    this._inFileList = null;
    this._outFileList = null;
    this._linkFileList = null;
    this._overviewFileList = null;
    delete this._globalActionsInitialized;
  },
  _createFileActions: function _createFileActions() {
    // inherit file actions from the files app
    var fileActions = new OCA.Files.FileActions();
    // note: not merging the legacy actions because legacy apps are not
    // compatible with the sharing overview and need to be adapted first
    fileActions.registerDefaultActions();
    fileActions.merge(OCA.Files.fileActions);
    if (!this._globalActionsInitialized) {
      // in case actions are registered later
      this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
      OCA.Files.fileActions.on('setDefault.app-sharing', this._onActionsUpdated);
      OCA.Files.fileActions.on('registerAction.app-sharing', this._onActionsUpdated);
      this._globalActionsInitialized = true;
    }

    // when the user clicks on a folder, redirect to the corresponding
    // folder in the files app instead of opening it directly
    fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
      OCA.Files.App.setActiveView('files', {
        silent: true
      });
      OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true);
    });
    fileActions.setDefault('dir', 'Open');
    return fileActions;
  },
  _restoreShareAction: function _restoreShareAction() {
    var fileActions = new OCA.Files.FileActions();
    fileActions.registerAction({
      name: 'Restore',
      displayName: t('files_sharing', 'Restore'),
      altText: t('files_sharing', 'Restore share'),
      mime: 'all',
      permissions: OC.PERMISSION_ALL,
      iconClass: 'icon-history',
      type: OCA.Files.FileActions.TYPE_INLINE,
      actionHandler: function actionHandler(fileName, context) {
        var shareId = context.$file.data('shareId');
        $.post(OC.linkToOCS('apps/files_sharing/api/v1/deletedshares', 2) + shareId).success(function (result) {
          context.fileList.remove(context.fileInfoModel.attributes.name);
        }).fail(function () {
          OC.Notification.showTemporary(t('files_sharing', 'Something happened. Unable to restore the share.'));
        });
      }
    });
    return fileActions;
  },
  _acceptShareAction: function _acceptShareAction() {
    var fileActions = new OCA.Files.FileActions();
    fileActions.registerAction({
      name: 'Accept share',
      displayName: t('files_sharing', 'Accept share'),
      mime: 'all',
      permissions: OC.PERMISSION_ALL,
      iconClass: 'icon-checkmark',
      type: OCA.Files.FileActions.TYPE_INLINE,
      actionHandler: function actionHandler(fileName, context) {
        var shareId = context.$file.data('shareId');
        var shareBase = 'shares/pending';
        if (context.$file.attr('data-remote-id')) {
          shareBase = 'remote_shares/pending';
        }
        $.post(OC.linkToOCS('apps/files_sharing/api/v1/' + shareBase, 2) + shareId).success(function (result) {
          context.fileList.remove(context.fileInfoModel.attributes.name);
        }).fail(function () {
          OC.Notification.showTemporary(t('files_sharing', 'Something happened. Unable to accept the share.'));
        });
      }
    });
    fileActions.registerAction({
      name: 'Reject share',
      displayName: t('files_sharing', 'Reject share'),
      mime: 'all',
      permissions: OC.PERMISSION_ALL,
      iconClass: 'icon-close',
      type: OCA.Files.FileActions.TYPE_INLINE,
      shouldRender: function shouldRender(context) {
        // disable rejecting group shares from the pending list because they anyway
        // land back into that same list
        if (context.$file.attr('data-remote-id') && parseInt(context.$file.attr('data-share-type'), 10) === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
          return false;
        }
        return true;
      },
      actionHandler: function actionHandler(fileName, context) {
        var shareId = context.$file.data('shareId');
        var shareBase = 'shares';
        if (context.$file.attr('data-remote-id')) {
          shareBase = 'remote_shares';
        }
        $.ajax({
          url: OC.linkToOCS('apps/files_sharing/api/v1/' + shareBase, 2) + shareId,
          type: 'DELETE'
        }).success(function (result) {
          context.fileList.remove(context.fileInfoModel.attributes.name);
        }).fail(function () {
          OC.Notification.showTemporary(t('files_sharing', 'Something happened. Unable to reject the share.'));
        });
      }
    });
    return fileActions;
  },
  _onActionsUpdated: function _onActionsUpdated(ev) {
    _.each([this._inFileList, this._outFileList, this._linkFileList], function (list) {
      if (!list) {
        return;
      }
      if (ev.action) {
        list.fileActions.registerAction(ev.action);
      } else if (ev.defaultAction) {
        list.fileActions.setDefault(ev.defaultAction.mime, ev.defaultAction.name);
      }
    });
  },
  _extendFileList: function _extendFileList(fileList) {
    // remove size column from summary
    fileList.fileSummary.$el.find('.filesize').remove();
  }
};
window.addEventListener('DOMContentLoaded', function () {
  $('#app-content-sharingin').on('show', function (e) {
    OCA.Sharing.App.initSharingIn($(e.target));
  });
  $('#app-content-sharingin').on('hide', function () {
    OCA.Sharing.App.removeSharingIn();
  });
  $('#app-content-sharingout').on('show', function (e) {
    OCA.Sharing.App.initSharingOut($(e.target));
  });
  $('#app-content-sharingout').on('hide', function () {
    OCA.Sharing.App.removeSharingOut();
  });
  $('#app-content-sharinglinks').on('show', function (e) {
    OCA.Sharing.App.initSharingLinks($(e.target));
  });
  $('#app-content-sharinglinks').on('hide', function () {
    OCA.Sharing.App.removeSharingLinks();
  });
  $('#app-content-deletedshares').on('show', function (e) {
    OCA.Sharing.App.initSharingDeleted($(e.target));
  });
  $('#app-content-deletedshares').on('hide', function () {
    OCA.Sharing.App.removeSharingDeleted();
  });
  $('#app-content-pendingshares').on('show', function (e) {
    OCA.Sharing.App.initSharingPening($(e.target));
  });
  $('#app-content-pendingshares').on('hide', function () {
    OCA.Sharing.App.removeSharingPending();
  });
  $('#app-content-shareoverview').on('show', function (e) {
    OCA.Sharing.App.initShareingOverview($(e.target));
  });
  $('#app-content-shareoverview').on('hide', function () {
    OCA.Sharing.App.removeSharingOverview();
  });
});

/***/ }),

/***/ "./apps/files_sharing/js/sharedfilelist.js":
/*!*************************************************!*\
  !*** ./apps/files_sharing/js/sharedfilelist.js ***!
  \*************************************************/
/***/ (function() {

/* eslint-disable */
/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  /**
   * @class OCA.Sharing.FileList
   * @augments OCA.Files.FileList
   *
   * @classdesc Sharing file list.
   * Contains both "shared with others" and "shared with you" modes.
   *
   * @param $el container element with existing markup for the .files-controls
   * and a table
   * @param [options] map of options, see other parameters
   * @param {boolean} [options.sharedWithUser] true to return files shared with
   * the current user, false to return files that the user shared with others.
   * Defaults to false.
   * @param {boolean} [options.linksOnly] true to return only link shares
   */
  var FileList = function FileList($el, options) {
    this.initialize($el, options);
  };
  FileList.prototype = _.extend({}, OCA.Files.FileList.prototype, /** @lends OCA.Sharing.FileList.prototype */{
    appName: 'Shares',
    /**
    * Whether the list shows the files shared with the user (true) or
    * the files that the user shared with others (false).
    */
    _sharedWithUser: false,
    _linksOnly: false,
    _showDeleted: false,
    _showPending: false,
    _clientSideSort: true,
    _allowSelection: false,
    _isOverview: false,
    /**
    * @private
    */
    initialize: function initialize($el, options) {
      OCA.Files.FileList.prototype.initialize.apply(this, arguments);
      if (this.initialized) {
        return;
      }

      // TODO: consolidate both options
      if (options && options.sharedWithUser) {
        this._sharedWithUser = true;
      }
      if (options && options.linksOnly) {
        this._linksOnly = true;
      }
      if (options && options.showDeleted) {
        this._showDeleted = true;
      }
      if (options && options.showPending) {
        this._showPending = true;
      }
      if (options && options.isOverview) {
        this._isOverview = true;
      }
    },
    _renderRow: function _renderRow() {
      // HACK: needed to call the overridden _renderRow
      // this is because at the time this class is created
      // the overriding hasn't been done yet...
      return OCA.Files.FileList.prototype._renderRow.apply(this, arguments);
    },
    _createRow: function _createRow(fileData) {
      // TODO: hook earlier and render the whole row here
      var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
      $tr.find('.filesize').remove();
      $tr.find('td.date').before($tr.children('td:first'));
      $tr.find('td.filename input:checkbox').remove();
      $tr.attr('data-share-id', _.pluck(fileData.shares, 'id').join(','));
      if (this._sharedWithUser) {
        $tr.attr('data-share-owner', fileData.shareOwner);
        $tr.attr('data-mounttype', 'shared-root');
        var permission = parseInt($tr.attr('data-permissions')) | OC.PERMISSION_DELETE;
        $tr.attr('data-permissions', permission);
      }
      if (this._showDeleted || this._showPending) {
        var permission = fileData.permissions;
        $tr.attr('data-share-permissions', permission);
      }
      if (fileData.remoteId) {
        $tr.attr('data-remote-id', fileData.remoteId);
      }
      if (fileData.shareType) {
        $tr.attr('data-share-type', fileData.shareType);
      }

      // add row with expiration date for link only shares - influenced by _createRow of filelist
      if (this._linksOnly) {
        var expirationTimestamp = 0;
        if (fileData.shares && fileData.shares[0].expiration !== null) {
          expirationTimestamp = moment(fileData.shares[0].expiration).valueOf();
        }
        $tr.attr('data-expiration', expirationTimestamp);

        // date column (1000 milliseconds to seconds, 60 seconds, 60 minutes, 24 hours)
        // difference in days multiplied by 5 - brightest shade for expiry dates in more than 32 days (160/5)
        var modifiedColor = Math.round((expirationTimestamp - new Date().getTime()) / 1000 / 60 / 60 / 24 * 5);
        // ensure that the brightest color is still readable
        if (modifiedColor >= 160) {
          modifiedColor = 160;
        }
        var formatted;
        var text;
        if (expirationTimestamp > 0) {
          formatted = OC.Util.formatDate(expirationTimestamp);
          text = OC.Util.relativeModifiedDate(expirationTimestamp);
        } else {
          formatted = t('files_sharing', 'No expiration date set');
          text = '';
          modifiedColor = 160;
        }
        td = $('<td></td>').attr({
          'class': 'date'
        });
        td.append($('<span></span>').attr({
          'class': 'modified',
          'title': formatted,
          'style': 'color:rgb(' + modifiedColor + ',' + modifiedColor + ',' + modifiedColor + ')'
        }).text(text));
        $tr.append(td);
      }
      return $tr;
    },
    /**
    * Set whether the list should contain outgoing shares
    * or incoming shares.
    *
    * @param state true for incoming shares, false otherwise
    */
    setSharedWithUser: function setSharedWithUser(state) {
      this._sharedWithUser = !!state;
    },
    updateEmptyContent: function updateEmptyContent() {
      var dir = this.getCurrentDirectory();
      if (dir === '/') {
        // root has special permissions
        this.$el.find('.emptyfilelist.emptycontent').toggleClass('hidden', !this.isEmpty);
        this.$el.find('.files-filestable thead th').toggleClass('hidden', this.isEmpty);

        // hide expiration date header for non link only shares
        if (!this._linksOnly) {
          this.$el.find('th.column-expiration').addClass('hidden');
        }
      } else {
        OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
      }
    },
    getDirectoryPermissions: function getDirectoryPermissions() {
      return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
    },
    updateStorageStatistics: function updateStorageStatistics() {
      // no op because it doesn't have
      // storage info like free space / used space
    },
    reload: function reload() {
      var _this$_reloadCall;
      this.showMask();
      if ((_this$_reloadCall = this._reloadCall) !== null && _this$_reloadCall !== void 0 && _this$_reloadCall.abort) {
        this._reloadCall.abort();
      }

      // there is only root
      this._setCurrentDir('/', false);
      var promises = [];
      var deletedShares = {
        url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'deletedshares',
        /* jshint camelcase: false */
        data: {
          format: 'json',
          include_tags: true
        },
        type: 'GET',
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        }
      };
      var pendingShares = {
        url: OC.linkToOCS('apps/files_sharing/api/v1/shares', 2) + 'pending',
        /* jshint camelcase: false */
        data: {
          format: 'json'
        },
        type: 'GET',
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        }
      };
      var pendingRemoteShares = {
        url: OC.linkToOCS('apps/files_sharing/api/v1/remote_shares', 2) + 'pending',
        /* jshint camelcase: false */
        data: {
          format: 'json'
        },
        type: 'GET',
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        }
      };
      var shares = {
        url: OC.linkToOCS('apps/files_sharing/api/v1') + 'shares',
        /* jshint camelcase: false */
        data: {
          format: 'json',
          shared_with_me: this._sharedWithUser !== false,
          include_tags: true
        },
        type: 'GET',
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        }
      };
      var remoteShares = {
        url: OC.linkToOCS('apps/files_sharing/api/v1') + 'remote_shares',
        /* jshint camelcase: false */
        data: {
          format: 'json',
          include_tags: true
        },
        type: 'GET',
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        }
      };

      // Add the proper ajax requests to the list and run them
      // and make sure we have 2 promises
      if (this._showDeleted) {
        promises.push($.ajax(deletedShares));
      } else if (this._showPending) {
        promises.push($.ajax(pendingShares));
        promises.push($.ajax(pendingRemoteShares));
      } else {
        promises.push($.ajax(shares));
        if (this._sharedWithUser !== false || this._isOverview) {
          promises.push($.ajax(remoteShares));
        }
        if (this._isOverview) {
          shares.data.shared_with_me = !shares.data.shared_with_me;
          promises.push($.ajax(shares));
        }
      }
      this._reloadCall = $.when.apply($, promises);
      var callBack = this.reloadCallback.bind(this);
      return this._reloadCall.then(callBack, callBack);
    },
    reloadCallback: function reloadCallback(shares, remoteShares, additionalShares) {
      delete this._reloadCall;
      this.hideMask();
      this.$el.find('#headerSharedWith').text(t('files_sharing', this._sharedWithUser ? 'Shared by' : 'Shared with'));
      var files = [];

      // make sure to use the same format
      if (shares[0] && shares[0].ocs) {
        shares = shares[0];
      }
      if (remoteShares && remoteShares[0] && remoteShares[0].ocs) {
        remoteShares = remoteShares[0];
      }
      if (additionalShares && additionalShares[0] && additionalShares[0].ocs) {
        additionalShares = additionalShares[0];
      }
      if (shares.ocs && shares.ocs.data) {
        files = files.concat(this._makeFilesFromShares(shares.ocs.data, this._sharedWithUser));
      }
      if (remoteShares && remoteShares.ocs && remoteShares.ocs.data) {
        files = files.concat(this._makeFilesFromRemoteShares(remoteShares.ocs.data));
      }
      if (additionalShares && additionalShares.ocs && additionalShares.ocs.data) {
        if (this._showPending) {
          // in this case the second callback is about pending remote shares
          files = files.concat(this._makeFilesFromRemoteShares(additionalShares.ocs.data));
        } else {
          files = files.concat(this._makeFilesFromShares(additionalShares.ocs.data, !this._sharedWithUser));
        }
      }
      this.setFiles(files);
      return true;
    },
    _makeFilesFromRemoteShares: function _makeFilesFromRemoteShares(data) {
      var files = data;
      files = _.chain(files)
      // convert share data to file data
      .map(function (share) {
        var file = {
          shareOwner: share.owner + '@' + share.remote.replace(/.*?:\/\//g, ''),
          name: OC.basename(share.mountpoint),
          mtime: share.mtime * 1000,
          mimetype: share.mimetype,
          type: share.type,
          // remote share types are different and need to be mapped
          shareType: parseInt(share.share_type, 10) === 1 ? OC.Share.SHARE_TYPE_REMOTE_GROUP : OC.Share.SHARE_TYPE_REMOTE,
          id: share.file_id,
          path: OC.dirname(share.mountpoint),
          permissions: share.permissions,
          tags: share.tags || []
        };
        if (share.remote_id) {
          // remote share
          if (share.accepted !== '1') {
            file.name = OC.basename(share.name);
            file.path = '/';
          }
          file.remoteId = share.remote_id;
          file.shareOwnerId = share.owner;
        }
        if (!file.mimetype) {
          // pending shares usually have no type, so default to showing a directory icon
          file.mimetype = 'dir-shared';
        }
        file.shares = [{
          id: share.id,
          type: OC.Share.SHARE_TYPE_REMOTE
        }];
        return file;
      }).value();
      return files;
    },
    /**
    * Converts the OCS API share response data to a file info
    * list
    * @param {Array} data OCS API share array
    * @param {boolean} sharedWithUser
    * @returns {Array.<OCA.Sharing.SharedFileInfo>} array of shared file info
    */
    _makeFilesFromShares: function _makeFilesFromShares(data, sharedWithUser) {
      /* jshint camelcase: false */
      var files = data;
      if (this._linksOnly) {
        files = _.filter(data, function (share) {
          return share.share_type === OC.Share.SHARE_TYPE_LINK;
        });
      }

      // OCS API uses non-camelcased names
      files = _.chain(files)
      // convert share data to file data
      .map(function (share) {
        // TODO: use OC.Files.FileInfo
        var file = {
          id: share.file_source,
          icon: OC.MimeType.getIconUrl(share.mimetype),
          mimetype: share.mimetype,
          hasPreview: share.has_preview,
          tags: share.tags || []
        };
        if (share.item_type === 'folder') {
          file.type = 'dir';
          file.mimetype = 'httpd/unix-directory';
        } else {
          file.type = 'file';
        }
        file.share = {
          id: share.id,
          type: share.share_type,
          target: share.share_with,
          stime: share.stime * 1000,
          expiration: share.expiration
        };
        if (sharedWithUser) {
          file.shareOwner = share.displayname_owner;
          file.shareOwnerId = share.uid_owner;
          file.name = OC.basename(share.file_target);
          file.path = OC.dirname(share.file_target);
          file.permissions = share.permissions;
          if (file.path) {
            file.extraData = share.file_target;
          }
        } else {
          if (share.share_type !== OC.Share.SHARE_TYPE_LINK) {
            file.share.targetDisplayName = share.share_with_displayname;
            file.share.targetShareWithId = share.share_with;
          }
          file.name = OC.basename(share.path);
          file.path = OC.dirname(share.path);
          file.permissions = OC.PERMISSION_ALL;
          if (file.path) {
            file.extraData = share.path;
          }
        }
        return file;
      })
      // Group all files and have a "shares" array with
      // the share info for each file.
      //
      // This uses a hash memo to cumulate share information
      // inside the same file object (by file id).
      .reduce(function (memo, file) {
        var data = memo[file.id];
        var recipient = file.share.targetDisplayName;
        var recipientId = file.share.targetShareWithId;
        if (!data) {
          data = memo[file.id] = file;
          data.shares = [file.share];
          // using a hash to make them unique,
          // this is only a list to be displayed
          data.recipients = {};
          data.recipientData = {};
          // share types
          data.shareTypes = {};
          // counter is cheaper than calling _.keys().length
          data.recipientsCount = 0;
          data.mtime = file.share.stime;
        } else {
          // always take the most recent stime
          if (file.share.stime > data.mtime) {
            data.mtime = file.share.stime;
          }
          data.shares.push(file.share);
        }
        if (recipient) {
          // limit counterparts for output
          if (data.recipientsCount < 4) {
            // only store the first ones, they will be the only ones
            // displayed
            data.recipients[recipient] = true;
            data.recipientData[data.recipientsCount] = {
              'shareWith': recipientId,
              'shareWithDisplayName': recipient
            };
          }
          data.recipientsCount++;
        }
        data.shareTypes[file.share.type] = true;
        delete file.share;
        return memo;
      }, {})
      // Retrieve only the values of the returned hash
      .values()
      // Clean up
      .each(function (data) {
        // convert the recipients map to a flat
        // array of sorted names
        data.mountType = 'shared';
        delete data.recipientsCount;
        if (sharedWithUser) {
          // only for outgoing shares
          delete data.shareTypes;
        } else {
          data.shareTypes = _.keys(data.shareTypes);
        }
      })
      // Finish the chain by getting the result
      .value();

      // Sort by expected sort comparator
      return files.sort(this._sortComparator);
    }
  });

  /**
   * Share info attributes.
   *
   * @typedef {Object} OCA.Sharing.ShareInfo
   *
   * @property {number} id share ID
   * @property {number} type share type
   * @property {String} target share target, either user name or group name
   * @property {number} stime share timestamp in milliseconds
   * @property {String} [targetDisplayName] display name of the recipient
   * (only when shared with others)
   * @property {String} [targetShareWithId] id of the recipient
   *
   */

  /**
   * Recipient attributes
   *
   * @typedef {Object} OCA.Sharing.RecipientInfo
   * @property {String} shareWith the id of the recipient
   * @property {String} shareWithDisplayName the display name of the recipient
   */

  /**
   * Shared file info attributes.
   *
   * @typedef {OCA.Files.FileInfo} OCA.Sharing.SharedFileInfo
   *
   * @property {Array.<OCA.Sharing.ShareInfo>} shares array of shares for
   * this file
   * @property {number} mtime most recent share time (if multiple shares)
   * @property {String} shareOwner name of the share owner
   * @property {Array.<String>} recipients name of the first 4 recipients
   * (this is mostly for display purposes)
   * @property {Object.<OCA.Sharing.RecipientInfo>} recipientData (as object for easier
   * passing to HTML data attributes with jQuery)
   */

  OCA.Sharing.FileList = FileList;
})();

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
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
!function() {
"use strict";
/*!*************************************************!*\
  !*** ./apps/files_sharing/src/files_sharing.js ***!
  \*************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _js_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../js/app */ "./apps/files_sharing/js/app.js");
/* harmony import */ var _js_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_js_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../js/sharedfilelist */ "./apps/files_sharing/js/sharedfilelist.js");
/* harmony import */ var _js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1__);
/**
 * @copyright Copyright (c) 2016 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



}();
/******/ })()
;
//# sourceMappingURL=files_sharing-files_sharing.js.map?v=00a8b3bc29a55cf5228a