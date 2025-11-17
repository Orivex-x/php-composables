<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

/**
 * Class ModuleAsyncQueueException
 *
 * Exception specifically for errors occurring within the ModuleAsyncQueue.
 */
class ModuleAsyncQueueException extends Exception
{
    /** @var string|null Context or identifier for the queue item that caused the exception */
    protected string|null $context = null;

    /**
     * ModuleAsyncQueueException constructor.
     *
     * @param string $message
     * @param string|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, string|null $context = null, int $code = 0, Throwable|null $previous = null) {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the queue context associated with the exception.
     *
     * @return string|null
     */
    public function getContext(): string|null
    {
        return $this->context;
    }

    /**
     * String representation with contextual info.
     *
     * @return string
     */
    public function __toString(): string
    {
        $prefix = $this->context ? "[Queue Context: {$this->getContext()}] " : '';

        return $prefix . parent::__toString();
    }
}
