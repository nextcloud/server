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
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./core/js/merged-share-backend.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./core/js/merged-share-backend.js":
/*!*****************************************!*\
  !*** ./core/js/merged-share-backend.js ***!
  \*****************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _shareconfigmodel_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./shareconfigmodel.js */ "./core/js/shareconfigmodel.js");
/* harmony import */ var _shareconfigmodel_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_shareconfigmodel_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _sharetemplates_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./sharetemplates.js */ "./core/js/sharetemplates.js");
/* harmony import */ var _sharetemplates_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_sharetemplates_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _shareitemmodel_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./shareitemmodel.js */ "./core/js/shareitemmodel.js");
/* harmony import */ var _shareitemmodel_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_shareitemmodel_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _sharesocialmanager_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./sharesocialmanager.js */ "./core/js/sharesocialmanager.js");
/* harmony import */ var _sharesocialmanager_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_sharesocialmanager_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _sharedialogresharerinfoview_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./sharedialogresharerinfoview.js */ "./core/js/sharedialogresharerinfoview.js");
/* harmony import */ var _sharedialogresharerinfoview_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_sharedialogresharerinfoview_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _sharedialoglinkshareview_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./sharedialoglinkshareview.js */ "./core/js/sharedialoglinkshareview.js");
/* harmony import */ var _sharedialoglinkshareview_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_sharedialoglinkshareview_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _sharedialogshareelistview_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./sharedialogshareelistview.js */ "./core/js/sharedialogshareelistview.js");
/* harmony import */ var _sharedialogshareelistview_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_sharedialogshareelistview_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _sharedialogview_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./sharedialogview.js */ "./core/js/sharedialogview.js");
/* harmony import */ var _sharedialogview_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_sharedialogview_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _share_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./share.js */ "./core/js/share.js");
/* harmony import */ var _share_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_share_js__WEBPACK_IMPORTED_MODULE_8__);










/***/ }),

/***/ "./core/js/share.js":
/*!**************************!*\
  !*** ./core/js/share.js ***!
  \**************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* global escapeHTML */

/**
 * @namespace
 */
OC.Share = _.extend(OC.Share || {}, {
  SHARE_TYPE_USER: 0,
  SHARE_TYPE_GROUP: 1,
  SHARE_TYPE_LINK: 3,
  SHARE_TYPE_EMAIL: 4,
  SHARE_TYPE_REMOTE: 6,
  SHARE_TYPE_CIRCLE: 7,
  SHARE_TYPE_GUEST: 8,
  SHARE_TYPE_REMOTE_GROUP: 9,
  SHARE_TYPE_ROOM: 10,

  /**
   * Regular expression for splitting parts of remote share owners:
   * "user@example.com/path/to/owncloud"
   * "user@anotherexample.com@example.com/path/to/owncloud
   */
  _REMOTE_OWNER_REGEXP: new RegExp("^([^@]*)@(([^@]*)@)?([^/]*)([/](.*)?)?$"),

  /**
   * @deprecated use OC.Share.currentShares instead
   */
  itemShares: [],

  /**
   * Full list of all share statuses
   */
  statuses: {},

  /**
   * Shares for the currently selected file.
   * (for which the dropdown is open)
   *
   * Key is item type and value is an array or
   * shares of the given item type.
   */
  currentShares: {},

  /**
   * Whether the share dropdown is opened.
   */
  droppedDown: false,

  /**
   * Loads ALL share statuses from server, stores them in
   * OC.Share.statuses then calls OC.Share.updateIcons() to update the
   * files "Share" icon to "Shared" according to their share status and
   * share type.
   *
   * If a callback is specified, the update step is skipped.
   *
   * @param itemType item type
   * @param fileList file list instance, defaults to OCA.Files.App.fileList
   * @param callback function to call after the shares were loaded
   */
  loadIcons: function loadIcons(itemType, fileList, callback) {
    var path = fileList.dirInfo.path;

    if (path === '/') {
      path = '';
    }

    path += '/' + fileList.dirInfo.name; // Load all share icons

    $.get(OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares', {
      subfiles: 'true',
      path: path,
      format: 'json'
    }, function (result) {
      if (result && result.ocs.meta.statuscode === 200) {
        OC.Share.statuses = {};
        $.each(result.ocs.data, function (it, share) {
          if (!(share.item_source in OC.Share.statuses)) {
            OC.Share.statuses[share.item_source] = {
              link: false
            };
          }

          if (share.share_type === OC.Share.SHARE_TYPE_LINK) {
            OC.Share.statuses[share.item_source] = {
              link: true
            };
          }
        });

        if (_.isFunction(callback)) {
          callback(OC.Share.statuses);
        } else {
          OC.Share.updateIcons(itemType, fileList);
        }
      }
    });
  },

  /**
   * Updates the files' "Share" icons according to the known
   * sharing states stored in OC.Share.statuses.
   * (not reloaded from server)
   *
   * @param itemType item type
   * @param fileList file list instance
   * defaults to OCA.Files.App.fileList
   */
  updateIcons: function updateIcons(itemType, fileList) {
    var item;
    var $fileList;
    var currentDir;

    if (!fileList && OCA.Files) {
      fileList = OCA.Files.App.fileList;
    } // fileList is usually only defined in the files app


    if (fileList) {
      $fileList = fileList.$fileList;
      currentDir = fileList.getCurrentDirectory();
    } // TODO: iterating over the files might be more efficient


    for (item in OC.Share.statuses) {
      var iconClass = 'icon-shared';
      var data = OC.Share.statuses[item];
      var hasLink = data.link; // Links override shared in terms of icon display

      if (hasLink) {
        iconClass = 'icon-public';
      }

      if (itemType !== 'file' && itemType !== 'folder') {
        $('a.share[data-item="' + item + '"] .icon').removeClass('icon-shared icon-public').addClass(iconClass);
      } else {
        // TODO: ultimately this part should be moved to files_sharing app
        var file = $fileList.find('tr[data-id="' + item + '"]');
        var shareFolder = OC.imagePath('core', 'filetypes/folder-shared');
        var img;

        if (file.length > 0) {
          this.markFileAsShared(file, true, hasLink);
        } else {
          var dir = currentDir;

          if (dir.length > 1) {
            var last = '';
            var path = dir; // Search for possible parent folders that are shared

            while (path != last) {
              if (path === data.path && !data.link) {
                var actions = $fileList.find('.fileactions .action[data-action="Share"]');
                var files = $fileList.find('.filename');
                var i;

                for (i = 0; i < actions.length; i++) {
                  // TODO: use this.markFileAsShared()
                  img = $(actions[i]).find('img');

                  if (img.attr('src') !== OC.imagePath('core', 'actions/public')) {
                    img.attr('src', image);
                    $(actions[i]).addClass('permanent');
                    $(actions[i]).html('<span> ' + t('core', 'Shared') + '</span>').prepend(img);
                  }
                }

                for (i = 0; i < files.length; i++) {
                  if ($(files[i]).closest('tr').data('type') === 'dir') {
                    $(files[i]).find('.thumbnail').css('background-image', 'url(' + shareFolder + ')');
                  }
                }
              }

              last = path;
              path = OC.Share.dirname(path);
            }
          }
        }
      }
    }
  },
  updateIcon: function updateIcon(itemType, itemSource) {
    var shares = false;
    var link = false;
    var iconClass = '';
    $.each(OC.Share.itemShares, function (index) {
      if (OC.Share.itemShares[index]) {
        if (index == OC.Share.SHARE_TYPE_LINK) {
          if (OC.Share.itemShares[index] == true) {
            shares = true;
            iconClass = 'icon-public';
            link = true;
            return;
          }
        } else if (OC.Share.itemShares[index].length > 0) {
          shares = true;
          iconClass = 'icon-shared';
        }
      }
    });

    if (itemType != 'file' && itemType != 'folder') {
      $('a.share[data-item="' + itemSource + '"] .icon').removeClass('icon-shared icon-public').addClass(iconClass);
    } else {
      var $tr = $('tr').filterAttr('data-id', String(itemSource));

      if ($tr.length > 0) {
        // it might happen that multiple lists exist in the DOM
        // with the same id
        $tr.each(function () {
          OC.Share.markFileAsShared($(this), shares, link);
        });
      }
    }

    if (shares) {
      OC.Share.statuses[itemSource] = OC.Share.statuses[itemSource] || {};
      OC.Share.statuses[itemSource].link = link;
    } else {
      delete OC.Share.statuses[itemSource];
    }
  },

  /**
   * Format a remote address
   *
   * @param {String} shareWith userid, full remote share, or whatever
   * @param {String} shareWithDisplayName
   * @param {String} message
   * @return {String} HTML code to display
   */
  _formatRemoteShare: function _formatRemoteShare(shareWith, shareWithDisplayName, message) {
    var parts = this._REMOTE_OWNER_REGEXP.exec(shareWith);

    if (!parts) {
      // display avatar of the user
      var avatar = '<span class="avatar" data-username="' + escapeHTML(shareWith) + '" title="' + message + " " + escapeHTML(shareWithDisplayName) + '"></span>';
      var hidden = '<span class="hidden-visually">' + message + ' ' + escapeHTML(shareWithDisplayName) + '</span> ';
      return avatar + hidden;
    }

    var userName = parts[1];
    var userDomain = parts[3];
    var server = parts[4];
    var tooltip = message + ' ' + userName;

    if (userDomain) {
      tooltip += '@' + userDomain;
    }

    if (server) {
      if (!userDomain) {
        userDomain = '…';
      }

      tooltip += '@' + server;
    }

    var html = '<span class="remoteAddress" title="' + escapeHTML(tooltip) + '">';
    html += '<span class="username">' + escapeHTML(userName) + '</span>';

    if (userDomain) {
      html += '<span class="userDomain">@' + escapeHTML(userDomain) + '</span>';
    }

    html += '</span> ';
    return html;
  },

  /**
   * Loop over all recipients in the list and format them using
   * all kind of fancy magic.
   *
   * @param {Object} recipients array of all the recipients
   * @return {String[]} modified list of recipients
   */
  _formatShareList: function _formatShareList(recipients) {
    var _parent = this;

    recipients = _.toArray(recipients);
    recipients.sort(function (a, b) {
      return a.shareWithDisplayName.localeCompare(b.shareWithDisplayName);
    });
    return $.map(recipients, function (recipient) {
      return _parent._formatRemoteShare(recipient.shareWith, recipient.shareWithDisplayName, t('core', 'Shared with'));
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
  markFileAsShared: function markFileAsShared($tr, hasShares, hasLink) {
    var action = $tr.find('.fileactions .action[data-action="Share"]');
    var type = $tr.data('type');
    var icon = action.find('.icon');
    var message, recipients, avatars;
    var ownerId = $tr.attr('data-share-owner-id');
    var owner = $tr.attr('data-share-owner');
    var shareFolderIcon;
    var iconClass = 'icon-shared';
    action.removeClass('shared-style'); // update folder icon

    if (type === 'dir' && (hasShares || hasLink || ownerId)) {
      if (hasLink) {
        shareFolderIcon = OC.MimeType.getIconUrl('dir-public');
      } else {
        shareFolderIcon = OC.MimeType.getIconUrl('dir-shared');
      }

      $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
      $tr.attr('data-icon', shareFolderIcon);
    } else if (type === 'dir') {
      var isEncrypted = $tr.attr('data-e2eencrypted');
      var mountType = $tr.attr('data-mounttype'); // FIXME: duplicate of FileList._createRow logic for external folder,
      // need to refactor the icon logic into a single code path eventually

      if (isEncrypted === 'true') {
        shareFolderIcon = OC.MimeType.getIconUrl('dir-encrypted');
        $tr.attr('data-icon', shareFolderIcon);
      } else if (mountType && mountType.indexOf('external') === 0) {
        shareFolderIcon = OC.MimeType.getIconUrl('dir-external');
        $tr.attr('data-icon', shareFolderIcon);
      } else {
        shareFolderIcon = OC.MimeType.getIconUrl('dir'); // back to default

        $tr.removeAttr('data-icon');
      }

      $tr.find('.filename .thumbnail').css('background-image', 'url(' + shareFolderIcon + ')');
    } // update share action text / icon


    if (hasShares || ownerId) {
      recipients = $tr.data('share-recipient-data');
      action.addClass('shared-style');
      avatars = '<span>' + t('core', 'Shared') + '</span>'; // even if reshared, only show "Shared by"

      if (ownerId) {
        message = t('core', 'Shared by');
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
        action.find('span[title]').tooltip({
          placement: 'top'
        });
      }
    } else {
      action.html('<span class="hidden-visually">' + t('core', 'Shared') + '</span>').prepend(icon);
    }

    if (hasLink) {
      iconClass = 'icon-public';
    }

    icon.removeClass('icon-shared icon-public').addClass(iconClass);
  },
  showDropDown: function showDropDown(itemType, itemSource, appendTo, link, possiblePermissions, filename) {
    var configModel = new OC.Share.ShareConfigModel();
    var attributes = {
      itemType: itemType,
      itemSource: itemSource,
      possiblePermissions: possiblePermissions
    };
    var itemModel = new OC.Share.ShareItemModel(attributes, {
      configModel: configModel
    });
    var dialogView = new OC.Share.ShareDialogView({
      id: 'dropdown',
      model: itemModel,
      configModel: configModel,
      className: 'drop shareDropDown',
      attributes: {
        'data-item-source-name': filename,
        'data-item-type': itemType,
        'data-item-source': itemSource
      }
    });
    dialogView.setShowLink(link);
    var $dialog = dialogView.render().$el;
    $dialog.appendTo(appendTo);
    $dialog.slideDown(OC.menuSpeed, function () {
      OC.Share.droppedDown = true;
    });
    itemModel.fetch();
  },
  hideDropDown: function hideDropDown(callback) {
    OC.Share.currentShares = null;
    $('#dropdown').slideUp(OC.menuSpeed, function () {
      OC.Share.droppedDown = false;
      $('#dropdown').remove();

      if (typeof FileActions !== 'undefined') {
        $('tr').removeClass('mouseOver');
      }

      if (callback) {
        callback.call();
      }
    });
  },
  dirname: function dirname(path) {
    return path.replace(/\\/g, '/').replace(/\/[^\/]*$/, '');
  }
});
$(document).ready(function () {
  if (typeof monthNames != 'undefined') {
    // min date should always be the next day
    var minDate = new Date();
    minDate.setDate(minDate.getDate() + 1);
    $.datepicker.setDefaults({
      monthNames: monthNames,
      monthNamesShort: monthNamesShort,
      dayNames: dayNames,
      dayNamesMin: dayNamesMin,
      dayNamesShort: dayNamesShort,
      firstDay: firstDay,
      minDate: minDate
    });
  }

  $(this).click(function (event) {
    var target = $(event.target);
    var isMatched = !target.is('.drop, .ui-datepicker-next, .ui-datepicker-prev, .ui-icon') && !target.closest('#ui-datepicker-div').length && !target.closest('.ui-autocomplete').length;

    if (OC.Share && OC.Share.droppedDown && isMatched && $('#dropdown').has(event.target).length === 0) {
      OC.Share.hideDropDown();
    }
  });
});

/***/ }),

/***/ "./core/js/shareconfigmodel.js":
/*!*************************************!*\
  !*** ./core/js/shareconfigmodel.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global moment, oc_appconfig, oc_config */
(function () {
  if (!OC.Share) {
    OC.Share = {};
    OC.Share.Types = {};
  } // FIXME: the config model should populate its own model attributes based on
  // the old DOM-based config


  var ShareConfigModel = OC.Backbone.Model.extend({
    defaults: {
      publicUploadEnabled: false,
      enforcePasswordForPublicLink: oc_appconfig.core.enforcePasswordForPublicLink,
      enableLinkPasswordByDefault: oc_appconfig.core.enableLinkPasswordByDefault,
      isDefaultExpireDateEnforced: oc_appconfig.core.defaultExpireDateEnforced === true,
      isDefaultExpireDateEnabled: oc_appconfig.core.defaultExpireDateEnabled === true,
      isRemoteShareAllowed: oc_appconfig.core.remoteShareAllowed,
      isMailShareAllowed: oc_appconfig.shareByMailEnabled !== undefined,
      defaultExpireDate: oc_appconfig.core.defaultExpireDate,
      isResharingAllowed: oc_appconfig.core.resharingAllowed,
      isPasswordForMailSharesRequired: oc_appconfig.shareByMail === undefined ? false : oc_appconfig.shareByMail.enforcePasswordProtection,
      allowGroupSharing: oc_appconfig.core.allowGroupSharing
    },

    /**
     * @returns {boolean}
     */
    isPublicUploadEnabled: function isPublicUploadEnabled() {
      var publicUploadEnabled = $('#filestable').data('allow-public-upload');
      return publicUploadEnabled === 'yes';
    },

    /**
     * @returns {boolean}
     */
    isShareWithLinkAllowed: function isShareWithLinkAllowed() {
      return $('#allowShareWithLink').val() === 'yes';
    },

    /**
     * @returns {string}
     */
    getFederatedShareDocLink: function getFederatedShareDocLink() {
      return oc_appconfig.core.federatedCloudShareDoc;
    },
    getDefaultExpirationDateString: function getDefaultExpirationDateString() {
      var expireDateString = '';

      if (this.get('isDefaultExpireDateEnabled')) {
        var date = moment.utc();
        var expireAfterDays = this.get('defaultExpireDate');
        date.add(expireAfterDays, 'days');
        expireDateString = date.format('YYYY-MM-DD 00:00:00');
      }

      return expireDateString;
    }
  });
  OC.Share.ShareConfigModel = ShareConfigModel;
})();

/***/ }),

/***/ "./core/js/sharedialoglinkshareview.js":
/*!*********************************************!*\
  !*** ./core/js/sharedialoglinkshareview.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Clipboard, Handlebars */
(function () {
  if (!OC.Share) {
    OC.Share = {};
  }

  var PASSWORD_PLACEHOLDER = '**********';
  var PASSWORD_PLACEHOLDER_MESSAGE = t('core', 'Choose a password for the public link');
  var PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL = t('core', 'Choose a password for the public link or press the "Enter" key');
  /**
   * @class OCA.Share.ShareDialogLinkShareView
   * @member {OC.Share.ShareItemModel} model
   * @member {jQuery} $el
   * @memberof OCA.Sharing
   * @classdesc
   *
   * Represents the GUI of the share dialogue
   *
   */

  var ShareDialogLinkShareView = OC.Backbone.View.extend({
    /** @type {string} **/
    id: 'shareDialogLinkShare',

    /** @type {OC.Share.ShareConfigModel} **/
    configModel: undefined,

    /** @type {boolean} **/
    showLink: true,

    /** @type {boolean} **/
    showPending: false,

    /** @type {string} **/
    password: '',

    /** @type {string} **/
    newShareId: 'new-share',
    events: {
      // open menu
      'click .share-menu .icon-more': 'onToggleMenu',
      // hide download
      'change .hideDownloadCheckbox': 'onHideDownloadChange',
      // password
      'click input.share-pass-submit': 'onPasswordEntered',
      'keyup input.linkPassText': 'onPasswordKeyUp',
      // check for the enter key
      'change .showPasswordCheckbox': 'onShowPasswordClick',
      'change .passwordByTalkCheckbox': 'onPasswordByTalkChange',
      'change .publicEditingCheckbox': 'onAllowPublicEditingChange',
      // copy link url
      'click .linkText': 'onLinkTextClick',
      // social
      'click .pop-up': 'onPopUpClick',
      // permission change
      'change .publicUploadRadio': 'onPublicUploadChange',
      // expire date
      'click .expireDate': 'onExpireDateChange',
      'change .datepicker': 'onChangeExpirationDate',
      'click .datepicker': 'showDatePicker',
      // note
      'click .share-add': 'showNoteForm',
      'click .share-note-delete': 'deleteNote',
      'click .share-note-submit': 'updateNote',
      // remove
      'click .unshare': 'onUnshare',
      // new share
      'click .new-share': 'newShare',
      // enforced pass set
      'submit .enforcedPassForm': 'enforcedPasswordSet'
    },
    initialize: function initialize(options) {
      var view = this;
      this.model.on('change:permissions', function () {
        view.render();
      });
      this.model.on('change:itemType', function () {
        view.render();
      });
      this.model.on('change:allowPublicUploadStatus', function () {
        view.render();
      });
      this.model.on('change:hideFileListStatus', function () {
        view.render();
      });
      this.model.on('change:linkShares', function (model, linkShares) {
        // The "Password protect by Talk" item is shown only when there
        // is a password. Unfortunately there is no fine grained
        // rendering of items in the link shares, so the whole view
        // needs to be rendered again when the password of a share
        // changes.
        // Note that this event handler is concerned only about password
        // changes; other changes in the link shares does not trigger
        // a rendering, so the view must be rendered again as needed in
        // those cases (for example, when a link share is removed).
        var previousLinkShares = model.previous('linkShares');

        if (previousLinkShares.length !== linkShares.length) {
          return;
        }

        var i;

        for (i = 0; i < linkShares.length; i++) {
          if (linkShares[i].id !== previousLinkShares[i].id) {
            // A resorting should never happen, but just in case.
            return;
          }

          if (linkShares[i].password !== previousLinkShares[i].password) {
            view.render();
            return;
          }
        }
      });

      if (!_.isUndefined(options.configModel)) {
        this.configModel = options.configModel;
      } else {
        throw 'missing OC.Share.ShareConfigModel';
      }

      var clipboard = new Clipboard('.clipboard-button');
      clipboard.on('success', function (e) {
        var $trigger = $(e.trigger);
        $trigger.tooltip('hide').attr('data-original-title', t('core', 'Copied!')).tooltip('fixTitle').tooltip({
          placement: 'bottom',
          trigger: 'manual'
        }).tooltip('show');

        _.delay(function () {
          $trigger.tooltip('hide').attr('data-original-title', t('core', 'Copy link')).tooltip('fixTitle');
        }, 3000);
      });
      clipboard.on('error', function (e) {
        var $trigger = $(e.trigger);
        var $menu = $trigger.next('.share-menu').find('.popovermenu');
        var $linkTextMenu = $menu.find('li.linkTextMenu');
        var $input = $linkTextMenu.find('.linkText');
        var $li = $trigger.closest('li[data-share-id]');
        var shareId = $li.data('share-id'); // show menu

        OC.showMenu(null, $menu);
        var actionMsg = '';

        if (/iPhone|iPad/i.test(navigator.userAgent)) {
          actionMsg = t('core', 'Not supported!');
        } else if (/Mac/i.test(navigator.userAgent)) {
          actionMsg = t('core', 'Press ⌘-C to copy.');
        } else {
          actionMsg = t('core', 'Press Ctrl-C to copy.');
        }

        $linkTextMenu.removeClass('hidden');
        $input.select();
        $input.tooltip('hide').attr('data-original-title', actionMsg).tooltip('fixTitle').tooltip({
          placement: 'bottom',
          trigger: 'manual'
        }).tooltip('show');

        _.delay(function () {
          $input.tooltip('hide');
          $input.attr('data-original-title', t('core', 'Copy')).tooltip('fixTitle');
        }, 3000);
      });
    },
    newShare: function newShare(event) {
      var self = this;
      var $target = $(event.target);
      var $li = $target.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $loading = $li.find('.share-menu > .icon-loading-small');

      if (!$loading.hasClass('hidden') && this.password === '') {
        // in process
        return false;
      } // hide all icons and show loading


      $li.find('.icon').addClass('hidden');
      $loading.removeClass('hidden'); // hide menu

      OC.hideMenus();
      var shareData = {};
      var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
      var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced'); // set default expire date

      if (isExpirationEnforced) {
        var defaultExpireDays = this.configModel.get('defaultExpireDate');
        var expireDate = moment().add(defaultExpireDays, 'day').format('DD-MM-YYYY');
        shareData.expireDate = expireDate;
      } // if password is set, add to data


      if (isPasswordEnforced && this.password !== '') {
        shareData.password = this.password;
      }

      var newShareId = false; // We need a password before the share creation

      if (isPasswordEnforced && !this.showPending && this.password === '') {
        this.showPending = shareId;
        var self = this.render();
        self.$el.find('.pending #enforcedPassText').focus();
      } else {
        // else, we have a password or it is not enforced
        $.when(this.model.saveLinkShare(shareData, {
          success: function success() {
            $loading.addClass('hidden');
            $li.find('.icon').removeClass('hidden');
            self.render(); // open the menu by default
            // we can only do that after the render

            if (newShareId) {
              var shares = self.$el.find('li[data-share-id]');
              var $newShare = self.$el.find('li[data-share-id="' + newShareId + '"]'); // only open the menu by default if this is the first share

              if ($newShare && shares.length === 1) {
                var $menu = $newShare.find('.popovermenu');
                OC.showMenu(null, $menu);
              }
            }
          },
          error: function error() {// empty function to override the default Dialog warning
          }
        })).fail(function (response) {
          // password failure? Show error
          self.password = '';

          if (isPasswordEnforced && response && response.responseJSON && response.responseJSON.ocs.meta && response.responseJSON.ocs.meta.message) {
            var $input = self.$el.find('.pending #enforcedPassText');
            $input.tooltip('destroy');
            $input.attr('title', response.responseJSON.ocs.meta.message);
            $input.tooltip({
              placement: 'bottom',
              trigger: 'manual'
            });
            $input.tooltip('show');
          } else {
            OC.Notification.showTemporary(t('core', 'Unable to create a link share'));
            $loading.addClass('hidden');
            $li.find('.icon').removeClass('hidden');
          }
        }).then(function (response) {
          // resolve before success
          newShareId = response.ocs.data.id;
        });
      }
    },
    enforcedPasswordSet: function enforcedPasswordSet(event) {
      event.preventDefault();
      var $form = $(event.target);
      var $input = $form.find('input.enforcedPassText');
      this.password = $input.val();
      this.showPending = false;
      this.newShare(event);
    },
    onLinkTextClick: function onLinkTextClick(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var $el = $li.find('.linkText');
      $el.focus();
      $el.select();
    },
    onHideDownloadChange: function onHideDownloadChange(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $checkbox = $li.find('.hideDownloadCheckbox');
      $checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');
      var hideDownload = false;

      if ($checkbox.is(':checked')) {
        hideDownload = true;
      }

      this.model.saveLinkShare({
        hideDownload: hideDownload,
        cid: shareId
      }, {
        success: function success() {
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        },
        error: function error(obj, msg) {
          OC.Notification.showTemporary(t('core', 'Unable to toggle this option'));
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        }
      });
    },
    onShowPasswordClick: function onShowPasswordClick(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      $li.find('.linkPass').slideToggle(OC.menuSpeed);
      $li.find('.linkPassMenu').toggleClass('hidden');

      if (!$li.find('.showPasswordCheckbox').is(':checked')) {
        this.model.saveLinkShare({
          password: '',
          cid: shareId
        });
      } else {
        if (!OC.Util.isIE()) {
          $li.find('.linkPassText').focus();
        }
      }
    },
    onPasswordKeyUp: function onPasswordKeyUp(event) {
      if (event.keyCode === 13) {
        this.onPasswordEntered(event);
      }
    },
    onPasswordEntered: function onPasswordEntered(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $loading = $li.find('.linkPassMenu .icon-loading-small');

      if (!$loading.hasClass('hidden')) {
        // still in process
        return;
      }

      var $input = $li.find('.linkPassText');
      $input.removeClass('error');
      var password = $input.val();

      if ($li.find('.linkPassText').attr('placeholder') === PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL) {
        // in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
        if (password === PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL) {
          password = '';
        }
      } else {
        // in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
        if (password === '' || password === PASSWORD_PLACEHOLDER || password === PASSWORD_PLACEHOLDER_MESSAGE) {
          return;
        }
      }

      $loading.removeClass('hidden').addClass('inlineblock');
      this.model.saveLinkShare({
        password: password,
        cid: shareId
      }, {
        complete: function complete(model) {
          $loading.removeClass('inlineblock').addClass('hidden');
        },
        error: function error(model, msg) {
          // destroy old tooltips
          var $container = $input.parent();
          $container.tooltip('destroy');
          $input.addClass('error');
          $container.attr('title', msg);
          $container.tooltip({
            placement: 'bottom',
            trigger: 'manual'
          });
          $container.tooltip('show');
        }
      });
    },
    onPasswordByTalkChange: function onPasswordByTalkChange(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $checkbox = $li.find('.passwordByTalkCheckbox');
      $checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');
      var sendPasswordByTalk = false;

      if ($checkbox.is(':checked')) {
        sendPasswordByTalk = true;
      }

      this.model.saveLinkShare({
        sendPasswordByTalk: sendPasswordByTalk,
        cid: shareId
      }, {
        success: function success() {
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        },
        error: function error(obj, msg) {
          OC.Notification.showTemporary(t('core', 'Unable to toggle this option'));
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        }
      });
    },
    onAllowPublicEditingChange: function onAllowPublicEditingChange(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $checkbox = $li.find('.publicEditingCheckbox');
      $checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');
      var permissions = OC.PERMISSION_READ;

      if ($checkbox.is(':checked')) {
        permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
      }

      this.model.saveLinkShare({
        permissions: permissions,
        cid: shareId
      }, {
        success: function success() {
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        },
        error: function error(obj, msg) {
          OC.Notification.showTemporary(t('core', 'Unable to toggle this option'));
          $checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
        }
      });
    },
    onPublicUploadChange: function onPublicUploadChange(event) {
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var permissions = event.currentTarget.value;
      this.model.saveLinkShare({
        permissions: permissions,
        cid: shareId
      });
    },
    showNoteForm: function showNoteForm(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var $menu = $element.closest('li');
      var $form = $menu.next('li.share-note-form'); // show elements

      $menu.find('.share-note-delete').toggleClass('hidden');
      $form.toggleClass('hidden');
      $form.find('textarea').focus();
    },
    deleteNote: function deleteNote(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $menu = $element.closest('li');
      var $form = $menu.next('li.share-note-form');
      $form.find('.share-note').val('');
      $form.addClass('hidden');
      $menu.find('.share-note-delete').addClass('hidden');
      self.sendNote('', shareId, $menu);
    },
    updateNote: function updateNote(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $form = $element.closest('li.share-note-form');
      var $menu = $form.prev('li');
      var message = $form.find('.share-note').val().trim();

      if (message.length < 1) {
        return;
      }

      self.sendNote(message, shareId, $menu);
    },
    sendNote: function sendNote(note, shareId, $menu) {
      var $form = $menu.next('li.share-note-form');
      var $submit = $form.find('input.share-note-submit');
      var $error = $form.find('input.share-note-error');
      $submit.prop('disabled', true);
      $menu.find('.icon-loading-small').removeClass('hidden');
      $menu.find('.icon-edit').hide();

      var complete = function complete() {
        $submit.prop('disabled', false);
        $menu.find('.icon-loading-small').addClass('hidden');
        $menu.find('.icon-edit').show();
      };

      var error = function error() {
        $error.show();
        setTimeout(function () {
          $error.hide();
        }, 3000);
      }; // send data


      $.ajax({
        method: 'PUT',
        url: OC.linkToOCS('apps/files_sharing/api/v1/shares', 2) + shareId + '?' + OC.buildQueryString({
          format: 'json'
        }),
        data: {
          note: note
        },
        complete: complete,
        error: error
      });
    },
    render: function render() {
      this.$el.find('.has-tooltip').tooltip(); // reset previously set passwords

      this.password = '';
      var linkShareTemplate = this.template();
      var resharingAllowed = this.model.sharePermissionPossible();

      if (!resharingAllowed || !this.showLink || !this.configModel.isShareWithLinkAllowed()) {
        var templateData = {
          shareAllowed: false
        };

        if (!resharingAllowed) {
          // add message
          templateData.noSharingPlaceholder = t('core', 'Resharing is not allowed');
        }

        this.$el.html(linkShareTemplate(templateData));
        return this;
      }

      var publicUpload = this.model.isFolder() && this.model.createPermissionPossible() && this.configModel.isPublicUploadEnabled();
      var publicEditingChecked = '';

      if (this.model.isPublicEditingAllowed()) {
        publicEditingChecked = 'checked="checked"';
      }

      var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
      var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
      var passwordPlaceholderInitial = this.configModel.get('enforcePasswordForPublicLink') ? PASSWORD_PLACEHOLDER_MESSAGE : PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL;
      var publicEditable = !this.model.isFolder() && this.model.updatePermissionPossible();
      var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced'); // what if there is another date picker on that page?

      var minDate = new Date(); // min date should always be the next day

      minDate.setDate(minDate.getDate() + 1);
      $.datepicker.setDefaults({
        minDate: minDate
      });
      this.$el.find('.datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
      });
      var minPasswordLength = 4; // password policy?

      if (oc_capabilities.password_policy && oc_capabilities.password_policy.minLength) {
        minPasswordLength = oc_capabilities.password_policy.minLength;
      }

      var popoverBase = {
        urlLabel: t('core', 'Link'),
        hideDownloadLabel: t('core', 'Hide download'),
        enablePasswordLabel: isPasswordEnforced ? t('core', 'Password protection enforced') : t('core', 'Password protect'),
        passwordLabel: t('core', 'Password'),
        passwordPlaceholderInitial: passwordPlaceholderInitial,
        publicUpload: publicUpload,
        publicEditing: publicEditable,
        publicEditingChecked: publicEditingChecked,
        publicEditingLabel: t('core', 'Allow editing'),
        mailPrivatePlaceholder: t('core', 'Email link to person'),
        mailButtonText: t('core', 'Send'),
        publicUploadRWLabel: t('core', 'Allow upload and editing'),
        publicUploadRLabel: t('core', 'Read only'),
        publicUploadWLabel: t('core', 'File drop (upload only)'),
        publicUploadRWValue: OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
        publicUploadRValue: OC.PERMISSION_READ,
        publicUploadWValue: OC.PERMISSION_CREATE,
        expireDateLabel: isExpirationEnforced ? t('core', 'Expiration date enforced') : t('core', 'Set expiration date'),
        expirationLabel: t('core', 'Expiration'),
        expirationDatePlaceholder: t('core', 'Expiration date'),
        isExpirationEnforced: isExpirationEnforced,
        isPasswordEnforced: isPasswordEnforced,
        defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'),
        // Can't expire today
        addNoteLabel: t('core', 'Note to recipient'),
        unshareLabel: t('core', 'Unshare'),
        unshareLinkLabel: t('core', 'Delete share link'),
        newShareLabel: t('core', 'Add another link')
      };
      var pendingPopover = {
        isPasswordEnforced: isPasswordEnforced,
        enforcedPasswordLabel: t('core', 'Password protection for links is mandatory'),
        passwordPlaceholder: passwordPlaceholderInitial,
        minPasswordLength: minPasswordLength
      };
      var pendingPopoverMenu = this.pendingPopoverMenuTemplate(_.extend({}, pendingPopover));
      var linkShares = this.getShareeList();

      if (_.isArray(linkShares)) {
        for (var i = 0; i < linkShares.length; i++) {
          var social = [];
          OC.Share.Social.Collection.each(function (model) {
            var url = model.get('url');
            url = url.replace('{{reference}}', linkShares[i].shareLinkURL);
            social.push({
              url: url,
              label: t('core', 'Share to {name}', {
                name: model.get('name')
              }),
              name: model.get('name'),
              iconClass: model.get('iconClass'),
              newWindow: model.get('newWindow')
            });
          });
          var popover = this.getPopoverObject(linkShares[i]);
          linkShares[i].popoverMenu = this.popoverMenuTemplate(_.extend({}, popoverBase, popover, {
            social: social
          }));
          linkShares[i].pendingPopoverMenu = pendingPopoverMenu;
        }
      }

      this.$el.html(linkShareTemplate({
        linkShares: linkShares,
        shareAllowed: true,
        nolinkShares: linkShares.length === 0,
        newShareLabel: t('core', 'Share link'),
        newShareTitle: t('core', 'New share link'),
        pendingPopoverMenu: pendingPopoverMenu,
        showPending: this.showPending === this.newShareId,
        newShareId: this.newShareId
      }));
      this.delegateEvents(); // new note autosize

      autosize(this.$el.find('.share-note-form .share-note'));
      return this;
    },
    onToggleMenu: function onToggleMenu(event) {
      event.preventDefault();
      event.stopPropagation();
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var $menu = $li.find('.sharingOptionsGroup .popovermenu');
      var shareId = $li.data('share-id');
      OC.showMenu(null, $menu); // focus the password if not set and enforced

      var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
      var haspassword = $menu.find('.linkPassText').val() !== '';

      if (!haspassword && isPasswordEnabledByDefault) {
        $menu.find('.linkPassText').focus();
      }
    },

    /**
     * @returns {Function} from Handlebars
     * @private
     */
    template: function template() {
      return OC.Share.Templates['sharedialoglinkshareview'];
    },

    /**
     * renders the popover template and returns the resulting HTML
     *
     * @param {Object} data
     * @returns {string}
     */
    popoverMenuTemplate: function popoverMenuTemplate(data) {
      return OC.Share.Templates['sharedialoglinkshareview_popover_menu'](data);
    },

    /**
     * renders the pending popover template and returns the resulting HTML
     *
     * @param {Object} data
     * @returns {string}
     */
    pendingPopoverMenuTemplate: function pendingPopoverMenuTemplate(data) {
      return OC.Share.Templates['sharedialoglinkshareview_popover_menu_pending'](data);
    },
    onPopUpClick: function onPopUpClick(event) {
      event.preventDefault();
      event.stopPropagation();
      var url = $(event.currentTarget).data('url');
      var newWindow = $(event.currentTarget).data('window');
      $(event.currentTarget).tooltip('hide');

      if (url) {
        if (newWindow === true) {
          var width = 600;
          var height = 400;
          var left = screen.width / 2 - width / 2;
          var top = screen.height / 2 - height / 2;
          window.open(url, 'name', 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
        } else {
          window.location.href = url;
        }
      }
    },
    onExpireDateChange: function onExpireDateChange(event) {
      var $element = $(event.target);
      var li = $element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var expirationDatePicker = '#expirationDateContainer-' + shareId;
      var datePicker = $(expirationDatePicker);
      var state = $element.prop('checked');
      datePicker.toggleClass('hidden', !state);

      if (!state) {
        // disabled, let's hide the input and
        // set the expireDate to nothing
        $element.closest('li').next('li').addClass('hidden');
        this.setExpirationDate('', shareId);
      } else {
        // enabled, show the input and the datepicker
        $element.closest('li').next('li').removeClass('hidden');
        this.showDatePicker(event);
      }
    },
    showDatePicker: function showDatePicker(event) {
      var $element = $(event.target);
      var li = $element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var maxDate = $element.data('max-date');
      var expirationDatePicker = '#expirationDatePicker-' + shareId;
      var self = this;
      $(expirationDatePicker).datepicker({
        dateFormat: 'dd-mm-yy',
        onSelect: function onSelect(expireDate) {
          self.setExpirationDate(expireDate, shareId);
        },
        maxDate: maxDate
      });
      $(expirationDatePicker).datepicker('show');
      $(expirationDatePicker).focus();
    },
    setExpirationDate: function setExpirationDate(expireDate, shareId) {
      this.model.saveLinkShare({
        expireDate: expireDate,
        cid: shareId
      });
    },
    onChangeExpirationDate: function onChangeExpirationDate(event) {
      var $element = $(event.target);
      var expireDate = $element.val();
      var li = $element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var expirationDatePicker = '#expirationDatePicker-' + shareId;
      this.setExpirationDate(expireDate, shareId);
      $(expirationDatePicker).datepicker('hide');
    },

    /**
     * get an array of sharees' share properties
     *
     * @returns {Array}
     */
    getShareeList: function getShareeList() {
      var shares = this.model.get('linkShares');

      if (!this.model.hasLinkShares()) {
        return [];
      }

      var list = [];

      for (var index = 0; index < shares.length; index++) {
        var share = this.getShareeObject(index); // first empty {} is necessary, otherwise we get in trouble
        // with references

        list.push(_.extend({}, share));
      }

      return list;
    },

    /**
     *
     * @param {OC.Share.Types.ShareInfo} shareInfo
     * @returns {object}
     */
    getShareeObject: function getShareeObject(shareIndex) {
      var share = this.model.get('linkShares')[shareIndex];
      return _.extend({}, share, {
        cid: share.id,
        shareAllowed: true,
        linkShareLabel: share.label ? share.label : t('core', 'Share link'),
        popoverMenu: {},
        shareLinkURL: share.url,
        newShareTitle: t('core', 'New share link'),
        copyLabel: t('core', 'Copy link'),
        showPending: this.showPending === share.id,
        linkShareCreationDate: t('core', 'Created on {time}', {
          time: moment(share.stime * 1000).format('LLLL')
        })
      });
    },
    getPopoverObject: function getPopoverObject(share) {
      var publicUploadRWChecked = '';
      var publicUploadRChecked = '';
      var publicUploadWChecked = '';

      switch (this.model.linkSharePermissions(share.id)) {
        case OC.PERMISSION_READ:
          publicUploadRChecked = 'checked';
          break;

        case OC.PERMISSION_CREATE:
          publicUploadWChecked = 'checked';
          break;

        case OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE:
          publicUploadRWChecked = 'checked';
          break;
      }

      var isPasswordSet = !!share.password;
      var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
      var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
      var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');
      var defaultExpireDays = this.configModel.get('defaultExpireDate');
      var hasExpireDate = !!share.expiration || isExpirationEnforced;
      var expireDate;

      if (hasExpireDate) {
        expireDate = moment(share.expiration, 'YYYY-MM-DD').format('DD-MM-YYYY');
      }

      var isTalkEnabled = oc_appswebroots['spreed'] !== undefined;
      var sendPasswordByTalk = share.sendPasswordByTalk;
      var hideDownload = share.hideDownload;
      var maxDate = null;

      if (hasExpireDate) {
        if (isExpirationEnforced) {
          // TODO: hack: backend returns string instead of integer
          var shareTime = share.stime;

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

      return {
        cid: share.id,
        shareLinkURL: share.url,
        passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
        isPasswordSet: isPasswordSet || isPasswordEnabledByDefault || isPasswordEnforced,
        showPasswordByTalkCheckBox: isTalkEnabled && isPasswordSet,
        passwordByTalkLabel: t('core', 'Password protect by Talk'),
        isPasswordByTalkSet: sendPasswordByTalk,
        publicUploadRWChecked: publicUploadRWChecked,
        publicUploadRChecked: publicUploadRChecked,
        publicUploadWChecked: publicUploadWChecked,
        hasExpireDate: hasExpireDate,
        expireDate: expireDate,
        shareNote: share.note,
        hasNote: share.note !== '',
        maxDate: maxDate,
        hideDownload: hideDownload,
        isExpirationEnforced: isExpirationEnforced
      };
    },
    onUnshare: function onUnshare(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);

      if (!$element.is('a')) {
        $element = $element.closest('a');
      }

      var $loading = $element.find('.icon-loading-small').eq(0);

      if (!$loading.hasClass('hidden')) {
        // in process
        return false;
      }

      $loading.removeClass('hidden');
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      self.model.removeShare(shareId, {
        success: function success() {
          $li.remove();
          self.render();
        },
        error: function error() {
          $loading.addClass('hidden');
          OC.Notification.showTemporary(t('core', 'Could not unshare'));
        }
      });
      return false;
    }
  });
  OC.Share.ShareDialogLinkShareView = ShareDialogLinkShareView;
})();

/***/ }),

/***/ "./core/js/sharedialogresharerinfoview.js":
/*!************************************************!*\
  !*** ./core/js/sharedialogresharerinfoview.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Handlebars */
(function () {
  if (!OC.Share) {
    OC.Share = {};
  }
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


  var ShareDialogResharerInfoView = OC.Backbone.View.extend({
    /** @type {string} **/
    id: 'shareDialogResharerInfo',

    /** @type {string} **/
    tagName: 'div',

    /** @type {string} **/
    className: 'reshare',

    /** @type {OC.Share.ShareConfigModel} **/
    configModel: undefined,

    /** @type {Function} **/
    _template: undefined,
    initialize: function initialize(options) {
      var view = this;
      this.model.on('change:reshare', function () {
        view.render();
      });

      if (!_.isUndefined(options.configModel)) {
        this.configModel = options.configModel;
      } else {
        throw 'missing OC.Share.ShareConfigModel';
      }
    },
    render: function render() {
      if (!this.model.hasReshare() || this.model.getReshareOwner() === OC.currentUser) {
        this.$el.empty();
        return this;
      }

      var reshareTemplate = this.template();
      var ownerDisplayName = this.model.getReshareOwnerDisplayname();
      var shareNote = this.model.getReshareNote();
      var sharedByText = '';

      if (this.model.getReshareType() === OC.Share.SHARE_TYPE_GROUP) {
        sharedByText = t('core', 'Shared with you and the group {group} by {owner}', {
          group: this.model.getReshareWithDisplayName(),
          owner: ownerDisplayName
        }, undefined, {
          escape: false
        });
      } else if (this.model.getReshareType() === OC.Share.SHARE_TYPE_CIRCLE) {
        sharedByText = t('core', 'Shared with you and {circle} by {owner}', {
          circle: this.model.getReshareWithDisplayName(),
          owner: ownerDisplayName
        }, undefined, {
          escape: false
        });
      } else if (this.model.getReshareType() === OC.Share.SHARE_TYPE_ROOM) {
        if (this.model.get('reshare').share_with_displayname) {
          sharedByText = t('core', 'Shared with you and the conversation {conversation} by {owner}', {
            conversation: this.model.getReshareWithDisplayName(),
            owner: ownerDisplayName
          }, undefined, {
            escape: false
          });
        } else {
          sharedByText = t('core', 'Shared with you in a conversation by {owner}', {
            owner: ownerDisplayName
          }, undefined, {
            escape: false
          });
        }
      } else {
        sharedByText = t('core', 'Shared with you by {owner}', {
          owner: ownerDisplayName
        }, undefined, {
          escape: false
        });
      }

      this.$el.html(reshareTemplate({
        reshareOwner: this.model.getReshareOwner(),
        sharedByText: sharedByText,
        shareNote: shareNote,
        hasShareNote: shareNote !== ''
      }));
      this.$el.find('.avatar').each(function () {
        var $this = $(this);
        $this.avatar($this.data('username'), 32);
      });
      this.$el.find('.reshare').contactsMenu(this.model.getReshareOwner(), OC.Share.SHARE_TYPE_USER, this.$el);
      return this;
    },

    /**
     * @returns {Function} from Handlebars
     * @private
     */
    template: function template() {
      return OC.Share.Templates['sharedialogresharerinfoview'];
    }
  });
  OC.Share.ShareDialogResharerInfoView = ShareDialogResharerInfoView;
})();

/***/ }),

/***/ "./core/js/sharedialogshareelistview.js":
/*!**********************************************!*\
  !*** ./core/js/sharedialogshareelistview.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* global OC, Handlebars */

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Handlebars */
(function () {
  var PASSWORD_PLACEHOLDER = '**********';
  var PASSWORD_PLACEHOLDER_MESSAGE = t('core', 'Choose a password for the mail share');

  if (!OC.Share) {
    OC.Share = {};
  }
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
    _menuOpen: false,

    /** @type {boolean|number} **/
    _renderPermissionChange: false,
    events: {
      'click .unshare': 'onUnshare',
      'click .share-add': 'showNoteForm',
      'click .share-note-delete': 'deleteNote',
      'click .share-note-submit': 'updateNote',
      'click .share-menu .icon-more': 'onToggleMenu',
      'click .permissions': 'onPermissionChange',
      'click .expireDate': 'onExpireDateChange',
      'click .password': 'onMailSharePasswordProtectChange',
      'click .passwordByTalk': 'onMailSharePasswordProtectByTalkChange',
      'click .secureDrop': 'onSecureDropChange',
      'keyup input.passwordField': 'onMailSharePasswordKeyUp',
      'focusout input.passwordField': 'onMailSharePasswordEntered',
      'change .datepicker': 'onChangeExpirationDate',
      'click .datepicker': 'showDatePicker'
    },
    initialize: function initialize(options) {
      if (!_.isUndefined(options.configModel)) {
        this.configModel = options.configModel;
      } else {
        throw 'missing OC.Share.ShareConfigModel';
      }

      var view = this;
      this.model.on('change:shares', function () {
        view.render();
      });
    },

    /**
     *
     * @param {OC.Share.Types.ShareInfo} shareInfo
     * @returns {object}
     */
    getShareeObject: function getShareeObject(shareIndex) {
      var shareWith = this.model.getShareWith(shareIndex);
      var shareWithDisplayName = this.model.getShareWithDisplayName(shareIndex);
      var shareWithAvatar = this.model.getShareWithAvatar(shareIndex);
      var shareWithTitle = '';
      var shareType = this.model.getShareType(shareIndex);
      var sharedBy = this.model.getSharedBy(shareIndex);
      var sharedByDisplayName = this.model.getSharedByDisplayName(shareIndex);
      var fileOwnerUid = this.model.getFileOwnerUid(shareIndex);
      var hasPermissionOverride = {};

      if (shareType === OC.Share.SHARE_TYPE_GROUP) {
        shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
        shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
        shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote group') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
        shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'email') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {} else if (shareType === OC.Share.SHARE_TYPE_ROOM) {
        shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'conversation') + ')';
      }

      if (shareType === OC.Share.SHARE_TYPE_GROUP) {
        shareWithTitle = shareWith + " (" + t('core', 'group') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
        shareWithTitle = shareWith + " (" + t('core', 'remote') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
        shareWithTitle = shareWith + " (" + t('core', 'remote group') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
        shareWithTitle = shareWith + " (" + t('core', 'email') + ')';
      } else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {
        shareWithTitle = shareWith; // Force "shareWith" in the template to a safe value, as the
        // original "shareWith" returned by the model may contain
        // problematic characters like "'".

        shareWith = 'circle-' + shareIndex;
      }

      if (sharedBy !== oc_current_user) {
        var empty = shareWithTitle === '';

        if (!empty) {
          shareWithTitle += ' (';
        }

        shareWithTitle += t('core', 'shared by {sharer}', {
          sharer: sharedByDisplayName
        });

        if (!empty) {
          shareWithTitle += ')';
        }
      }

      var share = this.model.get('shares')[shareIndex];
      var password = share.password;
      var hasPassword = password !== null && password !== '';
      var sendPasswordByTalk = share.send_password_by_talk;
      var shareNote = this.model.getNote(shareIndex);
      return _.extend(hasPermissionOverride, {
        cid: this.cid,
        hasSharePermission: this.model.hasSharePermission(shareIndex),
        editPermissionState: this.model.editPermissionState(shareIndex),
        hasCreatePermission: this.model.hasCreatePermission(shareIndex),
        hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
        hasDeletePermission: this.model.hasDeletePermission(shareIndex),
        sharedBy: sharedBy,
        sharedByDisplayName: sharedByDisplayName,
        shareWith: shareWith,
        shareWithDisplayName: shareWithDisplayName,
        shareWithAvatar: shareWithAvatar,
        shareWithTitle: shareWithTitle,
        shareType: shareType,
        shareId: this.model.get('shares')[shareIndex].id,
        modSeed: shareWithAvatar || shareType !== OC.Share.SHARE_TYPE_USER && shareType !== OC.Share.SHARE_TYPE_CIRCLE && shareType !== OC.Share.SHARE_TYPE_ROOM,
        owner: fileOwnerUid,
        isShareWithCurrentUser: shareType === OC.Share.SHARE_TYPE_USER && shareWith === oc_current_user,
        canUpdateShareSettings: sharedBy === oc_current_user || fileOwnerUid === oc_current_user,
        isRemoteShare: shareType === OC.Share.SHARE_TYPE_REMOTE,
        isRemoteGroupShare: shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP,
        isNoteAvailable: shareType !== OC.Share.SHARE_TYPE_REMOTE && shareType !== OC.Share.SHARE_TYPE_REMOTE_GROUP,
        isMailShare: shareType === OC.Share.SHARE_TYPE_EMAIL,
        isCircleShare: shareType === OC.Share.SHARE_TYPE_CIRCLE,
        isFileSharedByMail: shareType === OC.Share.SHARE_TYPE_EMAIL && !this.model.isFolder(),
        isPasswordSet: hasPassword && !sendPasswordByTalk,
        isPasswordByTalkSet: hasPassword && sendPasswordByTalk,
        isTalkEnabled: oc_appswebroots['spreed'] !== undefined,
        secureDropMode: !this.model.hasReadPermission(shareIndex),
        hasExpireDate: this.model.getExpireDate(shareIndex) !== null,
        shareNote: shareNote,
        hasNote: shareNote !== '',
        expireDate: moment(this.model.getExpireDate(shareIndex), 'YYYY-MM-DD').format('DD-MM-YYYY'),
        // The password placeholder does not take into account if
        // sending the password by Talk is enabled or not; when
        // switching from sending the password by Talk to sending the
        // password by email the password is reused and the share
        // updated, so the placeholder already shows the password in the
        // brief time between disabling sending the password by email
        // and receiving the updated share.
        passwordPlaceholder: hasPassword ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
        passwordByTalkPlaceholder: hasPassword && sendPasswordByTalk ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE
      });
    },
    getShareProperties: function getShareProperties() {
      return {
        unshareLabel: t('core', 'Unshare'),
        addNoteLabel: t('core', 'Note to recipient'),
        canShareLabel: t('core', 'Can reshare'),
        canEditLabel: t('core', 'Can edit'),
        createPermissionLabel: t('core', 'Can create'),
        updatePermissionLabel: t('core', 'Can change'),
        deletePermissionLabel: t('core', 'Can delete'),
        secureDropLabel: t('core', 'File drop (upload only)'),
        expireDateLabel: t('core', 'Set expiration date'),
        passwordLabel: t('core', 'Password protect'),
        passwordByTalkLabel: t('core', 'Password protect by Talk'),
        crudsLabel: t('core', 'Access control'),
        expirationDatePlaceholder: t('core', 'Expiration date'),
        defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'),
        // Can't expire today
        triangleSImage: OC.imagePath('core', 'actions/triangle-s'),
        isResharingAllowed: this.configModel.get('isResharingAllowed'),
        isPasswordForMailSharesRequired: this.configModel.get('isPasswordForMailSharesRequired'),
        sharePermissionPossible: this.model.sharePermissionPossible(),
        editPermissionPossible: this.model.editPermissionPossible(),
        createPermissionPossible: this.model.createPermissionPossible(),
        updatePermissionPossible: this.model.updatePermissionPossible(),
        deletePermissionPossible: this.model.deletePermissionPossible(),
        sharePermission: OC.PERMISSION_SHARE,
        createPermission: OC.PERMISSION_CREATE,
        updatePermission: OC.PERMISSION_UPDATE,
        deletePermission: OC.PERMISSION_DELETE,
        readPermission: OC.PERMISSION_READ,
        isFolder: this.model.isFolder()
      };
    },

    /**
     * get an array of sharees' share properties
     *
     * @returns {Array}
     */
    getShareeList: function getShareeList() {
      var universal = this.getShareProperties();

      if (!this.model.hasUserShares()) {
        return [];
      }

      var shares = this.model.get('shares');
      var list = [];

      for (var index = 0; index < shares.length; index++) {
        var share = this.getShareeObject(index);

        if (share.shareType === OC.Share.SHARE_TYPE_LINK) {
          continue;
        } // first empty {} is necessary, otherwise we get in trouble
        // with references


        list.push(_.extend({}, universal, share));
      }

      return list;
    },
    getLinkReshares: function getLinkReshares() {
      var universal = {
        unshareLabel: t('core', 'Unshare')
      };

      if (!this.model.hasUserShares()) {
        return [];
      }

      var shares = this.model.get('shares');
      var list = [];

      for (var index = 0; index < shares.length; index++) {
        var share = this.getShareeObject(index);

        if (share.shareType !== OC.Share.SHARE_TYPE_LINK) {
          continue;
        } // first empty {} is necessary, otherwise we get in trouble
        // with references


        list.push(_.extend({}, universal, share, {
          shareInitiator: shares[index].uid_owner,
          shareInitiatorText: t('core', '{shareInitiatorDisplayName} shared via link', {
            shareInitiatorDisplayName: shares[index].displayname_owner
          })
        }));
      }

      return list;
    },
    render: function render() {
      if (!this._renderPermissionChange) {
        this.$el.html(this.template({
          cid: this.cid,
          sharees: this.getShareeList(),
          linkReshares: this.getLinkReshares()
        }));
        this.$('.avatar').each(function () {
          var $this = $(this);

          if ($this.hasClass('imageplaceholderseed')) {
            $this.css({
              width: 32,
              height: 32
            });

            if ($this.data('avatar')) {
              $this.css('border-radius', '0%');
              $this.css('background', 'url(' + $this.data('avatar') + ') no-repeat');
              $this.css('background-size', '31px');
            } else {
              $this.imageplaceholder($this.data('seed'));
            }
          } else {
            //                         user,   size,  ie8fix, hidedefault,  callback, displayname
            $this.avatar($this.data('username'), 32, undefined, undefined, undefined, $this.data('displayname'));
          }
        });
        this.$('.has-tooltip').tooltip({
          placement: 'bottom'
        });
        this.$('ul.shareWithList > li').each(function () {
          var $this = $(this);
          var shareWith = $this.data('share-with');
          var shareType = $this.data('share-type');
          $this.find('div.avatar, span.username').contactsMenu(shareWith, shareType, $this);
        });
      } else {
        var permissionChangeShareId = parseInt(this._renderPermissionChange, 10);
        var shareWithIndex = this.model.findShareWithIndex(permissionChangeShareId);
        var sharee = this.getShareeObject(shareWithIndex);
        $.extend(sharee, this.getShareProperties());
        var $li = this.$('li[data-share-id=' + permissionChangeShareId + ']');
        $li.find('.sharingOptionsGroup .popovermenu').replaceWith(this.popoverMenuTemplate(sharee));
      }

      var _this = this;

      this.getShareeList().forEach(function (sharee) {
        var $edit = _this.$('#canEdit-' + _this.cid + '-' + sharee.shareId);

        if ($edit.length === 1) {
          $edit.prop('checked', sharee.editPermissionState === 'checked');

          if (sharee.isFolder) {
            $edit.prop('indeterminate', sharee.editPermissionState === 'indeterminate');
          }
        }
      });
      this.$('.popovermenu').on('afterHide', function () {
        _this._menuOpen = false;
      });
      this.$('.popovermenu').on('beforeHide', function () {
        var shareId = parseInt(_this._menuOpen, 10);

        if (!_.isNaN(shareId)) {
          var datePickerClass = '.expirationDateContainer-' + _this.cid + '-' + shareId;
          var datePickerInput = '#expirationDatePicker-' + _this.cid + '-' + shareId;
          var expireDateCheckbox = '#expireDate-' + _this.cid + '-' + shareId;

          if ($(expireDateCheckbox).prop('checked')) {
            $(datePickerInput).removeClass('hidden-visually');
            $(datePickerClass).removeClass('hasDatepicker');
            $(datePickerClass + ' .ui-datepicker').hide();
          }
        }
      });

      if (this._menuOpen !== false) {
        // Open menu again if it was opened before
        var shareId = parseInt(this._menuOpen, 10);

        if (!_.isNaN(shareId)) {
          var liSelector = 'li[data-share-id=' + shareId + ']';
          OC.showMenu(null, this.$(liSelector + ' .sharingOptionsGroup .popovermenu'));
        }
      }

      this._renderPermissionChange = false; // new note autosize

      autosize(this.$el.find('.share-note-form .share-note'));
      this.delegateEvents();
      return this;
    },

    /**
     * @returns {Function} from Handlebars
     * @private
     */
    template: function template(data) {
      var sharees = data.sharees;

      if (_.isArray(sharees)) {
        for (var i = 0; i < sharees.length; i++) {
          data.sharees[i].popoverMenu = this.popoverMenuTemplate(sharees[i]);
        }
      }

      return OC.Share.Templates['sharedialogshareelistview'](data);
    },

    /**
     * renders the popover template and returns the resulting HTML
     *
     * @param {Object} data
     * @returns {string}
     */
    popoverMenuTemplate: function popoverMenuTemplate(data) {
      return OC.Share.Templates['sharedialogshareelistview_popover_menu'](data);
    },
    showNoteForm: function showNoteForm(event) {
      event.preventDefault();
      event.stopPropagation();
      var $element = $(event.target);
      var $menu = $element.closest('li');
      var $form = $menu.next('li.share-note-form'); // show elements

      $menu.find('.share-note-delete').toggleClass('hidden');
      $form.toggleClass('hidden');
      $form.find('textarea').focus();
    },
    deleteNote: function deleteNote(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $menu = $element.closest('li');
      var $form = $menu.next('li.share-note-form');
      console.log($form.find('.share-note'));
      $form.find('.share-note').val('');
      $form.addClass('hidden');
      $menu.find('.share-note-delete').addClass('hidden');
      self.sendNote('', shareId, $menu);
    },
    updateNote: function updateNote(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var $form = $element.closest('li.share-note-form');
      var $menu = $form.prev('li');
      var message = $form.find('.share-note').val().trim();

      if (message.length < 1) {
        return;
      }

      self.sendNote(message, shareId, $menu);
    },
    sendNote: function sendNote(note, shareId, $menu) {
      var $form = $menu.next('li.share-note-form');
      var $submit = $form.find('input.share-note-submit');
      var $error = $form.find('input.share-note-error');
      $submit.prop('disabled', true);
      $menu.find('.icon-loading-small').removeClass('hidden');
      $menu.find('.icon-edit').hide();

      var complete = function complete() {
        $submit.prop('disabled', false);
        $menu.find('.icon-loading-small').addClass('hidden');
        $menu.find('.icon-edit').show();
      };

      var error = function error() {
        $error.show();
        setTimeout(function () {
          $error.hide();
        }, 3000);
      }; // send data


      $.ajax({
        method: 'PUT',
        url: OC.linkToOCS('apps/files_sharing/api/v1/shares', 2) + shareId + '?' + OC.buildQueryString({
          format: 'json'
        }),
        data: {
          note: note
        },
        complete: complete,
        error: error
      });
    },
    onUnshare: function onUnshare(event) {
      event.preventDefault();
      event.stopPropagation();
      var self = this;
      var $element = $(event.target);

      if (!$element.is('a')) {
        $element = $element.closest('a');
      }

      var $loading = $element.find('.icon-loading-small').eq(0);

      if (!$loading.hasClass('hidden')) {
        // in process
        return false;
      }

      $loading.removeClass('hidden');
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      self.model.removeShare(shareId).done(function () {
        $li.remove();
      }).fail(function () {
        $loading.addClass('hidden');
        OC.Notification.showTemporary(t('core', 'Could not unshare'));
      });
      return false;
    },
    onToggleMenu: function onToggleMenu(event) {
      event.preventDefault();
      event.stopPropagation();
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var $menu = $li.find('.sharingOptionsGroup .popovermenu');
      OC.showMenu(null, $menu);
      this._menuOpen = $li.data('share-id');
    },
    onExpireDateChange: function onExpireDateChange(event) {
      var $element = $(event.target);
      var li = $element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var datePickerClass = '.expirationDateContainer-' + this.cid + '-' + shareId;
      var datePicker = $(datePickerClass);
      var state = $element.prop('checked');
      datePicker.toggleClass('hidden', !state);

      if (!state) {
        // disabled, let's hide the input and
        // set the expireDate to nothing
        $element.closest('li').next('li').addClass('hidden');
        this.setExpirationDate(shareId, '');
      } else {
        // enabled, show the input and the datepicker
        $element.closest('li').next('li').removeClass('hidden');
        this.showDatePicker(event);
      }
    },
    showDatePicker: function showDatePicker(event) {
      var element = $(event.target);
      var li = element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var expirationDatePicker = '#expirationDatePicker-' + this.cid + '-' + shareId;
      var view = this;
      $(expirationDatePicker).datepicker({
        dateFormat: 'dd-mm-yy',
        onSelect: function onSelect(expireDate) {
          view.setExpirationDate(shareId, expireDate);
        }
      });
      $(expirationDatePicker).focus();
    },
    setExpirationDate: function setExpirationDate(shareId, expireDate) {
      this.model.updateShare(shareId, {
        expireDate: expireDate
      }, {});
    },
    onMailSharePasswordProtectChange: function onMailSharePasswordProtectChange(event) {
      var element = $(event.target);
      var li = element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
      var passwordContainer = $(passwordContainerClass);
      var loading = this.$el.find(passwordContainerClass + ' .icon-loading-small');
      var inputClass = '#passwordField-' + this.cid + '-' + shareId;
      var passwordField = $(inputClass);
      var state = element.prop('checked');
      var passwordByTalkElement = $('#passwordByTalk-' + this.cid + '-' + shareId);
      var passwordByTalkState = passwordByTalkElement.prop('checked');

      if (!state && !passwordByTalkState) {
        this.model.updateShare(shareId, {
          password: '',
          sendPasswordByTalk: false
        });
        passwordField.attr('value', '');
        passwordField.removeClass('error');
        passwordField.tooltip('hide');
        loading.addClass('hidden');
        passwordField.attr('placeholder', PASSWORD_PLACEHOLDER_MESSAGE); // We first need to reset the password field before we hide it

        passwordContainer.toggleClass('hidden', !state);
      } else if (state) {
        if (passwordByTalkState) {
          // Switching from sending the password by Talk to sending
          // the password by mail can be done keeping the previous
          // password sent by Talk.
          this.model.updateShare(shareId, {
            sendPasswordByTalk: false
          });
          var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
          var passwordByTalkContainer = $(passwordByTalkContainerClass);
          passwordByTalkContainer.addClass('hidden');
          passwordByTalkElement.prop('checked', false);
        }

        passwordContainer.toggleClass('hidden', !state);
        passwordField = '#passwordField-' + this.cid + '-' + shareId;
        this.$(passwordField).focus();
      }
    },
    onMailSharePasswordProtectByTalkChange: function onMailSharePasswordProtectByTalkChange(event) {
      var element = $(event.target);
      var li = element.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
      var passwordByTalkContainer = $(passwordByTalkContainerClass);
      var loading = this.$el.find(passwordByTalkContainerClass + ' .icon-loading-small');
      var inputClass = '#passwordByTalkField-' + this.cid + '-' + shareId;
      var passwordByTalkField = $(inputClass);
      var state = element.prop('checked');
      var passwordElement = $('#password-' + this.cid + '-' + shareId);
      var passwordState = passwordElement.prop('checked');

      if (!state) {
        this.model.updateShare(shareId, {
          password: '',
          sendPasswordByTalk: false
        });
        passwordByTalkField.attr('value', '');
        passwordByTalkField.removeClass('error');
        passwordByTalkField.tooltip('hide');
        loading.addClass('hidden');
        passwordByTalkField.attr('placeholder', PASSWORD_PLACEHOLDER_MESSAGE); // We first need to reset the password field before we hide it

        passwordByTalkContainer.toggleClass('hidden', !state);
      } else if (state) {
        if (passwordState) {
          // Enabling sending the password by Talk requires a new
          // password to be given (the one sent by mail is not reused,
          // as it would defeat the purpose of checking the identity
          // of the sharee by Talk if it was already sent by mail), so
          // the share is not updated until the user explicitly gives
          // the new password.
          var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
          var passwordContainer = $(passwordContainerClass);
          passwordContainer.addClass('hidden');
          passwordElement.prop('checked', false);
        }

        passwordByTalkContainer.toggleClass('hidden', !state);
        passwordByTalkField = '#passwordByTalkField-' + this.cid + '-' + shareId;
        this.$(passwordByTalkField).focus();
      }
    },
    onMailSharePasswordKeyUp: function onMailSharePasswordKeyUp(event) {
      if (event.keyCode === 13) {
        this.onMailSharePasswordEntered(event);
      }
    },
    onMailSharePasswordEntered: function onMailSharePasswordEntered(event) {
      var passwordField = $(event.target);
      var li = passwordField.closest('li[data-share-id]');
      var shareId = li.data('share-id');
      var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
      var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
      var sendPasswordByTalk = passwordField.attr('id').startsWith('passwordByTalk');
      var loading;

      if (sendPasswordByTalk) {
        loading = this.$el.find(passwordByTalkContainerClass + ' .icon-loading-small');
      } else {
        loading = this.$el.find(passwordContainerClass + ' .icon-loading-small');
      }

      if (!loading.hasClass('hidden')) {
        // still in process
        return;
      }

      passwordField.removeClass('error');
      var password = passwordField.val(); // in IE9 the password might be the placeholder due to bugs in the placeholders polyfill

      if (password === '' || password === PASSWORD_PLACEHOLDER || password === PASSWORD_PLACEHOLDER_MESSAGE) {
        return;
      }

      loading.removeClass('hidden').addClass('inlineblock');
      this.model.updateShare(shareId, {
        password: password,
        sendPasswordByTalk: sendPasswordByTalk
      }, {
        error: function error(model, msg) {
          // destroy old tooltips
          passwordField.tooltip('destroy');
          loading.removeClass('inlineblock').addClass('hidden');
          passwordField.addClass('error');
          passwordField.attr('title', msg);
          passwordField.tooltip({
            placement: 'bottom',
            trigger: 'manual'
          });
          passwordField.tooltip('show');
        },
        success: function success(model, msg) {
          passwordField.blur();
          passwordField.attr('value', '');
          passwordField.attr('placeholder', PASSWORD_PLACEHOLDER);
          loading.removeClass('inlineblock').addClass('hidden');
        }
      });
    },
    onPermissionChange: function onPermissionChange(event) {
      event.preventDefault();
      event.stopPropagation();
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var permissions = OC.PERMISSION_READ;

      if (this.model.isFolder()) {
        // adjust checkbox states
        var $checkboxes = $('.permissions', $li).not('input[name="edit"]').not('input[name="share"]');
        var checked;

        if ($element.attr('name') === 'edit') {
          checked = $element.is(':checked'); // Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck

          $($checkboxes).prop('checked', checked);

          if (checked) {
            permissions |= OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE;
          }
        } else {
          var numberChecked = $checkboxes.filter(':checked').length;
          checked = numberChecked === $checkboxes.length;
          var $editCb = $('input[name="edit"]', $li);
          $editCb.prop('checked', checked);
          $editCb.prop('indeterminate', !checked && numberChecked > 0);
        }
      } else {
        if ($element.attr('name') === 'edit' && $element.is(':checked')) {
          permissions |= OC.PERMISSION_UPDATE;
        }
      }

      $('.permissions', $li).not('input[name="edit"]').filter(':checked').each(function (index, checkbox) {
        permissions |= $(checkbox).data('permissions');
      });
      /** disable checkboxes during save operation to avoid race conditions **/

      $li.find('input[type=checkbox]').prop('disabled', true);

      var enableCb = function enableCb() {
        $li.find('input[type=checkbox]').prop('disabled', false);
      };

      var errorCb = function errorCb(elem, msg) {
        OC.dialogs.alert(msg, t('core', 'Error while sharing'));
        enableCb();
      };

      this.model.updateShare(shareId, {
        permissions: permissions
      }, {
        error: errorCb,
        success: enableCb
      });
      this._renderPermissionChange = shareId;
    },
    onSecureDropChange: function onSecureDropChange(event) {
      event.preventDefault();
      event.stopPropagation();
      var $element = $(event.target);
      var $li = $element.closest('li[data-share-id]');
      var shareId = $li.data('share-id');
      var permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE | OC.PERMISSION_READ;

      if ($element.is(':checked')) {
        permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE;
      }
      /** disable checkboxes during save operation to avoid race conditions **/


      $li.find('input[type=checkbox]').prop('disabled', true);

      var enableCb = function enableCb() {
        $li.find('input[type=checkbox]').prop('disabled', false);
      };

      var errorCb = function errorCb(elem, msg) {
        OC.dialogs.alert(msg, t('core', 'Error while sharing'));
        enableCb();
      };

      this.model.updateShare(shareId, {
        permissions: permissions
      }, {
        error: errorCb,
        success: enableCb
      });
      this._renderPermissionChange = shareId;
    }
  });
  OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;
})();

/***/ }),

/***/ "./core/js/sharedialogview.js":
/*!************************************!*\
  !*** ./core/js/sharedialogview.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Handlebars */
(function () {
  if (!OC.Share) {
    OC.Share = {};
  }
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
    _lookup: false,
    _lookupAllowed: false,

    /** @type {string} **/
    tagName: 'div',

    /** @type {OC.Share.ShareConfigModel} **/
    configModel: undefined,

    /** @type {object} **/
    resharerInfoView: undefined,

    /** @type {object} **/
    linkShareView: undefined,

    /** @type {object} **/
    shareeListView: undefined,

    /** @type {object} **/
    _lastSuggestions: undefined,

    /** @type {object} **/
    _lastRecommendations: undefined,

    /** @type {int} **/
    _pendingOperationsCount: 0,
    events: {
      'focus .shareWithField': 'onShareWithFieldFocus',
      'input .shareWithField': 'onShareWithFieldChanged',
      'click .shareWithConfirm': '_confirmShare'
    },
    initialize: function initialize(options) {
      var view = this;
      this.model.on('fetchError', function () {
        OC.Notification.showTemporary(t('core', 'Share details could not be loaded for this item.'));
      });

      if (!_.isUndefined(options.configModel)) {
        this.configModel = options.configModel;
      } else {
        throw 'missing OC.Share.ShareConfigModel';
      }

      this.configModel.on('change:isRemoteShareAllowed', function () {
        view.render();
      });
      this.configModel.on('change:isRemoteGroupShareAllowed', function () {
        view.render();
      });
      this.model.on('change:permissions', function () {
        view.render();
      });
      this.model.on('request', this._onRequest, this);
      this.model.on('sync', this._onEndRequest, this);
      var subViewOptions = {
        model: this.model,
        configModel: this.configModel
      };
      var subViews = {
        resharerInfoView: 'ShareDialogResharerInfoView',
        linkShareView: 'ShareDialogLinkShareView',
        shareeListView: 'ShareDialogShareeListView'
      };

      for (var name in subViews) {
        var className = subViews[name];
        this[name] = _.isUndefined(options[name]) ? new OC.Share[className](subViewOptions) : options[name];
      }

      _.bindAll(this, 'autocompleteHandler', '_onSelectRecipient', 'onShareWithFieldChanged', 'onShareWithFieldFocus');

      OC.Plugins.attach('OC.Share.ShareDialogView', this);
    },
    onShareWithFieldChanged: function onShareWithFieldChanged() {
      var $el = this.$el.find('.shareWithField');

      if ($el.val().length < 2) {
        $el.removeClass('error').tooltip('hide');
      }
    },

    /* trigger search after the field was re-selected */
    onShareWithFieldFocus: function onShareWithFieldFocus() {
      var $shareWithField = this.$el.find('.shareWithField');
      $shareWithField.autocomplete("search", $shareWithField.val());
    },
    _getSuggestions: function _getSuggestions(searchTerm, perPage, model, lookup) {
      if (this._lastSuggestions && this._lastSuggestions.searchTerm === searchTerm && this._lastSuggestions.lookup === lookup && this._lastSuggestions.perPage === perPage && this._lastSuggestions.model === model) {
        return this._lastSuggestions.promise;
      }

      var deferred = $.Deferred();
      var view = this;
      $.get(OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees', {
        format: 'json',
        search: searchTerm,
        lookup: lookup,
        perPage: perPage,
        itemType: model.get('itemType')
      }, function (result) {
        if (result.ocs.meta.statuscode === 100) {
          var dynamicSort = function dynamicSort(property) {
            return function (a, b) {
              var aProperty = '';
              var bProperty = '';

              if (typeof a[property] !== 'undefined') {
                aProperty = a[property];
              }

              if (typeof b[property] !== 'undefined') {
                bProperty = b[property];
              }

              return aProperty < bProperty ? -1 : aProperty > bProperty ? 1 : 0;
            };
          };
          /**
           * Sort share entries by uuid to properly group them
           */


          var filter = function filter(users, groups, remotes, remote_groups, emails, circles, rooms) {
            if (typeof emails === 'undefined') {
              emails = [];
            }

            if (typeof circles === 'undefined') {
              circles = [];
            }

            if (typeof rooms === 'undefined') {
              rooms = [];
            }

            var usersLength;
            var groupsLength;
            var remotesLength;
            var remoteGroupsLength;
            var emailsLength;
            var circlesLength;
            var roomsLength;
            var i, j; //Filter out the current user

            usersLength = users.length;

            for (i = 0; i < usersLength; i++) {
              if (users[i].value.shareWith === OC.currentUser) {
                users.splice(i, 1);
                break;
              }
            } // Filter out the owner of the share


            if (model.hasReshare()) {
              usersLength = users.length;

              for (i = 0; i < usersLength; i++) {
                if (users[i].value.shareWith === model.getReshareOwner()) {
                  users.splice(i, 1);
                  break;
                }
              }
            }

            var shares = model.get('shares');
            var sharesLength = shares.length; // Now filter out all sharees that are already shared with

            for (i = 0; i < sharesLength; i++) {
              var share = shares[i];

              if (share.share_type === OC.Share.SHARE_TYPE_USER) {
                usersLength = users.length;

                for (j = 0; j < usersLength; j++) {
                  if (users[j].value.shareWith === share.share_with) {
                    users.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_GROUP) {
                groupsLength = groups.length;

                for (j = 0; j < groupsLength; j++) {
                  if (groups[j].value.shareWith === share.share_with) {
                    groups.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
                remotesLength = remotes.length;

                for (j = 0; j < remotesLength; j++) {
                  if (remotes[j].value.shareWith === share.share_with) {
                    remotes.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
                remoteGroupsLength = remote_groups.length;

                for (j = 0; j < remoteGroupsLength; j++) {
                  if (remote_groups[j].value.shareWith === share.share_with) {
                    remote_groups.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
                emailsLength = emails.length;

                for (j = 0; j < emailsLength; j++) {
                  if (emails[j].value.shareWith === share.share_with) {
                    emails.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_CIRCLE) {
                circlesLength = circles.length;

                for (j = 0; j < circlesLength; j++) {
                  if (circles[j].value.shareWith === share.share_with) {
                    circles.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_ROOM) {
                roomsLength = rooms.length;

                for (j = 0; j < roomsLength; j++) {
                  if (rooms[j].value.shareWith === share.share_with) {
                    rooms.splice(j, 1);
                    break;
                  }
                }
              }
            }
          };

          filter(result.ocs.data.exact.users, result.ocs.data.exact.groups, result.ocs.data.exact.remotes, result.ocs.data.exact.remote_groups, result.ocs.data.exact.emails, result.ocs.data.exact.circles, result.ocs.data.exact.rooms);
          var exactUsers = result.ocs.data.exact.users;
          var exactGroups = result.ocs.data.exact.groups;
          var exactRemotes = result.ocs.data.exact.remotes;
          var exactRemoteGroups = result.ocs.data.exact.remote_groups;
          var exactEmails = [];

          if (typeof result.ocs.data.emails !== 'undefined') {
            exactEmails = result.ocs.data.exact.emails;
          }

          var exactCircles = [];

          if (typeof result.ocs.data.circles !== 'undefined') {
            exactCircles = result.ocs.data.exact.circles;
          }

          var exactRooms = [];

          if (typeof result.ocs.data.rooms !== 'undefined') {
            exactRooms = result.ocs.data.exact.rooms;
          }

          var exactMatches = exactUsers.concat(exactGroups).concat(exactRemotes).concat(exactRemoteGroups).concat(exactEmails).concat(exactCircles).concat(exactRooms);
          filter(result.ocs.data.users, result.ocs.data.groups, result.ocs.data.remotes, result.ocs.data.remote_groups, result.ocs.data.emails, result.ocs.data.circles, result.ocs.data.rooms);
          var users = result.ocs.data.users;
          var groups = result.ocs.data.groups;
          var remotes = result.ocs.data.remotes;
          var remoteGroups = result.ocs.data.remote_groups;
          var lookup = result.ocs.data.lookup;
          var lookupEnabled = result.ocs.data.lookupEnabled;
          var emails = [];

          if (typeof result.ocs.data.emails !== 'undefined') {
            emails = result.ocs.data.emails;
          }

          var circles = [];

          if (typeof result.ocs.data.circles !== 'undefined') {
            circles = result.ocs.data.circles;
          }

          var rooms = [];

          if (typeof result.ocs.data.rooms !== 'undefined') {
            rooms = result.ocs.data.rooms;
          }

          var suggestions = exactMatches.concat(users).concat(groups).concat(remotes).concat(remoteGroups).concat(emails).concat(circles).concat(rooms).concat(lookup);
          var grouped = suggestions.sort(dynamicSort('uuid'));
          var previousUuid = null;
          var groupedLength = grouped.length;
          var result = [];
          /**
           * build the result array that only contains all contact entries from
           * merged contacts, if the search term matches its contact name
           */

          for (var i = 0; i < groupedLength; i++) {
            if (typeof grouped[i].uuid !== 'undefined' && grouped[i].uuid === previousUuid) {
              grouped[i].merged = true;
            }

            if (searchTerm === grouped[i].name || typeof grouped[i].merged === 'undefined') {
              result.push(grouped[i]);
            }

            previousUuid = grouped[i].uuid;
          }

          var moreResultsAvailable = oc_config['sharing.maxAutocompleteResults'] > 0 && Math.min(perPage, oc_config['sharing.maxAutocompleteResults']) <= Math.max(users.length + exactUsers.length, groups.length + exactGroups.length, remoteGroups.length + exactRemoteGroups.length, remotes.length + exactRemotes.length, emails.length + exactEmails.length, circles.length + exactCircles.length, rooms.length + exactRooms.length, lookup.length);

          if (!view._lookup && lookupEnabled) {
            result.push({
              label: t('core', 'Search globally'),
              value: {},
              lookup: true
            });
          }

          deferred.resolve(result, exactMatches, moreResultsAvailable, lookupEnabled);
        } else {
          deferred.reject(result.ocs.meta.message);
        }
      }).fail(function () {
        deferred.reject();
      });
      this._lastSuggestions = {
        searchTerm: searchTerm,
        lookup: lookup,
        perPage: perPage,
        model: model,
        promise: deferred.promise()
      };
      return this._lastSuggestions.promise;
    },
    _getRecommendations: function _getRecommendations(model) {
      if (this._lastRecommendations && this._lastRecommendations.model === model) {
        return this._lastRecommendations.promise;
      }

      var deferred = $.Deferred();
      $.get(OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees_recommended', {
        format: 'json',
        itemType: model.get('itemType')
      }, function (result) {
        if (result.ocs.meta.statuscode === 100) {
          var dynamicSort = function dynamicSort(property) {
            return function (a, b) {
              var aProperty = '';
              var bProperty = '';

              if (typeof a[property] !== 'undefined') {
                aProperty = a[property];
              }

              if (typeof b[property] !== 'undefined') {
                bProperty = b[property];
              }

              return aProperty < bProperty ? -1 : aProperty > bProperty ? 1 : 0;
            };
          };
          /**
           * Sort share entries by uuid to properly group them
           */


          var filter = function filter(users, groups, remotes, remote_groups, emails, circles, rooms) {
            if (typeof emails === 'undefined') {
              emails = [];
            }

            if (typeof circles === 'undefined') {
              circles = [];
            }

            if (typeof rooms === 'undefined') {
              rooms = [];
            }

            var usersLength;
            var groupsLength;
            var remotesLength;
            var remoteGroupsLength;
            var emailsLength;
            var circlesLength;
            var roomsLength;
            var i, j; //Filter out the current user

            usersLength = users.length;

            for (i = 0; i < usersLength; i++) {
              if (users[i].value.shareWith === OC.currentUser) {
                users.splice(i, 1);
                break;
              }
            } // Filter out the owner of the share


            if (model.hasReshare()) {
              usersLength = users.length;

              for (i = 0; i < usersLength; i++) {
                if (users[i].value.shareWith === model.getReshareOwner()) {
                  users.splice(i, 1);
                  break;
                }
              }
            }

            var shares = model.get('shares');
            var sharesLength = shares.length; // Now filter out all sharees that are already shared with

            for (i = 0; i < sharesLength; i++) {
              var share = shares[i];

              if (share.share_type === OC.Share.SHARE_TYPE_USER) {
                usersLength = users.length;

                for (j = 0; j < usersLength; j++) {
                  if (users[j].value.shareWith === share.share_with) {
                    users.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_GROUP) {
                groupsLength = groups.length;

                for (j = 0; j < groupsLength; j++) {
                  if (groups[j].value.shareWith === share.share_with) {
                    groups.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
                remotesLength = remotes.length;

                for (j = 0; j < remotesLength; j++) {
                  if (remotes[j].value.shareWith === share.share_with) {
                    remotes.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
                remoteGroupsLength = remote_groups.length;

                for (j = 0; j < remoteGroupsLength; j++) {
                  if (remote_groups[j].value.shareWith === share.share_with) {
                    remote_groups.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
                emailsLength = emails.length;

                for (j = 0; j < emailsLength; j++) {
                  if (emails[j].value.shareWith === share.share_with) {
                    emails.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_CIRCLE) {
                circlesLength = circles.length;

                for (j = 0; j < circlesLength; j++) {
                  if (circles[j].value.shareWith === share.share_with) {
                    circles.splice(j, 1);
                    break;
                  }
                }
              } else if (share.share_type === OC.Share.SHARE_TYPE_ROOM) {
                roomsLength = rooms.length;

                for (j = 0; j < roomsLength; j++) {
                  if (rooms[j].value.shareWith === share.share_with) {
                    rooms.splice(j, 1);
                    break;
                  }
                }
              }
            }
          };

          filter(result.ocs.data.exact.users, result.ocs.data.exact.groups, result.ocs.data.exact.remotes, result.ocs.data.exact.remote_groups, result.ocs.data.exact.emails, result.ocs.data.exact.circles, result.ocs.data.exact.rooms);
          var exactUsers = result.ocs.data.exact.users;
          var exactGroups = result.ocs.data.exact.groups;
          var exactRemotes = result.ocs.data.exact.remotes || [];
          var exactRemoteGroups = result.ocs.data.exact.remote_groups || [];
          var exactEmails = [];

          if (typeof result.ocs.data.emails !== 'undefined') {
            exactEmails = result.ocs.data.exact.emails;
          }

          var exactCircles = [];

          if (typeof result.ocs.data.circles !== 'undefined') {
            exactCircles = result.ocs.data.exact.circles;
          }

          var exactRooms = [];

          if (typeof result.ocs.data.rooms !== 'undefined') {
            exactRooms = result.ocs.data.exact.rooms;
          }

          var exactMatches = exactUsers.concat(exactGroups).concat(exactRemotes).concat(exactRemoteGroups).concat(exactEmails).concat(exactCircles).concat(exactRooms);
          filter(result.ocs.data.users, result.ocs.data.groups, result.ocs.data.remotes, result.ocs.data.remote_groups, result.ocs.data.emails, result.ocs.data.circles, result.ocs.data.rooms);
          var users = result.ocs.data.users;
          var groups = result.ocs.data.groups;
          var remotes = result.ocs.data.remotes || [];
          var remoteGroups = result.ocs.data.remote_groups || [];
          var lookup = result.ocs.data.lookup || [];
          var emails = [];

          if (typeof result.ocs.data.emails !== 'undefined') {
            emails = result.ocs.data.emails;
          }

          var circles = [];

          if (typeof result.ocs.data.circles !== 'undefined') {
            circles = result.ocs.data.circles;
          }

          var rooms = [];

          if (typeof result.ocs.data.rooms !== 'undefined') {
            rooms = result.ocs.data.rooms;
          }

          var suggestions = exactMatches.concat(users).concat(groups).concat(remotes).concat(remoteGroups).concat(emails).concat(circles).concat(rooms).concat(lookup);
          var grouped = suggestions.sort(dynamicSort('uuid'));
          var previousUuid = null;
          var groupedLength = grouped.length;
          var result = [];
          /**
           * build the result array that only contains all contact entries from
           * merged contacts, if the search term matches its contact name
           */

          for (var i = 0; i < groupedLength; i++) {
            if (typeof grouped[i].uuid !== 'undefined' && grouped[i].uuid === previousUuid) {
              grouped[i].merged = true;
            }

            if (typeof grouped[i].merged === 'undefined') {
              result.push(grouped[i]);
            }

            previousUuid = grouped[i].uuid;
          }

          var moreResultsAvailable = oc_config['sharing.maxAutocompleteResults'] > 0 && Math.min(perPage, oc_config['sharing.maxAutocompleteResults']) <= Math.max(users.length + exactUsers.length, groups.length + exactGroups.length, remoteGroups.length + exactRemoteGroups.length, remotes.length + exactRemotes.length, emails.length + exactEmails.length, circles.length + exactCircles.length, rooms.length + exactRooms.length, lookup.length);
          deferred.resolve(result, exactMatches, moreResultsAvailable);
        } else {
          deferred.reject(result.ocs.meta.message);
        }
      }).fail(function () {
        deferred.reject();
      });
      this._lastRecommendations = {
        model: model,
        promise: deferred.promise()
      };
      return this._lastRecommendations.promise;
    },
    recommendationHandler: function recommendationHandler(response) {
      var view = this;
      var $shareWithField = $('.shareWithField');

      this._getRecommendations(view.model).done(function (suggestions) {
        console.info('recommendations', suggestions);

        if (suggestions.length > 0) {
          $shareWithField.autocomplete("option", "autoFocus", true);
          response(suggestions);
        } else {
          console.info('no sharing recommendations found');
          response();
        }
      }).fail(function (message) {
        console.error('could not load recommendations', message);
      });
    },
    autocompleteHandler: function autocompleteHandler(search, response) {
      // If nothing is entered we show recommendations instead of search
      // results
      if (search.term.length === 0) {
        console.info(search.term, 'empty search term -> using recommendations');
        this.recommendationHandler(response);
        return;
      }

      var $shareWithField = $('.shareWithField'),
          view = this,
          $loading = this.$el.find('.shareWithLoading'),
          $confirm = this.$el.find('.shareWithConfirm');
      var count = oc_config['sharing.minSearchStringLength'];

      if (search.term.trim().length < count) {
        var title = n('core', 'At least {count} character is needed for autocompletion', 'At least {count} characters are needed for autocompletion', count, {
          count: count
        });
        $shareWithField.addClass('error').attr('data-original-title', title).tooltip('hide').tooltip({
          placement: 'bottom',
          trigger: 'manual'
        }).tooltip('fixTitle').tooltip('show');
        response();
        return;
      }

      $loading.removeClass('hidden');
      $loading.addClass('inlineblock');
      $confirm.addClass('hidden');
      this._pendingOperationsCount++;
      $shareWithField.removeClass('error').tooltip('hide');
      var perPage = parseInt(oc_config['sharing.maxAutocompleteResults'], 10) || 200;

      this._getSuggestions(search.term.trim(), perPage, view.model, view._lookup).done(function (suggestions, exactMatches, moreResultsAvailable) {
        view._pendingOperationsCount--;

        if (view._pendingOperationsCount === 0) {
          $loading.addClass('hidden');
          $loading.removeClass('inlineblock');
          $confirm.removeClass('hidden');
        }

        if (suggestions.length > 0) {
          $shareWithField.autocomplete("option", "autoFocus", true);
          response(suggestions); // show a notice that the list is truncated
          // this is the case if one of the search results is at least as long as the max result config option

          if (moreResultsAvailable) {
            var message = t('core', 'This list is maybe truncated - please refine your search term to see more results.');
            $('.ui-autocomplete').append('<li class="autocomplete-note">' + message + '</li>');
          }
        } else {
          var title = t('core', 'No users or groups found for {search}', {
            search: $shareWithField.val()
          });

          if (!view.configModel.get('allowGroupSharing')) {
            title = t('core', 'No users found for {search}', {
              search: $('.shareWithField').val()
            });
          }

          $shareWithField.addClass('error').attr('data-original-title', title).tooltip('hide').tooltip({
            placement: 'top',
            trigger: 'manual'
          }).tooltip('fixTitle').tooltip('show');
          response();
        }
      }).fail(function (message) {
        view._pendingOperationsCount--;

        if (view._pendingOperationsCount === 0) {
          $loading.addClass('hidden');
          $loading.removeClass('inlineblock');
          $confirm.removeClass('hidden');
        }

        if (message) {
          OC.Notification.showTemporary(t('core', 'An error occurred ("{message}"). Please try again', {
            message: message
          }));
        } else {
          OC.Notification.showTemporary(t('core', 'An error occurred. Please try again'));
        }
      });
    },
    autocompleteRenderItem: function autocompleteRenderItem(ul, item) {
      var icon = 'icon-user';
      var text = escapeHTML(item.label);
      var description = '';
      var type = '';

      var getTranslatedType = function getTranslatedType(type) {
        switch (type) {
          case 'HOME':
            return t('core', 'Home');

          case 'WORK':
            return t('core', 'Work');

          case 'OTHER':
            return t('core', 'Other');

          default:
            return '' + type;
        }
      };

      if (typeof item.type !== 'undefined' && item.type !== null) {
        type = getTranslatedType(item.type) + ' ';
      }

      if (typeof item.name !== 'undefined') {
        text = escapeHTML(item.name);
      }

      if (item.value.shareType === OC.Share.SHARE_TYPE_GROUP) {
        icon = 'icon-contacts-dark';
      } else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE) {
        icon = 'icon-shared';
        description += item.value.shareWith;
      } else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
        text = t('core', '{sharee} (remote group)', {
          sharee: text
        }, undefined, {
          escape: false
        });
        icon = 'icon-shared';
        description += item.value.shareWith;
      } else if (item.value.shareType === OC.Share.SHARE_TYPE_EMAIL) {
        icon = 'icon-mail';
        description += item.value.shareWith;
      } else if (item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
        text = t('core', '{sharee} ({type}, {owner})', {
          sharee: text,
          type: item.value.circleInfo,
          owner: item.value.circleOwner
        }, undefined, {
          escape: false
        });
        icon = 'icon-circle';
      } else if (item.value.shareType === OC.Share.SHARE_TYPE_ROOM) {
        icon = 'icon-talk';
      }

      var insert = $("<div class='share-autocomplete-item'/>");

      if (item.merged) {
        insert.addClass('merged');
        text = item.value.shareWith;
        description = type;
      } else if (item.lookup) {
        text = item.label;
        icon = false;
        insert.append('<span class="icon icon-search search-globally"></span>');
      } else {
        var avatar = $("<div class='avatardiv'></div>").appendTo(insert);

        if (item.value.shareType === OC.Share.SHARE_TYPE_USER || item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
          avatar.avatar(item.value.shareWith, 32, undefined, undefined, undefined, item.label);
        } else {
          if (typeof item.uuid === 'undefined') {
            item.uuid = text;
          }

          avatar.imageplaceholder(item.uuid, text, 32);
        }

        description = type + description;
      }

      if (description !== '') {
        insert.addClass('with-description');
      }

      $("<div class='autocomplete-item-text'></div>").html(text.replace(new RegExp(this.term, "gi"), "<span class='ui-state-highlight'>$&</span>") + '<span class="autocomplete-item-details">' + description + '</span>').appendTo(insert);
      insert.attr('title', item.value.shareWith);

      if (icon) {
        insert.append('<span class="icon ' + icon + '" title="' + text + '"></span>');
      }

      insert = $("<a>").append(insert);
      return $("<li>").addClass(item.value.shareType === OC.Share.SHARE_TYPE_GROUP ? 'group' : 'user').append(insert).appendTo(ul);
    },
    _onSelectRecipient: function _onSelectRecipient(e, s) {
      var self = this;

      if (e.keyCode == 9) {
        e.preventDefault();

        if (typeof s.item.name !== 'undefined') {
          e.target.value = s.item.name;
        } else {
          e.target.value = s.item.label;
        }

        setTimeout(function () {
          $(e.target).attr('disabled', false).autocomplete('search', $(e.target).val());
        }, 0);
        return false;
      }

      if (s.item.lookup) {
        // Retrigger search but with global lookup this time
        this._lookup = true;
        var $shareWithField = this.$el.find('.shareWithField');
        var val = $shareWithField.val();
        setTimeout(function () {
          console.debug('searching again, but globally. search term: ' + val);
          $shareWithField.autocomplete("search", val);
        }, 0);
        return false;
      }

      e.preventDefault(); // Ensure that the keydown handler for the input field is not
      // called; otherwise it would try to add the recipient again, which
      // would fail.

      e.stopImmediatePropagation();
      $(e.target).attr('disabled', true).val(s.item.label);
      var $loading = this.$el.find('.shareWithLoading');
      var $confirm = this.$el.find('.shareWithConfirm');
      $loading.removeClass('hidden');
      $loading.addClass('inlineblock');
      $confirm.addClass('hidden');
      this._pendingOperationsCount++;
      this.model.addShare(s.item.value, {
        success: function success() {
          // Adding a share changes the suggestions.
          self._lastSuggestions = undefined;
          $(e.target).val('').attr('disabled', false);
          self._pendingOperationsCount--;

          if (self._pendingOperationsCount === 0) {
            $loading.addClass('hidden');
            $loading.removeClass('inlineblock');
            $confirm.removeClass('hidden');
          }
        },
        error: function error(obj, msg) {
          OC.Notification.showTemporary(msg);
          $(e.target).attr('disabled', false).autocomplete('search', $(e.target).val());
          self._pendingOperationsCount--;

          if (self._pendingOperationsCount === 0) {
            $loading.addClass('hidden');
            $loading.removeClass('inlineblock');
            $confirm.removeClass('hidden');
          }
        }
      });
    },
    _confirmShare: function _confirmShare() {
      var self = this;
      var $shareWithField = $('.shareWithField');
      var $loading = this.$el.find('.shareWithLoading');
      var $confirm = this.$el.find('.shareWithConfirm');
      $loading.removeClass('hidden');
      $loading.addClass('inlineblock');
      $confirm.addClass('hidden');
      this._pendingOperationsCount++;
      $shareWithField.prop('disabled', true); // Disabling the autocompletion does not clear its search timeout;
      // removing the focus from the input field does, but only if the
      // autocompletion is not disabled when the field loses the focus.
      // Thus, the field has to be disabled before disabling the
      // autocompletion to prevent an old pending search result from
      // appearing once the field is enabled again.

      $shareWithField.autocomplete('close');
      $shareWithField.autocomplete('disable');

      var restoreUI = function restoreUI() {
        self._pendingOperationsCount--;

        if (self._pendingOperationsCount === 0) {
          $loading.addClass('hidden');
          $loading.removeClass('inlineblock');
          $confirm.removeClass('hidden');
        }

        $shareWithField.prop('disabled', false);
        $shareWithField.focus();
      };

      var perPage = parseInt(oc_config['sharing.maxAutocompleteResults'], 10) || 200;

      this._getSuggestions($shareWithField.val(), perPage, this.model, this._lookup).done(function (suggestions, exactMatches) {
        if (suggestions.length === 0) {
          restoreUI();
          $shareWithField.autocomplete('enable'); // There is no need to show an error message here; it will
          // be automatically shown when the autocomplete is activated
          // again (due to the focus on the field) and it finds no
          // matches.

          return;
        }

        if (exactMatches.length !== 1) {
          restoreUI();
          $shareWithField.autocomplete('enable');
          return;
        }

        var actionSuccess = function actionSuccess() {
          // Adding a share changes the suggestions.
          self._lastSuggestions = undefined;
          $shareWithField.val('');
          restoreUI();
          $shareWithField.autocomplete('enable');
        };

        var actionError = function actionError(obj, msg) {
          restoreUI();
          $shareWithField.autocomplete('enable');
          OC.Notification.showTemporary(msg);
        };

        self.model.addShare(exactMatches[0].value, {
          success: actionSuccess,
          error: actionError
        });
      }).fail(function (message) {
        restoreUI();
        $shareWithField.autocomplete('enable'); // There is no need to show an error message here; it will be
        // automatically shown when the autocomplete is activated again
        // (due to the focus on the field) and getting the suggestions
        // fail.
      });
    },
    _toggleLoading: function _toggleLoading(state) {
      this._loading = state;
      this.$el.find('.subView').toggleClass('hidden', state);
      this.$el.find('.loading').toggleClass('hidden', !state);
    },
    _onRequest: function _onRequest() {
      // only show the loading spinner for the first request (for now)
      if (!this._loadingOnce) {
        this._toggleLoading(true);
      }
    },
    _onEndRequest: function _onEndRequest() {
      var self = this;

      this._toggleLoading(false);

      if (!this._loadingOnce) {
        this._loadingOnce = true; // the first time, focus on the share field after the spinner disappeared

        if (!OC.Util.isIE()) {
          _.defer(function () {
            self.$('.shareWithField').focus();
          });
        }
      }
    },
    render: function render() {
      var self = this;
      var baseTemplate = OC.Share.Templates['sharedialogview'];
      this.$el.html(baseTemplate({
        cid: this.cid,
        shareLabel: t('core', 'Share'),
        sharePlaceholder: this._renderSharePlaceholderPart(),
        isSharingAllowed: this.model.sharePermissionPossible()
      }));
      var $shareField = this.$el.find('.shareWithField');

      if ($shareField.length) {
        var shareFieldKeydownHandler = function shareFieldKeydownHandler(event) {
          if (event.keyCode !== 13) {
            return true;
          }

          self._confirmShare();

          return false;
        };

        $shareField.autocomplete({
          minLength: 0,
          delay: 750,
          focus: function focus(event) {
            event.preventDefault();
          },
          source: this.autocompleteHandler,
          select: this._onSelectRecipient,
          open: function open() {
            var autocomplete = $(this).autocomplete('widget');
            var numberOfItems = autocomplete.find('li').size();
            autocomplete.removeClass('item-count-1');
            autocomplete.removeClass('item-count-2');

            if (numberOfItems <= 2) {
              autocomplete.addClass('item-count-' + numberOfItems);
            }
          }
        }).data('ui-autocomplete')._renderItem = this.autocompleteRenderItem;
        $shareField.on('keydown', null, shareFieldKeydownHandler);
      }

      this.resharerInfoView.$el = this.$el.find('.resharerInfoView');
      this.resharerInfoView.render();
      this.linkShareView.$el = this.$el.find('.linkShareView');
      this.linkShareView.render();
      this.shareeListView.$el = this.$el.find('.shareeListView');
      this.shareeListView.render();
      this.$el.find('.hasTooltip').tooltip();
      return this;
    },

    /**
     * sets whether share by link should be displayed or not. Default is
     * true.
     *
     * @param {bool} showLink
     */
    setShowLink: function setShowLink(showLink) {
      this._showLink = typeof showLink === 'boolean' ? showLink : true;
      this.linkShareView.showLink = this._showLink;
    },
    _renderSharePlaceholderPart: function _renderSharePlaceholderPart() {
      var allowRemoteSharing = this.configModel.get('isRemoteShareAllowed');
      var allowMailSharing = this.configModel.get('isMailShareAllowed');

      if (!allowRemoteSharing && allowMailSharing) {
        return t('core', 'Name or email address...');
      }

      if (allowRemoteSharing && !allowMailSharing) {
        return t('core', 'Name or federated cloud ID...');
      }

      if (allowRemoteSharing && allowMailSharing) {
        return t('core', 'Name, federated cloud ID or email address...');
      }

      return t('core', 'Name...');
    }
  });
  OC.Share.ShareDialogView = ShareDialogView;
})();

/***/ }),

/***/ "./core/js/shareitemmodel.js":
/*!***********************************!*\
  !*** ./core/js/shareitemmodel.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  if (!OC.Share) {
    OC.Share = {};
    OC.Share.Types = {};
  }
  /**
   * @typedef {object} OC.Share.Types.LinkShareInfo
   * @property {string} token
   * @property {bool} hideDownload
   * @property {string|null} password
   * @property {bool} sendPasswordByTalk
   * @property {number} permissions
   * @property {Date} expiration
   * @property {number} stime share time
   */

  /**
   * @typedef {object} OC.Share.Types.Reshare
   * @property {string} uid_owner
   * @property {number} share_type
   * @property {string} share_with
   * @property {string} displayname_owner
   * @property {number} permissions
   */

  /**
   * @typedef {object} OC.Share.Types.ShareInfo
   * @property {number} share_type
   * @property {number} permissions
   * @property {number} file_source optional
   * @property {number} item_source
   * @property {string} token
   * @property {string} share_with
   * @property {string} share_with_displayname
   * @property {string} share_with_avatar
   * @property {string} mail_send
   * @property {Date} expiration optional?
   * @property {number} stime optional?
   * @property {string} uid_owner
   * @property {string} displayname_owner
   */

  /**
   * @typedef {object} OC.Share.Types.ShareItemInfo
   * @property {OC.Share.Types.Reshare} reshare
   * @property {OC.Share.Types.ShareInfo[]} shares
   * @property {OC.Share.Types.LinkShareInfo|undefined} linkShare
   */

  /**
   * These properties are sometimes returned by the server as strings instead
   * of integers, so we need to convert them accordingly...
   */


  var SHARE_RESPONSE_INT_PROPS = ['id', 'file_parent', 'mail_send', 'file_source', 'item_source', 'permissions', 'storage', 'share_type', 'parent', 'stime'];
  /**
   * @class OCA.Share.ShareItemModel
   * @classdesc
   *
   * Represents the GUI of the share dialogue
   *
   * // FIXME: use OC Share API once #17143 is done
   *
   * // TODO: this really should be a collection of share item models instead,
   * where the link share is one of them
   */

  var ShareItemModel = OC.Backbone.Model.extend({
    /**
     * share id of the link share, if applicable
     */
    _linkShareId: null,
    initialize: function initialize(attributes, options) {
      if (!_.isUndefined(options.configModel)) {
        this.configModel = options.configModel;
      }

      if (!_.isUndefined(options.fileInfoModel)) {
        /** @type {OC.Files.FileInfo} **/
        this.fileInfoModel = options.fileInfoModel;
      }

      _.bindAll(this, 'addShare');
    },
    defaults: {
      allowPublicUploadStatus: false,
      permissions: 0,
      linkShares: []
    },

    /**
     * Saves the current link share information.
     *
     * This will trigger an ajax call and, if successful, refetch the model
     * afterwards. Callbacks "success", "error" and "complete" can be given
     * in the options object; "success" is called after a successful save
     * once the model is refetch, "error" is called after a failed save, and
     * "complete" is called both after a successful save and after a failed
     * save. Note that "complete" is called before "success" and "error" are
     * called (unlike in jQuery, in which it is called after them); this
     * ensures that "complete" is called even if refetching the model fails.
     *
     * TODO: this should be a separate model
     */
    saveLinkShare: function saveLinkShare(attributes, options) {
      options = options || {};
      attributes = _.extend({}, attributes);
      var shareId = null;
      var call; // oh yeah...

      if (attributes.expiration) {
        attributes.expireDate = attributes.expiration;
        delete attributes.expiration;
      }

      var linkShares = this.get('linkShares');

      var shareIndex = _.findIndex(linkShares, function (share) {
        return share.id === attributes.cid;
      });

      if (linkShares.length > 0 && shareIndex !== -1) {
        shareId = linkShares[shareIndex].id; // note: update can only update a single value at a time

        call = this.updateShare(shareId, attributes, options);
      } else {
        attributes = _.defaults(attributes, {
          hideDownload: false,
          password: '',
          passwordChanged: false,
          sendPasswordByTalk: false,
          permissions: OC.PERMISSION_READ,
          expireDate: this.configModel.getDefaultExpirationDateString(),
          shareType: OC.Share.SHARE_TYPE_LINK
        });
        call = this.addShare(attributes, options);
      }

      return call;
    },
    addShare: function addShare(attributes, options) {
      var shareType = attributes.shareType;
      attributes = _.extend({}, attributes); // get default permissions

      var defaultPermissions = OC.getCapabilities()['files_sharing']['default_permissions'] || OC.PERMISSION_ALL;
      var possiblePermissions = OC.PERMISSION_READ;

      if (this.updatePermissionPossible()) {
        possiblePermissions = possiblePermissions | OC.PERMISSION_UPDATE;
      }

      if (this.createPermissionPossible()) {
        possiblePermissions = possiblePermissions | OC.PERMISSION_CREATE;
      }

      if (this.deletePermissionPossible()) {
        possiblePermissions = possiblePermissions | OC.PERMISSION_DELETE;
      }

      if (this.configModel.get('isResharingAllowed') && this.sharePermissionPossible()) {
        possiblePermissions = possiblePermissions | OC.PERMISSION_SHARE;
      }

      attributes.permissions = defaultPermissions & possiblePermissions;

      if (_.isUndefined(attributes.path)) {
        attributes.path = this.fileInfoModel.getFullPath();
      }

      return this._addOrUpdateShare({
        type: 'POST',
        url: this._getUrl('shares'),
        data: attributes,
        dataType: 'json'
      }, options);
    },
    updateShare: function updateShare(shareId, attrs, options) {
      return this._addOrUpdateShare({
        type: 'PUT',
        url: this._getUrl('shares/' + encodeURIComponent(shareId)),
        data: attrs,
        dataType: 'json'
      }, options);
    },
    _addOrUpdateShare: function _addOrUpdateShare(ajaxSettings, options) {
      var self = this;
      options = options || {};
      return $.ajax(ajaxSettings).always(function () {
        if (_.isFunction(options.complete)) {
          options.complete(self);
        }
      }).done(function () {
        self.fetch().done(function () {
          if (_.isFunction(options.success)) {
            options.success(self);
          }
        });
      }).fail(function (xhr) {
        var msg = t('core', 'Error');
        var result = xhr.responseJSON;

        if (result && result.ocs && result.ocs.meta) {
          msg = result.ocs.meta.message;
        }

        if (_.isFunction(options.error)) {
          options.error(self, msg);
        } else {
          OC.dialogs.alert(msg, t('core', 'Error while sharing'));
        }
      });
    },

    /**
     * Deletes the share with the given id
     *
     * @param {int} shareId share id
     * @return {jQuery}
     */
    removeShare: function removeShare(shareId, options) {
      var self = this;
      options = options || {};
      return $.ajax({
        type: 'DELETE',
        url: this._getUrl('shares/' + encodeURIComponent(shareId))
      }).done(function () {
        self.fetch({
          success: function success() {
            if (_.isFunction(options.success)) {
              options.success(self);
            }
          }
        });
      }).fail(function (xhr) {
        var msg = t('core', 'Error');
        var result = xhr.responseJSON;

        if (result.ocs && result.ocs.meta) {
          msg = result.ocs.meta.message;
        }

        if (_.isFunction(options.error)) {
          options.error(self, msg);
        } else {
          OC.dialogs.alert(msg, t('core', 'Error removing share'));
        }
      });
    },

    /**
     * @returns {boolean}
     */
    isPublicUploadAllowed: function isPublicUploadAllowed() {
      return this.get('allowPublicUploadStatus');
    },
    isPublicEditingAllowed: function isPublicEditingAllowed() {
      return this.get('allowPublicEditingStatus');
    },

    /**
     * @returns {boolean}
     */
    isHideFileListSet: function isHideFileListSet() {
      return this.get('hideFileListStatus');
    },

    /**
     * @returns {boolean}
     */
    isFolder: function isFolder() {
      return this.get('itemType') === 'folder';
    },

    /**
     * @returns {boolean}
     */
    isFile: function isFile() {
      return this.get('itemType') === 'file';
    },

    /**
     * whether this item has reshare information
     * @returns {boolean}
     */
    hasReshare: function hasReshare() {
      var reshare = this.get('reshare');
      return _.isObject(reshare) && !_.isUndefined(reshare.uid_owner);
    },

    /**
     * whether this item has user share information
     * @returns {boolean}
     */
    hasUserShares: function hasUserShares() {
      return this.getSharesWithCurrentItem().length > 0;
    },

    /**
     * Returns whether this item has link shares
     *
     * @return {bool} true if a link share exists, false otherwise
     */
    hasLinkShares: function hasLinkShares() {
      var linkShares = this.get('linkShares');

      if (linkShares && linkShares.length > 0) {
        return true;
      }

      return false;
    },

    /**
     * @returns {string}
     */
    getReshareOwner: function getReshareOwner() {
      return this.get('reshare').uid_owner;
    },

    /**
     * @returns {string}
     */
    getReshareOwnerDisplayname: function getReshareOwnerDisplayname() {
      return this.get('reshare').displayname_owner;
    },

    /**
     * @returns {string}
     */
    getReshareNote: function getReshareNote() {
      return this.get('reshare').note;
    },

    /**
     * @returns {string}
     */
    getReshareWith: function getReshareWith() {
      return this.get('reshare').share_with;
    },

    /**
     * @returns {string}
     */
    getReshareWithDisplayName: function getReshareWithDisplayName() {
      var reshare = this.get('reshare');
      return reshare.share_with_displayname || reshare.share_with;
    },

    /**
     * @returns {number}
     */
    getReshareType: function getReshareType() {
      return this.get('reshare').share_type;
    },
    getExpireDate: function getExpireDate(shareIndex) {
      return this._shareExpireDate(shareIndex);
    },
    getNote: function getNote(shareIndex) {
      return this._shareNote(shareIndex);
    },

    /**
     * Returns all share entries that only apply to the current item
     * (file/folder)
     *
     * @return {Array.<OC.Share.Types.ShareInfo>}
     */
    getSharesWithCurrentItem: function getSharesWithCurrentItem() {
      var shares = this.get('shares') || [];
      var fileId = this.fileInfoModel.get('id');
      return _.filter(shares, function (share) {
        return share.item_source === fileId;
      });
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getShareWith: function getShareWith(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.share_with;
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getShareWithDisplayName: function getShareWithDisplayName(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.share_with_displayname;
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getShareWithAvatar: function getShareWithAvatar(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.share_with_avatar;
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getSharedBy: function getSharedBy(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.uid_owner;
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getSharedByDisplayName: function getSharedByDisplayName(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.displayname_owner;
    },

    /**
     * @param shareIndex
     * @returns {string}
     */
    getFileOwnerUid: function getFileOwnerUid(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.uid_file_owner;
    },

    /**
     * returns the array index of a sharee for a provided shareId
     *
     * @param shareId
     * @returns {number}
     */
    findShareWithIndex: function findShareWithIndex(shareId) {
      var shares = this.get('shares');

      if (!_.isArray(shares)) {
        throw "Unknown Share";
      }

      for (var i = 0; i < shares.length; i++) {
        var shareWith = shares[i];

        if (shareWith.id === shareId) {
          return i;
        }
      }

      throw "Unknown Sharee";
    },
    getShareType: function getShareType(shareIndex) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.share_type;
    },

    /**
     * whether a share from shares has the requested permission
     *
     * @param {number} shareIndex
     * @param {number} permission
     * @returns {boolean}
     * @private
     */
    _shareHasPermission: function _shareHasPermission(shareIndex, permission) {
      /** @type OC.Share.Types.ShareInfo **/
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return (share.permissions & permission) === permission;
    },
    _shareExpireDate: function _shareExpireDate(shareIndex) {
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      var date2 = share.expiration;
      return date2;
    },
    _shareNote: function _shareNote(shareIndex) {
      var share = this.get('shares')[shareIndex];

      if (!_.isObject(share)) {
        throw "Unknown Share";
      }

      return share.note;
    },

    /**
     * @return {int}
     */
    getPermissions: function getPermissions() {
      return this.get('permissions');
    },

    /**
     * @returns {boolean}
     */
    sharePermissionPossible: function sharePermissionPossible() {
      return (this.get('permissions') & OC.PERMISSION_SHARE) === OC.PERMISSION_SHARE;
    },

    /**
     * @param {number} shareIndex
     * @returns {boolean}
     */
    hasSharePermission: function hasSharePermission(shareIndex) {
      return this._shareHasPermission(shareIndex, OC.PERMISSION_SHARE);
    },

    /**
     * @returns {boolean}
     */
    createPermissionPossible: function createPermissionPossible() {
      return (this.get('permissions') & OC.PERMISSION_CREATE) === OC.PERMISSION_CREATE;
    },

    /**
     * @param {number} shareIndex
     * @returns {boolean}
     */
    hasCreatePermission: function hasCreatePermission(shareIndex) {
      return this._shareHasPermission(shareIndex, OC.PERMISSION_CREATE);
    },

    /**
     * @returns {boolean}
     */
    updatePermissionPossible: function updatePermissionPossible() {
      return (this.get('permissions') & OC.PERMISSION_UPDATE) === OC.PERMISSION_UPDATE;
    },

    /**
     * @param {number} shareIndex
     * @returns {boolean}
     */
    hasUpdatePermission: function hasUpdatePermission(shareIndex) {
      return this._shareHasPermission(shareIndex, OC.PERMISSION_UPDATE);
    },

    /**
     * @returns {boolean}
     */
    deletePermissionPossible: function deletePermissionPossible() {
      return (this.get('permissions') & OC.PERMISSION_DELETE) === OC.PERMISSION_DELETE;
    },

    /**
     * @param {number} shareIndex
     * @returns {boolean}
     */
    hasDeletePermission: function hasDeletePermission(shareIndex) {
      return this._shareHasPermission(shareIndex, OC.PERMISSION_DELETE);
    },
    hasReadPermission: function hasReadPermission(shareIndex) {
      return this._shareHasPermission(shareIndex, OC.PERMISSION_READ);
    },

    /**
     * @returns {boolean}
     */
    editPermissionPossible: function editPermissionPossible() {
      return this.createPermissionPossible() || this.updatePermissionPossible() || this.deletePermissionPossible();
    },

    /**
     * @returns {string}
     *     The state that the 'can edit' permission checkbox should have.
     *     Possible values:
     *     - empty string: no permission
     *     - 'checked': all applicable permissions
     *     - 'indeterminate': some but not all permissions
     */
    editPermissionState: function editPermissionState(shareIndex) {
      var hcp = this.hasCreatePermission(shareIndex);
      var hup = this.hasUpdatePermission(shareIndex);
      var hdp = this.hasDeletePermission(shareIndex);

      if (this.isFile()) {
        if (hcp || hup || hdp) {
          return 'checked';
        }

        return '';
      }

      if (!hcp && !hup && !hdp) {
        return '';
      }

      if (this.createPermissionPossible() && !hcp || this.updatePermissionPossible() && !hup || this.deletePermissionPossible() && !hdp) {
        return 'indeterminate';
      }

      return 'checked';
    },

    /**
     * @returns {int}
     */
    linkSharePermissions: function linkSharePermissions(shareId) {
      var linkShares = this.get('linkShares');

      var shareIndex = _.findIndex(linkShares, function (share) {
        return share.id === shareId;
      });

      if (!this.hasLinkShares()) {
        return -1;
      } else if (linkShares.length > 0 && shareIndex !== -1) {
        return linkShares[shareIndex].permissions;
      }

      return -1;
    },
    _getUrl: function _getUrl(base, params) {
      params = _.extend({
        format: 'json'
      }, params || {});
      return OC.linkToOCS('apps/files_sharing/api/v1', 2) + base + '?' + OC.buildQueryString(params);
    },
    _fetchShares: function _fetchShares() {
      var path = this.fileInfoModel.getFullPath();
      return $.ajax({
        type: 'GET',
        url: this._getUrl('shares', {
          path: path,
          reshares: true
        })
      });
    },
    _fetchReshare: function _fetchReshare() {
      // only fetch original share once
      if (!this._reshareFetched) {
        var path = this.fileInfoModel.getFullPath();
        this._reshareFetched = true;
        return $.ajax({
          type: 'GET',
          url: this._getUrl('shares', {
            path: path,
            shared_with_me: true
          })
        });
      } else {
        return $.Deferred().resolve([{
          ocs: {
            data: [this.get('reshare')]
          }
        }]);
      }
    },

    /**
     * Group reshares into a single super share element.
     * Does this by finding the most precise share and
     * combines the permissions to be the most permissive.
     *
     * @param {Array} reshares
     * @return {Object} reshare
     */
    _groupReshares: function _groupReshares(reshares) {
      if (!reshares || !reshares.length) {
        return false;
      }

      var superShare = reshares.shift();
      var combinedPermissions = superShare.permissions;

      _.each(reshares, function (reshare) {
        // use share have higher priority than group share
        if (reshare.share_type === OC.Share.SHARE_TYPE_USER && superShare.share_type === OC.Share.SHARE_TYPE_GROUP) {
          superShare = reshare;
        }

        combinedPermissions |= reshare.permissions;
      });

      superShare.permissions = combinedPermissions;
      return superShare;
    },
    fetch: function fetch(options) {
      var model = this;
      this.trigger('request', this);
      var deferred = $.when(this._fetchShares(), this._fetchReshare());
      deferred.done(function (data1, data2) {
        model.trigger('sync', 'GET', this);
        var sharesMap = {};

        _.each(data1[0].ocs.data, function (shareItem) {
          sharesMap[shareItem.id] = shareItem;
        });

        var reshare = false;

        if (data2[0].ocs.data.length) {
          reshare = model._groupReshares(data2[0].ocs.data);
        }

        model.set(model.parse({
          shares: sharesMap,
          reshare: reshare
        }));

        if (!_.isUndefined(options) && _.isFunction(options.success)) {
          options.success();
        }
      });
      return deferred;
    },

    /**
     * Updates OC.Share.itemShares and OC.Share.statuses.
     *
     * This is required in case the user navigates away and comes back,
     * the share statuses from the old arrays are still used to fill in the icons
     * in the file list.
     */
    _legacyFillCurrentShares: function _legacyFillCurrentShares(shares) {
      var fileId = this.fileInfoModel.get('id');

      if (!shares || !shares.length) {
        delete OC.Share.statuses[fileId];
        OC.Share.currentShares = {};
        OC.Share.itemShares = [];
        return;
      }

      var currentShareStatus = OC.Share.statuses[fileId];

      if (!currentShareStatus) {
        currentShareStatus = {
          link: false
        };
        OC.Share.statuses[fileId] = currentShareStatus;
      }

      currentShareStatus.link = false;
      OC.Share.currentShares = {};
      OC.Share.itemShares = [];

      _.each(shares,
      /**
       * @param {OC.Share.Types.ShareInfo} share
       */
      function (share) {
        if (share.share_type === OC.Share.SHARE_TYPE_LINK) {
          OC.Share.itemShares[share.share_type] = true;
          currentShareStatus.link = true;
        } else {
          if (!OC.Share.itemShares[share.share_type]) {
            OC.Share.itemShares[share.share_type] = [];
          }

          OC.Share.itemShares[share.share_type].push(share.share_with);
        }
      });
    },
    parse: function parse(data) {
      if (data === false) {
        console.warn('no data was returned');
        this.trigger('fetchError');
        return {};
      }

      var permissions = this.fileInfoModel.get('permissions');

      if (!_.isUndefined(data.reshare) && !_.isUndefined(data.reshare.permissions) && data.reshare.uid_owner !== OC.currentUser) {
        permissions = permissions & data.reshare.permissions;
      }

      var allowPublicUploadStatus = false;

      if (!_.isUndefined(data.shares)) {
        $.each(data.shares, function (key, value) {
          if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
            allowPublicUploadStatus = value.permissions & OC.PERMISSION_CREATE ? true : false;
            return true;
          }
        });
      }

      var allowPublicEditingStatus = true;

      if (!_.isUndefined(data.shares)) {
        $.each(data.shares, function (key, value) {
          if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
            allowPublicEditingStatus = value.permissions & OC.PERMISSION_UPDATE ? true : false;
            return true;
          }
        });
      }

      var hideFileListStatus = false;

      if (!_.isUndefined(data.shares)) {
        $.each(data.shares, function (key, value) {
          if (value.share_type === OC.Share.SHARE_TYPE_LINK) {
            hideFileListStatus = value.permissions & OC.PERMISSION_READ ? false : true;
            return true;
          }
        });
      }
      /** @type {OC.Share.Types.ShareInfo[]} **/


      var shares = _.map(data.shares, function (share) {
        // properly parse some values because sometimes the server
        // returns integers as string...
        var i;

        for (i = 0; i < SHARE_RESPONSE_INT_PROPS.length; i++) {
          var prop = SHARE_RESPONSE_INT_PROPS[i];

          if (!_.isUndefined(share[prop])) {
            share[prop] = parseInt(share[prop], 10);
          }
        }

        return share;
      });

      this._legacyFillCurrentShares(shares);

      var linkShares = []; // filter out the share by link

      shares = _.reject(shares,
      /**
       * @param {OC.Share.Types.ShareInfo} share
       */
      function (share) {
        var isShareLink = share.share_type === OC.Share.SHARE_TYPE_LINK && (share.file_source === this.get('itemSource') || share.item_source === this.get('itemSource'));

        if (isShareLink) {
          /**
           * Ignore reshared link shares for now
           * FIXME: Find a way to display properly
           */
          if (share.uid_owner !== OC.currentUser) {
            return;
          }

          var link = window.location.protocol + '//' + window.location.host;

          if (!share.token) {
            // pre-token link
            var fullPath = this.fileInfoModel.get('path') + '/' + this.fileInfoModel.get('name');
            var location = '/' + OC.currentUser + '/files' + fullPath;
            var type = this.fileInfoModel.isDirectory() ? 'folder' : 'file';
            link += OC.linkTo('', 'public.php') + '?service=files&' + type + '=' + encodeURIComponent(location);
          } else {
            link += OC.generateUrl('/s/') + share.token;
          }

          linkShares.push(_.extend({}, share, {
            // hide_download is returned as an int, so force it
            // to a boolean
            hideDownload: !!share.hide_download,
            password: share.share_with,
            sendPasswordByTalk: share.send_password_by_talk
          }));
          return share;
        }
      }, this);
      return {
        reshare: data.reshare,
        shares: shares,
        linkShares: linkShares,
        permissions: permissions,
        allowPublicUploadStatus: allowPublicUploadStatus,
        allowPublicEditingStatus: allowPublicEditingStatus,
        hideFileListStatus: hideFileListStatus
      };
    },

    /**
     * Parses a string to an valid integer (unix timestamp)
     * @param time
     * @returns {*}
     * @internal Only used to work around a bug in the backend
     */
    _parseTime: function _parseTime(time) {
      if (_.isString(time)) {
        // skip empty strings and hex values
        if (time === '' || time.length > 1 && time[0] === '0' && time[1] === 'x') {
          return null;
        }

        time = parseInt(time, 10);

        if (isNaN(time)) {
          time = null;
        }
      }

      return time;
    },

    /**
     * Returns a list of share types from the existing shares.
     *
     * @return {Array.<int>} array of share types
     */
    getShareTypes: function getShareTypes() {
      var result;
      result = _.pluck(this.getSharesWithCurrentItem(), 'share_type');

      if (this.hasLinkShares()) {
        result.push(OC.Share.SHARE_TYPE_LINK);
      }

      return _.uniq(result);
    }
  });
  OC.Share.ShareItemModel = ShareItemModel;
})();

/***/ }),

/***/ "./core/js/sharesocialmanager.js":
/*!***************************************!*\
  !*** ./core/js/sharesocialmanager.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
(function () {
  if (!OC.Share) {
    OC.Share = {};
  }

  OC.Share.Social = {};
  var SocialModel = OC.Backbone.Model.extend({
    defaults: {
      /** used for sorting social buttons */
      key: null,

      /** url to open, {{reference}} will be replaced with the link */
      url: null,

      /** Name to show in the tooltip */
      name: null,

      /** Icon class to display */
      iconClass: null,

      /** Open in new windows */
      newWindow: true
    }
  });
  OC.Share.Social.Model = SocialModel;
  var SocialCollection = OC.Backbone.Collection.extend({
    model: OC.Share.Social.Model,
    comparator: 'key'
  });
  OC.Share.Social.Collection = new SocialCollection();
})();

/***/ }),

/***/ "./core/js/sharetemplates.js":
/*!***********************************!*\
  !*** ./core/js/sharetemplates.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function () {
  var template = Handlebars.template,
      templates = OC.Share.Templates = OC.Share.Templates || {};
  templates['sharedialoglinkshareview'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var stack1,
          alias1 = depth0 != null ? depth0 : container.nullContext || {};
      return "<ul class=\"shareWithList\">\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.nolinkShares : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(2, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + ((stack1 = helpers.each.call(alias1, depth0 != null ? depth0.linkShares : depth0, {
        "name": "each",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "</ul>\n";
    },
    "2": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "		<li data-share-id=\"" + alias4((helper = (helper = helpers.newShareId || (depth0 != null ? depth0.newShareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "newShareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\">" + alias4((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "newShareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-add new-share has-tooltip " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" title=\"" + alias4((helper = (helper = helpers.newShareTitle || (depth0 != null ? depth0.newShareTitle : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "newShareTitle",
        "hash": {},
        "data": data
      }) : helper)) + "\"></a>\n					<span class=\"icon icon-loading-small " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\"></span>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "				</div>\n			</span>\n		</li>\n";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      return "hidden";
    },
    "5": function _(container, depth0, helpers, partials, data) {
      var stack1, helper;
      return "						" + ((stack1 = (helper = (helper = helpers.pendingPopoverMenu || (depth0 != null ? depth0.pendingPopoverMenu : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "pendingPopoverMenu",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + "\n";
    },
    "7": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "		<li data-share-id=\"" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\" title=\"" + alias4((helper = (helper = helpers.linkShareCreationDate || (depth0 != null ? depth0.linkShareCreationDate : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "linkShareCreationDate",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.linkShareLabel || (depth0 != null ? depth0.linkShareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "linkShareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n			\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"clipboard-button icon icon-clippy has-tooltip\" data-clipboard-text=\"" + alias4((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareLinkURL",
        "hash": {},
        "data": data
      }) : helper)) + "\" title=\"" + alias4((helper = (helper = helpers.copyLabel || (depth0 != null ? depth0.copyLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "copyLabel",
        "hash": {},
        "data": data
      }) : helper)) + "\"></a>\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-more " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\"></a>\n					<span class=\"icon icon-loading-small " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\"></span>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.showPending : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.program(8, data, 0),
        "data": data
      })) != null ? stack1 : "") + "				</div>\n			</span>\n		</li>\n";
    },
    "8": function _(container, depth0, helpers, partials, data) {
      var stack1, helper;
      return "						" + ((stack1 = (helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "popoverMenu",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + "\n";
    },
    "10": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.noSharingPlaceholder : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(11, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n";
    },
    "11": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<input id=\"shareWith-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"shareWithField\" type=\"text\" placeholder=\"" + alias4((helper = (helper = helpers.noSharingPlaceholder || (depth0 != null ? depth0.noSharingPlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "noSharingPlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" disabled=\"disabled\" />";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.shareAllowed : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.program(10, data, 0),
        "data": data
      })) != null ? stack1 : "";
    },
    "useData": true
  });
  templates['sharedialoglinkshareview_popover_menu'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\"" + alias4((helper = (helper = helpers.publicUploadRValue || (depth0 != null ? depth0.publicUploadRValue : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRValue",
        "hash": {},
        "data": data
      }) : helper)) + "\" id=\"sharingDialogAllowPublicUpload-r-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"radio publicUploadRadio\" " + ((stack1 = (helper = (helper = helpers.publicUploadRChecked || (depth0 != null ? depth0.publicUploadRChecked : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRChecked",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + " />\n					<label for=\"sharingDialogAllowPublicUpload-r-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.publicUploadRLabel || (depth0 != null ? depth0.publicUploadRLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\"" + alias4((helper = (helper = helpers.publicUploadRWValue || (depth0 != null ? depth0.publicUploadRWValue : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRWValue",
        "hash": {},
        "data": data
      }) : helper)) + "\" id=\"sharingDialogAllowPublicUpload-rw-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"radio publicUploadRadio\" " + ((stack1 = (helper = (helper = helpers.publicUploadRWChecked || (depth0 != null ? depth0.publicUploadRWChecked : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRWChecked",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + " />\n					<label for=\"sharingDialogAllowPublicUpload-rw-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.publicUploadRWLabel || (depth0 != null ? depth0.publicUploadRWLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadRWLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\"" + alias4((helper = (helper = helpers.publicUploadWValue || (depth0 != null ? depth0.publicUploadWValue : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadWValue",
        "hash": {},
        "data": data
      }) : helper)) + "\" id=\"sharingDialogAllowPublicUpload-w-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"radio publicUploadRadio\" " + ((stack1 = (helper = (helper = helpers.publicUploadWChecked || (depth0 != null ? depth0.publicUploadWChecked : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadWChecked",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + " />\n					<label for=\"sharingDialogAllowPublicUpload-w-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.publicUploadWLabel || (depth0 != null ? depth0.publicUploadWLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicUploadWLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li id=\"allowPublicEditingWrapper\">\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"allowPublicEditing\" id=\"sharingDialogAllowPublicEditing-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"checkbox publicEditingCheckbox\" " + ((stack1 = (helper = (helper = helpers.publicEditingChecked || (depth0 != null ? depth0.publicEditingChecked : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicEditingChecked",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + " />\n					<label for=\"sharingDialogAllowPublicEditing-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.publicEditingLabel || (depth0 != null ? depth0.publicEditingLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "publicEditingLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n";
    },
    "5": function _(container, depth0, helpers, partials, data) {
      return "checked=\"checked\"";
    },
    "7": function _(container, depth0, helpers, partials, data) {
      return "disabled=\"disabled\"";
    },
    "9": function _(container, depth0, helpers, partials, data) {
      return "hidden";
    },
    "11": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li>\n				<span class=\"shareOption menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"passwordByTalk\" id=\"passwordByTalk-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"checkbox passwordByTalkCheckbox\"\n					" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordByTalkSet : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " />\n					<label for=\"passwordByTalk-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordByTalkLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n";
    },
    "13": function _(container, depth0, helpers, partials, data) {
      return "datepicker";
    },
    "15": function _(container, depth0, helpers, partials, data) {
      var helper;
      return container.escapeExpression((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "expireDate",
        "hash": {},
        "data": data
      }) : helper));
    },
    "17": function _(container, depth0, helpers, partials, data) {
      var helper;
      return container.escapeExpression((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "defaultExpireDate",
        "hash": {},
        "data": data
      }) : helper));
    },
    "19": function _(container, depth0, helpers, partials, data) {
      return "readonly";
    },
    "21": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li>\n				<a href=\"#\" class=\"menuitem pop-up\" data-url=\"" + alias4((helper = (helper = helpers.url || (depth0 != null ? depth0.url : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "url",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-window=\"" + alias4((helper = (helper = helpers.newWindow || (depth0 != null ? depth0.newWindow : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "newWindow",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n					<span class=\"icon " + alias4((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "iconClass",
        "hash": {},
        "data": data
      }) : helper)) + "\"></span>\n					<span>" + alias4((helper = (helper = helpers.label || (depth0 != null ? depth0.label : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "label",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n				</a>\n			</li>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<div class=\"popovermenu menu\">\n	<ul>\n		<li class=\"hidden linkTextMenu\">\n			<span class=\"menuitem icon-link-text\">\n				<input id=\"linkText-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"linkText\" type=\"text\" readonly=\"readonly\" value=\"" + alias4((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareLinkURL",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n			</span>\n		</li>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.publicUpload : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.publicEditing : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"hideDownload\" id=\"sharingDialogHideDownload-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"checkbox hideDownloadCheckbox\"\n					" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hideDownload : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " />\n					<label for=\"sharingDialogHideDownload-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.hideDownloadLabel || (depth0 != null ? depth0.hideDownloadLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "hideDownloadLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<input type=\"checkbox\" name=\"showPassword\" id=\"showPassword-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"checkbox showPasswordCheckbox\"\n					" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordSet : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordEnforced : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " value=\"1\" />\n					<label for=\"showPassword-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.enablePasswordLabel || (depth0 != null ? depth0.enablePasswordLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "enablePasswordLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n			<li class=\"" + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.isPasswordSet : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(9, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " linkPassMenu\">\n				<span class=\"menuitem icon-share-pass\">\n					<input id=\"linkPassText-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"linkPassText\" type=\"password\" placeholder=\"" + alias4((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordPlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" autocomplete=\"new-password\" />\n					<input type=\"submit\" class=\"icon-confirm share-pass-submit\" value=\"\" />\n					<span class=\"icon icon-loading-small hidden\"></span>\n				</span>\n			</li>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.showPasswordByTalkCheckBox : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(11, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\"\n				" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isExpirationEnforced : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " />\n				<label for=\"expireDate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expireDateLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n			</span>\n		</li>\n		<li class=\"" + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(9, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n			<span class=\"menuitem icon-expiredate expirationDateContainer-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n				<label for=\"expirationDatePicker-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"hidden-visually\" value=\"" + alias4((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationDate",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				<!-- do not use the datepicker if enforced -->\n				<input id=\"expirationDatePicker-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"" + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.isExpirationEnforced : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(13, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" type=\"text\"\n					placeholder=\"" + alias4((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationDatePlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" value=\"" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(15, data, 0),
        "inverse": container.program(17, data, 0),
        "data": data
      })) != null ? stack1 : "") + "\"\n					data-max-date=\"" + alias4((helper = (helper = helpers.maxDate || (depth0 != null ? depth0.maxDate : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "maxDate",
        "hash": {},
        "data": data
      }) : helper)) + "\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isExpirationEnforced : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(19, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " />\n			</span>\n			</li>\n		<li>\n			<a href=\"#\" class=\"share-add\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-edit\"></span>\n				<span>" + alias4((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "addNoteLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n				<input type=\"button\" class=\"share-note-delete icon-delete " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasNote : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(9, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n			</a>\n		</li>\n		<li class=\"share-note-form share-note-link " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasNote : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(9, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n			<span class=\"menuitem icon-note\">\n				<textarea class=\"share-note\">" + alias4((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareNote",
        "hash": {},
        "data": data
      }) : helper)) + "</textarea>\n				<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n			</span>\n		</li>\n" + ((stack1 = helpers.each.call(alias1, depth0 != null ? depth0.social : depth0, {
        "name": "each",
        "hash": {},
        "fn": container.program(21, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>" + alias4((helper = (helper = helpers.unshareLinkLabel || (depth0 != null ? depth0.unshareLinkLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "unshareLinkLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span></a>\n		</li>\n		<li>\n			<a href=\"#\" class=\"new-share\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-add\"></span>\n				<span>" + alias4((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "newShareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n			</a>\n		</li>\n	</ul>\n</div>\n";
    },
    "useData": true
  });
  templates['sharedialoglinkshareview_popover_menu_pending'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li>\n				<span class=\"menuitem icon-info\">\n					<p>" + alias4((helper = (helper = helpers.enforcedPasswordLabel || (depth0 != null ? depth0.enforcedPasswordLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "enforcedPasswordLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</p>\n				</span>\n			</li>\n			<li class=\"linkPassMenu\">\n				<span class=\"menuitem\">\n					<form autocomplete=\"off\" class=\"enforcedPassForm\">\n						<input id=\"enforcedPassText\" required class=\"enforcedPassText\" type=\"password\"\n							placeholder=\"" + alias4((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordPlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" autocomplete=\"enforcedPassText\" minlength=\"" + alias4((helper = (helper = helpers.minPasswordLength || (depth0 != null ? depth0.minPasswordLength : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "minPasswordLength",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n						<input type=\"submit\" value=\" \" class=\"primary icon-checkmark-white\">\n					</form>\n				</span>\n			</li>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1;
      return "<div class=\"popovermenu open menu pending\">\n	<ul>\n" + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isPasswordEnforced : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "	</ul>\n</div>\n";
    },
    "useData": true
  });
  templates['sharedialogresharerinfoview'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var helper;
      return "<div class=\"share-note\">" + container.escapeExpression((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "shareNote",
        "hash": {},
        "data": data
      }) : helper)) + "</div>";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<span class=\"reshare\">\n	<div class=\"avatar\" data-userName=\"" + alias4((helper = (helper = helpers.reshareOwner || (depth0 != null ? depth0.reshareOwner : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "reshareOwner",
        "hash": {},
        "data": data
      }) : helper)) + "\"></div>\n	" + alias4((helper = (helper = helpers.sharedByText || (depth0 != null ? depth0.sharedByText : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "sharedByText",
        "hash": {},
        "data": data
      }) : helper)) + "\n</span>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasShareNote : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n";
    },
    "useData": true
  });
  templates['sharedialogshareelistview'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers.unless.call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isShareWithCurrentUser : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(2, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "";
    },
    "2": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "		<li data-share-id=\"" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-share-type=\"" + alias4((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareType",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-share-with=\"" + alias4((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWith",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n			<div class=\"avatar " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.modSeed : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" data-username=\"" + alias4((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWith",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-avatar=\"" + alias4((helper = (helper = helpers.shareWithAvatar || (depth0 != null ? depth0.shareWithAvatar : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWithAvatar",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-displayname=\"" + alias4((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWithDisplayName",
        "hash": {},
        "data": data
      }) : helper)) + "\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.modSeed : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(5, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "></div>\n			<span class=\"username\" title=\"" + alias4((helper = (helper = helpers.shareWithTitle || (depth0 != null ? depth0.shareWithTitle : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWithTitle",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWithDisplayName",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.canUpdateShareSettings : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "		</li>\n";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      return "imageplaceholderseed";
    },
    "5": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "data-seed=\"" + alias4((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareWith",
        "hash": {},
        "data": data
      }) : helper)) + " " + alias4((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareType",
        "hash": {},
        "data": data
      }) : helper)) + "\"";
    },
    "7": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {};
      return "			<span class=\"sharingOptionsGroup\">\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.editPermissionPossible : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(8, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "				<div tabindex=\"0\" class=\"share-menu\"><span class=\"icon icon-more\"></span>\n					" + ((stack1 = (helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(alias1, {
        "name": "popoverMenu",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + "\n				</div>\n			</span>\n";
    },
    "8": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "					<span>\n						<input id=\"canEdit-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"edit\" class=\"permissions checkbox\" />\n						<label for=\"canEdit-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.canEditLabel || (depth0 != null ? depth0.canEditLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "canEditLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n";
    },
    "10": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "		<li data-share-id=\"" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" data-share-type=\"" + alias4((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareType",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n			<div class=\"avatar\" data-username=\"" + alias4((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareInitiator",
        "hash": {},
        "data": data
      }) : helper)) + "\"></div>\n			<span class=\"has-tooltip username\" title=\"" + alias4((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareInitiator",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.shareInitiatorText || (depth0 != null ? depth0.shareInitiatorText : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareInitiatorText",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span class=\"hidden-visually\">" + alias4((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "unshareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span></a>\n			</span>\n		</li>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          alias1 = depth0 != null ? depth0 : container.nullContext || {};
      return "<ul id=\"shareWithList\" class=\"shareWithList\">\n" + ((stack1 = helpers.each.call(alias1, depth0 != null ? depth0.sharees : depth0, {
        "name": "each",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + ((stack1 = helpers.each.call(alias1, depth0 != null ? depth0.linkReshares : depth0, {
        "name": "each",
        "hash": {},
        "fn": container.program(10, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "</ul>\n";
    },
    "useData": true
  });
  templates['sharedialogshareelistview_popover_menu'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return " " + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.sharePermissionPossible : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(2, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " ";
    },
    "2": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return " " + ((stack1 = helpers.unless.call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isMailShare : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " ";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "\n			<li>\n				<span class=\"menuitem\">\n					<input id=\"canShare-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"share\" class=\"permissions checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasSharePermission : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " data-permissions=\"" + alias4((helper = (helper = helpers.sharePermission || (depth0 != null ? depth0.sharePermission : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "sharePermission",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n					<label for=\"canShare-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.canShareLabel || (depth0 != null ? depth0.canShareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "canShareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n				</li>\n			";
    },
    "4": function _(container, depth0, helpers, partials, data) {
      return "checked=\"checked\"";
    },
    "6": function _(container, depth0, helpers, partials, data) {
      var stack1,
          alias1 = depth0 != null ? depth0 : container.nullContext || {};
      return "			" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.createPermissionPossible : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n			" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.updatePermissionPossible : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(10, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n			" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.deletePermissionPossible : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(13, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n";
    },
    "7": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers.unless.call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isMailShare : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(8, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "";
    },
    "8": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canCreate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"create\" class=\"permissions checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasCreatePermission : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " data-permissions=\"" + alias4((helper = (helper = helpers.createPermission || (depth0 != null ? depth0.createPermission : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "createPermission",
        "hash": {},
        "data": data
      }) : helper)) + "\"/>\n						<label for=\"canCreate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.createPermissionLabel || (depth0 != null ? depth0.createPermissionLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "createPermissionLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n				</li>\n			";
    },
    "10": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers.unless.call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isMailShare : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(11, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "";
    },
    "11": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canUpdate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"update\" class=\"permissions checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasUpdatePermission : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " data-permissions=\"" + alias4((helper = (helper = helpers.updatePermission || (depth0 != null ? depth0.updatePermission : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "updatePermission",
        "hash": {},
        "data": data
      }) : helper)) + "\"/>\n						<label for=\"canUpdate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.updatePermissionLabel || (depth0 != null ? depth0.updatePermissionLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "updatePermissionLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n				</li>\n				";
    },
    "13": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers.unless.call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isMailShare : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(14, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "";
    },
    "14": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canDelete-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"delete\" class=\"permissions checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasDeletePermission : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " data-permissions=\"" + alias4((helper = (helper = helpers.deletePermission || (depth0 != null ? depth0.deletePermission : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "deletePermission",
        "hash": {},
        "data": data
      }) : helper)) + "\"/>\n						<label for=\"canDelete-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.deletePermissionLabel || (depth0 != null ? depth0.deletePermissionLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "deletePermissionLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n				</li>\n				";
    },
    "16": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasCreatePermission : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(17, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "			<li>\n				<span class=\"menuitem\">\n					<input id=\"password-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"password\" class=\"password checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordSet : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordSet : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(19, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" />\n					<label for=\"password-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				</span>\n			</li>\n			<li class=\"passwordMenu-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.isPasswordSet : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(22, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n				<span class=\"passwordContainer-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " icon-passwordmail menuitem\">\n					<label for=\"passwordField-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"hidden-visually\" value=\"" + alias4((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "password",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					<input id=\"passwordField-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"passwordField\" type=\"password\" placeholder=\"" + alias4((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordPlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" value=\"" + alias4((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordValue",
        "hash": {},
        "data": data
      }) : helper)) + "\" autocomplete=\"new-password\" />\n					<span class=\"icon-loading-small hidden\"></span>\n				</span>\n			</li>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isTalkEnabled : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(24, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "");
    },
    "17": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"secureDrop-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"secureDrop\" class=\"checkbox secureDrop\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.secureDropMode : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " data-permissions=\"" + alias4((helper = (helper = helpers.readPermission || (depth0 != null ? depth0.readPermission : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "readPermission",
        "hash": {},
        "data": data
      }) : helper)) + "\"/>\n						<label for=\"secureDrop-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.secureDropLabel || (depth0 != null ? depth0.secureDropLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "secureDropLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n				</li>\n";
    },
    "19": function _(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isPasswordForMailSharesRequired : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(20, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "";
    },
    "20": function _(container, depth0, helpers, partials, data) {
      return "disabled=\"\"";
    },
    "22": function _(container, depth0, helpers, partials, data) {
      return "hidden";
    },
    "24": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"passwordByTalk-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"passwordByTalk\" class=\"passwordByTalk checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isPasswordByTalkSet : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + " />\n						<label for=\"passwordByTalk-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordByTalkLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n					</span>\n				</li>\n				<li class=\"passwordByTalkMenu-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.isPasswordByTalkSet : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(22, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n					<span class=\"passwordByTalkContainer-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " icon-passwordtalk menuitem\">\n						<label for=\"passwordByTalkField-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"hidden-visually\" value=\"" + alias4((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "password",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordByTalkLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n						<input id=\"passwordByTalkField-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"passwordField\" type=\"password\" placeholder=\"" + alias4((helper = (helper = helpers.passwordByTalkPlaceholder || (depth0 != null ? depth0.passwordByTalkPlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordByTalkPlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" value=\"" + alias4((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "passwordValue",
        "hash": {},
        "data": data
      }) : helper)) + "\" autocomplete=\"new-password\" />\n						<span class=\"icon-loading-small hidden\"></span>\n					</span>\n				</li>\n";
    },
    "26": function _(container, depth0, helpers, partials, data) {
      var helper;
      return container.escapeExpression((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "expireDate",
        "hash": {},
        "data": data
      }) : helper));
    },
    "28": function _(container, depth0, helpers, partials, data) {
      var helper;
      return container.escapeExpression((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "defaultExpireDate",
        "hash": {},
        "data": data
      }) : helper));
    },
    "30": function _(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<li>\n				<a href=\"#\" class=\"share-add\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<span class=\"icon icon-edit\"></span>\n					<span>" + alias4((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "addNoteLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n					<input type=\"button\" class=\"share-note-delete icon-delete " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasNote : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(22, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n				</a>\n			</li>\n			<li class=\"share-note-form " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasNote : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(22, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n				<span class=\"menuitem icon-note\">\n					<textarea class=\"share-note\">" + alias4((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareNote",
        "hash": {},
        "data": data
      }) : helper)) + "</textarea>\n					<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n				</span>\n			</li>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<div class=\"popovermenu bubble hidden menu\">\n	<ul>\n		" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isResharingAllowed : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isFolder : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(6, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isMailShare : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(16, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\" " + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(4, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" />\n				<label for=\"expireDate-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expireDateLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n			</span>\n		</li>\n		<li class=\"expirationDateMenu-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " " + ((stack1 = helpers.unless.call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "unless",
        "hash": {},
        "fn": container.program(22, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\">\n			<span class=\"expirationDateContainer-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + " icon-expiredate menuitem\">\n				<label for=\"expirationDatePicker-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"hidden-visually\" value=\"" + alias4((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationDate",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n				<input id=\"expirationDatePicker-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-" + alias4((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareId",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"datepicker\" type=\"text\" placeholder=\"" + alias4((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "expirationDatePlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" value=\"" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.hasExpireDate : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(26, data, 0),
        "inverse": container.program(28, data, 0),
        "data": data
      })) != null ? stack1 : "") + "\" />\n			</span>\n		</li>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isNoteAvailable : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(30, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>" + alias4((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "unshareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</span></a>\n		</li>\n	</ul>\n</div>\n";
    },
    "useData": true
  });
  templates['sharedialogview'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "	<label for=\"shareWith-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"hidden-visually\">" + alias4((helper = (helper = helpers.shareLabel || (depth0 != null ? depth0.shareLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "shareLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n	<div class=\"oneline\">\n		<input id=\"shareWith-" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"shareWithField\" type=\"text\" placeholder=\"" + alias4((helper = (helper = helpers.sharePlaceholder || (depth0 != null ? depth0.sharePlaceholder : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "sharePlaceholder",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n		<span class=\"shareWithLoading icon-loading-small hidden\"></span>\n		<span class=\"shareWithConfirm icon icon-confirm\"></span>\n	</div>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1;
      return "<div class=\"resharerInfoView subView\"></div>\n" + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isSharingAllowed : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "<div class=\"linkShareView subView\"></div>\n<div class=\"shareeListView subView\"></div>\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
    },
    "useData": true
  });
})();

/***/ })

/******/ });
//# sourceMappingURL=share_backend.js.map