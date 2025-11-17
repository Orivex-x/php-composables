<?php
namespace PhpComposables\Exceptions;

use Exception;
use Throwable;

class ModuleSchemaException extends Exception
{
    /** @var string|null Name of the module associated with the schema error */
    protected string|null $moduleName = null;

    /**
     * ModuleSchemaException constructor.
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
    public function getModuleName(): string|null {
        return $this->moduleName;
    }

    /**
     * String representation with module context.
     *
     * @return string
     */
    public function __toString(): string {
        $prefix = $this->moduleName ? "[Module: {$this->moduleName}] " : '';

        return $prefix . parent::__toString();
    }
}
