/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

/* global Files */
describe('Files tests', function() {
	describe('File name validation', function() {
		it('Validates correct file names', function() {
			var fileNames = [
				'boringname',
				'something.with.extension',
				'now with spaces',
				'.a',
				'..a',
				'.dotfile',
				'single\'quote',
				'  spaces before',
				'spaces after   ',
				'allowed chars including the crazy ones $%&_-^@!,()[]{}=;#',
				'汉字也能用',
				'und Ümläüte sind auch willkommen'
			];
			for ( var i = 0; i < fileNames.length; i++ ) {
				var error = false;
				try {
					expect(Files.isFileNameValid(fileNames[i])).toEqual(true);
				}
				catch (e) {
					error = e;
				}
				expect(error).toEqual(false);
			}
		});
		it('Detects invalid file names', function() {
			var fileNames = [
				'',
				'     ',
				'.',
				'..',
				'back\\slash',
				'sl/ash',
				'lt<lt',
				'gt>gt',
				'col:on',
				'double"quote',
				'pi|pe',
				'dont?ask?questions?',
				'super*star',
				'new\nline',
				' ..',
				'.. ',
				'. ',
				' .'
			];
			for ( var i = 0; i < fileNames.length; i++ ) {
				var threwException = false;
				try {
					Files.isFileNameValid(fileNames[i]);
					console.error('Invalid file name not detected:', fileNames[i]);
				}
				catch (e) {
					threwException = true;
				}
				expect(threwException).toEqual(true);
			}
		});
	});
});
