/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Namespace to hold functions related to convert mimetype to icons
 *
 * @namespace
 */
OC.MimeType = {

	/**
	 * Cache that maps mimeTypes to icon urls
	 */
	_mimeTypeIcons: {},

	/**
	 * Return the file icon we want to use for the given mimeType.
	 * The file needs to be present in the supplied file list
	 *
	 * @param {string} mimeType The mimeType we want an icon for
	 * @param {array} files The available icons in this theme
	 * @return {string} The icon to use or null if there is no match
	 */
	_getFile: function(mimeType, files) {
		var icon = mimeType.replace(new RegExp('/', 'g'), '-');

		// Generate path
		if (mimeType === 'dir' && $.inArray('folder', files) !== -1) {
			return 'folder';
		} else if (mimeType === 'dir-shared' && $.inArray('folder-shared', files) !== -1) {
			return 'folder-shared';
		} else if (mimeType === 'dir-public' && $.inArray('folder-public', files) !== -1) {
			return 'folder-public';
		} else if (mimeType === 'dir-external' && $.inArray('folder-external', files) !== -1) {
			return 'folder-external';
		} else if ($.inArray(icon, files) !== -1) {
			return icon;
		} else if ($.inArray(icon.split('-')[0], files) !== -1) {
			return icon.split('-')[0];
		} else if ($.inArray('file', files) !== -1) {
			return 'file';
		}

		return null;
	},

	/**
	 * Return the url to icon of the given mimeType
	 *
	 * @param {string} mimeType The mimeType to get the icon for
	 * @return {string} Url to the icon for mimeType
	 */
	getIconUrl: function(mimeType) {
		if (_.isUndefined(mimeType)) {
			return undefined;
		}

		while (mimeType in OC.MimeTypeList.aliases) {
			mimeType = OC.MimeTypeList.aliases[mimeType];
		}
		if (mimeType in OC.MimeType._mimeTypeIcons) {
			return OC.MimeType._mimeTypeIcons[mimeType];
		}

		// First try to get the correct icon from the current theme
		var gotIcon = null;
		var path = '';
		if (OC.theme.folder !== '' && $.isArray(OC.MimeTypeList.themes[OC.theme.folder])) {
			path = OC.webroot + '/themes/' + OC.theme.folder + '/core/img/filetypes/';
			var icon = OC.MimeType._getFile(mimeType, OC.MimeTypeList.themes[OC.theme.folder]);

			if (icon !== null) {
				gotIcon = true;
				path += icon;
			}
		}
		if(OCA.Theming) {
			path = OC.generateUrl('/apps/theming/img/core/filetypes/');
			path += OC.MimeType._getFile(mimeType, OC.MimeTypeList.files);
			gotIcon = true;
		}

		// If we do not yet have an icon fall back to the default
		if (gotIcon === null) {
			path = OC.webroot + '/core/img/filetypes/';
			path += OC.MimeType._getFile(mimeType, OC.MimeTypeList.files);
		}

		path += '.svg';

		if(OCA.Theming) {
			path += "?v=" + OCA.Theming.cacheBuster;
		}

		// Cache the result
		OC.MimeType._mimeTypeIcons[mimeType] = path;
		return path;
	}

};
