<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown for errors related to ModuleRegistry operations.
 */
class ModuleRegistryException extends Exception
{
    /** @var string|null The module name associated with the error */
    protected string|null $moduleName = null;

    /**
     * ModuleRegistryException constructor.
     *
     * @param string $message The error message
     * @param string|null $moduleName Optional module name
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, string|null $moduleName = null, int $code = 0, Throwable|null $previous = null) {
        $this->moduleName = $moduleName;

        parent::__construct($message, $code, $previous);

    }

    /**
     * Returns the module name where the exception occurred.
     *
     * @return string|null
     */
    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    /**
     * String representation of the exception.
     *
     * @return string
     */
    public function __toString(): string
    {
        $prefix = $this->moduleName ? "[Module: {$this->getModuleName()}] " : '';

        return $prefix . parent::__toString();
    }
}
