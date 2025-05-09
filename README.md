# Harvest

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/harvest?style=flat)](https://packagist.org/packages/decodelabs/harvest)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/harvest.svg?style=flat)](https://packagist.org/packages/decodelabs/harvest)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/harvest.svg?style=flat)](https://packagist.org/packages/decodelabs/harvest)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/harvest/integrate.yml?branch=develop)](https://github.com/decodelabs/harvest/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/harvest?style=flat)](https://packagist.org/packages/decodelabs/harvest)

### PSR-15 HTTP stack without the mess

Harvest provides a unified PSR-15 HTTP stack with a simple, expressive API on top of PHP Fibers to avoid common pitfalls of other PSR-15 implementations such as call stack size, memory usage and Middleware traversal.

---

## Installation

Install via Composer:

```bash
composer require decodelabs/harvest
```

## Usage

Harvest provides the full PSR-15 stack, including Request, Response, Middleware and Handler interfaces.

```php
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Middleware\ContentSecurityPolicy;
use DecodeLabs\Harvest\Profile;

// Create a Middleware Profile
$profile = new Profile(
    'ErrorHandler', // Resolve by name via container / Archetype

    new ContentSecurityPolicy(), // Add middleware instance

    function($request, $handler) {
        // Add middleware callback
        // $handler is the next middleware in the stack
        // $request is the current request

        // Return a response
        return Harvest::text('Hello World!');
    }
);

// Create a Dispatcher
$dispatcher = new Dispatcher($profile);

$request = Harvest::createRequestFromEnvironment();
$response = $dispatcher->dispatch($request);
```

String names passed to the Dispatcher will resolve via the optional PSR Container and then Archetype which has a default mapping for <code>DecodeLabs\Harvest\Middleware</code> but can easily be extended with:

```php
use DecodeLabs\Archetype;
use DecodeLabs\Harvest\Middleware;

Archetype::map(Middleware::class, MyMiddlewareNamespace::class);
```

### Fibers

Harvest uses PHP Fibers to _flatten_ the call stack within the dispatch loop - this makes for considerably less _noise_ when debugging and understanding Exception call stacks.

Instead of a call stack that grows by at least 2 frames for every Middleware instance in the queue (which gets problematic very quickly), Harvest utilises the flexbility of Fibers to break out of the stack at each call to the _next_ HTTP handler and effectively run each Middleware as if it were in a flat list, but without breaking Exception handling or any of the semantics of stacking the Middleware contexts.

### Transports

Once a Response has been generated, you can then use an instance of a Harvest <code>Transport</code> to send it to the client.

Harvest currently provides a Generic Transport implementation that uses PHP's built in header and output stream functions.

```php
use DecodeLabs\Harvest;

$transport = Harvest::createTransport(
    // $name - a null name will default to the Generic transport
);

$transport->sendResponse(
    $request, $response
);

exit;
```

### Responses

Harvest provides easy shortcuts for creating Response instances:

```php
use DecodeLabs\Harvest;

$text = Harvest::text('Hello World!'); // Text

$customText = Harvest::text('Hello World!', 201, [
    'Custom-Header' => 'header-value'
]);

$html = Harvest::html('<h1>Hello World!</h1>'); // HTML

$json = Harvest::json([
    'whatever-data' => 'Hello World!'
]); // JSON

$xml = Harvest::xml($xmlString); // XML

$redirect = Harvest::redirect('/some/other/path'); // Redirect

$file = Harvest::stream('/path/to/file'); // Stream

$resource = Harvest::stream(Harvest::createStreamFromResource($resource)); // Stream

$generator = Harvest::generator(function() {
    yield 'Custom content';
    yield ' can be written';
    yield ' from a generator';
}, 200, [
    'Content-Type' => 'text/plain'
]);
```

## Licensing

Harvest is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
