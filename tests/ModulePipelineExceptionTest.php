<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModulePipelineException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModulePipelineExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructorPreservesMessageAndContext(): void
    {
        $exception = new ModulePipelineException(
            "Pipeline failed",
            "example-pipeline"
        );

        $this->assertSame("Pipeline failed", $exception->getMessage());
        $this->assertSame("example-pipeline", $exception->getContext());
    }

    /**
     * @return void
     */
    public function testConstructorDefaultsCodeToZero(): void
    {
        $exception = new ModulePipelineException("Pipeline failed");

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * @return void
     */
    public function testConstructorPreservesCustomCode(): void
    {
        $exception = new ModulePipelineException(
            "Pipeline failed",
            "example-pipeline",
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

        $exception = new ModulePipelineException(
            "Pipeline failed",
            "example-pipeline",
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
        $exception = new ModulePipelineException("Pipeline failed");

        $this->assertNull($exception->getContext());
    }
}
