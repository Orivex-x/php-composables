<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleAsyncQueueException;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use Throwable;

class ModuleAsyncQueue {
    /**
     * @var array<array{
     *     callback: callable,
     *     data: mixed|null,
     *     schema: ModuleSchema|null,
     *     type: string
     * }> $queue Stores async tasks
     */
    private static array $queue = [];

    /** @var bool $running Determines if the task is running */
    private static bool $running = false;

    /**
     * Enqueue a callable for async execution.
     *
     * @param callable $callback
     * @param mixed|null $data
     * @param ModuleSchema|null $schema
     * @param string $type 'hook' or 'module'
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public static function enqueue(callable $callback, mixed $data = null, ModuleSchema|null $schema = null, string $type = "hook"): void {
        self::$queue[] = [
            "callback" => $callback,
            "data" => $data,
            "schema" => $schema,
            "type" => $type
        ];

        ModuleEventDispatcher::dispatch(ModuleEventDispatcher::EVENT_QUEUE_ENQUEUE, [
            "callback" => $callback,
            "data" => $data,
            "schema" => $schema,
            "type" => $type
        ]);
    }

    /**
     * Run all queued async tasks.
     *
     * @return void
     * @throws ModuleAsyncQueueException
     * @throws ModuleEventDispatcherException
     */
    public static function run(): void {
        if (self::$running) {
            return;
        }

        self::$running = true;

        try {
            while (($item = array_shift(self::$queue)) !== null) {
                try {
                    ModuleEventDispatcher::dispatch(ModuleEventDispatcher::EVENT_QUEUE_RUN, [
                        "callback" => $item["callback"],
                        "data" => $item["data"],
                        "type" => $item["type"]
                    ]);

                    // Hooks are optional
                    if ($item["type"] === "hook") {
                        ($item["callback"])($item["data"]);
                        continue; // done, no exception needed
                    }

                    // Modules are strict
                    if ($item["type"] === "module") {
                        // Validate outputs if schema exists and strict mode
                        if ($item["schema"] instanceof ModuleSchema && is_array($item["data"]) && Module::$asyncValidationStrict) {
                            $item["schema"]->validateOutputs($item["data"]);
                        }

                        // Execute the module callback
                        ($item["callback"])($item["data"]);
                    }

                } catch (Throwable $e) {
                    ModuleEventDispatcher::dispatch("queue.error", ["error" => $e]);
                    self::clear();

                    // Only throw if this is a real module; just warn for hooks
                    if ($item["type"] === "module") {
                        $context = json_encode($item["data"]);

                        throw new ModuleAsyncQueueException(
                            "Async queue execution failed.",
                            $context !== false ? $context : null,
                            0,
                            $e
                        );
                    } else {
                        trigger_error("Async hook failed: " . $e->getMessage(), E_USER_WARNING);
                    }
                }
            }
        } finally {
            self::$running = false;
        }
    }

    /**
     * Get the queue
     *
     * @return array<array{
     *     callback: callable,
     *     data: mixed|null,
     *     schema: ModuleSchema|null,
     *     type: string
     * }>
     */
    public static function getQueue(): array {
        return self::$queue;
    }

    /**
     * Clear the queue
     *
     * @return void
     */
    public static function clear(): void {
        self::$queue = [];
    }
}
