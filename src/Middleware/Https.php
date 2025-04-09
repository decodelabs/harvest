<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest;
use DecodeLabs\Monarch;
use DecodeLabs\Harvest\PriorityProvider;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Https implements
    Middleware,
    PriorityProvider
{
    /**
     * Get default priority
     */
    public function getPriority(): int
    {
        return -1;
    }

    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $production = Monarch::isProduction();

        // Check for HTTPS
        if (
            $production &&
            $request->getUri()->getScheme() !== 'https'
        ) {
            $url = $request->getUri()
                ->withScheme('https')
                ->withPort(null);

            return Harvest::redirect($url);
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
