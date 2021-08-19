<?php

declare(strict_types=1);

namespace OpenStack\Images\v2;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    private $basePath;

    public function __construct()
    {
        $this->params   = new Params();
        $this->basePath = 'v2/';
    }

    public function postImages(): array
    {
        return [
            'method' => 'POST',
            'path'   => $this->basePath.'images',
            'params' => [
                'name'            => $this->params->imageName(),
                'visibility'      => $this->params->visibility(),
                'tags'            => $this->params->tags(),
                'containerFormat' => $this->params->containerFormat(),
                'diskFormat'      => $this->params->diskFormat(),
                'minDisk'         => $this->params->minDisk(),
                'minRam'          => $this->params->minRam(),
                'protected'       => $this->params->protectedParam(),
            ],
        ];
    }

    public function getImages(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'images',
            'params' => [
                'limit'        => $this->params->limit(),
                'marker'       => $this->params->marker(),
                'sortKey'      => $this->params->sortKey(),
                'sortDir'      => $this->params->sortDir(),
                'name'         => $this->params->queryName(),
                'visibility'   => $this->params->queryVisibility(),
                'memberStatus' => $this->params->queryMemberStatus(),
                'owner'        => $this->params->queryOwner(),
                'status'       => $this->params->queryStatus(),
                'sizeMin'      => $this->params->querySizeMin(),
                'sizeMax'      => $this->params->querySizeMax(),
                'tag'          => $this->params->queryTag(),
            ],
        ];
    }

    public function getImage(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'images/{id}',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function patchImage(): array
    {
        return [
            'method' => 'PATCH',
            'path'   => $this->basePath.'images/{id}',
            'params' => [
                'id'          => $this->params->idPath(),
                'patchDoc'    => $this->params->patchDoc(),
                'contentType' => $this->params->contentType(),
            ],
        ];
    }

    public function deleteImage(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->basePath.'images/{id}',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function reactivateImage(): array
    {
        return [
            'method' => 'POST',
            'path'   => $this->basePath.'images/{id}/actions/reactivate',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function deactivateImage(): array
    {
        return [
            'method' => 'POST',
            'path'   => $this->basePath.'images/{id}/actions/deactivate',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function postImageData(): array
    {
        return [
            'method' => 'PUT',
            'path'   => $this->basePath.'images/{id}/file',
            'params' => [
                'id'          => $this->params->idPath(),
                'data'        => $this->params->data(),
                'contentType' => $this->params->contentType(),
            ],
        ];
    }

    public function getImageData(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'images/{id}/file',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function getImageSchema(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'schemas/image',
            'params' => [],
        ];
    }

    public function postImageMembers(): array
    {
        return [
            'method' => 'POST',
            'path'   => $this->basePath.'images/{imageId}/members',
            'params' => [
                'imageId' => $this->params->idPath(),
                'id'      => $this->params->memberId(),
            ],
        ];
    }

    public function getImageMembers(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'images/{imageId}/members',
            'params' => ['imageId' => $this->params->idPath()],
        ];
    }

    public function getImageMember(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->basePath.'images/{imageId}/members/{id}',
            'params' => [
                'imageId' => $this->params->idPath(),
                'id'      => $this->params->idPath(),
            ],
        ];
    }

    public function deleteImageMember(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->basePath.'images/{imageId}/members/{id}',
            'params' => [
                'imageId' => $this->params->idPath(),
                'id'      => $this->params->idPath(),
            ],
        ];
    }

    public function putImageMember(): array
    {
        return [
            'method' => 'PUT',
            'path'   => $this->basePath.'images/{imageId}/members/{id}',
            'params' => [
                'imageId' => $this->params->idPath(),
                'id'      => $this->params->idPath(),
                'status'  => $this->params->status(),
            ],
        ];
    }
}
