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
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateFloatInputAndOutput(): void
    {
        $schema = new ModuleSchema("FloatTest");
        $schema->declareInput("price", "float")
            ->declareOutput("total", "float");

        $schema->validateInputs(["price" => 19.99]);
        $schema->validateOutputs(["total" => 39.98]);

        $this->expectException(ModuleSchemaException::class);
        $schema->validateInputs(["price" => 20]);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateDoubleInputAndOutput(): void
    {
        $schema = new ModuleSchema("DoubleTest");
        $schema->declareInput("value", "double")
            ->declareOutput("result", "double");

        $schema->validateInputs(["value" => 3.14]);
        $schema->validateOutputs(["result" => 6.28]);

        $this->expectException(ModuleSchemaException::class);
        $schema->validateOutputs(["result" => 6]);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateArrayInputAndOutput(): void
    {
        $schema = new ModuleSchema("ArrayTest");
        $schema->declareInput("items", "array")
            ->declareOutput("result", "array");

        $schema->validateInputs(["items" => [1, 2, 3]]);
        $schema->validateOutputs(["result" => ["success" => true]]);

        $this->expectException(ModuleSchemaException::class);
        $schema->validateInputs(["items" => "not an array"]);
    }

    /**
     * @return void
     * @throws ModuleSchemaException
     */
    public function testValidateCallableInputAndOutput(): void
    {
        $schema = new ModuleSchema("CallableTest");
        $schema->declareInput("handler", "callable")
            ->declareOutput("callback", "callable");

        $handler = static fn (): string => "hello";
        $callback = static function (): string {
            return "world";
        };

        $schema->validateInputs(["handler" => $handler]);
        $schema->validateOutputs(["callback" => $callback]);

        $this->expectException(ModuleSchemaException::class);
        $schema->validateInputs(["handler" => "not callable"]);
    }
}
