<?php
require_once "./vendor/autoload.php";

use PhpComposables\Module;
use PhpComposables\ModulePipeline;
use PhpComposables\ModuleEventDispatcher;

// Event Listeners
try {
    ModuleEventDispatcher::listen(ModuleEventDispatcher::EVENT_MODULE_RUN_START, fn($payload) =>
        print("[EVENT] Module '{$payload['module']}' starting with inputs: " . json_encode($payload["inputs"]) . "\n")
    );

    ModuleEventDispatcher::listen(ModuleEventDispatcher::EVENT_MODULE_RUN_END, fn($payload) =>
        print("[EVENT] Module '{$payload['module']}' finished with outputs: " . json_encode($payload["outputs"]) . "\n")
    );

    ModuleEventDispatcher::listen(ModuleEventDispatcher::EVENT_QUEUE_ENQUEUE, fn($payload) =>
        print("[EVENT] Async queue enqueued for module output\n")
    );

    ModuleEventDispatcher::listen(ModuleEventDispatcher::EVENT_QUEUE_RUN, fn($payload) =>
        print("[EVENT] Async queue item running\n")
    );
} catch (Exception $e) {
    echo "Event Dispatcher Error: {$e->getMessage()}\n";
}

// Global flags
Module::$useExceptions = true;
Module::$asyncQueue = true; // Enable async for hooks/branches
Module::$asyncValidationStrict = false; // Async validation errors will only warn
ModuleEventDispatcher::$throwOnListenerError = false;

/**
 * Define Modules
 */
// User Module
$userModule = Module::create("User", "1.0.0")
    ->declareInput("name", "string")
    ->declareOutput("greeting", "string")
    ->setLogic(fn($inputs) => ["greeting" => "Hello, {$inputs['name']}!"])
    ->onOutput("greeting", fn($greeting) => print("[HOOK] Greeting: $greeting\n"))
    ->register();

// Standard Email Module
$standardEmail = Module::create("StandardEmail", "1.0.0")
    ->declareInput("greeting", "string")
    ->declareOutput("status", "string")
    ->setLogic(function($inputs) {
        echo "[EMAIL] Standard Email: {$inputs['greeting']}\n"; // side effect
        return ["status" => "sent"]; // valid string output
    })
    ->register();

// VIP Email Module
$vipEmail = Module::create("VIPEmail", "1.0.0")
    ->declareInput("greeting", "string")
    ->declareOutput("status", "string")
    ->setLogic(function($inputs) {
        echo "[EMAIL] VIP Email: {$inputs['greeting']}\n"; // side effect
        return ["status" => "sent"];
    })
    ->register();

// Auth Module (v1.2.0)
$authModule = Module::create("Auth", "1.2.0")
    ->declareInput("name", "string")
    ->declareInput("password", "string")
    ->declareOutput("authToken", "string")
    ->declareDependency("User")
    ->setLogic(function($inputs) {
        $token = base64_encode($inputs["name"]);
        return ["authToken" => $token];
    })
    ->onOutput("authToken", fn($token) => print("[HOOK] Auth Token: $token\n"))
    ->register();

// Analytics Module
$analyticsModule = Module::create("Analytics", "1.0.0")
    ->declareInput("authToken", "string")
    ->declareOutput("analyticsToken", "string")
    ->declareDependency("Auth")
    ->setLogic(function($inputs) {
        echo "[ANALYTICS] Tracking token: {$inputs['authToken']}\n";
        return ["analyticsToken" => $inputs["authToken"]]; // valid string
    })
    ->register();

// Conditional Branching
$userModule->branchOutput("greeting", fn($v) => str_contains($v, 'Alice'), $vipEmail);
$userModule->branchOutput("greeting", fn($v) => !str_contains($v, 'Alice'), $standardEmail);

// Compose Pipeline
$pipeline = ModulePipeline::compose([
    $userModule,
    $authModule,
    $analyticsModule,
    $standardEmail,
    $vipEmail
])
    ->setAsync() // Enable async hooks & branches
    ->setModuleVersion("Auth", "1.2.0");

// Run Pipeline
try {
    $result = $pipeline->run([
        "name" => "Alice",
        "password" => "secret"
    ]);

    echo "\n[FINAL RESULT]\n";
    print_r($result);

} catch (\Throwable $e) {
    echo "[PIPELINE ERROR] {$e->getMessage()}\n";
}
