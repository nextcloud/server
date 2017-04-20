/**
 *
 * @copyright Copyright (c) 2017, Artur Neumann (info@individual-it.net)
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

describe("files Drop tests", function() {
	//some testing data
	var sharingToken = "fVCiSMhScgWfiuv";
	var testFiles = [
		{ name: 'test.txt', expectedValidationResult: true },
		{ name: 'testनेपाल.txt', expectedValidationResult: true },
		{ name: 'test.part', expectedValidationResult: false },
		{ name: 'test.filepart', expectedValidationResult: false },
		{ name: '.', expectedValidationResult: false },
		{ name: '..', expectedValidationResult: false },
	];

	//this pre/post positions should not change the result of the file name validation
	var prePostPositions = [""," ","  ","	"];
	
	//use the testFiles and the pre/post positions to generate more testing data
	var replicatedTestFiles = [];
	prePostPositions.map(function (prePostPosition) {
		testFiles.map(function (testFile) {
			replicatedTestFiles.push(
				{
					name: testFile.name + prePostPosition,
					expectedValidationResult: testFile.expectedValidationResult
				}
			);
			replicatedTestFiles.push(
				{
					name: prePostPosition + testFile.name,
					expectedValidationResult: testFile.expectedValidationResult
				}
			);
			replicatedTestFiles.push(
				{
					name: prePostPosition + testFile.name + prePostPosition,
					expectedValidationResult: testFile.expectedValidationResult
				}
			);
		});
	});

	beforeEach (function () {
		//fake input for the sharing token
		$('#testArea').append(
				'<input name="sharingToken" value="" id="sharingToken" type="hidden">'
		);
	});


	replicatedTestFiles.map(function (testFile) {
		it("validates the filenames correctly", function() {
				data = {
					'submit': function() {},
					'files': [testFile]
				}
				expect(OCA.FilesSharingDrop.addFileToUpload('',data)).
					toBe(
						testFile.expectedValidationResult,
						'wrongly validated file named "'+testFile.name+'"'
						);
		});
		
		if (testFile.expectedValidationResult === true) {
			it("should set correct PUT URL, Auth header and submit", function () {
				data = {
						'submit': sinon.stub(),
						'files': [testFile]
					}
				$('#sharingToken').val(sharingToken);
				
				OCA.FilesSharingDrop.addFileToUpload('',data);
				expect(data.submit.calledOnce).toEqual(true);
				expect(data.url).toContain("/public.php/webdav/" + encodeURI(testFile.name));
				expect(data.headers['Authorization']).toEqual('Basic ' + btoa(sharingToken+":"));
			});
		}
	});
});
