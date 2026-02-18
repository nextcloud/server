<?php
// apps/admin_audit/tests/Actions/ActionTest.php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\IAuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ActionTest extends TestCase {
    private IAuditLogger&MockObject $logger;
    private Action $action;

    protected function setUp(): void {
        parent::setUp();
        $this->logger = $this->createMock(IAuditLogger:: class);
        $this->action = new Action($this->logger);
    }

    public function testLogWithBooleanTrue(): void {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Setting enabled: true', ['app' => 'admin_audit']);

        $this->action->log(
            'Setting enabled: %s',
            ['enabled' => true],
            ['enabled']
        );
    }

    public function testLogWithBooleanFalse(): void {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Setting enabled: false', ['app' => 'admin_audit']);

        $this->action->log(
            'Setting enabled: %s',
            ['enabled' => false],
            ['enabled']
        );
    }

    public function testLogWithNull(): void {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Value is: null', ['app' => 'admin_audit']);

        $this->action->log(
            'Value is: %s',
            ['value' => null],
            ['value']
        );
    }

    public function testLogWithMissingKey(): void {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                'Required audit parameters missing: {missing_keys}',
                $this->callback(function ($context) {
                    return $context['app'] === 'admin_audit'
                        && $context['missing_keys'] === ['missing_key']
                        && isset($context['provided_keys']);
                })
            );

        $this->action->log(
            'Value: %s',
            ['other_key' => 'value'],
            ['missing_key']
        );
    }

    public function testLogWithDateTimeValue(): void {
        $date = new \DateTime('2026-01-02 15:30:45');
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Date: 2026-01-02 15:30:45', ['app' => 'admin_audit']);

        $this->action->log(
            'Date: %s',
            ['date' => $date],
            ['date']
        );
    }

    public function testLogWithFormatMismatch(): void {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                'Audit log format string mismatch: {error}',
                $this->callback(function ($context) {
                    return $context['app'] === 'admin_audit'
                        && isset($context['error'])
                        && $context['format'] === 'Too many:  %s %s %s';
                })
            );

        $this->action->log(
            'Too many: %s %s %s',
            ['one' => '1', 'two' => '2'],
            ['one', 'two']  // Only 2 values for 3 placeholders
        );
    }
}
