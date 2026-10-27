<?php

namespace PhpComposables\Tests;

use Exception;
use PhpComposables\ModuleAsyncQueue;
use PhpComposables\ModuleSchema;
use PhpComposables\Module;
use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PHPUnit\Framework\TestCase;

class ModuleAsyncQueueTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        ModuleAsyncQueue::clear();

        Module::$asyncValidationStrict = true;
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testEnqueueAndRunHook(): void
    {
        $called = false;

        ModuleAsyncQueue::enqueue(function ($data) use (&$called) {
            $called = $data;
        }, true);

        $this->assertCount(1, ModuleAsyncQueue::getQueue());

        ModuleAsyncQueue::run();

        $this->assertTrue($called);
        $this->assertEmpty(ModuleAsyncQueue::getQueue());
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testEnqueueAndRunModule(): void
    {
        $schema = new ModuleSchema("TestModule");
        $schema->declareOutput("val", "int");

        $result = 0;

        /** @param array{val: int} $data */
        $callback = function (array $data) use (&$result): void {
            $result = $data["val"];
        };

        $data = ["val" => 5];
        ModuleAsyncQueue::enqueue($callback, $data, $schema, "module");

        $this->assertCount(1, ModuleAsyncQueue::getQueue());

        ModuleAsyncQueue::run();

        $this->assertEquals(5, $result);
        $this->assertEmpty(ModuleAsyncQueue::getQueue());
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testModuleExecutionFailsWithInvalidOutput(): void
    {
        $this->expectException(ModuleAsyncQueueException::class);

        $schema = new ModuleSchema("FailModule");
        $schema->declareOutput("val", "int");

        ModuleAsyncQueue::enqueue(function () {
            return ["val" => "wrong_type"];
        }, ["val" => "wrong_type"], $schema, "module");

        ModuleAsyncQueue::run();
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testHookExecutionFailsButDoesNotThrow(): void
    {
        $warnings = [];

        set_error_handler(
            function (int $errno, string $errStr) use (&$warnings): bool {
                if ($errno === E_USER_WARNING) {
                    $warnings[] = $errStr;

                    return true;
                }

                return false;
            }
        );

        try {
            // Enqueue a hook that throws an exception
            ModuleAsyncQueue::enqueue(function () {
                throw new Exception("hook failure");
            });

            // Run the queue
            ModuleAsyncQueue::run();
        } finally {
            $this->assertNotEmpty($warnings);
            $this->assertStringContainsString("Async hook failed", $warnings[0]);
        }
    }

    /**
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public function testClearEmptiesQueue(): void
    {
        ModuleAsyncQueue::enqueue(function () {
        });

        $this->assertNotEmpty(ModuleAsyncQueue::getQueue());

        ModuleAsyncQueue::clear();

        $this->assertEmpty(ModuleAsyncQueue::getQueue());
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testRunReturnsEarlyWhenQueueIsAlreadyRunning(): void
    {
        $nestedRunReturned = false;

        ModuleAsyncQueue::enqueue(
            function () use (&$nestedRunReturned): void {
                // This nested call should return immediately because the queue is running.
                ModuleAsyncQueue::run();
                $nestedRunReturned = true;
            }
        );

        ModuleAsyncQueue::run();

        $this->assertTrue($nestedRunReturned);
        $this->assertEmpty(ModuleAsyncQueue::getQueue());
    }

    /**
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public function testModuleExecutionFailsWithNonStringOutputKey(): void
    {
        $schema = new ModuleSchema("InvalidKeyModule");
        $schema->declareOutput("val", "string");

        ModuleAsyncQueue::enqueue(
            static function (array $data): void {
                // The queue validates the data before executing this callback.
            },
            [1 => "invalid"],
            $schema,
            "module"
        );

        try {
            ModuleAsyncQueue::run();
            $this->fail("Expected ModuleAsyncQueueException was not thrown.");
        } catch (ModuleAsyncQueueException $exception) {
            $this->assertSame("Async queue execution failed.", $exception->getMessage());
            $this->assertInstanceOf(ModuleAsyncQueueException::class, $exception->getPrevious());

            $this->assertSame(
                "Async module output contains a non-string key.",
                $exception->getPrevious()->getMessage()
            );
        }
    }
}
