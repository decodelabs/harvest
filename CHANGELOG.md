## v0.4.8 (2025-04-04)
* Improved form data handling

## v0.4.7 (2025-04-02)
* Upgraded Tagged dependency
* Upgraded Collections dependency

## v0.4.6 (2025-03-25)
* Added Vary: Origin to CORS Middleware

## v0.4.5 (2025-03-14)
* Added Response Transformer structure

## v0.4.4 (2025-03-14)
* Added ResponseProxy interface

## v0.4.3 (2025-03-04)
* Improved origin handling in CORS Middleware
* Support optional middleware stages

## v0.4.2 (2025-03-03)
* Added closure and generator support to response factories

## v0.4.1 (2025-02-20)
* Upgraded Coercion dependency
* Upgraded Genesis dependency

## v0.4.0 (2025-02-16)
* Replaced accessors with property hooks
* Upgraded PHPStan to v2
* Tidied boolean logic
* Fixed Exceptional syntax
* Added PHP8.4 to CI workflow
* Made PHP8.4 minimum version

## v0.3.1 (2025-02-07)
* Fixed implicit nullable arguments
* Fixed Glitch Exception logging via Proxy
* Added @phpstan-require-implements constraints

## v0.3.0 (2024-08-21)
* Converted consts to protected PascalCase
* Updated Veneer dependency and Stub

## v0.2.23 (2024-08-09)
* Added Veneer stub
* Removed unneeded LazyLoad binding attribute

## v0.2.22 (2024-07-17)
* Updated Veneer dependency

## v0.2.21 (2024-05-07)
* Simplified JSON response format options

## v0.2.20 (2024-04-29)
* Fixed Veneer stubs in gitattributes

## v0.2.19 (2024-04-26)
* Aliased Psr types in Archetype

## v0.2.18 (2024-04-26)
* Updated Archetype dependency
* Updated dependency list

## v0.2.17 (2023-12-15)
* Allow number types in headers

## v0.2.16 (2023-12-12)
* Added dev OverrideMethod middleware

## v0.2.15 (2023-12-11)
* Fixed Dispatcher add() signature

## v0.2.14 (2023-12-11)
* Added message body getter helpers
* Improved JSON error handling

## v0.2.13 (2023-12-08)
* Added allowed headers to CORS Middleware
* Added JSON error response in ErrorHandler Middleware

## v0.2.12 (2023-12-08)
* Added CORS Middleware
* Added priority ordering to incoming Middleware

## v0.2.11 (2023-11-28)
* Improved ob_flush handling in transport

## v0.2.10 (2023-11-26)
* Added parameter support to deferred stages

## v0.2.9 (2023-11-25)
* Fixed Exception handling for last Middleware

## v0.2.8 (2023-11-24)
* Added universal PSR-17 Factory
* Added Client Request implementation

## v0.2.7 (2023-11-18)
* Added non-linear stream writer support to Generator responses with Fibers

## v0.2.6 (2023-11-18)
* Improved live generator responses

## v0.2.5 (2023-11-08)
* Fixed end-of-stack Exception handling

## v0.2.4 (2023-11-08)
* Added nominal Response interface

## v0.2.3 (2023-11-08)
* Fixed Exception stack handling in Dispatcher
* Added permanence methods to Redirect responses

## v0.2.2 (2023-11-07)
* Added HTTPS Middleware

## v0.2.1 (2023-11-02)
* Fixed Fiber stack Exception handling

## v0.2.0 (2023-11-02)
* Converted Dispatcher to use flat Fiber structure
* Added priority ordering to Stages

## v0.1.6 (2023-11-01)
* Fixed upload file nesting
* Clear URL Query in error handler Middleware

## v0.1.5 (2023-11-01)
* Added ContentSecgurityPolicy Middleware
* Added initial ErrorHandler implementation

## v0.1.4 (2023-11-01)
* Removed time limit when sending responses
* Added body HTML message to redirect response
* Improved Generator message type
* Added generator shortcut to Context

## v0.1.3 (2023-10-31)
* Fixed IP extract Request type

## v0.1.2 (2023-10-31)
* Fixed Deferred type parameter hint

## v0.1.1 (2023-10-31)
* Added IP support to Requests
* Improved environment variable handling
* Improved x-sendfile handling

## v0.1.0 (2023-10-30)
* Built initial implementation
