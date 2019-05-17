/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/js/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./apps/files_sharing/src/files_sharing.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./apps/files_sharing/js/app.js":
/*!**************************************!*\
  !*** ./apps/files_sharing/js/app.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
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

    this._inFileList.$el.find('#emptycontent').html('<div class="icon-shared"></div>' + '<h2>' + t('files_sharing', 'Nothing shared with you yet') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders others share with you will show up here') + '</p>');

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

    this._outFileList.$el.find('#emptycontent').html('<div class="icon-shared"></div>' + '<h2>' + t('files_sharing', 'Nothing shared yet') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders you share will show up here') + '</p>');

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

    this._linkFileList.$el.find('#emptycontent').html('<div class="icon-public"></div>' + '<h2>' + t('files_sharing', 'No shared links') + '</h2>' + '<p>' + t('files_sharing', 'Files and folders you share by link will show up here') + '</p>');

    return this._linkFileList;
  },
  initSharingDeleted: function initSharingDeleted($el) {
    if (this._deletedFileList) {
      return this._deletedFileList;
    }

    this._deletedFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.deleted',
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

    this._deletedFileList.$el.find('#emptycontent').html('<div class="icon-share"></div>' + '<h2>' + t('files_sharing', 'No deleted shares') + '</h2>' + '<p>' + t('files_sharing', 'Shares you deleted will show up here') + '</p>');

    return this._deletedFileList;
  },
  initShareingOverview: function initShareingOverview($el) {
    if (this._overviewFileList) {
      return this._overviewFileList;
    }

    this._overviewFileList = new OCA.Sharing.FileList($el, {
      id: 'shares.overview',
      config: OCA.Files.App.getFilesConfig(),
      isOverview: true,
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });

    this._extendFileList(this._overviewFileList);

    this._overviewFileList.appName = t('files_sharing', 'Shares');

    this._overviewFileList.$el.find('#emptycontent').html('<div class="icon-share"></div>' + '<h2>' + t('files_sharing', 'No shares') + '</h2>' + '<p>' + t('files_sharing', 'Shares will show up here') + '</p>');

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
    var fileActions = new OCA.Files.FileActions(); // note: not merging the legacy actions because legacy apps are not
    // compatible with the sharing overview and need to be adapted first

    fileActions.registerDefaultActions();
    fileActions.merge(OCA.Files.fileActions);

    if (!this._globalActionsInitialized) {
      // in case actions are registered later
      this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
      OCA.Files.fileActions.on('setDefault.app-sharing', this._onActionsUpdated);
      OCA.Files.fileActions.on('registerAction.app-sharing', this._onActionsUpdated);
      this._globalActionsInitialized = true;
    } // when the user clicks on a folder, redirect to the corresponding
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
      displayName: '',
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
$(document).ready(function () {
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
/*! no static exports found */
/***/ (function(module, exports) {

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
   * @param $el container element with existing markup for the #controls
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

  FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
  /** @lends OCA.Sharing.FileList.prototype */
  {
    appName: 'Shares',

    /**
     * Whether the list shows the files shared with the user (true) or
     * the files that the user shared with others (false).
     */
    _sharedWithUser: false,
    _linksOnly: false,
    _showDeleted: false,
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
      } // TODO: consolidate both options


      if (options && options.sharedWithUser) {
        this._sharedWithUser = true;
      }

      if (options && options.linksOnly) {
        this._linksOnly = true;
      }

      if (options && options.showDeleted) {
        this._showDeleted = true;
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

      if (this._showDeleted) {
        var permission = fileData.permissions;
        $tr.attr('data-share-permissions', permission);
      } // add row with expiration date for link only shares - influenced by _createRow of filelist


      if (this._linksOnly) {
        var expirationTimestamp = 0;

        if (fileData.shares && fileData.shares[0].expiration !== null) {
          expirationTimestamp = moment(fileData.shares[0].expiration).valueOf();
        }

        $tr.attr('data-expiration', expirationTimestamp); // date column (1000 milliseconds to seconds, 60 seconds, 60 minutes, 24 hours)
        // difference in days multiplied by 5 - brightest shade for expiry dates in more than 32 days (160/5)

        var modifiedColor = Math.round((expirationTimestamp - new Date().getTime()) / 1000 / 60 / 60 / 24 * 5); // ensure that the brightest color is still readable

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
          "class": "date"
        });
        td.append($('<span></span>').attr({
          "class": "modified",
          "title": formatted,
          "style": 'color:rgb(' + modifiedColor + ',' + modifiedColor + ',' + modifiedColor + ')'
        }).text(text).tooltip({
          placement: 'top'
        }));
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
        this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
        this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty); // hide expiration date header for non link only shares

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
    updateStorageStatistics: function updateStorageStatistics() {// no op because it doesn't have
      // storage info like free space / used space
    },
    updateRow: function updateRow($tr, fileInfo, options) {
      // no-op, suppress re-rendering
      return $tr;
    },
    reload: function reload() {
      this.showMask();

      if (this._reloadCall) {
        this._reloadCall.abort();
      } // there is only root


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
      }; // Add the proper ajax requests to the list and run them
      // and make sure we have 2 promises

      if (this._showDeleted) {
        promises.push($.ajax(deletedShares));
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
      var files = []; // make sure to use the same format

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
        files = files.concat(this._makeFilesFromShares(additionalShares.ocs.data, !this._sharedWithUser));
      }

      this.setFiles(files);
      return true;
    },
    _makeFilesFromRemoteShares: function _makeFilesFromRemoteShares(data) {
      var files = data;
      files = _.chain(files) // convert share data to file data
      .map(function (share) {
        var file = {
          shareOwner: share.owner + '@' + share.remote.replace(/.*?:\/\//g, ""),
          name: OC.basename(share.mountpoint),
          mtime: share.mtime * 1000,
          mimetype: share.mimetype,
          type: share.type,
          id: share.file_id,
          path: OC.dirname(share.mountpoint),
          permissions: share.permissions,
          tags: share.tags || []
        };
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
     * @param {bool} sharedWithUser
     * @return {Array.<OCA.Sharing.SharedFileInfo>} array of shared file info
     */
    _makeFilesFromShares: function _makeFilesFromShares(data, sharedWithUser) {
      /* jshint camelcase: false */
      var files = data;

      if (this._linksOnly) {
        files = _.filter(data, function (share) {
          return share.share_type === OC.Share.SHARE_TYPE_LINK;
        });
      } // OCS API uses non-camelcased names


      files = _.chain(files) // convert share data to file data
      .map(function (share) {
        // TODO: use OC.Files.FileInfo
        var file = {
          id: share.file_source,
          icon: OC.MimeType.getIconUrl(share.mimetype),
          mimetype: share.mimetype,
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
      }) // Group all files and have a "shares" array with
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
          data.shares = [file.share]; // using a hash to make them unique,
          // this is only a list to be displayed

          data.recipients = {};
          data.recipientData = {}; // share types

          data.shareTypes = {}; // counter is cheaper than calling _.keys().length

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
      }, {}) // Retrieve only the values of the returned hash
      .values() // Clean up
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
      }) // Finish the chain by getting the result
      .value(); // Sort by expected sort comparator

      return files.sort(this._sortComparator);
    }
  });
  /**
   * Share info attributes.
   *
   * @typedef {Object} OCA.Sharing.ShareInfo
   *
   * @property {int} id share ID
   * @property {int} type share type
   * @property {String} target share target, either user name or group name
   * @property {int} stime share timestamp in milliseconds
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
   * @property {int} mtime most recent share time (if multiple shares)
   * @property {String} shareOwner name of the share owner
   * @property {Array.<String>} recipients name of the first 4 recipients
   * (this is mostly for display purposes)
   * @property {Object.<OCA.Sharing.RecipientInfo>} recipientData (as object for easier
   * passing to HTML data attributes with jQuery)
   */

  OCA.Sharing.FileList = FileList;
})();

/***/ }),

/***/ "./apps/files_sharing/src/files_sharing.js":
/*!*************************************************!*\
  !*** ./apps/files_sharing/src/files_sharing.js ***!
  \*************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _js_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../js/app */ "./apps/files_sharing/js/app.js");
/* harmony import */ var _js_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_js_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../js/sharedfilelist */ "./apps/files_sharing/js/sharedfilelist.js");
/* harmony import */ var _js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_js_sharedfilelist__WEBPACK_IMPORTED_MODULE_1__);
__webpack_require__.nc = btoa(OC.requestToken);
__webpack_require__.p = OC.linkTo('files_sharing', 'js/dist/');



/***/ })

/******/ });
//# sourceMappingURL=files_sharing.js.map