<?php

namespace OpenStack\integration\Images\v2;

use OpenStack\Images\v2\Models\Image;
use OpenStack\Images\v2\Models\Member;
use OpenStack\Integration\TestCase;

class CoreTest extends TestCase
{
    public function runTests()
    {
        $this->startTimer();

        $this->images();
        $this->members();

        $this->outputTimeTaken();
    }

    public function images()
    {
        $replacements = [
            '{name}'            => 'Ubuntu 12.10',
            '{tag1}'            => 'ubuntu',
            '{tag2}'            => 'quantal',
            '{containerFormat}' => 'bare',
            '{diskFormat}'      => 'qcow2',
            '{visibility}'      => 'private',
        ];

        $this->logStep('Creating image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/create.php');
        self::assertInstanceOf(Image::class, $image);

        $replacements = ['{imageId}' => $image->id];

        $this->logStep('Listing images');
        /** @var \Generator $images */
        require_once $this->sampleFile($replacements, 'images/list.php');

        $this->logStep('Getting image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/get.php');
        self::assertInstanceOf(Image::class, $image);

        $replacements += [
            '{name}'       => 'newName',
            '{visibility}' => 'private',
        ];

        $this->logStep('Updating image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/update.php');

        $this->logStep('Deleting image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/delete.php');
    }

    public function members()
    {
        $replacements = [
            '{name}'            => 'Ubuntu 12.10',
            '{tag1}'            => 'ubuntu',
            '{tag2}'            => 'quantal',
            '{containerFormat}' => 'bare',
            '{diskFormat}'      => 'qcow2',
            '{visibility}'      => 'shared',
            'true'              => 'false',
        ];

        $this->logStep('Creating image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/create.php');



        $this->logStep(sprintf('Image created with id=%s', $image->id));

        $this->logStep('Adding member');
        $replacements += ['{imageId}' => $image->id];
        /** @var Member $member */
        require_once $this->sampleFile(['{imageId}' => $image->id, ], 'members/add.php');
        self::assertInstanceOf(Member::class, $member);

        $replacements += ['status' => Member::STATUS_REJECTED];
        $this->logStep('Updating member status');
        /** @var Member $member */
        require_once $this->sampleFile($replacements, 'members/update_status.php');
        self::assertInstanceOf(Member::class, $member);

        $this->logStep('Deleting member');
        /** @var Member $member */
        require_once $this->sampleFile($replacements, 'members/delete.php');

        $this->logStep('Deleting image');
        /** @var Image $image */
        require_once $this->sampleFile($replacements, 'images/delete.php');
    }
}
