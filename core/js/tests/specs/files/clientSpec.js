/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
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

/* global dav */

describe('OC.Files.Client tests', function() {
	var Client = OC.Files.Client;
	var baseUrl;
	var client;
	var requestStub;
	var requestDeferred;

	beforeEach(function() {
		requestDeferred = new $.Deferred();
		requestStub = sinon.stub(dav.Client.prototype, 'request').returns(requestDeferred.promise());
		baseUrl = 'https://testhost/owncloud/remote.php/webdav/';

		client = new Client({
			host: 'testhost',
			root: '/owncloud/remote.php/webdav',
			useHTTPS: true
		});
	});
	afterEach(function() {
		client = null;
		requestStub.restore();
	});

	/**
	 * Send an status response and check that the given
	 * promise gets its success handler called with the error
	 * status code
	 *
	 * @param {Promise} promise promise
	 * @param {number} status status to test
	 */
	function respondAndCheckStatus(promise, status) {
		var successHandler = sinon.stub();
		var failHandler = sinon.stub();
		promise.done(successHandler);
		promise.fail(failHandler);

		requestDeferred.resolve({
			status: status,
			body: ''
		});

		promise.then(function() {
			expect(successHandler.calledOnce).toEqual(true);
			expect(successHandler.getCall(0).args[0]).toEqual(status);

			expect(failHandler.notCalled).toEqual(true);
		});

		return promise;
	}

	/**
	 * Send an error response and check that the given
	 * promise gets its fail handler called with the error
	 * status code
	 *
	 * @param {Promise} promise promise object
	 * @param {number} status error status to test
	 */
	function respondAndCheckError(promise, status) {
		var successHandler = sinon.stub();
		var failHandler = sinon.stub();
		promise.done(successHandler);
		promise.fail(failHandler);

		var errorXml =
			'<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">' +
			'    <s:exception>Sabre\\DAV\\Exception\\SomeException</s:exception>' +
			'    <s:message>Some error message</s:message>' +
			'</d:error>';

		var parser = new DOMParser();

		requestDeferred.resolve({
			status: status,
			body: errorXml,
			xhr: {
				responseXML: parser.parseFromString(errorXml, 'application/xml')
			}
		});

		promise.then(function() {
			expect(failHandler.calledOnce).toEqual(true);
			expect(failHandler.getCall(0).args[0]).toEqual(status);
			expect(failHandler.getCall(0).args[1].status).toEqual(status);
			expect(failHandler.getCall(0).args[1].message).toEqual('Some error message');
			expect(failHandler.getCall(0).args[1].exception).toEqual('Sabre\\DAV\\Exception\\SomeException');

			expect(successHandler.notCalled).toEqual(true);
		});

		return promise;
	}

	/**
	 * Returns a list of request properties parsed from the given request body.
	 *
	 * @param {string} requestBody request XML
	 *
	 * @return {Array.<String>} array of request properties in the format
	 * "{NS:}propname"
	 */
	function getRequestedProperties(requestBody) {
		var doc = (new window.DOMParser()).parseFromString(
				requestBody,
				'application/xml'
		);
		var propRoots = doc.getElementsByTagNameNS('DAV:', 'prop');
		var propsList = propRoots.item(0).childNodes;
		return _.map(propsList, function(propNode) {
			return '{' + propNode.namespaceURI + '}' + propNode.localName;
		});
	}

	function makePropBlock(props) {
		var s = '<d:prop>\n';

		_.each(props, function(value, key) {
			s += '<' + key + '>' + value + '</' + key + '>\n';
		});

		return s + '</d:prop>\n';
	}

	function makeResponseBlock(href, props, failedProps) {
		var s = '<d:response>\n';
		s += '<d:href>' + href + '</d:href>\n';
		s += '<d:propstat>\n';
		s += makePropBlock(props);
		s += '<d:status>HTTP/1.1 200 OK</d:status>';
		s += '</d:propstat>\n';
		if (failedProps) {
			s += '<d:propstat>\n';
			_.each(failedProps, function(prop) {
				s += '<' + prop + '/>\n';
			});
			s += '<d:status>HTTP/1.1 404 Not Found</d:status>\n';
			s += '</d:propstat>\n';
		}
		return s + '</d:response>\n';
	}

	describe('file listing', function() {

		// TODO: switch this to the already parsed structure
		var folderContentsXml = dav.Client.prototype.parseMultiStatus(
			'<?xml version="1.0" encoding="utf-8"?>' +
			'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
				'd:getetag': '"56cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000011oc2d13a6a068',
				'oc:fileid': '11',
				'oc:permissions': 'GRDNVCK',
				'oc:size': '120'
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 13:38:05 GMT',
				'd:getetag': '"559fcabd79a38"',
				'd:getcontenttype': 'text/plain',
				'd:getcontentlength': 250,
				'd:resourcetype': '',
				'oc:id': '00000051oc2d13a6a068',
				'oc:fileid': '51',
				'oc:permissions': 'RDNVW'
			},
			[
				'oc:size',
			]
			) +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/sub',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 14:00:00 GMT',
				'd:getetag': '"66cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000015oc2d13a6a068',
				'oc:fileid': '15',
				'oc:permissions': 'GRDNVCK',
				'oc:size': '100'
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			'</d:multistatus>'
		);

		it('sends PROPFIND with explicit properties to get file list', function() {
			client.getFolderContents('path/to space/文件夹');

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('PROPFIND');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Depth).toEqual('1');

			var props = getRequestedProperties(requestStub.lastCall.args[3]);
			expect(props).toContain('{DAV:}getlastmodified');
			expect(props).toContain('{DAV:}getcontentlength');
			expect(props).toContain('{DAV:}getcontenttype');
			expect(props).toContain('{DAV:}getetag');
			expect(props).toContain('{DAV:}resourcetype');
			expect(props).toContain('{http://owncloud.org/ns}fileid');
			expect(props).toContain('{http://owncloud.org/ns}size');
			expect(props).toContain('{http://owncloud.org/ns}permissions');
			expect(props).toContain('{http://nextcloud.org/ns}is-encrypted');
		});
		it('sends PROPFIND to base url when empty path given', function() {
			client.getFolderContents('');
			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl);
		});
		it('sends PROPFIND to base url when root path given', function() {
			client.getFolderContents('/');
			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl);
		});
		it('parses the result list into a FileInfo array', function() {
			var promise = client.getFolderContents('path/to space/文件夹');

			expect(requestStub.calledOnce).toEqual(true);

			requestDeferred.resolve({
				status: 207,
				body: folderContentsXml
			});

			promise.then(function(status, response) {
				expect(status).toEqual(207);
				expect(_.isArray(response)).toEqual(true);

				expect(response.length).toEqual(2);

				// file entry
				var info = response[0];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(51);
				expect(info.path).toEqual('/path/to space/文件夹');
				expect(info.name).toEqual('One.txt');
				expect(info.permissions).toEqual(26);
				expect(info.size).toEqual(250);
				expect(info.mtime).toEqual(1436535485000);
				expect(info.mimetype).toEqual('text/plain');
				expect(info.etag).toEqual('559fcabd79a38');
				expect(info.isEncrypted).toEqual(false);

				// sub entry
				info = response[1];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(15);
				expect(info.path).toEqual('/path/to space/文件夹');
				expect(info.name).toEqual('sub');
				expect(info.permissions).toEqual(31);
				expect(info.size).toEqual(100);
				expect(info.mtime).toEqual(1436536800000);
				expect(info.mimetype).toEqual('httpd/unix-directory');
				expect(info.etag).toEqual('66cfcabd79abb');
				expect(info.isEncrypted).toEqual(false);
			});
		});
		it('returns parent node in result if specified', function() {
			var promise = client.getFolderContents('path/to space/文件夹', {includeParent: true});

			expect(requestStub.calledOnce).toEqual(true);

			requestDeferred.resolve({
				status: 207,
				body: folderContentsXml
			});

			promise.then(function(status, response) {
				expect(status).toEqual(207);
				expect(_.isArray(response)).toEqual(true);

				expect(response.length).toEqual(3);

				// root entry
				var info = response[0];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(11);
				expect(info.path).toEqual('/path/to space');
				expect(info.name).toEqual('文件夹');
				expect(info.permissions).toEqual(31);
				expect(info.size).toEqual(120);
				expect(info.mtime).toEqual(1436522405000);
				expect(info.mimetype).toEqual('httpd/unix-directory');
				expect(info.etag).toEqual('56cfcabd79abb');
				expect(info.isEncrypted).toEqual(false);

				// the two other entries follow
				expect(response[1].id).toEqual(51);
				expect(response[2].id).toEqual(15);
			});
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFolderContents('path/to space/文件夹', {includeParent: true});
			respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('file filtering', function() {

		// TODO: switch this to the already parsed structure
		var folderContentsXml = dav.Client.prototype.parseMultiStatus(
			'<?xml version="1.0" encoding="utf-8"?>' +
			'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
				'd:getetag': '"56cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000011oc2d13a6a068',
				'oc:fileid': '11',
				'oc:permissions': 'RDNVCK',
				'oc:size': '120'
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 13:38:05 GMT',
				'd:getetag': '"559fcabd79a38"',
				'd:getcontenttype': 'text/plain',
				'd:getcontentlength': 250,
				'd:resourcetype': '',
				'oc:id': '00000051oc2d13a6a068',
				'oc:fileid': '51',
				'oc:permissions': 'RDNVW'
			},
			[
				'oc:size',
			]
			) +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/sub',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 14:00:00 GMT',
				'd:getetag': '"66cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000015oc2d13a6a068',
				'oc:fileid': '15',
				'oc:permissions': 'RDNVCK',
				'oc:size': '100'
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			'</d:multistatus>'
		);

		it('sends REPORT with filter information', function() {
			client.getFilteredFiles({
				systemTagIds: ['123', '456']
			});

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('REPORT');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl);

			var body = requestStub.lastCall.args[3];
			var doc = (new window.DOMParser()).parseFromString(
					body,
					'application/xml'
			);

			var ns = 'http://owncloud.org/ns';
			expect(doc.documentElement.localName).toEqual('filter-files');
			expect(doc.documentElement.namespaceURI).toEqual(ns);

			var filterRoots = doc.getElementsByTagNameNS(ns, 'filter-rules');
			var rulesList = filterRoots[0] = doc.getElementsByTagNameNS(ns, 'systemtag');
			expect(rulesList.length).toEqual(2);
			expect(rulesList[0].localName).toEqual('systemtag');
			expect(rulesList[0].namespaceURI).toEqual(ns);
			expect(rulesList[0].textContent).toEqual('123');
			expect(rulesList[1].localName).toEqual('systemtag');
			expect(rulesList[1].namespaceURI).toEqual(ns);
			expect(rulesList[1].textContent).toEqual('456');
		});
		it('sends REPORT with explicit properties to filter file list', function() {
			client.getFilteredFiles({
				systemTagIds: ['123', '456']
			});

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('REPORT');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl);

			var props = getRequestedProperties(requestStub.lastCall.args[3]);
			expect(props).toContain('{DAV:}getlastmodified');
			expect(props).toContain('{DAV:}getcontentlength');
			expect(props).toContain('{DAV:}getcontenttype');
			expect(props).toContain('{DAV:}getetag');
			expect(props).toContain('{DAV:}resourcetype');
			expect(props).toContain('{http://owncloud.org/ns}fileid');
			expect(props).toContain('{http://owncloud.org/ns}size');
			expect(props).toContain('{http://owncloud.org/ns}permissions');
			expect(props).toContain('{http://nextcloud.org/ns}is-encrypted');
		});
		it('parses the result list into a FileInfo array', function() {
			var promise = client.getFilteredFiles({
				systemTagIds: ['123', '456']
			});

			expect(requestStub.calledOnce).toEqual(true);

			requestDeferred.resolve({
				status: 207,
				body: folderContentsXml
			});

			promise.then(function(status, response) {
				expect(status).toEqual(207);
				expect(_.isArray(response)).toEqual(true);

				// returns all entries
				expect(response.length).toEqual(3);

				// file entry
				var info = response[0];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(11);

				// file entry
				info = response[1];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(51);

				// sub entry
				info = response[2];
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(15);
			});
		});
		it('throws exception if arguments are missing', function() {
			var thrown = null;
			try {
				client.getFilteredFiles({});
			} catch (e) {
				thrown = true;
			}

			expect(thrown).toEqual(true);
		});
	});

	describe('file info', function() {
		var responseXml = dav.Client.prototype.parseMultiStatus(
			'<?xml version="1.0" encoding="utf-8"?>' +
			'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">' +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
				'd:getetag': '"56cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000011oc2d13a6a068',
				'oc:fileid': '11',
				'oc:permissions': 'GRDNVCK',
				'oc:size': '120',
				'nc:is-encrypted': '1'
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			'</d:multistatus>'
		);

		it('sends PROPFIND with zero depth to get single file info', function() {
			client.getFileInfo('path/to space/文件夹');

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('PROPFIND');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Depth).toEqual('0');

			var props = getRequestedProperties(requestStub.lastCall.args[3]);
			expect(props).toContain('{DAV:}getlastmodified');
			expect(props).toContain('{DAV:}getcontentlength');
			expect(props).toContain('{DAV:}getcontenttype');
			expect(props).toContain('{DAV:}getetag');
			expect(props).toContain('{DAV:}resourcetype');
			expect(props).toContain('{http://owncloud.org/ns}fileid');
			expect(props).toContain('{http://owncloud.org/ns}size');
			expect(props).toContain('{http://owncloud.org/ns}permissions');
			expect(props).toContain('{http://nextcloud.org/ns}is-encrypted');
		});
		it('parses the result into a FileInfo', function() {
			var promise = client.getFileInfo('path/to space/文件夹');

			expect(requestStub.calledOnce).toEqual(true);

			requestDeferred.resolve({
				status: 207,
				body: responseXml
			});

			promise.then(function(status, response) {
				expect(status).toEqual(207);
				expect(_.isArray(response)).toEqual(false);

				var info = response;
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(11);
				expect(info.path).toEqual('/path/to space');
				expect(info.name).toEqual('文件夹');
				expect(info.permissions).toEqual(31);
				expect(info.size).toEqual(120);
				expect(info.mtime).toEqual(1436522405000);
				expect(info.mimetype).toEqual('httpd/unix-directory');
				expect(info.etag).toEqual('56cfcabd79abb');
				expect(info.isEncrypted).toEqual(true);
			});
		});
		it('properly parses entry inside root', function() {
			var responseXml = dav.Client.prototype.parseMultiStatus(
				'<?xml version="1.0" encoding="utf-8"?>' +
				'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
				makeResponseBlock(
				'/owncloud/remote.php/webdav/in%20root',
				{
					'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
					'd:getetag': '"56cfcabd79abb"',
					'd:resourcetype': '<d:collection/>',
					'oc:id': '00000011oc2d13a6a068',
					'oc:fileid': '11',
					'oc:permissions': 'GRDNVCK',
					'oc:size': '120'
				},
				[
					'd:getcontenttype',
					'd:getcontentlength'
				]
				) +
				'</d:multistatus>'
			);

			var promise = client.getFileInfo('in root');

			expect(requestStub.calledOnce).toEqual(true);

			requestDeferred.resolve({
				status: 207,
				body: responseXml
			});

			promise.then(function(status, response) {
				expect(status).toEqual(207);
				expect(_.isArray(response)).toEqual(false);

				var info = response;
				expect(info instanceof OC.Files.FileInfo).toEqual(true);
				expect(info.id).toEqual(11);
				expect(info.path).toEqual('/');
				expect(info.name).toEqual('in root');
				expect(info.permissions).toEqual(31);
				expect(info.size).toEqual(120);
				expect(info.mtime).toEqual(1436522405000);
				expect(info.mimetype).toEqual('httpd/unix-directory');
				expect(info.etag).toEqual('56cfcabd79abb');
				expect(info.isEncrypted).toEqual(false);
			});
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFileInfo('path/to space/文件夹');
			respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('permissions', function() {

		function getFileInfoWithPermission(webdavPerm, isFile) {
			var props = {
				'd:getlastmodified': 'Fri, 10 Jul 2015 13:38:05 GMT',
				'd:getetag': '"559fcabd79a38"',
				'd:getcontentlength': 250,
				'oc:id': '00000051oc2d13a6a068',
				'oc:fileid': '51',
				'oc:permissions': webdavPerm,
			};

			if (isFile) {
				props['d:getcontenttype'] = 'text/plain';
			} else {
				props['d:resourcetype'] = '<d:collection/>';
			}

			var def = new $.Deferred();
			requestStub.reset();
			requestStub.returns(def);

			var responseXml = dav.Client.prototype.parseMultiStatus(
				'<?xml version="1.0" encoding="utf-8"?>' +
				'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
				makeResponseBlock(
					'/owncloud/remote.php/webdav/file.txt',
					props
				) +
				'</d:multistatus>'
			);

			var promise = client.getFileInfo('file.txt');

			expect(requestStub.calledOnce).toEqual(true);

			def.resolve({
				status: 207,
				body: responseXml
			});

			return promise;
		}

		function testPermission(permission, isFile, expectedPermissions) {
			var promise = getFileInfoWithPermission(permission, isFile);
			promise.then(function(status, result) {
				expect(result.permissions).toEqual(expectedPermissions);
			});
		}

		function testMountType(permission, isFile, expectedMountType) {
			var promise = getFileInfoWithPermission(permission, isFile);
			promise.then(function(status, result) {
				expect(result.mountType).toEqual(expectedMountType);
			});
		}

		it('properly parses file permissions', function() {
			// permission, isFile, expectedPermissions
			var testCases = [
				['', true, OC.PERMISSION_NONE],
				['C', true, OC.PERMISSION_CREATE],
				['K', true, OC.PERMISSION_CREATE],
				['G', true, OC.PERMISSION_READ],
				['W', true, OC.PERMISSION_UPDATE],
				['D', true, OC.PERMISSION_DELETE],
				['R', true, OC.PERMISSION_SHARE],
				['CKGWDR', true, OC.PERMISSION_ALL]
			];
			_.each(testCases, function(testCase) {
				return testPermission.apply(this, testCase);
			});
		});
		it('properly parses mount types', function() {
			var testCases = [
				['CKGWDR', false, null],
				['M', false, 'external'],
				['S', false, 'shared'],
				['SM', false, 'shared']
			];

			_.each(testCases, function(testCase) {
				return testMountType.apply(this, testCase);
			});
		});
	});

	describe('get file contents', function() {
		it('returns file contents', function() {
			var promise = client.getFileContents('path/to space/文件夹/One.txt');

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('GET');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');

			requestDeferred.resolve({
				status: 200,
				body: 'some contents'
			});

			promise.then(function(status, response) {
				expect(status).toEqual(200);
				expect(response).toEqual('some contents');
			});
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFileContents('path/to space/文件夹/One.txt');
			respondAndCheckError(promise, 409);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('put file contents', function() {
		it('sends PUT with file contents', function() {
			var promise = client.putFileContents(
					'path/to space/文件夹/One.txt',
					'some contents'
			);

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('PUT');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');
			expect(requestStub.lastCall.args[2]['If-None-Match']).toEqual('*');
			expect(requestStub.lastCall.args[2]['Content-Type']).toEqual('text/plain;charset=utf-8');
			expect(requestStub.lastCall.args[3]).toEqual('some contents');

			respondAndCheckStatus(promise, 201);
		});
		it('sends PUT with file contents with headers matching options', function() {
			var promise = client.putFileContents(
					'path/to space/文件夹/One.txt',
					'some contents',
					{
						overwrite: false,
						contentType: 'text/markdown'
					}
			);

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('PUT');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');
			expect(requestStub.lastCall.args[2]['If-None-Match']).not.toBeDefined();
			expect(requestStub.lastCall.args[2]['Content-Type']).toEqual('text/markdown');
			expect(requestStub.lastCall.args[3]).toEqual('some contents');

			respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.putFileContents(
					'path/to space/文件夹/One.txt',
					'some contents'
			);
			respondAndCheckError(promise, 409);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('create directory', function() {
		it('sends MKCOL with specified path', function() {
			var promise = client.createDirectory('path/to space/文件夹/new dir');

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('MKCOL');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/new%20dir');

			respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.createDirectory('path/to space/文件夹/new dir');
			respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('deletion', function() {
		it('sends DELETE with specified path', function() {
			var promise = client.remove('path/to space/文件夹');

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('DELETE');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');

			respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.remove('path/to space/文件夹');
			respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('move', function() {
		it('sends MOVE with specified paths with fail on overwrite by default', function() {
			var promise = client.move(
					'path/to space/文件夹',
					'path/to space/anotherdir/文件夹'
			);

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('MOVE');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Destination)
				.toEqual(baseUrl + 'path/to%20space/anotherdir/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Overwrite)
				.toEqual('F');

			respondAndCheckStatus(promise, 201);
		});
		it('sends MOVE with silent overwrite mode when specified', function() {
			var promise = client.move(
					'path/to space/文件夹',
					'path/to space/anotherdir/文件夹',
					{allowOverwrite: true}
			);

			expect(requestStub.calledOnce).toEqual(true);
			expect(requestStub.lastCall.args[0]).toEqual('MOVE');
			expect(requestStub.lastCall.args[1]).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Destination)
				.toEqual(baseUrl + 'path/to%20space/anotherdir/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(requestStub.lastCall.args[2].Overwrite)
				.not.toBeDefined();

			respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.move(
					'path/to space/文件夹',
					'path/to space/anotherdir/文件夹',
					{allowOverwrite: true}
			);
			respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});
});
