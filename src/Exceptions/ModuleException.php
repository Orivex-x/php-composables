<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

/**
 * Exception class specific to Module operations.
 */
class ModuleException extends Exception
{
    /** @var string|null The module name where the exception originated */
    protected string|null $moduleName;

    /**
     * ModuleException constructor.
     *
     * @param string $message
     * @param string|null $moduleName
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, string|null $moduleName = null, int $code = 0, Throwable|null $previous = null) {
        $this->moduleName = $moduleName;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the module name where the exception occurred.
     *
     * @return string|null
     */
    public function getModuleName(): string|null
    {
        return $this->moduleName;
    }

    /**
     * String representation with module context.
     *
     * @return string
     */
    public function __toString(): string
    {
        $prefix = $this->moduleName ? "[Module: {$this->getModuleName()}] " : '';

        return $prefix . parent::__toString();
    }
}
