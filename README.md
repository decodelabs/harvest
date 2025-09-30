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

This package requires PHP 8.4 or higher.

Install via Composer:

```bash
composer require decodelabs/harvest
```

## Usage

Harvest provides the full PSR-15 stack, including Request, Response, Middleware and Handler interfaces.
The root of the system is the `Dispatcher` which takes a `Request` and a Middleware `Profile` and returns a `Response`.

The `Profile` defines what Middleware is used to process the request and how it is ordered.

```php
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Middleware\ContentSecurityPolicy;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Harvest\Response\Text as TextResponse;
use DecodeLabs\Monarch;

// Create a Middleware Profile
$profile = new Profile(
    'ErrorHandler', // Resolve by name via container / Archetype

    new ContentSecurityPolicy(), // Add middleware instance

    function($request, $handler) {
        // Add middleware callback
        // $handler is the next middleware in the stack
        // $request is the current request

        // Return a response
        return new TextResponse('Hello World!');
    }
);

// Create a Dispatcher
$dispatcher = new Dispatcher($profile);
$harvest = Monarch::getService(Harvest::class);

$request = $harvest->createRequestFromEnvironment();
$response = $dispatcher->dispatch($request);
```

String names passed to a `Profile` will resolve via `Slingshot`, allowing for easy dependency injection and container resolution.

#### Ordering

Middleware is sorted by a dual level priority system, first grouped by the _intent_ of the Middleware, in this order:

- **ErrorHandler** - catches and handles errors within the stack
- **Inbound** - processes the request before it is passed to the next Middleware
- **Outbound** - processes the response before it is sent to the client
- **Generic** - generic Middleware that does not fit into the above categories
- **Generator** - generates or loads the primary response content

Then each group is sorted by the _priority_ of the Middleware, with lower numbers being higher in the group. Harvest Middleware implement an extension to the Psr Middleware interface, defining defaults for group and priority.

These can be overridden when defining your `Profile`:

```php
use DecodeLabs\Harvest\Profile;

$profile = new Profile()
    ->add('Cors', priority: 5, group: 'Generic')
    ->add(new SomeOtherVendorMiddleware(), priority: 10, group: 'Inbound');
```


### Fibers

Harvest uses PHP Fibers to _flatten_ the call stack within the dispatch loop - this makes for considerably less _noise_ when debugging and understanding Exception call stacks.

Instead of a call stack that grows by at least 2 frames for every Middleware instance in the queue (which gets unwieldy very quickly), Harvest utilises the flexbility of Fibers to break out of the stack at each call to the _next_ HTTP handler and effectively run each Middleware as if it were in a flat list, but without breaking Exception handling or any of the semantics of stacking the Middleware contexts.


### ResponseHandler and Transports

Once a Response has been generated, you can then use an instance of a Harvest `ResponseHandler` and `Transport` to prepare and send it to the client.

Harvest currently provides a generic `Native` Transport implementation that uses PHP's built in header and output stream functions.

```php
use DecodeLabs\Harvest\ResponseHandler;
use DecodeLabs\Harvest\Transport\Native as NativeTransport;

$handler = new ResponseHandler(
    new NativeTransport()
);

$handler->sendResponse(
    $request,
    $response
);

exit;
```

### Responses

Harvest provides easy shortcuts for creating Responses:

```php
use DecodeLabs\Harvest\Response\Text as TextResponse;
use DecodeLabs\Harvest\Response\Html as HtmlResponse;
use DecodeLabs\Harvest\Response\Json as JsonResponse;
use DecodeLabs\Harvest\Response\Xml as XmlResponse;
use DecodeLabs\Harvest\Response\Redirect as RedirectResponse;
use DecodeLabs\Harvest\Response\Stream as StreamResponse;
use DecodeLabs\Harvest\Response\Generator as GeneratorResponse;

$text = new TextResponse('Hello World!'); // Text

$customText = new TextResponse('Hello World!', 201, [
    'Custom-Header' => 'header-value'
]);

$html = new HtmlResponse('<h1>Hello World!</h1>'); // HTML

$json = new JsonResponse([
    'whatever-data' => 'Hello World!'
]); // JSON

$xml = new XmlResponse($xmlString); // XML

$redirect = new RedirectResponse('/some/other/path'); // Redirect

$file = new StreamResponse('/path/to/file'); // Stream

$resource = new StreamResponse(
    $harvest->createStreamFromResource($resource)
); // Stream

$generator = new GeneratorResponse(function() {
    yield 'Custom content';
    yield ' can be written';
    yield ' and streamed';
    yield ' from a generator';
}, 200, [
    'Content-Type' => 'text/plain'
]);
```


### Cookies

Harvest provides a `Cookies` Middleware and a global `Cookie Collection` that allows you to define request-level cookies separately from the response generation process and merges them into the response. Just make sure the Cookie Middleware is added to your `Profile`.


```php
$profile->add('Cookies');

$harvest->cookies->set(
    name: 'cookie-name',
    value: 'cookie-value',
    domain: 'example.com',
    path: '/',
    expires: '10 minutes',
    maxAge: 600,
    httpOnly: true,
    secure: true,
    sameSite: 'Strict',
    partitioned: true
);

$harvest->cookies->expire(
    name: 'cookie-name',
    domain: 'example.com',
    path: '/',
);
```


## Licensing

Harvest is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
