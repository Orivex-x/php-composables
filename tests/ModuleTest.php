<?php
namespace PhpComposables\Tests;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PhpComposables\Exceptions\ModulePipelineException;
use PhpComposables\Module;
use PhpComposables\ModulePipeline;
use PhpComposables\ModuleAsyncQueue;
use PhpComposables\Exceptions\ModuleException;
use PHPUnit\Framework\TestCase;

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
            ->setLogic(fn($inputs) => ["greeting" => "Hello, {$inputs['name']}!"]);

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
            ->setLogic(fn($inputs) => ["value" => $inputs["value"] + 1])
            ->onOutput("value", function($v) use (&$hookCalled) {
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
            ->setLogic(function($inputs) use (&$targetCalled) {
                return ["result" => $targetCalled = $inputs["flag"] ? "yes" : "no"];
            });

        $branchModule = Module::create("Brancher")
            ->declareInput("flag", "bool")
            ->declareOutput("flag", "bool")
            ->setLogic(fn($inputs) => ["flag" => $inputs["flag"]])
            ->branchOutput("flag", fn($v) => $v === true, $target);

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
            ->setLogic(fn($inputs) => ["num" => $inputs["num"] * 2])
            ->onOutput("num", function($v) use (&$hookExecuted) {
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
            ->setLogic(fn($inputs) => "not-an-array");

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
            ->setLogic(fn($inputs) => ["result" => 123]);

        $module->run(["num" => 5]);
    }

    /**
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModulePipelineException
     */
    public function testPipelineExecutesModulesInOrder(): void
    {
        Module::$asyncQueue = false;
        $results = [];

        $mod1 = Module::create("M1")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] + 1])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "M1:$v";
            });

        $mod2 = Module::create("M2")
            ->declareInput("val", "int")
            ->declareOutput("val", "int")
            ->declareDependency("M1")
            ->setLogic(fn($inputs) => ["val" => $inputs["val"] * 2])
            ->onOutput("val", function($v) use (&$results) {
                $results[] = "M2:$v";
            });

        $pipeline = ModulePipeline::compose([$mod1, $mod2]);
        $final = $pipeline->run(["val" => 3]);

        $this->assertEquals(["M1:4", "M2:8"], $results);
        $this->assertEquals(8, $final["val"]);
    }
}
