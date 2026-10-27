<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleSchemaException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleSchemaExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndModuleName(): void
    {
        $exception = new ModuleSchemaException("Schema validation failed", "ExampleModule");

        $this->assertSame("Schema validation failed", $exception->getMessage());
        $this->assertSame("ExampleModule", $exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModuleSchemaException("Schema validation failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModuleSchemaException(
            "Schema validation failed",
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

        $exception = new ModuleSchemaException(
            "Schema validation failed",
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
        $exception = new ModuleSchemaException("Schema validation failed");

        $this->assertNull($exception->getModuleName());
    }

    /**
     * @return void
     */
    public function testToStringIncludesModuleName(): void
    {
        $exception = new ModuleSchemaException("Schema validation failed", "ExampleModule");

        $result = (string) $exception;

        $this->assertStringStartsWith("[Module: ExampleModule] ", $result);
        $this->assertStringContainsString(
            "PhpComposables\\Exceptions\\ModuleSchemaException: Schema validation failed",
            $result
        );
    }

    /**
     * @return void
     */
    public function testToStringOmitsPrefixWhenModuleNameIsNull(): void
    {
        $exception = new ModuleSchemaException("Schema validation failed");

        $result = (string) $exception;

        $this->assertStringNotContainsString("[Module:", $result);
        $this->assertStringContainsString(
            "PhpComposables\\Exceptions\\ModuleSchemaException: Schema validation failed",
            $result
        );
    }
}
