<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace SearchDAV\Test;

use PHPUnit\Framework\TestCase;
use Sabre\DAV\FS\Directory;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\Xml\Service;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\Backend\SearchResult;
use SearchDAV\DAV\SearchPlugin;
use SearchDAV\Query\Query;
use SearchDAV\XML\Limit;
use SearchDAV\XML\Literal;
use SearchDAV\XML\Scope;
use SearchDAV\XML\SupportedQueryGrammar;

class SearchPluginTest extends TestCase {
	/** @var ISearchBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $searchBackend;

	protected function setUp(): void {
		parent::setUp();

		$this->searchBackend = $this->getMockBuilder(ISearchBackend::class)
			->getMock();
	}

	public function testNoXmlBody() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$request = new Request('SEARCH', 'foo', [
			'Content-Type' => 'text/plain',
		], fopen(__DIR__ . '/nofrom.xml', 'r'));
		$response = new Response();

		$plugin = new SearchPlugin($this->searchBackend);

		$this->assertNotEquals(false, $plugin->searchHandler($request, $response));
	}

	public function testNotArbiterPath() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$request = new Request('SEARCH', 'bar', [
			'Content-Type' => 'text/xml',
		], fopen(__DIR__ . '/nofrom.xml', 'r'));
		$response = new Response();

		$plugin = new SearchPlugin($this->searchBackend);

		$this->assertNotEquals(false, $plugin->searchHandler($request, $response));
	}

	public function testInvalidType() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$request = new Request('SEARCH', 'foo', [
			'Content-Type' => 'text/xml',
		], fopen(__DIR__ . '/invalidtype.xml', 'r'));
		$response = new Response();

		$plugin = new SearchPlugin($this->searchBackend);

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testHandleParseException() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$request = new Request('SEARCH', 'foo', [
			'Content-Type' => 'text/xml',
		], fopen(__DIR__ . '/nofrom.xml', 'r'));
		$response = new Response();

		$plugin = new SearchPlugin($this->searchBackend);

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testHTTPMethods() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$server->setBaseUri('/index.php');
		$plugin->initialize($server);

		$this->assertEquals([], $plugin->getHTTPMethods('bar'));

		$this->assertEquals(['SEARCH'], $plugin->getHTTPMethods('foo'));

		$this->assertEquals([], $plugin->getHTTPMethods('http://example.com/index.php/bar'));

		$this->assertEquals(['SEARCH'], $plugin->getHTTPMethods('http://example.com/index.php/foo'));
	}

	public function testOptionHandler() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);

		$request = new Request('OPTIONS', '/index.php/bar');
		$request->setBaseUrl('/index.php');
		$response = new Response();

		$plugin->optionHandler($request, $response);

		$this->assertEquals(false, $response->hasHeader('DASL'));

		$request = new Request('OPTIONS', '/index.php/foo');
		$request->setBaseUrl('/index.php');
		$response = new Response();

		$plugin->optionHandler($request, $response);

		$this->assertEquals(true, $response->hasHeader('DASL'));
	}

	public function testSchemaDiscovery() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/discover.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->once())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->once())
			->method('getPropertyDefinitionsForScope')
			->willReturn([
				new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
				new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true),
				new SearchPropertyDefinition('{DAV:}displayname', true, true, true),
				new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			]);

		$plugin->searchHandler($request, $response);

		$parser = new Service();
		$parsedResponse = $parser->parse($response->getBody());
		$expected = $parser->parse(fopen(__DIR__ . '/discoverresponse.xml', 'r'));
		$this->assertEquals($expected, $parsedResponse);
	}

	public function testSchemaDiscoveryInvalidScope() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/discover.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->once())
			->method('isValidScope')
			->willReturn(false);

		$this->searchBackend->expects($this->never())
			->method('getPropertyDefinitionsForScope');

		$plugin->searchHandler($request, $response);

		$parser = new Service();
		$parsedResponse = $parser->parse($response->getBody());
		$expected = $parser->parse(fopen(__DIR__ . '/invalidscoperesponse.xml', 'r'));
		$this->assertEquals($expected, $parsedResponse);
	}

	public function testSchemaDiscoveryInvalid() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/invaliddiscover.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->never())
			->method('isValidScope');

		$this->searchBackend->expects($this->never())
			->method('getPropertyDefinitionsForScope');

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testSearchQuery() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/basicquery.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$lengthProp = new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true,
			SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER);
		$orderBy = [
			new \SearchDAV\Query\Order($lengthProp, \SearchDAV\Query\Order::ASC),
		];
		$select = [$lengthProp];
		$from = [
			new Scope('/container1/', 'infinity', '/container1/'),
		];
		$where = new \SearchDAV\Query\Operator(\SearchDAV\Query\Operator::OPERATION_GREATER_THAN, [
			$lengthProp,
			new Literal(10000),
		]);
		$limit = new Limit();
		$query = new Query($select, $from, $where, $orderBy, $limit);

		$this->searchBackend->expects($this->once())
			->method('search')
			->with($query)
			->willReturn([
				new SearchResult(
					new Directory('/foo'),
					'/foo'
				),
			]);

		$this->searchBackend->expects($this->any())
			->method('getPropertyDefinitionsForScope')
			->willReturn([
				$lengthProp,
			]);

		$plugin->searchHandler($request, $response);

		$parser = new Service();
		$parsedResponse = $parser->parse($response->getBody());
		$expected = $parser->parse(fopen(__DIR__ . '/searchresult.xml', 'r'));
		$this->assertEquals($expected, $parsedResponse);
	}

	public function testSearchQueryNoFrom() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/nofrom.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testSearchQueryNoWhere() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$lengthProp = new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true,
			SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER);
		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/nowhere.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->any())
			->method('getPropertyDefinitionsForScope')
			->willReturn([$lengthProp]);

		$this->searchBackend->expects($this->once())
			->method('search')
			->willReturnCallback(function (Query $query) {
				$this->assertNull($query->where);
				return [];
			});

		$plugin->searchHandler($request, $response);

		$this->assertEquals(207, $response->getStatus());
	}

	public function testSearchQueryNoSelect() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/noselect.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testSearchInvalid() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/invalid.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testPropFindHandler() {
		$propFind = new PropFind('bar', ['{DAV:}supported-query-grammar-set']);

		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);

		/** @var INode $node */
		$node = $this->getMockBuilder(INode::class)->getMock();
		$plugin->propFindHandler($propFind, $node);

		$this->assertEquals(null, $propFind->get('{DAV:}supported-query-grammar-set'));

		$propFind = new PropFind('foo', ['{DAV:}supported-query-grammar-set']);
		$plugin->propFindHandler($propFind, $node);

		$this->assertEquals(new SupportedQueryGrammar(), $propFind->get('{DAV:}supported-query-grammar-set'));
	}

	public function testSearchQueryInvalidWhere() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/invalidwhere.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$this->searchBackend->expects($this->once())
			->method('getPropertyDefinitionsForScope')
			->willReturn([
				new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			]);

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testSearchQueryInvalidWhereNoProp() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/invalidwherenoprop.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$this->searchBackend->expects($this->any())
			->method('getPropertyDefinitionsForScope')
			->willReturn([
				new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
				new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			]);

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testSearchQueryInfiniteLoopEmptyLiteral() {
		$this->searchBackend->expects($this->any())
			->method('getArbiterPath')
			->willReturn('foo');

		$plugin = new SearchPlugin($this->searchBackend);
		$server = new Server();
		$plugin->initialize($server);

		$request = new Request('SEARCH', '/index.php/foo', [
			'Content-Type' => 'text/xml',
		]);
		$request->setBaseUrl('/index.php');
		$request->setBody(fopen(__DIR__ . '/infiniteloopemptyliteral.xml', 'r'));
		$response = new Response();

		$this->searchBackend->expects($this->any())
			->method('isValidScope')
			->willReturn(true);

		$this->searchBackend->expects($this->never())
			->method('search');

		$this->searchBackend->expects($this->any())
			->method('getPropertyDefinitionsForScope')
			->willReturn([
				new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
				new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true,
					SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			]);

		$plugin->searchHandler($request, $response);

		$this->assertEquals(400, $response->getStatus());
	}

}
