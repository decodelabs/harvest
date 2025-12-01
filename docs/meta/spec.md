# Harvest — Package Specification

> **Cluster:** `http`
> **Language:** `php`
> **Milestone:** `m3`
> **Repo:** `https://github.com/decodelabs/harvest`
> **Role:** HTTP dispatcher

This document describes the purpose, contracts, and design of **Harvest** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Harvest in their own applications or libraries.
- Contributors **maintaining or extending** Harvest.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Harvest provides a unified PSR-15 HTTP stack with a simple, expressive API built on PHP Fibers. It implements the full PSR-15 specification (Request, Response, Middleware, and Handler interfaces) while avoiding common pitfalls of other PSR-15 implementations such as excessive call stack depth, memory usage, and complex middleware traversal. The package uses Fibers to flatten the dispatch call stack, making debugging and exception handling more straightforward while maintaining the semantic correctness of middleware stacking.

### 1.2 Non-Goals

Harvest does **not**:

- Provide a routing system (see `decodelabs/greenleaf` for routing)
- Implement HTTP client functionality (see `decodelabs/hydro` for HTTP clients)
- Handle content security policies directly (optional integration via `decodelabs/sanctum`)
- Provide templating or view rendering (see other frontend packages)
- Manage application lifecycle or bootstrapping (see `decodelabs/genesis`)

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `http` (see Chorus taxonomy)
- Harvest is a mid-level package in the HTTP cluster, providing the core request/response dispatch mechanism. It depends on foundational packages (Archetype, Exceptional, Kingdom, Monarch) and is used by higher-level HTTP packages like Greenleaf (routing) and Fabric (framework).

### 2.2 Typical Usage Contexts

Typical places Harvest appears:

- HTTP request handling in web applications
- Middleware stack composition and execution
- PSR-15 compliant request/response processing
- Framework-level HTTP dispatch layers

Harvest is intended to be used whenever you need a clean, efficient PSR-15 middleware dispatcher that avoids the call stack and memory issues common in traditional middleware implementations.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Harvest`
  The main service class providing factory methods for creating requests, streams, URIs, and transports. Implements PSR-17 URI factory interface and provides cookie collection management.

- `DecodeLabs\Harvest\Dispatcher`
  The core middleware dispatcher that executes a Profile's middleware stack using PHP Fibers. Implements PSR-15 RequestHandlerInterface.

- `DecodeLabs\Harvest\Profile`
  A fluent builder for defining middleware stacks with grouping and priority ordering. Supports adding middleware as class names (resolved via Slingshot), instances, or closures.

- `DecodeLabs\Harvest\Middleware`
  Extension of PSR-15 MiddlewareInterface that adds group and priority properties for automatic ordering within the Profile.

- `DecodeLabs\Harvest\Request`
  Full PSR-15 ServerRequestInterface implementation with additional convenience methods for IP extraction and attribute management.

- `DecodeLabs\Harvest\Response`
  Extension of PSR-15 ResponseInterface with cookie management methods.

- `DecodeLabs\Harvest\ResponseHandler`
  Handles sending responses to clients via Transport implementations, including range request support, protocol version propagation, and sendfile optimization.

- `DecodeLabs\Harvest\Transport`
  Interface for sending HTTP responses, with a Native implementation using PHP's built-in header and output functions.

### 3.2 Main Entry Points

The main usage pattern is creating a Profile with middleware, then dispatching requests through a Dispatcher:

```php
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Monarch;

$profile = new Profile(
    'ErrorHandler',
    new SomeMiddleware(),
    function($request, $handler) {
        return new TextResponse('Hello World!');
    }
);

$dispatcher = new Dispatcher($profile);
$harvest = Monarch::getService(Harvest::class);
$request = $harvest->createRequestFromEnvironment();
$response = $dispatcher->dispatch($request);
```

Middleware is automatically sorted by group (ErrorHandler, Inbound, Outbound, Generic, Generator) and then by priority within each group. String middleware names are resolved via Slingshot for dependency injection support.

---

## 4. Dependencies

### 4.1 Direct Decode Labs Dependencies

From `composer.json`:

- `decodelabs/archetype`
  Used for class resolution and aliasing PSR interfaces to Harvest implementations.

- `decodelabs/coercion`
  Type coercion utilities for request processing.

- `decodelabs/collections`
  Collection data structures used internally.

- `decodelabs/compass`
  IP address parsing and validation for request IP extraction.

- `decodelabs/deliverance`
  Stream channel abstractions for request/response bodies.

- `decodelabs/enumerable`
  Enum support for MiddlewareGroup.

- `decodelabs/exceptional`
  Enhanced exception handling throughout the package.

- `decodelabs/fluidity`
  Fluent interface support for Profile builder.

- `decodelabs/kingdom`
  Service container integration via EagreService.

- `decodelabs/monarch`
  Single source of truth for service resolution.

- `decodelabs/nuance`
  Debugging and inspection utilities.

- `decodelabs/singularity`
  URI parsing and manipulation.

- `decodelabs/slingshot`
  Dependency injection invoker for middleware resolution.

**Optional integration:**

- `decodelabs/sanctum` (optional)
  Detected at runtime if installed, used for content security policy middleware support.

### 4.2 External Dependencies

- `psr/container` (^2.0.2)
  PSR-11 container interface for service resolution.

- `psr/http-factory` (^1.1)
  PSR-17 HTTP factory interfaces.

- `psr/http-message` (^2.0)
  PSR-7 HTTP message interfaces.

- `psr/http-server-handler` (^1.0.2)
  PSR-15 request handler interface.

- `psr/http-server-middleware` (^1.0.2)
  PSR-15 middleware interface.

- `nesbot/carbon` (^3.10.2)
  Date/time handling for cookie expiration.

See `composer.json` for supported PHP versions.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- All middleware must return a PSR-15 ResponseInterface instance
- The Dispatcher always returns a valid ResponseInterface or throws an exception
- Middleware groups are always processed in order: ErrorHandler → Inbound → Outbound → Generic → Generator
- Within each group, middleware is sorted by priority (lower numbers execute first)
- Request objects are immutable; all modifications return new instances
- Response objects are immutable; all modifications return new instances
- The Fiber-based dispatch loop maintains exception propagation semantics equivalent to traditional call stacks

### 5.2 Input & Output Contracts

**Dispatcher::handle(PsrRequest):**
- **Input:** A valid PSR-15 ServerRequestInterface instance
- **Output:** A valid PSR-15 ResponseInterface instance
- **Preconditions:** Profile must not be empty
- **Postconditions:** Response is always returned or exception is thrown

**Profile::add():**
- **Input:** Middleware as string (class name), instance, closure, or Stage
- **Output:** Returns self for fluent chaining
- **Preconditions:** Middleware must be resolvable or valid
- **Postconditions:** Middleware is added to the profile and will be sorted on next access

**Harvest::createRequestFromEnvironment():**
- **Input:** Optional method, URI, and server array
- **Output:** A Harvest Request instance populated from environment
- **Preconditions:** None
- **Postconditions:** Request is fully populated with server params, cookies, files, etc.

---

## 6. Error Handling

### 6.1 Exception Types

Harvest throws Exceptional exceptions:

- `Exceptional::Setup`: When middleware stack is empty or misconfigured
- `Exceptional::NotFound`: When no middleware handles the request (HTTP 404)
- `Exceptional::Runtime`: When middleware stack corruption is detected
- `Exceptional::InvalidArgument`: When invalid arguments are provided (e.g., invalid uploaded files)

All exceptions use the Exceptional pattern for enhanced stack traces and context.

### 6.2 Error Strategy

Harvest uses a fail-fast error strategy. Exceptions propagate through the middleware stack using Fiber exception handling, allowing error-handling middleware (in the ErrorHandler group) to catch and transform exceptions. If no error handler catches an exception, it propagates to the caller. The Fiber-based implementation ensures exception propagation maintains the same semantics as traditional call stacks while keeping the actual call stack flat.

---

## 7. Configuration & Extensibility

### 7.1 Configuration

No runtime configuration is required. Harvest works out of the box with sensible defaults. Optional configuration includes:

- Custom Transport implementations for different runtime environments
- Middleware priority and group overrides when adding to Profile
- ResponseHandler chunk size (default 4096 bytes) for streaming responses
- Sendfile header detection for optimized file serving

### 7.2 Extension Points

Harvest supports extension via:

- **Custom Middleware implementations:** Implement `DecodeLabs\Harvest\Middleware` interface with group and priority properties
- **Custom Transport implementations:** Implement `DecodeLabs\Harvest\Transport` interface for different output mechanisms
- **Custom Response types:** Extend response classes or implement `DecodeLabs\Harvest\Response` interface
- **Stage implementations:** Create custom Stage classes for advanced middleware resolution patterns
- **Profile builder methods:** Extend Profile class for domain-specific middleware composition patterns

---

## 8. Interactions with Other Packages

Harvest is designed to be used by higher-level packages:

- **`decodelabs/greenleaf`**
  Uses Harvest as the HTTP dispatcher for its routing system

- **`decodelabs/fabric`**
  Uses Harvest as the core HTTP layer in the framework

- **`decodelabs/sanctum`**
  Optional integration for CSP middleware (detected at runtime)

Design assumptions:

- Harvest is available early in the HTTP request lifecycle
- Monarch service container is available for service resolution
- Slingshot is available for dependency injection of middleware
- Archetype is available for class resolution and interface aliasing

---

## 9. Usage Examples

### 9.1 Basic Middleware Stack

```php
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Harvest\Response\Text as TextResponse;
use DecodeLabs\Monarch;

$profile = new Profile(
    'ErrorHandler',
    function($request, $handler) {
        return new TextResponse('Hello World!');
    }
);

$dispatcher = new Dispatcher($profile);
$harvest = Monarch::getService(Harvest::class);
$request = $harvest->createRequestFromEnvironment();
$response = $dispatcher->handle($request);
```

### 9.2 Sending a Response

```php
use DecodeLabs\Harvest\ResponseHandler;
use DecodeLabs\Harvest\Transport\Native as NativeTransport;

$handler = new ResponseHandler(new NativeTransport());
$handler->sendResponse($request, $response);
exit;
```

### 9.3 Cookie Management

```php
$profile->add('Cookies');

$harvest->cookies->set(
    name: 'session',
    value: 'abc123',
    domain: 'example.com',
    path: '/',
    expires: '1 hour',
    httpOnly: true,
    secure: true,
    sameSite: 'Strict'
);
```

### 9.4 Custom Middleware with Priority

```php
$profile = new Profile()
    ->add('Cors', priority: 5, group: 'Generic')
    ->add(new CustomMiddleware(), priority: 10, group: 'Inbound');
```

---

## 10. Implementation Notes (For Contributors)

### 10.1 Internal Architecture

At a high level, Harvest:

- Uses PHP Fibers to flatten the middleware call stack while maintaining exception propagation semantics
- Implements a dual-level sorting system: first by middleware group (enum), then by priority (integer)
- Stages wrap middleware in a uniform interface (Closure, Instance, or Deferred) for consistent execution
- The Dispatcher maintains a stack of Fiber instances, resuming and yielding to simulate traditional call stack behavior
- ResponseHandler handles HTTP protocol details like range requests, protocol version propagation, and sendfile optimization

Contributors should:

- Preserve the Fiber-based dispatch semantics to maintain flat call stacks
- Maintain PSR-15 compliance in all public interfaces
- Use Exceptional for all error conditions
- Keep middleware resolution via Slingshot for dependency injection support
- Ensure immutable request/response objects throughout

### 10.2 Performance Considerations

- Fiber-based dispatch reduces call stack depth, improving memory usage and debugging clarity
- Middleware sorting is lazy (only when Profile is accessed) to avoid unnecessary computation
- ResponseHandler uses chunked streaming for large responses to manage memory
- Sendfile header detection allows web servers to handle file serving directly
- Range request support enables efficient partial content delivery

### 10.3 Gotchas & Historical Decisions

- **Fiber usage:** The Fiber implementation was chosen to solve call stack depth issues while maintaining exception semantics. The implementation uses Fiber::resume() and Fiber::throw() to simulate traditional call stack behavior.
- **Middleware groups:** The five-group system (ErrorHandler, Inbound, Outbound, Generic, Generator) provides clear separation of concerns but requires middleware authors to understand the grouping system.
- **Stage abstraction:** The Stage system (Closure, Instance, Deferred) allows flexible middleware definition but adds a layer of indirection that may be confusing initially.

---

## 11. Testing & Quality

### 11.1 Testing Strategy

Tests should cover:

- Core middleware dispatch functionality with various middleware types
- Fiber-based exception propagation through the stack
- Middleware group and priority sorting
- Request/response immutability
- Cookie collection merging into responses
- ResponseHandler range request handling
- Transport implementations
- Edge cases like empty profiles, corrupted stacks, and invalid middleware

### 11.2 Quality Signals

From the Decode Labs package index (at time of writing):

- **Code:** 4.5
- **Readme:** 3
- **Docs:** 0
- **Tests:** 0

Harvest is a mature, production-ready package with high code quality. The README provides good usage examples, though comprehensive documentation (this spec) was not present at indexing time. Test coverage is planned but not yet implemented.

---

## 12. Roadmap & Future Ideas

Non-binding ideas:

- Comprehensive test suite covering all middleware dispatch scenarios
- Additional built-in middleware implementations (rate limiting, authentication helpers)
- WebSocket support via Transport extensions
- Performance profiling middleware
- Request/response transformation pipeline
- Enhanced cookie security features (SameSite, Partitioned cookie support improvements)

---

## 13. References

- **Chorus docs:**
  - Architecture principles
  - Package taxonomy & clusters
  - Backwards compatibility strategy (once published)

- **Related packages:**
  - `decodelabs/greenleaf` (uses Harvest for routing dispatch)
  - `decodelabs/fabric` (uses Harvest as HTTP layer)
  - `decodelabs/singularity` (URI parsing for requests)
  - `decodelabs/slingshot` (middleware dependency injection)
  - `decodelabs/sanctum` (optional CSP middleware integration)

- **Standards:**
  - PSR-7: HTTP message interfaces
  - PSR-15: HTTP server request handlers and middleware
  - PSR-17: HTTP factories

- **Repository:**
  - `https://github.com/decodelabs/harvest`

---

> This spec is intended to stay in sync with the **actual behaviour** of the package.
> When you make significant changes to the public surface or semantics, please update this document and, where applicable, add or update ADRs in Chorus.

