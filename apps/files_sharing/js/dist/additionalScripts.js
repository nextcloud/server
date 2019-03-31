/******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 	};
/******/
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		"additionalScripts": 0
/******/ 	};
/******/
/******/
/******/
/******/ 	// script path function
/******/ 	function jsonpScriptSrc(chunkId) {
/******/ 		return __webpack_require__.p + "files_sharing." + chunkId + ".js"
/******/ 	}
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
/******/ 	// This file contains only the entry chunk.
/******/ 	// The chunk loading function for additional chunks
/******/ 	__webpack_require__.e = function requireEnsure(chunkId) {
/******/ 		var promises = [];
/******/
/******/
/******/ 		// JSONP chunk loading for javascript
/******/
/******/ 		var installedChunkData = installedChunks[chunkId];
/******/ 		if(installedChunkData !== 0) { // 0 means "already installed".
/******/
/******/ 			// a Promise means "currently loading".
/******/ 			if(installedChunkData) {
/******/ 				promises.push(installedChunkData[2]);
/******/ 			} else {
/******/ 				// setup Promise in chunk cache
/******/ 				var promise = new Promise(function(resolve, reject) {
/******/ 					installedChunkData = installedChunks[chunkId] = [resolve, reject];
/******/ 				});
/******/ 				promises.push(installedChunkData[2] = promise);
/******/
/******/ 				// start chunk loading
/******/ 				var script = document.createElement('script');
/******/ 				var onScriptComplete;
/******/
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.src = jsonpScriptSrc(chunkId);
/******/
/******/ 				onScriptComplete = function (event) {
/******/ 					// avoid mem leaks in IE.
/******/ 					script.onerror = script.onload = null;
/******/ 					clearTimeout(timeout);
/******/ 					var chunk = installedChunks[chunkId];
/******/ 					if(chunk !== 0) {
/******/ 						if(chunk) {
/******/ 							var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 							var realSrc = event && event.target && event.target.src;
/******/ 							var error = new Error('Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')');
/******/ 							error.type = errorType;
/******/ 							error.request = realSrc;
/******/ 							chunk[1](error);
/******/ 						}
/******/ 						installedChunks[chunkId] = undefined;
/******/ 					}
/******/ 				};
/******/ 				var timeout = setTimeout(function(){
/******/ 					onScriptComplete({ type: 'timeout', target: script });
/******/ 				}, 120000);
/******/ 				script.onerror = script.onload = onScriptComplete;
/******/ 				document.head.appendChild(script);
/******/ 			}
/******/ 		}
/******/ 		return Promise.all(promises);
/******/ 	};
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
/******/ 	// on error function for async loading
/******/ 	__webpack_require__.oe = function(err) { console.error(err); throw err; };
/******/
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./apps/files_sharing/src/additionalScripts.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./apps/files_sharing/src/additionalScripts.js":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/additionalScripts.js ***!
  \*****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _share__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./share */ "./apps/files_sharing/src/share.js");
/* harmony import */ var _share__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_share__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _sharetabview__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./sharetabview */ "./apps/files_sharing/src/sharetabview.js");
/* harmony import */ var _sharetabview__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_sharetabview__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _sharebreadcrumbview__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./sharebreadcrumbview */ "./apps/files_sharing/src/sharebreadcrumbview.js");
/* harmony import */ var _sharebreadcrumbview__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_sharebreadcrumbview__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _style_sharetabview_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style/sharetabview.scss */ "./apps/files_sharing/src/style/sharetabview.scss");
/* harmony import */ var _style_sharetabview_scss__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_style_sharetabview_scss__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _style_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./style/sharebreadcrumb.scss */ "./apps/files_sharing/src/style/sharebreadcrumb.scss");
/* harmony import */ var _style_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_style_sharebreadcrumb_scss__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _collaborationresourceshandler_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./collaborationresourceshandler.js */ "./apps/files_sharing/src/collaborationresourceshandler.js");
/* harmony import */ var _collaborationresourceshandler_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_collaborationresourceshandler_js__WEBPACK_IMPORTED_MODULE_5__);
__webpack_require__.p = OC.linkTo('files_sharing', 'js/dist/');
__webpack_require__.nc = btoa(OC.requestToken);






window.OCA.Sharing = OCA.Sharing;

/***/ }),

/***/ "./apps/files_sharing/src/collaborationresourceshandler.js":
/*!*****************************************************************!*\
  !*** ./apps/files_sharing/src/collaborationresourceshandler.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__.p = OC.linkTo('files_sharing', 'js/dist/');
__webpack_require__.nc = btoa(OC.requestToken);
window.OCP.Collaboration.registerType('file', {
  action: function action() {
    return new Promise(function (resolve, reject) {
      OC.dialogs.filepicker(t('files_sharing', 'Link to a file'), function (f) {
        var client = OC.Files.getClient();
        client.getFileInfo(f).then(function (status, fileInfo) {
          resolve(fileInfo.id);
        }, function () {
          reject();
        });
      }, false);
    });
  },

  /** used in "Link to a {typeString}" */
  typeString: t('files_sharing', 'file'),
  typeIconClass: 'icon-files-dark'
});

/***/ }),

/***/ "./apps/files_sharing/src/share.js":
/*!*****************************************!*\
  !*** ./apps/files_sharing/src/share.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
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
     * Initialize the sharing plugin.
     *
     * Registers the "Share" file action and adds additional
     * DOM attributes for the sharing file info.
     *
     * @param {OCA.Files.FileList} fileList file list to be extended
     */
    attach: function attach(fileList) {
      // core sharing is disabled/not loaded
      if (!OC.Share) {
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

        tr.attr('data-share-permissions', sharePermissions);

        if (fileData.shareOwner) {
          tr.attr('data-share-owner', fileData.shareOwner);
          tr.attr('data-share-owner-id', fileData.shareOwnerId); // user should always be able to rename a mount point

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
      }); // use delegate to catch the case with multiple file lists

      fileList.$el.on('fileActionsReady', function (ev) {
        var $files = ev.$files;

        _.each($files, function (file) {
          var $tr = $(file);
          var shareTypes = $tr.attr('data-share-types') || '';
          var shareOwner = $tr.attr('data-share-owner');

          if (shareTypes || shareOwner) {
            var hasLink = false;
            var hasShares = false;

            _.each(shareTypes.split(',') || [], function (shareType) {
              shareType = parseInt(shareType, 10);

              if (shareType === OC.Share.SHARE_TYPE_LINK) {
                hasLink = true;
              } else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
                hasLink = true;
              } else if (shareType === OC.Share.SHARE_TYPE_USER) {
                hasShares = true;
              } else if (shareType === OC.Share.SHARE_TYPE_GROUP) {
                hasShares = true;
              } else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
                hasShares = true;
              } else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {
                hasShares = true;
              } else if (shareType === OC.Share.SHARE_TYPE_ROOM) {
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
        displayName: function displayName(context) {
          if (context && context.$file) {
            var shareType = parseInt(context.$file.data('share-types'), 10);
            var shareOwner = context.$file.data('share-owner-id');

            if (shareType >= 0 || shareOwner) {
              return t('core', 'Shared');
            }
          }

          return t('core', 'Share');
        },
        altText: t('core', 'Share'),
        mime: 'all',
        order: -150,
        permissions: OC.PERMISSION_ALL,
        iconClass: function iconClass(fileName, context) {
          var shareType = parseInt(context.$file.data('share-types'), 10);

          if (shareType === OC.Share.SHARE_TYPE_EMAIL || shareType === OC.Share.SHARE_TYPE_LINK) {
            return 'icon-public';
          }

          return 'icon-shared';
        },
        icon: function icon(fileName, context) {
          var shareOwner = context.$file.data('share-owner-id');

          if (shareOwner) {
            return OC.generateUrl("/avatar/".concat(shareOwner, "/32"));
          }
        },
        type: OCA.Files.FileActions.TYPE_INLINE,
        actionHandler: function actionHandler(fileName, context) {
          // do not open sidebar if permission is set and equal to 0
          var permissions = parseInt(context.$file.data('share-permissions'), 10);

          if (isNaN(permissions) || permissions > 0) {
            fileList.showDetailsView(fileName, 'shareTabView');
          }
        },
        render: function render(actionSpec, isDefault, context) {
          var permissions = parseInt(context.$file.data('permissions'), 10); // if no share permissions but share owner exists, still show the link

          if ((permissions & OC.PERMISSION_SHARE) !== 0 || context.$file.attr('data-share-owner')) {
            return fileActions._defaultRenderAction.call(fileActions, actionSpec, isDefault, context);
          } // don't render anything


          return null;
        }
      });
      var shareTab = new OCA.Sharing.ShareTabView('shareTabView', {
        order: -20
      }); // detect changes and change the matching list entry

      shareTab.on('sharesChanged', function (shareModel) {
        var fileInfoModel = shareModel.fileInfoModel;
        var $tr = fileList.findFileEl(fileInfoModel.get('name')); // We count email shares as link share

        var hasLinkShares = shareModel.hasLinkShares();
        shareModel.get('shares').forEach(function (share) {
          if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
            hasLinkShares = true;
          }
        });

        OCA.Sharing.Util._updateFileListDataAttributes(fileList, $tr, shareModel);

        if (!OCA.Sharing.Util._updateFileActionIcon($tr, shareModel.hasUserShares(), hasLinkShares)) {
          // remove icon, if applicable
          OC.Share.markFileAsShared($tr, false, false);
        } // FIXME: this is too convoluted. We need to get rid of the above updates
        // and only ever update the model and let the events take care of rerendering


        fileInfoModel.set({
          shareTypes: shareModel.getShareTypes(),
          // in case markFileAsShared decided to change the icon,
          // we need to modify the model
          // (FIXME: yes, this is hacky)
          icon: $tr.attr('data-icon')
        });
      });
      fileList.registerTabView(shareTab);
      var breadCrumbSharingDetailView = new OCA.Sharing.ShareBreadCrumbView({
        shareTab: shareTab
      });
      fileList.registerBreadCrumbDetailView(breadCrumbSharingDetailView);
    },

    /**
     * Update file list data attributes
     */
    _updateFileListDataAttributes: function _updateFileListDataAttributes(fileList, $tr, shareModel) {
      // files app current cannot show recipients on load, so we don't update the
      // icon when changed for consistency
      if (fileList.id === 'files') {
        return;
      }

      var recipients = _.pluck(shareModel.get('shares'), 'share_with_displayname'); // note: we only update the data attribute because updateIcon()


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
     * @return {boolean} true if the icon was set, false otherwise
     */
    _updateFileActionIcon: function _updateFileActionIcon($tr, hasUserShares, hasLinkShares) {
      // if the statuses are loaded already, use them for the icon
      // (needed when scrolling to the next page)
      if (hasUserShares || hasLinkShares || $tr.attr('data-share-recipient-data') || $tr.attr('data-share-owner')) {
        OC.Share.markFileAsShared($tr, true, hasLinkShares);
        return true;
      }

      return false;
    },

    /**
     * @param {Array} fileData
     * @returns {String}
     */
    getSharePermissions: function getSharePermissions(fileData) {
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
/*! no static exports found */
/***/ (function(module, exports) {

/* global Handlebars, OC */

/**
 * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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
  'use strict';

  var BreadCrumbView = OC.Backbone.View.extend({
    tagName: 'span',
    events: {
      click: '_onClick'
    },
    _dirInfo: undefined,

    /** @type OCA.Sharing.ShareTabView */
    _shareTab: undefined,
    initialize: function initialize(options) {
      this._shareTab = options.shareTab;
    },
    render: function render(data) {
      this._dirInfo = data.dirInfo || null;

      if (this._dirInfo !== null && (this._dirInfo.path !== '/' || this._dirInfo.name !== '')) {
        var isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0;
        this.$el.removeClass('shared icon-public icon-shared');

        if (isShared) {
          this.$el.addClass('shared');

          if (data.dirInfo.shareTypes.indexOf(OC.Share.SHARE_TYPE_LINK) !== -1) {
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
    _onClick: function _onClick(e) {
      e.preventDefault();
      var fileInfoModel = new OCA.Files.FileInfoModel(this._dirInfo);
      var self = this;
      fileInfoModel.on('change', function () {
        self.render({
          dirInfo: self._dirInfo
        });
      });

      this._shareTab.on('sharesChanged', function (shareModel) {
        var shareTypes = [];
        var shares = shareModel.getSharesWithCurrentItem();

        for (var i = 0; i < shares.length; i++) {
          if (shareTypes.indexOf(shares[i].share_type) === -1) {
            shareTypes.push(shares[i].share_type);
          }
        }

        if (shareModel.hasLinkShares()) {
          shareTypes.push(OC.Share.SHARE_TYPE_LINK);
        } // Since the dirInfo isn't updated we need to do this dark hackery


        self._dirInfo.shareTypes = shareTypes;
        self.render({
          dirInfo: self._dirInfo
        });
      });

      OCA.Files.App.fileList.showDetailsView(fileInfoModel, 'shareTabView');
    }
  });
  OCA.Sharing.ShareBreadCrumbView = BreadCrumbView;
})();

/***/ }),

/***/ "./apps/files_sharing/src/sharetabview.js":
/*!************************************************!*\
  !*** ./apps/files_sharing/src/sharetabview.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* @global Handlebars */
(function () {
  var TEMPLATE = '<div>' + '<div class="dialogContainer"></div>' + '<div id="collaborationResources"></div>' + '</div>';
  /**
   * @memberof OCA.Sharing
   */

  var ShareTabView = OCA.Files.DetailTabView.extend(
  /** @lends OCA.Sharing.ShareTabView.prototype */
  {
    id: 'shareTabView',
    className: 'tab shareTabView',
    initialize: function initialize(name, options) {
      OCA.Files.DetailTabView.prototype.initialize.call(this, name, options);
      OC.Plugins.attach('OCA.Sharing.ShareTabView', this);
    },
    template: function template(params) {
      return TEMPLATE;
    },
    getLabel: function getLabel() {
      return t('files_sharing', 'Sharing');
    },
    getIcon: function getIcon() {
      return 'icon-shared';
    },

    /**
     * Renders this details view
     */
    render: function render() {
      var _this = this;

      var self = this;

      if (this._dialog) {
        // remove/destroy older instance
        this._dialog.model.off();

        this._dialog.remove();

        this._dialog = null;
      }

      if (this.model) {
        this.$el.html(this.template());

        if (_.isUndefined(this.model.get('sharePermissions'))) {
          this.model.set('sharePermissions', OCA.Sharing.Util.getSharePermissions(this.model.attributes));
        } // TODO: the model should read these directly off the passed fileInfoModel


        var attributes = {
          itemType: this.model.isDirectory() ? 'folder' : 'file',
          itemSource: this.model.get('id'),
          possiblePermissions: this.model.get('sharePermissions')
        };
        var configModel = new OC.Share.ShareConfigModel();
        var shareModel = new OC.Share.ShareItemModel(attributes, {
          configModel: configModel,
          fileInfoModel: this.model
        });
        this._dialog = new OC.Share.ShareDialogView({
          configModel: configModel,
          model: shareModel
        });
        this.$el.find('.dialogContainer').append(this._dialog.$el);

        this._dialog.render();

        this._dialog.model.fetch();

        this._dialog.model.on('change', function () {
          self.trigger('sharesChanged', shareModel);
        });

        Promise.all(/*! import() */[__webpack_require__.e(0), __webpack_require__.e(1)]).then(__webpack_require__.bind(null, /*! ./collaborationresources */ "./apps/files_sharing/src/collaborationresources.js")).then(function (Resources) {
          var vm = new Resources.Vue({
            el: '#collaborationResources',
            render: function render(h) {
              return h(Resources.View);
            },
            data: {
              model: _this.model.toJSON()
            }
          });

          _this.model.on('change', function () {
            vm.data = _this.model.toJSON();
          });
        });
      } else {
        this.$el.empty(); // TODO: render placeholder text?
      }

      this.trigger('rendered');
    }
  });
  OCA.Sharing.ShareTabView = ShareTabView;
})();

/***/ }),

/***/ "./apps/files_sharing/src/style/sharebreadcrumb.scss":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/style/sharebreadcrumb.scss ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/lib/loader.js!./sharebreadcrumb.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharebreadcrumb.scss");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("27ced9ca", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./apps/files_sharing/src/style/sharetabview.scss":
/*!********************************************************!*\
  !*** ./apps/files_sharing/src/style/sharetabview.scss ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/lib/loader.js!./sharetabview.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharetabview.scss");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("03f26936", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharebreadcrumb.scss":
/*!******************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharebreadcrumb.scss ***!
  \******************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "/**\n * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>\n *\n * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program.  If not, see <http://www.gnu.org/licenses/>.\n *\n */\ndiv.crumb span.icon-shared,\ndiv.crumb span.icon-public {\n  display: inline-block;\n  cursor: pointer;\n  opacity: 0.2;\n  margin-right: 6px; }\n\ndiv.crumb span.icon-shared.shared,\ndiv.crumb span.icon-public.shared {\n  opacity: 0.7; }\n", ""]);



/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharetabview.scss":
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_sharing/src/style/sharetabview.scss ***!
  \***************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, ".app-files .shareTabView {\n  min-height: 100px; }\n\n.share-autocomplete-item {\n  display: flex; }\n  .share-autocomplete-item.merged {\n    margin-left: 32px; }\n  .share-autocomplete-item .autocomplete-item-text {\n    margin-left: 10px;\n    margin-right: 10px;\n    white-space: nowrap;\n    text-overflow: ellipsis;\n    overflow: hidden;\n    line-height: 32px;\n    vertical-align: middle;\n    flex-grow: 1; }\n    .share-autocomplete-item .autocomplete-item-text .ui-state-highlight {\n      border: none;\n      margin: 0; }\n  .share-autocomplete-item.with-description .autocomplete-item-text {\n    line-height: 100%; }\n  .share-autocomplete-item .autocomplete-item-details {\n    display: block;\n    line-height: 130%;\n    font-size: 90%;\n    opacity: 0.7; }\n  .share-autocomplete-item .icon {\n    opacity: .7;\n    margin-right: 7px; }\n  .share-autocomplete-item .icon.search-globally {\n    width: 32px;\n    height: 32px;\n    margin-right: 0; }\n\n.shareTabView .oneline {\n  white-space: nowrap;\n  position: relative; }\n\n.shareTabView .shareWithLoading {\n  padding-left: 10px;\n  right: 35px;\n  top: 3px; }\n\n.shareTabView .shareWithConfirm {\n  position: absolute;\n  right: 2px;\n  top: 6px;\n  padding: 14px;\n  opacity: 0.5; }\n\n.shareTabView .shareWithField:focus ~ .shareWithConfirm {\n  opacity: 1; }\n\n.shareTabView .linkMore {\n  position: absolute;\n  right: -7px;\n  top: -4px;\n  padding: 14px; }\n\n.shareTabView .popovermenu {\n  /* Border above last entry '+ Add another share' to separate it from current link settings */ }\n  .shareTabView .popovermenu .linkPassMenu .share-pass-submit {\n    width: auto !important; }\n  .shareTabView .popovermenu .linkPassMenu .icon-loading-small {\n    background-color: var(--color-main-background);\n    position: absolute;\n    right: 8px;\n    margin: 3px;\n    padding: 10px;\n    width: 32px;\n    height: 32px;\n    z-index: 10; }\n  .shareTabView .popovermenu .datepicker {\n    margin-left: 35px; }\n  .shareTabView .popovermenu .share-add input.share-note-delete {\n    border: none;\n    background-color: transparent;\n    width: 44px !important;\n    padding: 0;\n    flex: 0 0 44px;\n    margin-left: auto; }\n    .shareTabView .popovermenu .share-add input.share-note-delete.hidden {\n      display: none; }\n  .shareTabView .popovermenu .share-note-form span.icon-note {\n    position: relative; }\n  .shareTabView .popovermenu .share-note-form textarea.share-note {\n    margin: 0;\n    width: 200px;\n    min-height: 70px;\n    resize: none; }\n    .shareTabView .popovermenu .share-note-form textarea.share-note + input.share-note-submit {\n      position: absolute;\n      width: 44px !important;\n      height: 44px;\n      bottom: 0px;\n      right: 10px;\n      margin: 0;\n      background-color: transparent;\n      border: none;\n      opacity: .7; }\n      .shareTabView .popovermenu .share-note-form textarea.share-note + input.share-note-submit:hover, .shareTabView .popovermenu .share-note-form textarea.share-note + input.share-note-submit:focus, .shareTabView .popovermenu .share-note-form textarea.share-note + input.share-note-submit:active {\n        opacity: 1; }\n  .shareTabView .popovermenu .share-note-form.share-note-link {\n    margin-bottom: 10px; }\n  .shareTabView .popovermenu .new-share {\n    border-top: 1px solid var(--color-border); }\n\n.shareTabView .linkPass .icon-loading-small {\n  margin-right: 0px; }\n\n.shareTabView .icon {\n  background-size: 16px 16px; }\n\n.shareTabView .shareWithList .icon-loading-small:not(.hidden) + span,\n.shareTabView .linkShareView .icon-loading-small:not(.hidden) + input + label:before {\n  /* Hide if loader is visible */\n  display: none !important; }\n\n.shareTabView input[type='checkbox'] {\n  margin: 0 3px 0 8px;\n  vertical-align: middle; }\n\n.shareTabView input[type='text'].shareWithField, .shareTabView input[type='text'].emailField {\n  width: 100%;\n  box-sizing: border-box;\n  padding-right: 32px;\n  text-overflow: ellipsis; }\n\n.shareTabView input[type='text'].linkText .shareTabView input[type='password'].linkPassText, .shareTabView input[type='password'].passwordField {\n  width: 180px !important; }\n\n.shareTabView form {\n  font-size: 100%;\n  margin-left: 0;\n  margin-right: 0; }\n\n.shareTabView .share-note {\n  border-radius: var(--border-radius);\n  margin-bottom: 10px;\n  margin-left: 37px; }\n\n.shareWithList {\n  list-style-type: none;\n  display: flex;\n  flex-direction: column; }\n  .shareWithList > li {\n    height: 44px;\n    white-space: normal;\n    display: inline-flex;\n    align-items: center;\n    position: relative; }\n    .shareWithList > li .avatar {\n      width: 32px;\n      height: 32px;\n      background-color: var(--color-primary); }\n  .shareWithList .unshare img {\n    vertical-align: text-bottom;\n    /* properly align icons */ }\n  .shareWithList .sharingOptionsGroup {\n    margin-left: auto;\n    display: flex;\n    align-items: center;\n    white-space: nowrap; }\n    .shareWithList .sharingOptionsGroup > .icon:not(.hidden),\n    .shareWithList .sharingOptionsGroup .share-menu > .icon:not(.hidden) {\n      padding: 14px;\n      height: 44px;\n      width: 44px;\n      opacity: .5;\n      display: block;\n      cursor: pointer; }\n      .shareWithList .sharingOptionsGroup > .icon:not(.hidden):hover, .shareWithList .sharingOptionsGroup > .icon:not(.hidden):focus, .shareWithList .sharingOptionsGroup > .icon:not(.hidden):active,\n      .shareWithList .sharingOptionsGroup .share-menu > .icon:not(.hidden):hover,\n      .shareWithList .sharingOptionsGroup .share-menu > .icon:not(.hidden):focus,\n      .shareWithList .sharingOptionsGroup .share-menu > .icon:not(.hidden):active {\n        opacity: .7; }\n    .shareWithList .sharingOptionsGroup > .share-menu {\n      position: relative;\n      display: block; }\n  .shareWithList .username {\n    padding: 0 8px;\n    overflow: hidden;\n    white-space: nowrap;\n    text-overflow: ellipsis; }\n\n.ui-autocomplete {\n  /* limit dropdown height to 6 1/2 entries */\n  max-height: calc(36px * 6.5);\n  overflow-y: auto;\n  overflow-x: hidden;\n  z-index: 1550 !important; }\n\n.notCreatable {\n  padding-left: 12px;\n  padding-top: 12px;\n  color: var(--color-text-lighter); }\n\n.contactsmenu-popover {\n  left: -6px;\n  right: auto;\n  padding: 3px 6px;\n  top: 100%;\n  margin-top: 0; }\n  .contactsmenu-popover li.hidden {\n    display: none !important; }\n  .contactsmenu-popover:after {\n    left: 8px;\n    right: auto; }\n\n.reshare,\n#link label,\n#expiration label {\n  display: inline-flex;\n  align-items: center; }\n  .reshare .avatar,\n  #link label .avatar,\n  #expiration label .avatar {\n    margin-right: 5px; }\n\n.resharerInfoView.subView {\n  position: relative; }\n", ""]);



/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/api.js":
/*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function (useSourceMap) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item, useSourceMap);

      if (item[2]) {
        return '@media ' + item[2] + '{' + content + '}';
      } else {
        return content;
      }
    }).join('');
  }; // import a list of modules into the list


  list.i = function (modules, mediaQuery) {
    if (typeof modules === 'string') {
      modules = [[null, modules, '']];
    }

    var alreadyImportedModules = {};

    for (var i = 0; i < this.length; i++) {
      var id = this[i][0];

      if (id != null) {
        alreadyImportedModules[id] = true;
      }
    }

    for (i = 0; i < modules.length; i++) {
      var item = modules[i]; // skip already imported module
      // this implementation is not 100% perfect for weird media query combinations
      // when a module is imported multiple times with different media queries.
      // I hope this will never occur (Hey this way we have smaller bundles)

      if (item[0] == null || !alreadyImportedModules[item[0]]) {
        if (mediaQuery && !item[2]) {
          item[2] = mediaQuery;
        } else if (mediaQuery) {
          item[2] = '(' + item[2] + ') and (' + mediaQuery + ')';
        }

        list.push(item);
      }
    }
  };

  return list;
};

function cssWithMappingToString(item, useSourceMap) {
  var content = item[1] || '';
  var cssMapping = item[3];

  if (!cssMapping) {
    return content;
  }

  if (useSourceMap && typeof btoa === 'function') {
    var sourceMapping = toComment(cssMapping);
    var sourceURLs = cssMapping.sources.map(function (source) {
      return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */';
    });
    return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
  }

  return [content].join('\n');
} // Adapted from convert-source-map (MIT)


function toComment(sourceMap) {
  // eslint-disable-next-line no-undef
  var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
  var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;
  return '/*# ' + data + ' */';
}

/***/ }),

/***/ "./node_modules/vue-style-loader/lib/addStylesClient.js":
/*!**************************************************************!*\
  !*** ./node_modules/vue-style-loader/lib/addStylesClient.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return addStylesClient; });
/* harmony import */ var _listToStyles__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./listToStyles */ "./node_modules/vue-style-loader/lib/listToStyles.js");
/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
  Modified by Evan You @yyx990803
*/



var hasDocument = typeof document !== 'undefined'

if (typeof DEBUG !== 'undefined' && DEBUG) {
  if (!hasDocument) {
    throw new Error(
    'vue-style-loader cannot be used in a non-browser environment. ' +
    "Use { target: 'node' } in your Webpack config to indicate a server-rendering environment."
  ) }
}

/*
type StyleObject = {
  id: number;
  parts: Array<StyleObjectPart>
}

type StyleObjectPart = {
  css: string;
  media: string;
  sourceMap: ?string
}
*/

var stylesInDom = {/*
  [id: number]: {
    id: number,
    refs: number,
    parts: Array<(obj?: StyleObjectPart) => void>
  }
*/}

var head = hasDocument && (document.head || document.getElementsByTagName('head')[0])
var singletonElement = null
var singletonCounter = 0
var isProduction = false
var noop = function () {}
var options = null
var ssrIdKey = 'data-vue-ssr-id'

// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
// tags it will allow on a page
var isOldIE = typeof navigator !== 'undefined' && /msie [6-9]\b/.test(navigator.userAgent.toLowerCase())

function addStylesClient (parentId, list, _isProduction, _options) {
  isProduction = _isProduction

  options = _options || {}

  var styles = Object(_listToStyles__WEBPACK_IMPORTED_MODULE_0__["default"])(parentId, list)
  addStylesToDom(styles)

  return function update (newList) {
    var mayRemove = []
    for (var i = 0; i < styles.length; i++) {
      var item = styles[i]
      var domStyle = stylesInDom[item.id]
      domStyle.refs--
      mayRemove.push(domStyle)
    }
    if (newList) {
      styles = Object(_listToStyles__WEBPACK_IMPORTED_MODULE_0__["default"])(parentId, newList)
      addStylesToDom(styles)
    } else {
      styles = []
    }
    for (var i = 0; i < mayRemove.length; i++) {
      var domStyle = mayRemove[i]
      if (domStyle.refs === 0) {
        for (var j = 0; j < domStyle.parts.length; j++) {
          domStyle.parts[j]()
        }
        delete stylesInDom[domStyle.id]
      }
    }
  }
}

function addStylesToDom (styles /* Array<StyleObject> */) {
  for (var i = 0; i < styles.length; i++) {
    var item = styles[i]
    var domStyle = stylesInDom[item.id]
    if (domStyle) {
      domStyle.refs++
      for (var j = 0; j < domStyle.parts.length; j++) {
        domStyle.parts[j](item.parts[j])
      }
      for (; j < item.parts.length; j++) {
        domStyle.parts.push(addStyle(item.parts[j]))
      }
      if (domStyle.parts.length > item.parts.length) {
        domStyle.parts.length = item.parts.length
      }
    } else {
      var parts = []
      for (var j = 0; j < item.parts.length; j++) {
        parts.push(addStyle(item.parts[j]))
      }
      stylesInDom[item.id] = { id: item.id, refs: 1, parts: parts }
    }
  }
}

function createStyleElement () {
  var styleElement = document.createElement('style')
  styleElement.type = 'text/css'
  head.appendChild(styleElement)
  return styleElement
}

function addStyle (obj /* StyleObjectPart */) {
  var update, remove
  var styleElement = document.querySelector('style[' + ssrIdKey + '~="' + obj.id + '"]')

  if (styleElement) {
    if (isProduction) {
      // has SSR styles and in production mode.
      // simply do nothing.
      return noop
    } else {
      // has SSR styles but in dev mode.
      // for some reason Chrome can't handle source map in server-rendered
      // style tags - source maps in <style> only works if the style tag is
      // created and inserted dynamically. So we remove the server rendered
      // styles and inject new ones.
      styleElement.parentNode.removeChild(styleElement)
    }
  }

  if (isOldIE) {
    // use singleton mode for IE9.
    var styleIndex = singletonCounter++
    styleElement = singletonElement || (singletonElement = createStyleElement())
    update = applyToSingletonTag.bind(null, styleElement, styleIndex, false)
    remove = applyToSingletonTag.bind(null, styleElement, styleIndex, true)
  } else {
    // use multi-style-tag mode in all other cases
    styleElement = createStyleElement()
    update = applyToTag.bind(null, styleElement)
    remove = function () {
      styleElement.parentNode.removeChild(styleElement)
    }
  }

  update(obj)

  return function updateStyle (newObj /* StyleObjectPart */) {
    if (newObj) {
      if (newObj.css === obj.css &&
          newObj.media === obj.media &&
          newObj.sourceMap === obj.sourceMap) {
        return
      }
      update(obj = newObj)
    } else {
      remove()
    }
  }
}

var replaceText = (function () {
  var textStore = []

  return function (index, replacement) {
    textStore[index] = replacement
    return textStore.filter(Boolean).join('\n')
  }
})()

function applyToSingletonTag (styleElement, index, remove, obj) {
  var css = remove ? '' : obj.css

  if (styleElement.styleSheet) {
    styleElement.styleSheet.cssText = replaceText(index, css)
  } else {
    var cssNode = document.createTextNode(css)
    var childNodes = styleElement.childNodes
    if (childNodes[index]) styleElement.removeChild(childNodes[index])
    if (childNodes.length) {
      styleElement.insertBefore(cssNode, childNodes[index])
    } else {
      styleElement.appendChild(cssNode)
    }
  }
}

function applyToTag (styleElement, obj) {
  var css = obj.css
  var media = obj.media
  var sourceMap = obj.sourceMap

  if (media) {
    styleElement.setAttribute('media', media)
  }
  if (options.ssrId) {
    styleElement.setAttribute(ssrIdKey, obj.id)
  }

  if (sourceMap) {
    // https://developer.chrome.com/devtools/docs/javascript-debugging
    // this makes source maps inside style tags work properly in Chrome
    css += '\n/*# sourceURL=' + sourceMap.sources[0] + ' */'
    // http://stackoverflow.com/a/26603875
    css += '\n/*# sourceMappingURL=data:application/json;base64,' + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + ' */'
  }

  if (styleElement.styleSheet) {
    styleElement.styleSheet.cssText = css
  } else {
    while (styleElement.firstChild) {
      styleElement.removeChild(styleElement.firstChild)
    }
    styleElement.appendChild(document.createTextNode(css))
  }
}


/***/ }),

/***/ "./node_modules/vue-style-loader/lib/listToStyles.js":
/*!***********************************************************!*\
  !*** ./node_modules/vue-style-loader/lib/listToStyles.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return listToStyles; });
/**
 * Translates the list format produced by css-loader into something
 * easier to manipulate.
 */
function listToStyles (parentId, list) {
  var styles = []
  var newStyles = {}
  for (var i = 0; i < list.length; i++) {
    var item = list[i]
    var id = item[0]
    var css = item[1]
    var media = item[2]
    var sourceMap = item[3]
    var part = {
      id: parentId + ':' + i,
      css: css,
      media: media,
      sourceMap: sourceMap
    }
    if (!newStyles[id]) {
      styles.push(newStyles[id] = { id: id, parts: [part] })
    } else {
      newStyles[id].parts.push(part)
    }
  }
  return styles
}


/***/ })

/******/ });
//# sourceMappingURL=additionalScripts.js.map