<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use DecodeLabs\Harvest\Response\Redirect as RedirectResponse;
use DecodeLabs\Monarch;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class Https implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::Inbound;
    }

    public int $priority {
        get => -1;
    }

    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $production = Monarch::isProduction();

        // Check for HTTPS
        if (
            $production &&
            $request->getUri()->getScheme() !== 'https'
        ) {
            $url = $request->getUri()
                ->withScheme('https')
                ->withPort(null);

            return new RedirectResponse($url);
        }


        // Continue
        $response = $next->handle($request);


        // HSTS
        if ($production) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
