<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PhpComposables\Exceptions\ModulePipelineException;
use PhpComposables\Exceptions\ModuleRegistryException;
use PhpComposables\Module;
use PhpComposables\ModuleRegistry;
use PhpComposables\Exceptions\ModuleSchemaException;
use PhpComposables\ModulePipeline;
use PhpComposables\ModuleAsyncQueue;
use PhpComposables\Exceptions\ModuleException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        Module::$asyncQueue = false;
        Module::$useExceptions = true;
        Module::$asyncValidationStrict = true;

        ModuleAsyncQueue::clear();
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testModuleLogicAndOutput(): void
    {
        $userModule = Module::create("User")
            ->declareInput("name", "string")
            ->declareOutput("greeting", "string")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{name: string} $inputs */
                    return ["greeting" => "Hello, {$inputs['name']}!"];
                }
            );

        $result = $userModule->run(["name" => "Bob"]);

        $this->assertArrayHasKey("greeting", $result);
        $this->assertEquals("Hello, Bob!", $result["greeting"]);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testHooksAreCalled(): void
    {
        $hookCalled = null;

        $module = Module::create("HookTest")
            ->declareInput("value", "int")
            ->declareOutput("value", "int")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{value: int} $inputs */
                    return ["value" => $inputs["value"] + 1];
                }
            )
            ->onOutput("value", function (int $v) use (&$hookCalled) {
                $hookCalled = $v;
            });

        $module->run(["value" => 10]);

        $this->assertEquals(11, $hookCalled);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testBranchingExecutesCorrectModule(): void
    {
        $targetCalled = null;

        $target = Module::create("Target")
            ->declareInput("flag", "bool")
            ->declareOutput("result", "string")
            ->setLogic(
                function (array $inputs) use (&$targetCalled): array {
                    /** @var array{flag: bool} $inputs */
                    return ["result" => $targetCalled = $inputs["flag"] ? "yes" : "no"];
                }
            );

        $branchModule = Module::create("Brancher")
            ->declareInput("flag", "bool")
            ->declareOutput("flag", "bool")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{flag: bool} $inputs */
                    return ["flag" => $inputs["flag"]];
                }
            )
            ->branchOutput("flag", fn ($v) => $v === true, $target);

        $branchModule->run(["flag" => true]);

        $this->assertEquals("yes", $targetCalled);
    }

    /**
     * @return void
     * @throws ModuleException
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testAsyncQueueExecutesHooks(): void
    {
        Module::$asyncQueue = true;
        $hookExecuted = null;

        $module = Module::create("AsyncHook")
            ->declareInput("num", "int")
            ->declareOutput("num", "int")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{num: int} $inputs */
                    return ["num" => $inputs["num"] * 2];
                }
            )
            ->onOutput("num", function (int $v) use (&$hookExecuted) {
                $hookExecuted = $v;
            });

        $module->run(["num" => 5]);
        ModuleAsyncQueue::run();

        $this->assertEquals(10, $hookExecuted);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testModuleExceptionThrownForInvalidLogicReturn(): void
    {
        $this->expectException(ModuleException::class);

        $module = Module::create("BadLogic")
            ->declareInput("input", "string")
            ->declareOutput("output", "string")
            ->setLogic(fn ($inputs) => "not-an-array");

        $module->run(["input" => "test"]);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testSchemaValidationFails(): void
    {
        $this->expectException(ModuleException::class);

        $module = Module::create("SchemaFail")
            ->declareInput("num", "int")
            ->declareOutput("result", "string")
            ->setLogic(fn ($inputs) => ["result" => 123]);

        $module->run(["num" => 5]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testPipelineExecutesModulesInOrder(): void
    {
        Module::$asyncQueue = false;
        $results = [];

        $mod1 = Module::create("M1")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{val: int} $inputs */
                    return ["val" => $inputs["val"] + 1];
                }
            )
            ->onOutput("val", function (int $v) use (&$results) {
                $results[] = "M1:$v";
            });

        $mod2 = Module::create("M2")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("M1")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{val: int} $inputs */
                    return ["val" => $inputs["val"] * 2];
                }
            )
            ->onOutput("val", function (int $v) use (&$results) {
                $results[] = "M2:$v";
            });

        $pipeline = ModulePipeline::compose([$mod1, $mod2]);
        $final = $pipeline->run(["val" => 3]);

        $this->assertEquals(["M1:4", "M2:8"], $results);
        $this->assertEquals(8, $final["val"]);
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testRegisterAddsModuleToRegistry(): void
    {
        $module = Module::create("RegistrationTest", "1.0.0");

        $returned = $module->register();

        $this->assertSame($module, $returned);
        $this->assertSame($module, ModuleRegistry::get("RegistrationTest"));
    }

    /**
     * @return void
     */
    public function testSchemaExceptionIsWrappedInModuleException(): void
    {
        $module = Module::create("SchemaExceptionTest")
            ->declareInput("number", "int")
            ->declareOutput("result", "string")
            ->setLogic(fn (array $inputs): array => ["result" => 123]);

        try {
            $module->run(["number" => 1]);
            $this->fail("Expected ModuleException was not thrown.");
        } catch (ModuleException $exception) {
            $this->assertStringContainsString(
                "Schema validation failed",
                $exception->getMessage()
            );

            $this->assertInstanceOf(
                ModuleSchemaException::class,
                $exception->getPrevious()
            );
        }
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testRunReturnsEmptyArrayAndWarnsWhenExceptionsDisabled(): void
    {
        Module::$useExceptions = false;

        $warnings = [];

        set_error_handler(
            function (
                int $errno,
                string $errStr
            ) use (&$warnings): bool {
                if ($errno === E_USER_WARNING) {
                    $warnings[] = $errStr;

                    return true;
                }

                return false;
            }
        );

        try {
            $module = Module::create("WarningModeTest")
                ->setLogic(
                    function (array $inputs): array {
                        throw new RuntimeException("Logic failed");
                    }
                );

            $result = $module->run([]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $result);
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString("Logic failed", $warnings[0]);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function testModuleExceptionWarnsAndReturnsEmptyArrayWhenExceptionsDisabled(): void
    {
        Module::$useExceptions = false;

        $warnings = [];

        set_error_handler(
            function (
                int $errno,
                string $errStr
            ) use (&$warnings): bool {
                if ($errno === E_USER_WARNING) {
                    $warnings[] = $errStr;

                    return true;
                }

                return false;
            }
        );

        try {
            $module = Module::create("NoLogicTest");

            $result = $module->run([]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $result);
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString(
            "No logic callable is defined",
            $warnings[0]
        );
    }
}
