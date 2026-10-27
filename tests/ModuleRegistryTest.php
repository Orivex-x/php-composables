<?php

namespace PhpComposables\Tests;

use PhpComposables\Module;
use PhpComposables\ModuleRegistry;
use PhpComposables\Exceptions\ModuleRegistryException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ModuleRegistryTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        // Reset the registry before each test using Reflection
        $ref = new ReflectionClass(ModuleRegistry::class);
        $prop = $ref->getProperty("modules");
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testRegisterAndRetrieveModule(): void
    {
        $module = Module::create("User")
            ->declareInput("name", "string")
            ->declareOutput("greeting", "string");

        ModuleRegistry::register($module);

        $retrieved = ModuleRegistry::get("User", "1.0.0");

        $this->assertSame($module, $retrieved);
        $this->assertEquals("User", $retrieved->name);
        $this->assertEquals("1.0.0", $retrieved->version);
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testRetrieveLatestVersion(): void
    {
        $v1 = Module::create("User");
        $v2 = Module::create("User", "1.2.0");

        ModuleRegistry::register($v1);
        ModuleRegistry::register($v2);

        $latest = ModuleRegistry::get("User");

        $this->assertSame($v2, $latest);
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testRetrieveNonexistentModuleThrowsException(): void
    {
        $this->expectException(ModuleRegistryException::class);
        ModuleRegistry::get("NonExistentModule");
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testRetrieveNonexistentVersionThrowsException(): void
    {
        $module = Module::create("User");
        ModuleRegistry::register($module);

        $this->expectException(ModuleRegistryException::class);
        ModuleRegistry::get("User", "2.0.0");
    }

    /**
     * @return void
     * @throws ModuleRegistryException
     */
    public function testMultipleModulesDifferentNames(): void
    {
        $userModule = Module::create("User");
        $authModule = Module::create("Auth");

        ModuleRegistry::register($userModule);
        ModuleRegistry::register($authModule);

        $this->assertSame($userModule, ModuleRegistry::get("User"));
        $this->assertSame($authModule, ModuleRegistry::get("Auth"));
    }

    /**
     * @return void
     */
    public function testClearRemovesAllRegisteredModules(): void
    {
        $userModule = Module::create("User");
        $authModule = Module::create("Auth");

        ModuleRegistry::register($userModule);
        ModuleRegistry::register($authModule);

        ModuleRegistry::clear();

        try {
            ModuleRegistry::get("User");

            $this->fail("Expected ModuleRegistryException for cleared User module.");
        } catch (ModuleRegistryException $exception) {
            $this->assertStringContainsString("not found in registry", $exception->getMessage());
        }

        try {
            ModuleRegistry::get("Auth");

            $this->fail("Expected ModuleRegistryException for cleared Auth module.");
        } catch (ModuleRegistryException $exception) {
            $this->assertStringContainsString("not found in registry", $exception->getMessage());
        }
    }
}
