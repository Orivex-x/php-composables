<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndModuleName(): void
    {
        $exception = new ModuleException("Module failed", "ExampleModule");

        $this->assertSame("Module failed", $exception->getMessage());
        $this->assertSame("ExampleModule", $exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModuleException("Module failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModuleException("Module failed", "ExampleModule", 42);

        $this->assertSame(42, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesPreviousException(): void
    {
        $previous = new RuntimeException("Original failure");
        $exception = new ModuleException("Module failed", "ExampleModule", 42, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @return void
     */
    public function testConstructorAllowsNullModuleName(): void
    {
        $exception = new ModuleException("Module failed");

        $this->assertNull($exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testToStringIncludesModuleName(): void
    {
        $exception = new ModuleException("Module failed", "ExampleModule");

        $result = (string) $exception;

        $this->assertStringStartsWith("[Module: ExampleModule] ", $result);
        $this->assertStringContainsString("PhpComposables\\Exceptions\\ModuleException: Module failed", $result);
    }

    /**
     * @return void
     */
    public function testToStringOmitsPrefixWhenModuleNameIsNull(): void
    {
        $exception = new ModuleException("Module failed");

        $result = (string) $exception;

        $this->assertStringNotContainsString("[Module:", $result);
        $this->assertStringContainsString("PhpComposables\\Exceptions\\ModuleException: Module failed", $result);
    }
}
