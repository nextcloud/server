/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
				expect(data.url).toContain("/public.php/dav/files/" + sharingToken + '/' + encodeURI(testFile.name));
			});
		}
	});
});
