<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class Cors implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::Outbound;
    }

    public int $priority {
        get => -1;
    }


    /**
     * @var array<string>
     */
    protected array $allow = [];

    /**
     * @var array<string>
     */
    protected array $headers = [];

    /**
     * Init with allow list
     *
     * @param array<string> $allow
     * @param array<string> $headers
     */
    public function __construct(
        array $allow = [],
        array $headers = []
    ) {
        $this->allow = $allow;
        $this->headers = $headers;
    }


    /**
     * Process middleware
     */
    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $response = $next->handle($request);
        $response = $this->applyOrigin($request, $response);

        if (
            $request->hasHeader('Access-Control-Request-Method') &&
            !$response->hasHeader('Access-Control-Allow-Headers')
        ) {
            if (empty($this->headers)) {
                $headers = '*';
            } else {
                $headers = implode(', ', $this->headers);
            }

            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                $headers
            );
        }

        return $response;
    }


    protected function applyOrigin(
        PsrRequest $request,
        PsrResponse $response
    ): PsrResponse {
        // Check header
        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            return $response;
        }

        // Check origin
        $allow = false;
        $origin = $request->getHeaderLine('Origin');

        if (empty($origin)) {
            return $response;
        }

        if (empty($this->allow)) {
            $allow = true;
        } else {
            foreach ($this->allow as $allow) {
                if ($allow === '*') {
                    $allow = true;
                    break;
                }

                if ($allow === $origin) {
                    $allow = true;
                    break;
                }
            }
        }

        // Add header
        if ($allow) {
            $response = $response->withHeader(
                'Access-Control-Allow-Origin',
                $origin
            );

            $response = $response->withHeader('Vary', 'Origin');
        }

        return $response;
    }
}
