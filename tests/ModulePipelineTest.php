<?php

namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PhpComposables\Exceptions\ModuleException;
use PhpComposables\Module;
use PhpComposables\ModulePipeline;
use PhpComposables\ModuleAsyncQueue;
use PhpComposables\Exceptions\ModulePipelineException;
use PHPUnit\Framework\TestCase;

class ModulePipelineTest extends TestCase
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
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testPipelineRuns(): void
    {
        $userModule = Module::create("User")
            ->declareInput("name", "string")
            ->declareOutput("greeting", "string")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{name: string} $inputs */
                    return ["greeting" => "Hello, {$inputs['name']}!"];
                }
            )
            ->register();

        $pipeline = ModulePipeline::compose([$userModule])->setAsync(false);
        $result = $pipeline->run(["name" => "Alice"]);

        $this->assertEquals("Hello, Alice!", $result["greeting"]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModulePipelineException
     * @throws ModuleException
     */
    public function testPipelineExecutesModulesInDependencyOrder(): void
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
            })
            ->register();

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
            })
            ->register();

        $pipeline = ModulePipeline::compose([$mod1, $mod2])->setAsync(false);
        $final = $pipeline->run(["val" => 3]);

        $this->assertEquals(["M1:4", "M2:8"], $results);
        $this->assertEquals(8, $final["val"]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testPipelineWithAsyncHooks(): void
    {
        Module::$asyncQueue = true;
        $hookResult = [];

        $mod = Module::create("AsyncMod")
            ->declareInput("num", "int")
            ->declareOutput("num", "int")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{num: int} $inputs */
                    return ["num" => $inputs["num"] + 1];
                }
            )
            ->onOutput("num", function ($v) use (&$hookResult) {
                $hookResult[] = $v;
            })
            ->register();

        $pipeline = ModulePipeline::compose([$mod])->setAsync(true);
        $final = $pipeline->run(["num" => 5]);

        ModuleAsyncQueue::run();

        $this->assertEquals([6], $hookResult);
        $this->assertEquals(6, $final["num"]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testPipelineThrowsOnMissingInput(): void
    {
        $this->expectException(ModulePipelineException::class);

        $mod = Module::create("RequiredInput")
            ->declareInput("required", "string")
            ->declareOutput("out", "string")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{required: string} $inputs */
                    return ["out" => strtoupper($inputs["required"])];
                }
            )
            ->register();

        $pipeline = ModulePipeline::compose([$mod])->setAsync(false);
        $pipeline->run([]); // missing required input
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testPipelineExecutesMultipleModulesWithMixedDependencies(): void
    {
        Module::$asyncQueue = false;
        $results = [];

        $a = Module::create("A")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{val: int} $inputs */
                    return ["val" => $inputs["val"] + 1];
                }
            )
            ->onOutput("val", function (int $v) use (&$results) {
                $results[] = "A:$v";
            })
            ->register();

        $b = Module::create("B")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("A")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{val: int} $inputs */
                    return ["val" => $inputs["val"] * 3];
                }
            )
            ->onOutput("val", function (int $v) use (&$results) {
                $results[] = "B:$v";
            })
            ->register();

        $c = Module::create("C")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("B")
            ->setLogic(
                function (array $inputs): array {
                    /** @var array{val: int} $inputs */
                    return ["val" => $inputs["val"] - 2];
                }
            )
            ->onOutput("val", function (int $v) use (&$results) {
                $results[] = "C:$v";
            })
            ->register();

        $pipeline = ModulePipeline::compose([$a, $b, $c])->setAsync(false);
        $final = $pipeline->run(["val" => 2]);

        $this->assertEquals(["A:3", "B:9", "C:7"], $results);
        $this->assertEquals(7, $final["val"]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testSetModuleVersionReturnsPipelineAndSetsVersion(): void
    {
        $module = Module::create("VersionedModule", "1.0.0")
            ->declareOutput("result", "string")
            ->setLogic(
                static fn (array $inputs): array => ["result" => "ok"]
            );

        $pipeline = ModulePipeline::compose([$module]);

        $returnedPipeline = $pipeline->setModuleVersion("VersionedModule", "2.0.0");

        $this->assertSame($pipeline, $returnedPipeline);
        $this->assertSame(["result" => "ok"], $pipeline->run([]));
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     */
    public function testMissingDependencyIsWrappedInPipelineException(): void
    {
        $module = Module::create("ModuleWithMissingDependency")
            ->declareDependency("DoesNotExist")
            ->declareOutput("result", "string")
            ->setLogic(
                static fn (array $inputs): array => ["result" => "ok"]
            );

        $pipeline = ModulePipeline::compose([$module]);

        try {
            $pipeline->run([]);

            $this->fail("Expected ModulePipelineException was not thrown.");
        } catch (ModulePipelineException $exception) {
            $this->assertSame("Failed to resolve pipeline execution order.", $exception->getMessage());
            $this->assertInstanceOf(ModulePipelineException::class, $exception->getPrevious());

            $this->assertSame(
                "Module 'DoesNotExist' not found in pipeline modules.",
                $exception->getPrevious()->getMessage()
            );
        }
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModuleException
     * @throws ModulePipelineException
     */
    public function testSkipsModuleAlreadyMarkedAsExecuted(): void
    {
        $secondModule = Module::create("SecondModule")
            ->declareInput("value", "int")
            ->declareOutput("result", "int")
            ->setLogic(
                static fn (array $inputs): array => [
                    "result" => $inputs["value"] * 2,
                ]
            );

        $firstModule = Module::create("FirstModule")
            ->declareInput("value", "int")
            ->declareOutput("value", "int")
            ->setLogic(
                static function (array $inputs): array {
                    ModulePipeline::markExecuted("SecondModule");

                    return ["value" => $inputs["value"] + 1];
                }
            );

        $pipeline = ModulePipeline::compose([$firstModule, $secondModule]);

        $result = $pipeline->run(["value" => 5]);

        $this->assertSame(6, $result["value"]);
        $this->assertArrayNotHasKey("result", $result);
    }
}
