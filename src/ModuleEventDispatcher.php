<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleEventDispatcherException;
use Throwable;

class ModuleEventDispatcher {
    /** @var array<string, callable[]> $listeners Array of registered event listeners */
    private static array $listeners = [];

    /** @var bool $throwOnListenerError Determines whether to throw errors on listener failures */
    public static bool $throwOnListenerError = false;

    public final const EVENT_MODULE_RUN_START = "module.run.start";
    public final const EVENT_MODULE_RUN_END = "module.run.end";
    public final const EVENT_QUEUE_ENQUEUE = "queue.enqueue";
    public final const EVENT_QUEUE_RUN = "queue.run";

    /**
     * Listen for an event
     *
     * @param string $event
     * @param mixed $listener
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public static function listen(string $event, mixed $listener): void {
        if (!is_callable($listener)) {
            throw new ModuleEventDispatcherException("Listener not callable for event '$event'.", $event);
        }

        self::$listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event
     *
     * @param string $event
     * @param array<string, mixed> $payload
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public static function dispatch(string $event, array $payload = []): void {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            try {
                $listener($payload);
            } catch (Throwable $e) {
                if (self::$throwOnListenerError) {
                    throw new ModuleEventDispatcherException(
                        "Listener error for '$event': {$e->getMessage()}",
                        $event,
                        0,
                        $e
                    );
                } else {
                    trigger_error("Event listener error for '$event': {$e->getMessage()}", E_USER_WARNING);
                }
            }
        }
    }
}
