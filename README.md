# PHP Composables

[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-blue.svg)](#)
[![CI](https://github.com/orivex-x/php-composables/actions/workflows/ci.yml/badge.svg)](https://github.com/orivex-x/php-composables/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/orivex-x/php-composables.svg)](https://codecov.io/gh/orivex-x/php-composables)
[![Packagist Version](https://img.shields.io/packagist/v/orivex-x/php-composables.svg)](https://packagist.org/packages/orivex-x/php-composables)
[![License](https://img.shields.io/github/license/orivex-x/php-composables.svg)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/orivex-x/php-composables.svg)](https://packagist.org/packages/orivex-x/php-composables)

## Overview
PHP Composables is a modular, reactive, and event-driven framework for PHP that allows developers to build dynamic workflows using composable modules. Each module can define:

- **Inputs & Outputs** - specify required data and its type.
- **Dependencies** - automatically resolved by pipelines.
- **Logic** - core callable that produces outputs from inputs.
- **Hooks** - functions triggered after outputs are produced.
- **Branches** - conditional downstream modules based on outputs.
- **Async Execution** - offload hooks and branches to an asynchronous queue.

Modules can be combined into **pipelines** that handle execution order, versioning, and dependency resolution. PHP Composables also includes an **event system** for monitoring module lifecycle and async operations.

Ideal for microservices, automation, data pipelines, and dynamic web applications.

## Requirements
- **PHP >= 8.2**
- **Composer 2.x**

## Installation
Install via Composer:
```shell
composer require orivex-x/php-composables
```

Include Composer autoload in your project:
```php
require_once './vendor/autoload.php';
```

## Core Concepts
### Module

A **Module** is a self-contained unit of work.

**Key Features**:
- **Name & Version**: Each module has a semantic version.
- **Inputs & Outputs**: Enforce data types.
- **Dependencies**: Modules can require other modules.
- **Logic**: Callable function producing outputs.
- **Hooks**: Triggered after outputs are produced.
- **Branches**: Conditional downstream execution.
- **Async Execution**: Hooks and branches can run asynchronously.

### Example
```php
use PhpComposables\Module;

$userModule = Module::create("User", "1.0.0")
    ->declareInput("name", "string")
    ->declareOutput("greeting", "string")
    ->setLogic(fn($inputs) => ["greeting" => "Hello, {$inputs['name']}!"])
    ->onOutput("greeting", fn($g) => print("[HOOK] Greeting: $g\n"))
    ->register();
```

## ModulePipeline
A **pipeline** composes multiple modules, resolving dependencies and handling execution order.

**Features**:
- Async execution of hooks and branches.
- Force specific module versions.
- Avoid repeated module execution.
- Executes branches conditionally based on outputs.

### Example
```php
use PhpComposables\ModulePipeline;

$pipeline = ModulePipeline::compose([$userModule])
    ->setAsync() // Enable async hooks & branches
    ->setModuleVersion("User", "1.0.0");

$result = $pipeline->run(["name" =>" '"Alice']);
print_r($result);
```

## ModuleRegistry
Central registry for modules and their versions. Retrieve modules by name and optionally by version. If no version is specified, the latest semantic version is returned.

### Example
```php
use PhpComposables\ModuleRegistry;

$latestUser = ModuleRegistry::get("User");
$authModule = ModuleRegistry::get("Auth", "1.2.0");
```

## ModuleAsyncQueue
Handles asynchronous execution for hooks and branches.

**Features**:
- Enqueue tasks with optional schema validation.
- Run queued tasks in order.
- Async validation respects Module::$asyncValidationStrict.
- Emits events for enqueue, run, and errors.

**Important**: Module outputs must match the declared types. Hooks can use ```print()``` or side effects, but **do not return non-compliant types**.

### Async Hook & Branch Warnings

When using `Module::$asyncQueue = true`:

- Hooks and branch logic are executed **after the main module run**.
- By default, **validation errors in async hooks/branches will throw exceptions**.
- To make async hooks safer, you can disable strict validation:

```php
Module::$asyncValidationStrict = false; // Async validation errors will only warn
```

- Always return type-compliant outputs from async hooks; returning invalid types may cause queue warnings or skipped execution.
- Avoid relying on async hook outputs in the main module run - they execute after the module completes.

### Example
```php
use PhpComposables\ModuleAsyncQueue;

// Side effect safe callback
ModuleAsyncQueue::enqueue(
    fn($data) => print_r($data),
    ["foo" => "bar"],
    null,
    "hook"
);

// Execute queued tasks
ModuleAsyncQueue::run();
```

## ModuleEventDispatcher
Dispatches events for module lifecycle and async queue activities.

**Supported Events**:

| Event            | Description                    |
|------------------|--------------------------------|
| module.run.start | Before a module executes       |
| module.run.end   | After a module completes       |
| queue.enqueue    | When an async item is enqueued |
| queue.run        | When an async item runs        |

### Example
```php
use PhpComposables\ModuleEventDispatcher;

ModuleEventDispatcher::listen(ModuleEventDispatcher::EVENT_MODULE_RUN_START, fn($payload) => 
    echo "Module started: {$payload['module']}\n"
);
```

## Exception Handling
PHP Composables provides specialized exception classes:

- **ModuleException** - errors within a module (logic, hooks, branches)
- **ModuleSchemaException** - errors from the module schema.
- **ModulePipelineException** - errors during pipeline execution
- **ModuleRegistryException** - when retrieving modules fails
- **ModuleAsyncQueueException** - for async queue failures
- **ModuleEventDispatcherException** - for event listener errors

```php
try {
    $pipeline->run(["name" => "Alice"]);
} catch (ModuleException $e) {
    echo "Module failed: {$e->getMessage()} in module {$e->getModuleName()}";
}
```

## Best Practices
- Version modules consistently.
- Use hooks for side effects (logging, emails, analytics), not main logic.
- Branch outputs carefully to avoid cycles.
- Async queues are recommended for non-critical tasks.
- Catch ModuleException or ModulePipelineException at the top level.
- **Always return type-compliant outputs** from modules to avoid async queue failures.

## Advanced Features
- **Dependency Resolution** - pipelines automatically order modules.
- **Conditional Branching** - modules trigger downstream modules based on outputs.
- **Async Hooks & Branches** - offload non-critical tasks.
- **Event System** - monitor execution and async operations.

## Developer Quickstart
```php
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
```

## MIT License
Free to use, modify, and distribute.
