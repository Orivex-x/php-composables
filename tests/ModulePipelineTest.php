<?php
namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
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
     * @throws ModulePipelineException
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public function testPipelineRuns(): void
    {
        $userModule = Module::create("User")
            ->declareInput("name", "string")
            ->declareOutput("greeting", "string")
            ->setLogic(fn($inputs) => ["greeting" => "Hello, {$inputs['name']}!"])
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
     */
    public function testPipelineExecutesModulesInDependencyOrder(): void
    {
        Module::$asyncQueue = false;

        $results = [];

        $mod1 = Module::create("M1")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] + 1])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "M1:$v";
            })
            ->register();

        $mod2 = Module::create("M2")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("M1")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] * 2])
            ->onOutput("val", function($v) use (&$results) {
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
     * @throws ModulePipelineException
     */
    public function testPipelineWithAsyncHooks(): void
    {
        Module::$asyncQueue = true;
        $hookResult = [];

        $mod = Module::create("AsyncMod")
            ->declareInput("num", "int")
            ->declareOutput("num", "int")
            ->setLogic(fn($inputs) => ["num" => $inputs["num"] + 1])
            ->onOutput("num", function($v) use (&$hookResult) {
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
     * @throws ModulePipelineException
     */
    public function testPipelineThrowsOnMissingInput(): void
    {
        $this->expectException(ModulePipelineException::class);

        $mod = Module::create("RequiredInput")
            ->declareInput("required", "string")
            ->declareOutput("out", "string")
            ->setLogic(fn($inputs) => ["out" => strtoupper($inputs["required"])])
            ->register();

        $pipeline = ModulePipeline::compose([$mod])->setAsync(false);
        $pipeline->run([]); // missing required input
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModulePipelineException
     */
    public function testPipelineExecutesMultipleModulesWithMixedDependencies(): void
    {
        Module::$asyncQueue = false;
        $results = [];

        $a = Module::create("A")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] + 1])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "A:$v";
            })
            ->register();

        $b = Module::create("B")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("A")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] * 3])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "B:$v";
            })
            ->register();

        $c = Module::create("C")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("B")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] - 2])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "C:$v";
            })
            ->register();

        $pipeline = ModulePipeline::compose([$a, $b, $c])->setAsync(false);
        $final = $pipeline->run(["val" => 2]);

        $this->assertEquals(["A:3", "B:9", "C:7"], $results);
        $this->assertEquals(7, $final["val"]);
    }
}
