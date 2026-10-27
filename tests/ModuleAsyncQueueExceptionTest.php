<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleAsyncQueueExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndContext(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed", "example-module");

        $this->assertSame("Queue failed", $exception->getMessage());
        $this->assertSame("example-module", $exception->getContext());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed", "example-module", 42);

        $this->assertSame(42, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesPreviousException(): void
    {
        $previous = new RuntimeException("Original failure");

        $exception = new ModuleAsyncQueueException(
            "Queue failed",
            "example-module",
            42,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @return void
     */
    public function testConstructorAllowsNullContext(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed");

        $this->assertNull($exception->getContext());
    }

    /**
     * @return void
     */
    public function testToStringIncludesQueueContext(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed", "example-module");

        $result = (string) $exception;

        $this->assertStringStartsWith("[Queue Context: example-module] ", $result);
        $this->assertStringContainsString("ModuleAsyncQueueException: Queue failed", $result);
    }

    /**
     * @return void
     */
    public function testToStringOmitsPrefixWhenContextIsNull(): void
    {
        $exception = new ModuleAsyncQueueException("Queue failed");

        $result = (string) $exception;

        $this->assertStringNotContainsString("[Queue Context:", $result);
        $this->assertStringContainsString("ModuleAsyncQueueException: Queue failed", $result);
    }
}
