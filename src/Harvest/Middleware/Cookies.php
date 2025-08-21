<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Cookie\Collection as CookieCollection;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class Cookies implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::Outbound;
    }

    public int $priority {
        get => -100;
    }

    public function __construct(
        protected Harvest $harvest
    ) {
    }

    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $response = $next->handle($request);

        if (!$this->harvest->cookies->isEmpty()) {
            if ($response->hasHeader('Set-Cookie')) {
                $collection = CookieCollection::from(
                    $response->getHeader('Set-Cookie')
                );

                $collection->merge($this->harvest->cookies);
            } else {
                $collection = $this->harvest->cookies;
            }

            $response = $response->withHeader(
                'Set-Cookie',
                $collection->toStringArray()
            );
        }

        return $response;
    }
}
