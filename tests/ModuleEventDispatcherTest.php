<?php
namespace PhpComposables\Tests;

use Exception;
use PhpComposables\ModuleEventDispatcher;
use PhpComposables\Exceptions\ModuleEventDispatcherException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ModuleEventDispatcherTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        // Reset the internal static listeners before each test
        $reflection = new ReflectionClass(ModuleEventDispatcher::class);
        $listenersProp = $reflection->getProperty("listeners");
        $listenersProp->setAccessible(true);
        $listenersProp->setValue([]);

        ModuleEventDispatcher::$throwOnListenerError = false;
    }

    /**
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public function testListenerIsCalled(): void
    {
        $called = false;

        ModuleEventDispatcher::listen("test.event", function($payload) use (&$called) {
            $called = $payload["flag"] ?? false;
        });

        ModuleEventDispatcher::dispatch("test.event", ["flag" => true]);

        $this->assertTrue($called);
    }

    /**
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public function testListenerExceptionIsTriggeredAsWarning(): void
    {
        $warnings = [];

        set_error_handler(function(int $errno, string $errStr, string $errFile, int $errLine) use (&$warnings): bool {
            $warnings[] = $errStr;

            return false; // Don't suppress the error
        });

        ModuleEventDispatcher::listen("error.event", function() {
            throw new Exception("Listener failure");
        });

        ModuleEventDispatcher::dispatch("error.event");

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString("Event listener error", $warnings[0]);
    }

    /**
     * @return void
     * @throws ModuleEventDispatcherException
     */
    public function testListenerExceptionIsThrownWhenConfigured(): void
    {
        ModuleEventDispatcher::$throwOnListenerError = true;

        ModuleEventDispatcher::listen("throw.event", function() {
            throw new Exception("Listener failure");
        });

        $this->expectException(ModuleEventDispatcherException::class);
        $this->expectExceptionMessageMatches("/Listener error for 'throw.event'/");

        ModuleEventDispatcher::dispatch("throw.event");
    }
}
