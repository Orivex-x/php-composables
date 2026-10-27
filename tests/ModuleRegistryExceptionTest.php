<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleRegistryException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleRegistryExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndModuleName(): void
    {
        $exception = new ModuleRegistryException(
            "Registry failed",
            "ExampleModule"
        );

        $this->assertSame("Registry failed", $exception->getMessage());
        $this->assertSame("ExampleModule", $exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModuleRegistryException("Registry failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModuleRegistryException(
            "Registry failed",
            "ExampleModule",
            42
        );

        $this->assertSame(42, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesPreviousException(): void
    {
        $previous = new RuntimeException("Original failure");

        $exception = new ModuleRegistryException(
            "Registry failed",
            "ExampleModule",
            42,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @return void
     */
    public function testConstructorAllowsNullModuleName(): void
    {
        $exception = new ModuleRegistryException("Registry failed");

        $this->assertNull($exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testToStringIncludesModuleName(): void
    {
        $exception = new ModuleRegistryException("Registry failed", "ExampleModule");

        $result = (string) $exception;

        $this->assertStringStartsWith("[Module: ExampleModule] ", $result);
        $this->assertStringContainsString(
            "PhpComposables\\Exceptions\\ModuleRegistryException: Registry failed",
            $result
        );
    }

    /**
     * @return void
     */
    public function testToStringOmitsPrefixWhenModuleNameIsNull(): void
    {
        $exception = new ModuleRegistryException("Registry failed");

        $result = (string) $exception;

        $this->assertStringNotContainsString("[Module:", $result);
        $this->assertStringContainsString(
            "PhpComposables\\Exceptions\\ModuleRegistryException: Registry failed",
            $result
        );
    }
}
