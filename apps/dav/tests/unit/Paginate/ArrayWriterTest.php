<?php

namespace OCA\DAV\tests\unit\Paginate;

use DateTime;
use InvalidArgumentException;
use LogicException;
use OCA\DAV\Paginate\ArrayWriter;
use Sabre\DAV\Xml\Property\GetLastModified;
use Sabre\DAV\Xml\Property\ResourceType;
use stdClass;
use Test\TestCase;

class ArrayWriterTest extends TestCase {
	private ArrayWriter $writer;

	public static function dataProviderWriterShouldThrowForUnsupportedMethodCalls(): array {
		return [
			'toStream' => ['toStream', 'bogus'],
			'toUri' => ['toUri', 'bogus'],
			'openUri' => ['openUri', 'bogus'],
			'setIndent' => ['setIndent', true],
			'setIndentString' => ['setIndentString', 'bogus'],
			'startComment' => ['startComment'],
			'endComment' => ['endComment'],
			'startAttribute' => ['startAttribute', 'bogus'],
			'endAttribute' => ['endAttribute'],
			'startAttributeNs' => ['startAttributeNs', 'bogus', 'bogus', 'bogus'],
			'writeAttributeNs' => ['writeAttributeNs', 'bogus', 'bogus', 'bogus'],
			'startElementNs' => ['startElementNs', 'bogus', 'bogus', 'bogus'],
			'writeElementNs' => ['writeElementNs', 'bogus', 'bogus', 'bogus'],
			'fullEndElement' => ['fullEndElement'],
			'startPi' => ['startPi', 'bogus'],
			'endPi' => ['endPi'],
			'writePi' => ['writePi', 'bogus', 'bogus'],
			'startCdata' => ['startCdata'],
			'endCdata' => ['endCdata'],
			'writeCData' => ['writeCData', 'bogus'],
			'writeRaw' => ['writeRaw', 'bogus'],
			'writeComment' => ['writeComment', 'bogus'],
			'startDtd' => ['startDtd', 'bogus'],
			'endDtd' => ['endDtd'],
			'writeDtd' => ['writeDtd', 'bogus'],
			'startDtdElement' => ['startDtdElement', 'bogus'],
			'endDtdElement' => ['endDtdElement'],
			'writeDtdElement' => ['writeDtdElement', 'bogus', 'bogus'],
			'startDtdAttlist' => ['startDtdAttlist', 'bogus'],
			'endDtdAttlist' => ['endDtdAttlist'],
			'writeDtdAttlist' => ['writeDtdAttlist', 'bogus', 'bogus'],
			'startDtdEntity' => ['startDtdEntity', 'bogus'],
			'endDtdEntity' => ['endDtdEntity'],
			'writeDtdEntity' => ['writeDtdEntity', 'bogus', 'bogus'],
		];
	}

	/**
	 * Those functions are not implemented, they should throw an exception.
	 *
	 * @dataProvider dataProviderWriterShouldThrowForUnsupportedMethodCalls
	 */
	public function testWriterShouldThrowForUnsupportedMethodCalls($function, ...$params): void {
		$this->expectException(LogicException::class);
		$this->writer->$function(...$params);
	}

	public function testWriteWithUnknownObjectShouldFail(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->writer->write(new stdClass());
	}

	public function testWriteWithMappedObjectShouldSucceed(): void {
		$this->writer->classMap[get_class(new stdClass())] = fn ($w)
		=> $w->write('value');
		$this->writer->write(new stdClass());

		$this->compare(['value'], $this->writer->getDocument());
	}

	private function compare(array $expected, array $document): void {
		$this->assertEquals($expected, $document[0]['value']);
	}

	public function testWithResourceTypeShouldMapToPlainArray(): void {
		$this->writer->write(['{DAV:}resourceType' => new ResourceType(['dir'])]);

		$this->compare(
			[['name' => '{DAV:}resourceType', 'value' => [['name' => 'dir']]]],
			$this->writer->getDocument()
		);
	}

	public function testWithGetLastModifiedShouldMapToPlainArray(): void {
		$dateTime = new DateTime();
		$this->writer->write(
			[
				'{DAV:}getlastmodified' => [
					new GetLastModified(
						$dateTime->getTimestamp()
					)
				]
			]
		);

		$this->compare(
			[
				[
					'name' => '{DAV:}getlastmodified',
					'value' => [$dateTime->format('D, d M Y H:i:s \G\M\T')]
				]
			],
			$this->writer->getDocument()
		);
	}

	public function testWriteWithCallable(): void {
		$this->writer->startElement('element');
		$this->writer->write(fn ($w) => $w->write('bogus'));
		$this->writer->endElement();

		$this->compare([['name' => 'element', 'value' => ['bogus']]],
			$this->writer->getDocument());
	}

	protected function setUp(): void {
		parent::setUp();

		$this->writer = new ArrayWriter();
		$this->writer->openMemory();
		$this->writer->startElement('root');
	}
}
