<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\MockObject;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This trait is not covered by the backward compatibility promise for PHPUnit
 */
trait StubApi
{
    /**
     * @psalm-var list<ConfigurableMethod>
     */
    private static array $__phpunit_configurableMethods;
    private bool $__phpunit_returnValueGeneration          = true;
    private ?InvocationHandler $__phpunit_invocationMocker = null;

    /** @noinspection MagicMethodsValidityInspection */
    public static function __phpunit_initConfigurableMethods(ConfigurableMethod ...$configurableMethods): void
    {
        static::$__phpunit_configurableMethods = $configurableMethods;
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __phpunit_setReturnValueGeneration(bool $returnValueGeneration): void
    {
        $this->__phpunit_returnValueGeneration = $returnValueGeneration;
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __phpunit_getInvocationHandler(): InvocationHandler
    {
        if ($this->__phpunit_invocationMocker === null) {
            $this->__phpunit_invocationMocker = new InvocationHandler(
                static::$__phpunit_configurableMethods,
                $this->__phpunit_returnValueGeneration,
            );
        }

        return $this->__phpunit_invocationMocker;
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __phpunit_unsetInvocationMocker(): void
    {
        $this->__phpunit_invocationMocker = null;
    }
}
