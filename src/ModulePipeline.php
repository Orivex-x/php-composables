<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PhpComposables\Exceptions\ModuleException;
use PhpComposables\Exceptions\ModulePipelineException;
use Exception;

class ModulePipeline {
    /**
     * @var Module[] Array of modules in the pipeline
     */
    private array $modules;

    /**
     * @var array<string, string> Associative array of module versions
     * @phpstan-ignore-next-line
     */
    private array $moduleVersions = [];

    /**
     * @var bool Indicates if the pipeline runs asynchronously
     */
    private bool $pipelineAsync = false;

    /**
     * @var array<string, bool> Static array of executed modules (module names as keys)
     */
    private static array $executedGlobal = [];

    /**
     * @param Module[] $modules Array of Module instances to compose the pipeline
     */
    public function __construct(array $modules) {
        $this->modules = $modules;
    }

    /**
     * Compose the pipeline with modules.
     *
     * @param Module[] $modules Array of Module instances
     * @return self
     */
    public static function compose(array $modules): self {
        return new self($modules);
    }

    /**
     * Set the version for a module.
     *
     * @param string $moduleName
     * @param string $version
     * @return $this
     */
    public function setModuleVersion(string $moduleName, string $version): self {
        $this->moduleVersions[$moduleName] = $version;
        return $this;
    }

    /**
     * Set whether the pipeline should run asynchronously.
     *
     * @param bool $enabled
     * @return $this
     */
    public function setAsync(bool $enabled = true): self {
        $this->pipelineAsync = $enabled;
        return $this;
    }

    /**
     * Find and return the module by its name.
     *
     * @param string $name The module name
     * @throws ModulePipelineException
     * @return Module
     */
    private function findModuleByName(string $name): Module {
        foreach ($this->modules as $m) {
            if ($m->name === $name) return $m;
        }

        throw new ModulePipelineException("Module '$name' not found in pipeline modules.");
    }

    /**
     * Resolve the execution order of pipeline modules considering dependencies.
     *
     * @return Module[] Array of modules in the resolved execution order
     * @throws ModulePipelineException
     */
    private function resolveOrder(): array {
        $order = [];
        $visited = [];

        $visit = function(Module $module) use (&$visit, &$order, &$visited) {
            if (isset($visited[$module->name])) {
                return;
            }

            $visited[$module->name] = true;

            foreach ($module->dependencies as $dependencyName) {
                $dependency = $this->findModuleByName($dependencyName);
                $visit($dependency);
            }

            $order[] = $module;
        };

        foreach ($this->modules as $m) {
            $visit($m);
        }

        return $order;
    }

    /**
     * Check if a module has already been executed.
     *
     * @param string $moduleName
     * @return bool
     */
    public static function isExecuted(string $moduleName): bool {
        return isset(self::$executedGlobal[$moduleName]);
    }

    /**
     * Mark a module as executed.
     *
     * @param string $moduleName
     * @return void
     */
    public static function markExecuted(string $moduleName): void {
        self::$executedGlobal[$moduleName] = true;
    }

    /**
     * Run the pipeline and process all modules.
     *
     * @param array<string, mixed> $inputs Associative array of input data for the modules
     * @return array<string, mixed> Processed data after pipeline execution
     * @throws ModuleException
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     * @throws ModulePipelineException
     */
    public function run(array $inputs): array {
        self::$executedGlobal = [];

        $data = $inputs;
        Module::$asyncQueue = $this->pipelineAsync;

        try {
            $order = $this->resolveOrder();
        } catch (Exception $e) {
            throw new ModulePipelineException(
                "Failed to resolve pipeline execution order.",
                null,
                0,
                $e
            );
        }

        foreach ($order as $module) {
            if (self::isExecuted($module->name)) {
                continue;
            }

            self::markExecuted($module->name);

            $moduleInputs = [];

            foreach ($module->getSchema()->getInputs() as $name => $type) {
                if (!array_key_exists($name, $data)) {
                    throw new ModulePipelineException(
                        "Missing required input '$name' for module '$module->name'.",
                        $module->name
                    );
                }

                $moduleInputs[$name] = $data[$name];
            }

            $result = $module->run($moduleInputs);
            $data = array_merge($data, $result);

            // Run queued hooks or branches immediately if pipeline is async
            if ($this->pipelineAsync) {
                ModuleAsyncQueue::run();
            }
        }

        // Flush any remaining async queue items
        if (Module::$asyncQueue) {
            ModuleAsyncQueue::run();
        }

        return $data;
    }
}
