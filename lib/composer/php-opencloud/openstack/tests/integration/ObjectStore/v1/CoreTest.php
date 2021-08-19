<?php

namespace OpenStack\integration\ObjectStore\v1;

use OpenStack\Integration\TestCase;
use OpenStack\Integration\Utils;
use Psr\Http\Message\StreamInterface;

class CoreTest extends TestCase
{
    private $service;

    /**
     * @return \OpenStack\ObjectStore\v1\Service
     */
    protected function getService()
    {
        if (null === $this->service) {
            $this->service = Utils::getOpenStack()->objectStoreV1();
        }

        return $this->service;
    }

    public function runTests()
    {
        $this->startTimer();

        $this->accountMetadata();
        $this->containers();
        $this->objects();

        $this->outputTimeTaken();
    }

    public function accountMetadata()
    {
        $this->logStep('Ensure all metadata is wiped');
        $this->getService()->getAccount()->resetMetadata([]);

        $replacements = [
            '{key_1}' => 'Foo',
            '{key_2}' => 'Bar',
            '{val_1}' => $this->randomStr(),
            '{val_2}' => $this->randomStr(),
        ];

        $this->logStep('Setting account metadata');
        require_once $this->sampleFile($replacements, 'account/merge_metadata.php');

        $this->logStep('Getting account metadata');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'account/get_metadata.php');
        self::assertArraySubset([
            'Foo' => $replacements['{val_1}'],
            'Bar' => $replacements['{val_2}'],
        ], $metadata);

        $this->logStep('Resetting account metadata');
        $replacements = [
            '{key_1}' => 'Foo1',
            '{key_2}' => 'Bar1',
            '{val_1}' => $this->randomStr(),
            '{val_2}' => $this->randomStr(),
        ];
        require_once $this->sampleFile($replacements, 'account/reset_metadata.php');

        $this->logStep('Checking account metadata was reset properly');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'account/get_metadata.php');
        self::assertEquals([
            'Foo1' => $replacements['{val_1}'],
            'Bar1' => $replacements['{val_2}'],
        ], $metadata);
    }

    public function containers()
    {
        $containerName = $this->randomStr();
        $replacements = ['{containerName}' => $containerName];

        $this->logStep('Create container');
        require_once $this->sampleFile($replacements, 'containers/create.php');

        $this->logStep('Get container');
        require_once $this->sampleFile($replacements, 'containers/get.php');

        $this->logStep('Listing containers');
        require_once $this->sampleFile($replacements, 'containers/list.php');

        $this->logStep('Merging metadata');
        $replacements += [
            '{key_1}' => 'Foo',
            '{key_2}' => 'Bar',
            '{val_1}' => $this->randomStr(),
            '{val_2}' => $this->randomStr(),
        ];
        require_once $this->sampleFile($replacements, 'containers/merge_metadata.php');

        $this->logStep('Getting metadata');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'containers/get_metadata.php');
        self::assertEquals([
            'Foo' => $replacements['{val_1}'],
            'Bar' => $replacements['{val_2}'],
        ], $metadata);

        $this->logStep('Resetting metadata');
        $replacements['{key_1}'] = 'Foo1';
        $replacements['{key_2}'] = 'Bar1';

        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'containers/reset_metadata.php');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'containers/get_metadata.php');
        self::assertEquals([
            'Foo1' => $replacements['{val_1}'],
            'Bar1' => $replacements['{val_2}'],
        ], $metadata);

        $this->logStep('Delete container');
        $replacements = ['{containerName}' => $containerName];
        require_once $this->sampleFile($replacements, 'containers/delete.php');
    }

    public function objects()
    {
        $containerName = $this->randomStr();

        $this->logStep('Create container named {name}', ['{name}' => $containerName]);
        $container = $this->getService()->createContainer(['name' => $containerName]);

        $objectName = $this->randomStr();
        $replacements = ['{containerName}' => $container->name, '{objectName}' => $objectName];

        $this->logStep('Create object');
        $replacements['{objectContent}'] = str_repeat('A', 1000);
        require_once $this->sampleFile($replacements, 'objects/create.php');

        $this->logStep('Copy object');
        $newName = $this->randomStr();
        $replacements += ['{newContainerName}' => $containerName, '{newObjectName}' => $newName];
        require_once $this->sampleFile($replacements, 'objects/copy.php');

        $this->logStep('Check that new object exists');
        /** @var bool $exists */
        require_once $this->sampleFile(['{containerName}' => $containerName, '{objectName}' => $newName], 'objects/check_exists.php');
        self::assertTrue($exists);

        $this->logStep('Downloading object');
        /** @var StreamInterface $stream */
        require_once $this->sampleFile($replacements, 'objects/download.php');
        self::assertInstanceOf(StreamInterface::class, $stream);
        self::assertEquals(1000, $stream->getSize());

        $this->logStep('Downloading object using streaming');
        /** @var StreamInterface $stream */
        require_once $this->sampleFile($replacements, 'objects/download_stream.php');
        self::assertInstanceOf(StreamInterface::class, $stream);

        $body = '';
        while (!$stream->eof()) {
            $body .= $stream->read(64);
        }
        self::assertEquals(1000, strlen($body));

        $this->logStep('Get object');
        require_once $this->sampleFile($replacements, 'objects/get.php');

        $this->logStep('Listing objects');
        require_once $this->sampleFile($replacements, 'objects/list.php');

        $this->logStep('Merging metadata');
        $replacements += [
            '{key_1}' => 'Foo',
            '{key_2}' => 'Bar',
            '{val_1}' => $this->randomStr(),
            '{val_2}' => $this->randomStr(),
        ];
        require_once $this->sampleFile($replacements, 'objects/merge_metadata.php');

        $this->logStep('Getting metadata');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'objects/get_metadata.php');
        self::assertEquals([
            'Foo' => $replacements['{val_1}'],
            'Bar' => $replacements['{val_2}'],
        ], $metadata);

        $this->logStep('Resetting metadata');
        $replacements['{key_1}'] = 'Foo1';
        $replacements['{key_2}'] = 'Bar1';

        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'objects/reset_metadata.php');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'objects/get_metadata.php');
        self::assertEquals([
            'Foo1' => $replacements['{val_1}'],
            'Bar1' => $replacements['{val_2}'],
        ], $metadata);

        $this->logStep('Delete object');
        require_once $this->sampleFile($replacements, 'objects/delete.php');
        $container->getObject($replacements['{newObjectName}'])->delete();

        $this->logStep('Delete container');
        $container->delete();
    }
}
