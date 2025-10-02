<?php

declare(strict_types=1);

namespace OpenStack\ObjectStore\v1;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    public function __construct()
    {
        $this->params = new Params();
    }

    public function getAccount(): array
    {
        return [
            'method' => 'GET',
            'path'   => '',
            'params' => [
                'format'    => $this->params->format(),
                'limit'     => $this->params->limit(),
                'marker'    => $this->params->marker(),
                'endMarker' => $this->params->endMarker(),
                'prefix'    => $this->params->prefix(),
                'delimiter' => $this->params->delimiter(),
                'newest'    => $this->params->newest(),
            ],
        ];
    }

    public function postAccount(): array
    {
        return [
            'method' => 'POST',
            'path'   => '',
            'params' => [
                'tempUrlKey'     => $this->params->tempUrlKey('account'),
                'tempUrlKey2'    => $this->params->tempUrlKey2('account'),
                'metadata'       => $this->params->metadata('account'),
                'removeMetadata' => $this->params->metadata('account', true),
            ],
        ];
    }

    public function headAccount(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => '',
            'params' => [],
        ];
    }

    public function getContainer(): array
    {
        return [
            'method' => 'GET',
            'path'   => '{name}',
            'params' => [
                'name'      => $this->params->containerName(),
                'format'    => $this->params->format(),
                'limit'     => $this->params->limit(),
                'marker'    => $this->params->marker(),
                'endMarker' => $this->params->endMarker(),
                'prefix'    => $this->params->prefix(),
                'path'      => $this->params->path(),
                'delimiter' => $this->params->delimiter(),
                'newest'    => $this->params->newest(),
            ],
        ];
    }

    public function putContainer(): array
    {
        return [
            'method' => 'PUT',
            'path'   => '{name}',
            'params' => [
                'name'              => $this->params->containerName(),
                'readAccess'        => $this->params->readAccess('container'),
                'writeAccess'       => $this->params->writeAccess('container'),
                'metadata'          => $this->params->metadata('container'),
                'syncTo'            => $this->params->syncTo(),
                'syncKey'           => $this->params->syncKey(),
                'versionsLocation'  => $this->params->versionsLocation(),
                'bytesQuota'        => $this->params->bytesQuota(),
                'countQuota'        => $this->params->countQuota(),
                'webDirectoryType'  => $this->params->webDirType(),
                'detectContentType' => $this->params->detectContentType(),
            ],
        ];
    }

    public function deleteContainer(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => '{name}',
            'params' => [
                'name' => $this->params->containerName(),
            ],
        ];
    }

    public function postContainer(): array
    {
        return [
            'method' => 'POST',
            'path'   => '{name}',
            'params' => [
                'name'                   => $this->params->containerName(),
                'readAccess'             => $this->params->readAccess('container'),
                'writeAccess'            => $this->params->writeAccess('container'),
                'metadata'               => $this->params->metadata('container'),
                'removeMetadata'         => $this->params->metadata('container', true),
                'syncTo'                 => $this->params->syncTo(),
                'syncKey'                => $this->params->syncKey(),
                'versionsLocation'       => $this->params->versionsLocation(),
                'removeVersionsLocation' => $this->params->removeVersionsLocation(),
                'bytesQuota'             => $this->params->bytesQuota(),
                'countQuota'             => $this->params->countQuota(),
                'webDirectoryType'       => $this->params->webDirType(),
                'detectContentType'      => $this->params->detectContentType(),
            ],
        ];
    }

    public function headContainer(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => '{name}',
            'params' => ['name' => $this->params->containerName()],
        ];
    }

    public function getObject(): array
    {
        return [
            'method' => 'GET',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName'     => $this->params->containerName(),
                'name'              => $this->params->objectName(),
                'range'             => $this->params->range(),
                'ifMatch'           => $this->params->ifMatch(),
                'ifNoneMatch'       => $this->params->ifNoneMatch(),
                'ifModifiedSince'   => $this->params->ifModifiedSince(),
                'ifUnmodifiedSince' => $this->params->ifUnmodifiedSince(),
            ],
        ];
    }

    public function putObject(): array
    {
        return [
            'method' => 'PUT',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName'      => $this->params->containerName(),
                'name'               => $this->params->objectName(),
                'content'            => $this->params->content(),
                'stream'             => $this->params->stream(),
                'contentType'        => $this->params->contentType(),
                'detectContentType'  => $this->params->detectContentType(),
                'copyFrom'           => $this->params->copyFrom(),
                'ETag'               => $this->params->etag(),
                'contentDisposition' => $this->params->contentDisposition(),
                'contentEncoding'    => $this->params->contentEncoding(),
                'deleteAt'           => $this->params->deleteAt(),
                'deleteAfter'        => $this->params->deleteAfter(),
                'metadata'           => $this->params->metadata('object'),
                'ifNoneMatch'        => $this->params->ifNoneMatch(),
                'objectManifest'     => $this->params->objectManifest(),
            ],
        ];
    }

    public function copyObject(): array
    {
        return [
            'method' => 'COPY',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName'      => $this->params->containerName(),
                'name'               => $this->params->objectName(),
                'destination'        => $this->params->destination(),
                'contentType'        => $this->params->contentType(),
                'contentDisposition' => $this->params->contentDisposition(),
                'contentEncoding'    => $this->params->contentEncoding(),
                'metadata'           => $this->params->metadata('object'),
                'freshMetadata'      => $this->params->freshMetadata(),
            ],
        ];
    }

    public function deleteObject(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName' => $this->params->containerName(),
                'name'          => $this->params->objectName(),
            ],
        ];
    }

    public function headObject(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName' => $this->params->containerName(),
                'name'          => $this->params->objectName(),
            ],
        ];
    }

    public function postObject(): array
    {
        return [
            'method' => 'POST',
            'path'   => '{containerName}/{+name}',
            'params' => [
                'containerName'      => $this->params->containerName(),
                'name'               => $this->params->objectName(),
                'metadata'           => $this->params->metadata('object'),
                'removeMetadata'     => $this->params->metadata('object', true),
                'deleteAt'           => $this->params->deleteAt(),
                'deleteAfter'        => $this->params->deleteAfter(),
                'contentDisposition' => $this->params->contentDisposition(),
                'contentEncoding'    => $this->params->contentEncoding(),
                'contentType'        => $this->params->contentType(),
                'detectContentType'  => $this->params->detectContentType(),
            ],
        ];
    }
}
