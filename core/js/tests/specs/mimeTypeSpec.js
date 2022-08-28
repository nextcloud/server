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

describe('MimeType tests', function() {
	var _files;
	var _aliases;
	var _theme;

	beforeEach(function() {
		_files = OC.MimeTypeList.files;
		_aliases = OC.MimeTypeList.aliases;
		_theme = OC.MimeTypeList.themes.abc;

		OC.MimeTypeList.files = ['folder', 'folder-shared', 'folder-external', 'foo-bar', 'foo', 'file'];
		OC.MimeTypeList.aliases = {'app/foobar': 'foo/bar'};
		OC.MimeTypeList.themes.abc = ['folder'];
	});

	afterEach(function() {
		OC.MimeTypeList.files = _files;
		OC.MimeTypeList.aliases = _aliases;
		OC.MimeTypeList.themes.abc = _theme;
	});

	describe('_getFile', function() {

		it('returns the correct icon for "dir"', function() {
			var res = OC.MimeType._getFile('dir', OC.MimeTypeList.files);
			expect(res).toEqual('folder');
		});

		it('returns the correct icon for "dir-shared"', function() {
			var res = OC.MimeType._getFile('dir-shared', OC.MimeTypeList.files);
			expect(res).toEqual('folder-shared');
		});

		it('returns the correct icon for "dir-external"', function() {
			var res = OC.MimeType._getFile('dir-external', OC.MimeTypeList.files);
			expect(res).toEqual('folder-external');
		});

		it('returns the correct icon for a mimetype for which we have an icon', function() {
			var res = OC.MimeType._getFile('foo/bar', OC.MimeTypeList.files);
			expect(res).toEqual('foo-bar');
		});

		it('returns the correct icon for a mimetype for which we only have a general mimetype icon', function() {
			var res = OC.MimeType._getFile('foo/baz', OC.MimeTypeList.files);
			expect(res).toEqual('foo');
		});

		it('return the file mimetype if we have no matching icon but do have a file icon', function() {
			var res = OC.MimeType._getFile('foobar', OC.MimeTypeList.files);
			expect(res).toEqual('file');
		});

		it('return null if we do not have a matching icon', function() {
			var res = OC.MimeType._getFile('xyz', []);
			expect(res).toEqual(null);
		});
	});

	describe('getIconUrl', function() {

		describe('no theme', function() {
			var _themeFolder;

			beforeEach(function() {
				_themeFolder = OC.theme.folder;
				OC.theme.folder = '';
				//Clear mimetypeIcons caches
				OC.MimeType._mimeTypeIcons = {};
			});

			afterEach(function() {
				OC.theme.folder = _themeFolder;
			});

			it('return undefined if the an icon for undefined is requested', function() {
				var res = OC.MimeType.getIconUrl(undefined);
				expect(res).toEqual(undefined);
			});

			it('return the url for the mimetype file', function() {
				var res = OC.MimeType.getIconUrl('file');
				expect(res).toEqual(OC.getRootPath() + '/core/img/filetypes/file.svg');
			});

			it('test if the cache works correctly', function() {
				OC.MimeType._mimeTypeIcons = {};
				expect(Object.keys(OC.MimeType._mimeTypeIcons).length).toEqual(0);

				var res = OC.MimeType.getIconUrl('dir');
				expect(Object.keys(OC.MimeType._mimeTypeIcons).length).toEqual(1);
				expect(OC.MimeType._mimeTypeIcons.dir).toEqual(res);

				res = OC.MimeType.getIconUrl('dir-shared');
				expect(Object.keys(OC.MimeType._mimeTypeIcons).length).toEqual(2);
				expect(OC.MimeType._mimeTypeIcons['dir-shared']).toEqual(res);
			});

			it('test if alaiases are converted correctly', function() {
				var res = OC.MimeType.getIconUrl('app/foobar');
				expect(res).toEqual(OC.getRootPath() + '/core/img/filetypes/foo-bar.svg');
				expect(OC.MimeType._mimeTypeIcons['foo/bar']).toEqual(res);
			});
		});

		describe('themes', function() {
			var _themeFolder;

			beforeEach(function() {
				_themeFolder = OC.theme.folder;
				OC.theme.folder = 'abc';
				//Clear mimetypeIcons caches
				OC.MimeType._mimeTypeIcons = {};
			});

			afterEach(function() {
				OC.theme.folder = _themeFolder;
			});

			it('test if theme path is used if a theme icon is availble', function() {
				var res = OC.MimeType.getIconUrl('dir');
				expect(res).toEqual(OC.getRootPath() + '/themes/abc/core/img/filetypes/folder.svg');
			});

			it('test if we fallback to the default theme if no icon is available in the theme', function() {
				var res = OC.MimeType.getIconUrl('dir-shared');
				expect(res).toEqual(OC.getRootPath() + '/core/img/filetypes/folder-shared.svg');
			});
		});
	});
});
