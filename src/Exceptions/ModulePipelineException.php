<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

/**
 * Exception class specific to ModulePipeline operations.
 */
class ModulePipelineException extends Exception
{
    /** @var string|null The pipeline or module context */
    protected string|null $context = null;

    /**
     * ModulePipelineException constructor.
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
     * Get the context associated with the exception (pipeline/module name, etc.).
     *
     * @return string|null
     */
    public function getContext(): string|null
    {
        return $this->context;
    }

    /**
     * String representation including context info.
     *
     * @return string
     */
    public function __toString(): string
    {
        $prefix = $this->context ? "[Pipeline Context: {$this->getContext()}] " : '';

        return $prefix . parent::__toString();
    }
}
