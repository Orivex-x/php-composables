<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleSchemaException;

/**
 * ModuleSchema
 *
 * Represents the input/output schema of a module.
 * Provides validation and type enforcement.
 *
 * @package php-composables
 */
class ModuleSchema
{
    /** @var array<string, string> Input definitions: key => type */
    protected array $inputs = [];

    /** @var array<string, string> Output definitions: key => type */
    protected array $outputs = [];

    /** @var string|null Associated module name (optional) */
    protected string|null $moduleName = null;

    /**
     * @param string|null $moduleName
     */
    public function __construct(string|null $moduleName = null)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Declare an input.
     *
     * @param string $name
     * @param string $type
     * @return $this
     */
    public function declareInput(string $name, string $type): static
    {
        $this->inputs[$name] = $type;

        return $this;
    }

    /**
     * Declare an output.
     *
     * @param string $name
     * @param string $type
     * @return $this
     */
    public function declareOutput(string $name, string $type): static
    {
        $this->outputs[$name] = $type;

        return $this;
    }

    /**
     * Validate an input array against the schema.
     *
     * @param array<string, mixed> $inputs The input data to validate, where keys are input names and values are their respective values.
     * @throws ModuleSchemaException
     */
    public function validateInputs(array $inputs): void
    {
        foreach ($this->inputs as $name => $type) {
            if (!array_key_exists($name, $inputs)) {
                throw new ModuleSchemaException("Missing required input '$name'.", $this->moduleName);
            }

            if (!$this->validateType($inputs[$name], $type)) {
                $actual = gettype($inputs[$name]);

                throw new ModuleSchemaException("Input '$name' expected type '$type', got '$actual'.", $this->moduleName);
            }
        }
    }

    /**
     * Validate an output array against the schema
     *
     * @param array<string, mixed> $outputs The output data to validate, where keys are output names and values are their respective values.
     * @throws ModuleSchemaException
     */
    public function validateOutputs(array $outputs): void
    {
        foreach ($this->outputs as $name => $type) {
            if (!array_key_exists($name, $outputs)) {
                throw new ModuleSchemaException("Missing required output '$name'.", $this->moduleName);
            }

            if (!$this->validateType($outputs[$name], $type)) {
                $actual = gettype($outputs[$name]);

                throw new ModuleSchemaException("Output '$name' expected type '$type', got '$actual'.", $this->moduleName);
            }
        }
    }

    /**
     * Validate a value against a type string
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    protected function validateType(mixed $value, string $type): bool
    {
        return match(strtolower($type)) {
            "string" => is_string($value),
            "int", "integer" => is_int($value),
            "float", "double" => is_float($value),
            "bool", "boolean" => is_bool($value),
            "array" => is_array($value),
            "callable" => is_callable($value),
            "mixed" => true,
            default => false,
        };
    }

    /**
     * Get the input definitions
     *
     * @return array<string, string>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get the output definitions
     *
     * @return array<string, string>
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }
}
