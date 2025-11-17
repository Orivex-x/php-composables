<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleException;
use PhpComposables\Exceptions\ModuleSchemaException;
use Throwable;

class Module {
    public string $name;
    public string $version;

    /** @var string[] $dependencies */
    public array $dependencies = [];

    /** @var callable|null $logic */
    private $logic = null;

    /** @var callable[][] $hooks */
    private array $hooks = [];
    /** @var array<string, array<array{condition: callable, module: Module|string}>> */
    private array $branches = [];
    private ModuleSchema $schema;

    /** @var bool Whether ModuleException should be thrown or just warn */
    public static bool $useExceptions = true;

    /** @var bool Enable async queue for hooks/branches */
    public static bool $asyncQueue = false;

    /** @var bool Whether async validation errors throw or just warn */
    public static bool $asyncValidationStrict = true;

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct(string $name, string $version = "1.0.0") {
        $this->name = $name;
        $this->version = $version;
        $this->schema = new ModuleSchema($name);
    }

    /**
     * Create a module
     *
     * @param string $name
     * @param string $version
     * @return self
     */
    public static function create(string $name, string $version = "1.0.0"): self {
        return new self($name, $version);
    }

    /**
     * Declare an input for a module
     *
     * @param string $name
     * @param string $type
     * @return $this
     */
    public function declareInput(string $name, string $type): static {
        $this->schema->declareInput($name, $type);

        return $this;
    }

    /**
     * Declare an output for a module
     *
     * @param string $name
     * @param string $type
     * @return $this
     */
    public function declareOutput(string $name, string $type): static {
        $this->schema->declareOutput($name, $type);

        return $this;
    }

    /**
     * Declare a dependency for a module
     *
     * @param string $moduleName
     * @return $this
     */
    public function declareDependency(string $moduleName): static {
        $this->dependencies[] = $moduleName;

        return $this;
    }

    /**
     * Set callable logic for a module
     *
     * @param callable $logic
     * @return $this
     */
    public function setLogic(callable $logic): static {
        $this->logic = $logic;

        return $this;
    }

    /**
     * Trigger for handling onOutput hook
     *
     * @param string $outputName
     * @param callable $hook
     * @return $this
     */
    public function onOutput(string $outputName, callable $hook): static {
        $this->hooks[$outputName][] = $hook;

        return $this;
    }

    /**
     * Declare an output for a module branch
     *
     * @param string $outputName
     * @param callable $condition
     * @param Module|string $targetModule
     * @return $this
     */
    public function branchOutput(string $outputName, callable $condition, Module|string $targetModule): static {
        $this->branches[$outputName][] = ["condition" => $condition, "module" => $targetModule];
        return $this;
    }

    /**
     * Register a module in the registry
     *
     * @return $this
     */
    public function register(): static {
        ModuleRegistry::register($this);

        return $this;
    }

    /**
     * Run the module
     *
     * @param array<string, mixed> $inputs
     * @return array<string, mixed>
     * @throws ModuleException
     */
    public function run(array $inputs): array {
        try {
            ModuleEventDispatcher::dispatch(
                ModuleEventDispatcher::EVENT_MODULE_RUN_START,
                ["module" => $this->name, "inputs" => $inputs]
            );

            $this->schema->validateInputs($inputs);

            if (!is_callable($this->logic)) {
                throw new ModuleException("No logic callable is defined.", $this->name);
            }

            $result = call_user_func($this->logic, $inputs);

            if (!is_array($result)) {
                throw new ModuleException("Logic returned non-array type: " . gettype($result), $this->name);
            }

            $this->schema->validateOutputs($result);

            foreach ($result as $key => $value) {
                // Hooks
                foreach ($this->hooks[$key] ?? [] as $hook) {
                    $v = $value;

                    if (self::$asyncQueue) {
                        ModuleAsyncQueue::enqueue(fn() => $hook($v), [$key => $v], $this->schema);
                    } else {
                        $hook($v);
                    }
                }

                // Branches
                foreach ($this->branches[$key] ?? [] as $branch) {
                    if ($branch["condition"]($value)) {
                        $module = is_string($branch["module"]) ? ModuleRegistry::get($branch["module"]) : $branch["module"];

                        if (!ModulePipeline::isExecuted($module->name)) {
                            ModulePipeline::markExecuted($module->name);
                            $v = $value;

                            if (self::$asyncQueue) {
                                ModuleAsyncQueue::enqueue(fn() => $module->run([$key => $v]), [$key => $v], $module->getSchema());
                            } else {
                                $module->run([$key => $v]);
                            }
                        }
                    }
                }
            }

            ModuleEventDispatcher::dispatch(
                ModuleEventDispatcher::EVENT_MODULE_RUN_END,
                ["module" => $this->name, "outputs" => $result]
            );

            return $result;

        } catch (ModuleSchemaException $e) {
            throw new ModuleException("Schema validation failed: {$e->getMessage()}", $this->name, 0, $e);
        } catch (ModuleException $e) {
            if (!self::$useExceptions) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                return [];
            }

            throw $e;
        } catch (Throwable $e) {
            $ex = new ModuleException($e->getMessage(), $this->name, 0, $e);

            if (!self::$useExceptions) {
                trigger_error($ex->getMessage(), E_USER_WARNING);
                return [];
            }

            throw $ex;
        }
    }

    /**
     * Get module schema
     *
     * @return ModuleSchema
     */
    public function getSchema(): ModuleSchema {
        return $this->schema;
    }
}
