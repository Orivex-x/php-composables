<?php
namespace PhpComposables;

use PhpComposables\Exceptions\ModuleRegistryException;

/**
 * Class ModuleRegistry
 *
 * Maintains a central registry of all modules and their versions.
 * Allows registering modules and retrieving them by name or specific version.
 *
 * @package php-composables
 */
class ModuleRegistry {
    /** @var array<string, array<string, Module>> Stores modules indexed by name and version. */
    protected static array $modules = [];

    /**
     * Register a module in the registry.
     *
     * @param Module $module The module instance to register.
     * @return void
     */
    public static function register(Module $module): void
    {
        self::$modules[$module->name][$module->version] = $module;
    }

    /**
     * Retrieve a module by name and optionally version.
     *
     * If no version is specified, the latest registered version is returned.
     *
     * @param string $name The name of the module.
     * @param string|null $version Optional semantic version string to retrieve a specific version.
     * @return Module
     * @throws ModuleRegistryException If module or version is not found.
     */
    public static function get(string $name, string|null $version = null): Module
    {
        $versions = self::$modules[$name] ?? null;

        if (!$versions) {
            throw new ModuleRegistryException("Module '$name' not found in registry.", $name);
        }

        if ($version !== null) {
            $module = $versions[$version] ?? null;

            if (!$module) {
                throw new ModuleRegistryException("Module '$name' with version '$version' not found.", $name);
            }

            return $module;
        }

        usort($versions, function($a, $b) {
            return version_compare($a->version, $b->version);
        });

        return end($versions); // Get the latest version
    }

    /**
     * Clears the module register.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$modules = [];
    }
}
