<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest\PriorityProvider;
use DecodeLabs\Monarch;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Cors implements
    Middleware,
    PriorityProvider
{
    /**
     * @var array<string>
     */
    protected array $allow = [];

    /**
     * Init with allow list
     *
     * @param array<string> $allow
     */
    public function __construct(
        array $allow = []
    ) {
        $this->allow = $allow;
    }

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
        $response = $next->handle($request);
        $response = $this->applyOrigin($request, $response);

        if (!$response->hasHeader('Access-Control-Allow-Headers')) {
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                'Content-Type, Authorization, X-Requested-With'
            );
        }

        return $response;
    }


    protected function applyOrigin(
        Request $request,
        Response $response
    ): Response {
        // Check header
        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            return $response;
        }

        // Env mode
        $development = Monarch::isDevelopment();

        // Check origin
        $allow = false;
        $origin = $request->getHeaderLine('Origin');

        if (empty($origin)) {
            return $response;
        }

        if (empty($this->allow)) {
            if (!$development) {
                return $response;
            }

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
