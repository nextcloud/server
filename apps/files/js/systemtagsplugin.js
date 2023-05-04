/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars */

(function (OCA) {

	_.extend(OC.Files.Client, {
		PROPERTY_SYSTEM_TAGS: '{' + OC.Files.Client.NS_NEXTCLOUD + '}system-tags',
	});

	OCA.Files = OCA.Files || {};

	/**
	 * Extends the file actions and file list to add system tags inline
	 *
	 * @namespace OCA.Files.SystemTagsPlugin
	 */
	OCA.Files.SystemTagsPlugin = {
		name: 'SystemTags',

		allowedLists: [
			'files',
			'favorites',
			'shares.self',
			'shares.others',
			'shares.link'
		],
		
		_buildTagSpan: function(tag, isMore = false) {
			var $tag = $('<li class="system-tags__tag"></li>');
			$tag.text(tag).addClass(isMore ? 'system-tags__tag--more' : '');
			return $tag;
		},

		_buildTagsUI: function(tags) {
			$systemTags = $('<ul class="system-tags"></ul>');
			if (tags.length === 1) {
				$systemTags.attr('aria-label', t('files', 'This file has the tag {tag}', { tag: tags[0] }));
			} else if (tags.length > 1) {
				var firstTags = tags.slice(0, -1).join(', ');
				var lastTag = tags[tags.length - 1];
				$systemTags.attr('aria-label', t('files', 'This file has the tags {firstTags} and {lastTag}', { firstTags, lastTag }));
			}

			if (tags.length > 0) {
				$systemTags.append(this._buildTagSpan(tags[0]));
			}

			// More tags than the one we're showing
			if (tags.length > 1) {
				$moreTag = this._buildTagSpan('+' + (tags.length - 1), true)
				$moreTag.attr('title', tags.slice(1).join(', '));
				$systemTags.append($moreTag);
			}

			return $systemTags;
		},

		_extendFileList: function(fileList) {
			var self = this;

			// extend row prototype
			var oldCreateRow = fileList._createRow;
			fileList._createRow = function(fileData) {
				var $tr = oldCreateRow.apply(this, arguments);
				var systemTags = fileData.systemTags || [];

				// Update tr data list
				$tr.attr('data-systemTags', systemTags.join('|'));

				// No tags, no need to do anything
				if (systemTags.length === 0) {
					return $tr;
				}

				// Build tags ui and inject
				$systemTags = self._buildTagsUI.apply(self, [systemTags])
				$systemTags.insertAfter($tr.find('td.filename .nametext'));
				return $tr;
			};

			var oldElementToFile = fileList.elementToFile;
			fileList.elementToFile = function ($el) {
				var fileInfo = oldElementToFile.apply(this, arguments);
				var systemTags = $el.attr('data-systemTags');
				fileInfo.systemTags = systemTags?.split?.('|') || [];
				return fileInfo;
			};

			var oldGetWebdavProperties = fileList._getWebdavProperties;
			fileList._getWebdavProperties = function () {
				var props = oldGetWebdavProperties.apply(this, arguments);
				props.push(OC.Files.Client.PROPERTY_SYSTEM_TAGS);
				return props;
			};

			fileList.filesClient.addFileInfoParser(function (response) {
				var data = {};
				var props = response.propStat[0].properties;
				var systemTags = props[OC.Files.Client.PROPERTY_SYSTEM_TAGS] || [];
				if (systemTags && systemTags.length) {
					data.systemTags = systemTags
						.filter(xmlvalue => xmlvalue.namespaceURI === OC.Files.Client.NS_NEXTCLOUD && xmlvalue.nodeName.split(':')[1] === 'system-tag')
						.map(xmlvalue => xmlvalue.textContent || xmlvalue.text);
				}
				return data;
			});
		},

		attach: function(fileList) {
			if (this.allowedLists.indexOf(fileList.id) < 0) {
				return;
			}
			this._extendFileList(fileList);
		},
	};
})
(OCA);

OC.Plugins.register('OCA.Files.FileList', OCA.Files.SystemTagsPlugin);
