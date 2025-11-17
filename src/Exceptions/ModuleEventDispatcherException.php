<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

class ModuleEventDispatcherException extends Exception
{
    /** @var string|null The event name associated with the error */
    protected string|null $eventName = null;

    /**
     * ModuleEventDispatcherException constructor.
     *
     * @param string $message
     * @param string|null $eventName
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, string|null $eventName = null, int $code = 0, Throwable|null $previous = null) {
        $this->eventName = $eventName;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the event name where the exception occurred.
     *
     * @return string|null
     */
    public function getEventName(): string|null
    {
        return $this->eventName;
    }

    /**
     * Stringify with contextual info.
     *
     * @return string
     */
    public function __toString(): string
    {
        $prefix = $this->eventName ? "[Event: {$this->getEventName()}] " : '';

        return $prefix . parent::__toString();
    }
}
