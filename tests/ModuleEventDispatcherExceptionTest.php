<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleEventDispatcherExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndEventName(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed", "user.created");

        $this->assertSame("Listener failed", $exception->getMessage());
        $this->assertSame("user.created", $exception->getEventName());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed", "user.created", 42);

        $this->assertSame(42, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesPreviousException(): void
    {
        $previous = new RuntimeException("Original failure");

        $exception = new ModuleEventDispatcherException(
            "Listener failed",
            "user.created",
            42,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @return void
     */
    public function testConstructorAllowsNullEventName(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed");

        $this->assertNull($exception->getEventName());
    }

    /**
     * @return void
     */
    public function testToStringIncludesEventName(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed", "user.created");

        $result = (string) $exception;

        $this->assertStringStartsWith("[Event: user.created] ", $result);
        $this->assertStringContainsString("ModuleEventDispatcherException: Listener failed", $result);
    }

    /**
     * @return void
     */
    public function testToStringOmitsPrefixWhenEventNameIsNull(): void
    {
        $exception = new ModuleEventDispatcherException("Listener failed");

        $result = (string) $exception;

        $this->assertStringNotContainsString("[Event:", $result);
        $this->assertStringContainsString("ModuleEventDispatcherException: Listener failed", $result);
    }
}
