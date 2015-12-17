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

describe('OC.Files.Client tests', function() {
	var Client = OC.Files.Client;
	var baseUrl;
	var client;

	beforeEach(function() {
		baseUrl = 'https://testhost/owncloud/remote.php/webdav/';

		client = new Client({
			host: 'testhost',
			root: '/owncloud/remote.php/webdav',
			useHTTPS: true
		});
	});
	afterEach(function() {
		client = null;
	});

	/**
	 * Send an status response and check that the given
	 * promise gets its success handler called with the error
	 * status code
	 *
	 * @param {Promise} promise promise
	 * @param {int} status status to test
	 */
	function respondAndCheckStatus(promise, status) {
		var successHandler = sinon.stub();
		var failHandler = sinon.stub();
		promise.done(successHandler);
		promise.fail(failHandler);

		fakeServer.requests[0].respond(
			status,
			{'Content-Type': 'application/xml'},
			''
		);

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
	 * @param {int} status error status to test
	 */
	function respondAndCheckError(promise, status) {
		var successHandler = sinon.stub();
		var failHandler = sinon.stub();
		promise.done(successHandler);
		promise.fail(failHandler);

		fakeServer.requests[0].respond(
			status,
			{'Content-Type': 'application/xml'},
			''
		);

		promise.then(function() {
			expect(failHandler.calledOnce).toEqual(true);
			expect(failHandler.calledWith(status)).toEqual(true);

			expect(successHandler.notCalled).toEqual(true);

			fulfill();
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

		var folderContentsXml =
			'<?xml version="1.0" encoding="utf-8"?>' +
			'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
				'd:getetag': '"56cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000011oc2d13a6a068',
				'oc:permissions': 'RDNVCK',
				'oc:size': 120
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
				'oc:permissions': 'RDNVCK',
				'oc:size': 100
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			'</d:multistatus>';

		it('sends PROPFIND with explicit properties to get file list', function() {
			client.getFolderContents('path/to space/文件夹');

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PROPFIND');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Depth).toEqual(1);

			var props = getRequestedProperties(fakeServer.requests[0].requestBody);
			expect(props).toContain('{DAV:}getlastmodified');
			expect(props).toContain('{DAV:}getcontentlength');
			expect(props).toContain('{DAV:}getcontenttype');
			expect(props).toContain('{DAV:}getetag');
			expect(props).toContain('{DAV:}resourcetype');
			expect(props).toContain('{http://owncloud.org/ns}fileid');
			expect(props).toContain('{http://owncloud.org/ns}size');
			expect(props).toContain('{http://owncloud.org/ns}permissions');
		});
		it('sends PROPFIND to base url when empty path given', function() {
			client.getFolderContents('');
			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].url).toEqual(baseUrl);
		});
		it('sends PROPFIND to base url when root path given', function() {
			client.getFolderContents('/');
			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].url).toEqual(baseUrl);
		});
		it('parses the result list into a FileInfo array', function() {
			var promise = client.getFolderContents('path/to space/文件夹');

			expect(fakeServer.requests.length).toEqual(1);

			fakeServer.requests[0].respond(
				207,
				{'Content-Type': 'application/xml'},
				folderContentsXml
			);

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
				expect(info.permissions).toEqual(31);
				expect(info.size).toEqual(250);
				expect(info.mtime).toEqual(1436535485000);
				expect(info.mimetype).toEqual('text/plain');
				expect(info.etag).toEqual('559fcabd79a38');

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
			});
			return promise.promise();
		});
		it('returns parent node in result if specified', function() {
			var promise = client.getFolderContents('path/to space/文件夹', {includeParent: true});

			expect(fakeServer.requests.length).toEqual(1);

			fakeServer.requests[0].respond(
				207,
				{'Content-Type': 'application/xml'},
				folderContentsXml
			);

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

				// the two other entries follow
				expect(response[1].id).toEqual(51);
				expect(response[2].id).toEqual(15);
			});

			return promise;
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFolderContents('path/to space/文件夹', {includeParent: true});
			return respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('file info', function() {
		var responseXml =
			'<?xml version="1.0" encoding="utf-8"?>' +
			'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
			makeResponseBlock(
			'/owncloud/remote.php/webdav/path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/',
			{
				'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
				'd:getetag': '"56cfcabd79abb"',
				'd:resourcetype': '<d:collection/>',
				'oc:id': '00000011oc2d13a6a068',
				'oc:permissions': 'RDNVCK',
				'oc:size': 120
			},
			[
				'd:getcontenttype',
				'd:getcontentlength'
			]
			) +
			'</d:multistatus>';

		it('sends PROPFIND with zero depth to get single file info', function() {
			client.getFileInfo('path/to space/文件夹');

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PROPFIND');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Depth).toEqual(0);

			var props = getRequestedProperties(fakeServer.requests[0].requestBody);
			expect(props).toContain('{DAV:}getlastmodified');
			expect(props).toContain('{DAV:}getcontentlength');
			expect(props).toContain('{DAV:}getcontenttype');
			expect(props).toContain('{DAV:}getetag');
			expect(props).toContain('{DAV:}resourcetype');
			expect(props).toContain('{http://owncloud.org/ns}fileid');
			expect(props).toContain('{http://owncloud.org/ns}size');
			expect(props).toContain('{http://owncloud.org/ns}permissions');
		});
		it('parses the result into a FileInfo', function() {
			var promise = client.getFileInfo('path/to space/文件夹');

			expect(fakeServer.requests.length).toEqual(1);

			fakeServer.requests[0].respond(
				207,
				{'Content-Type': 'application/xml'},
				responseXml
			);

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
			});

			return promise;
		});
		it('properly parses entry inside root', function() {
			var responseXml =
				'<?xml version="1.0" encoding="utf-8"?>' +
				'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
				makeResponseBlock(
				'/owncloud/remote.php/webdav/in%20root',
				{
					'd:getlastmodified': 'Fri, 10 Jul 2015 10:00:05 GMT',
					'd:getetag': '"56cfcabd79abb"',
					'd:resourcetype': '<d:collection/>',
					'oc:id': '00000011oc2d13a6a068',
					'oc:permissions': 'RDNVCK',
					'oc:size': 120
				},
				[
					'd:getcontenttype',
					'd:getcontentlength'
				]
				) +
				'</d:multistatus>';

			var promise = client.getFileInfo('in root');

			expect(fakeServer.requests.length).toEqual(1);

			fakeServer.requests[0].respond(
				207,
				{'Content-Type': 'application/xml'},
				responseXml
			);

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
			});

			return promise;
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFileInfo('path/to space/文件夹');
			return respondAndCheckError(promise, 404);
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
				'oc:permissions': webdavPerm,
			};

			if (isFile) {
				props['d:getcontenttype'] = 'text/plain';
			} else {
				props['d:resourcetype'] = '<d:collection/>';
			}

			var responseXml =
				'<?xml version="1.0" encoding="utf-8"?>' +
				'<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:oc="http://owncloud.org/ns">' +
				makeResponseBlock(
					'/owncloud/remote.php/webdav/file.txt',
					props
				) +
				'</d:multistatus>';
			var promise = client.getFileInfo('file.txt');

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				207,
				{'Content-Type': 'application/xml'},
				responseXml
			);

			fakeServer.restore();
			fakeServer = sinon.fakeServer.create();

			return promise;
		}

		function testPermission(permission, isFile, expectedPermissions) {
			var promise = getFileInfoWithPermission(permission, isFile);
			promise.then(function(result) {
				expect(result.permissions).toEqual(expectedPermissions);
			});
			return promise;
		}

		function testMountType(permission, isFile, expectedMountType) {
			var promise = getFileInfoWithPermission(permission, isFile);
			promise.then(function(result) {
				expect(result.mountType).toEqual(expectedMountType);
			});
			return promise;
		}

		it('properly parses file permissions', function() {
			// permission, isFile, expectedPermissions
			var testCases = [
				['', true, OC.PERMISSION_READ],
				['C', true, OC.PERMISSION_READ | OC.PERMISSION_CREATE],
				['K', true, OC.PERMISSION_READ | OC.PERMISSION_CREATE],
				['W', true, OC.PERMISSION_READ | OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE],
				['D', true, OC.PERMISSION_READ | OC.PERMISSION_DELETE],
				['R', true, OC.PERMISSION_READ | OC.PERMISSION_SHARE],
				['CKWDR', true, OC.PERMISSION_ALL]
			];
			return Promise.all(
				_.map(testCases, function(testCase) {
					return testPermission.apply(testCase);
				})
			);
		});
		it('properly parses folder permissions', function() {
			var testCases = [
				['', false, OC.PERMISSION_READ],
				['C', false, OC.PERMISSION_READ | OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE],
				['K', false, OC.PERMISSION_READ | OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE],
				['W', false, OC.PERMISSION_READ | OC.PERMISSION_UPDATE],
				['D', false, OC.PERMISSION_READ | OC.PERMISSION_DELETE],
				['R', false, OC.PERMISSION_READ | OC.PERMISSION_SHARE],
				['CKWDR', false, OC.PERMISSION_ALL]
			];

			return Promise.all(
				_.map(testCases, function(testCase) {
					return testPermission.apply(testCase);
				})
			);
		});
		it('properly parses mount types', function() {
			var testCases = [
				['CKWDR', false, null],
				['M', false, 'external'],
				['S', false, 'shared'],
				['SM', false, 'shared']
			];

			return Promise.all(
				_.map(testCases, function(testCase) {
					return testMountType.apply(testCase);
				})
			);
		});
	});

	describe('get file contents', function() {
		it('returns file contents', function() {
			var promise = client.getFileContents('path/to space/文件夹/One.txt');

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');

			fakeServer.requests[0].respond(
				200,
				{'Content-Type': 'text/plain'},
				'some contents'
			);

			promise.then(function(status, response) {
				expect(status).toEqual(200);
				expect(response).toEqual('some contents');
			});

			return promise;
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.getFileContents('path/to space/文件夹/One.txt');
			return respondAndCheckError(promise, 409);
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

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PUT');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');
			expect(fakeServer.requests[0].requestBody).toEqual('some contents');
			expect(fakeServer.requests[0].requestHeaders['If-None-Match']).toEqual('*');
			expect(fakeServer.requests[0].requestHeaders['Content-Type']).toEqual('text/plain;charset=utf-8');

			return respondAndCheckStatus(promise, 201);
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

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PUT');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/One.txt');
			expect(fakeServer.requests[0].requestBody).toEqual('some contents');
			expect(fakeServer.requests[0].requestHeaders['If-None-Match']).not.toBeDefined();
			expect(fakeServer.requests[0].requestHeaders['Content-Type']).toEqual('text/markdown;charset=utf-8');

			return respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.putFileContents(
					'path/to space/文件夹/One.txt',
					'some contents'
			);
			return respondAndCheckError(promise, 409);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('create directory', function() {
		it('sends MKCOL with specified path', function() {
			var promise = client.createDirectory('path/to space/文件夹/new dir');

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('MKCOL');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9/new%20dir');

			return respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.createDirectory('path/to space/文件夹/new dir');
			return respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});

	describe('deletion', function() {
		it('sends DELETE with specified path', function() {
			var promise = client.remove('path/to space/文件夹');

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('DELETE');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');

			return respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.remove('path/to space/文件夹');
			return respondAndCheckError(promise, 404);
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

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('MOVE');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Destination)
				.toEqual(baseUrl + 'path/to%20space/anotherdir/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Overwrite)
				.toEqual('F');

			return respondAndCheckStatus(promise, 201);
		});
		it('sends MOVE with silent overwrite mode when specified', function() {
			var promise = client.move(
					'path/to space/文件夹',
					'path/to space/anotherdir/文件夹',
					{allowOverwrite: true}
			);

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('MOVE');
			expect(fakeServer.requests[0].url).toEqual(baseUrl + 'path/to%20space/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Destination)
				.toEqual(baseUrl + 'path/to%20space/anotherdir/%E6%96%87%E4%BB%B6%E5%A4%B9');
			expect(fakeServer.requests[0].requestHeaders.Overwrite)
				.not.toBeDefined();

			return respondAndCheckStatus(promise, 201);
		});
		it('rejects promise when an error occurred', function() {
			var promise = client.move(
					'path/to space/文件夹',
					'path/to space/anotherdir/文件夹',
					{allowOverwrite: true}
			);
			return respondAndCheckError(promise, 404);
		});
		it('throws exception if arguments are missing', function() {
			// TODO
		});
	});
});
