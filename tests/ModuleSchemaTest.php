<?php
namespace PhpComposables\Tests;

use PhpComposables\ModuleSchema;
use PhpComposables\Exceptions\ModuleSchemaException;
use PHPUnit\Framework\TestCase;

class ModuleSchemaTest extends TestCase
{
    /**
     * @return void
     */
    public function testDeclareInputsAndOutputs(): void
    {
        $schema = new ModuleSchema("TestModule");
        $schema->declareInput("name", "string")
            ->declareInput("age", "int")
            ->declareOutput("greeting", "string")
            ->declareOutput("isAdult", "bool");

        $this->assertEquals(
            ["name" => "string", "age" => "int"],
            $schema->getInputs()
        );

        $this->assertEquals(
            ["greeting" => "string", "isAdult" => "bool"],
            $schema->getOutputs()
        );
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateCorrectInputsAndOutputs(): void
    {
        $schema = new ModuleSchema("TestModule");
        $schema->declareInput("name", "string")
            ->declareInput("age", "int")
            ->declareOutput("greeting", "string")
            ->declareOutput("isAdult", "bool");

        $inputs = ["name" => "Alice", "age" => 25];
        $outputs = ["greeting" => "Hello, Alice", "isAdult" => true];

        // Should not throw exceptions
        $schema->validateInputs($inputs);
        $schema->validateOutputs($outputs);

        $this->assertTrue(true); // if no exception, test passes
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateMissingInputThrowsException(): void
    {
        $this->expectException(ModuleSchemaException::class);

        $schema = new ModuleSchema("TestModule");
        $schema->declareInput("name", "string")
            ->declareOutput("greeting", "string");

        $inputs = []; // missing "name"
        $schema->validateInputs($inputs);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateWrongInputTypeThrowsException(): void
    {
        $this->expectException(ModuleSchemaException::class);

        $schema = new ModuleSchema("TestModule");
        $schema->declareInput("age", "int");

        $inputs = ["age" => "twenty"];
        $schema->validateInputs($inputs);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateMissingOutputThrowsException(): void
    {
        $this->expectException(ModuleSchemaException::class);

        $schema = new ModuleSchema("TestModule");
        $schema->declareOutput("result", "string");

        $outputs = []; // missing "result"
        $schema->validateOutputs($outputs);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateWrongOutputTypeThrowsException(): void
    {
        $this->expectException(ModuleSchemaException::class);

        $schema = new ModuleSchema("TestModule");
        $schema->declareOutput("isActive", "bool");

        $outputs = ["isActive" => 1]; // int instead of bool
        $schema->validateOutputs($outputs);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testMixedTypeAlwaysValid(): void
    {
        $schema = new ModuleSchema("TestModule");
        $schema->declareInput("anything", "mixed")
            ->declareOutput("anythingOut", "mixed");

        $inputs = ["anything" => 123];
        $outputs = ["anythingOut" => ["array"]];

        $schema->validateInputs($inputs);
        $schema->validateOutputs($outputs);

        $this->assertTrue(true);
    }
}
