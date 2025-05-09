<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Harvest;
use DecodeLabs\Monarch;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class DefaultHeaders implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::Outbound;
    }

    public int $priority {
        get => -9999;
    }

    /**
     * @var array<string,string>
     */
    protected(set) array $headers = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'no-referrer-when-downgrade'
    ];

    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        array $headers = []
    ) {
        foreach($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function setHeader(
        string $name,
        string $value
    ): void {
        $name = $this->normalizeName($name);
        $this->headers[$name] = $value;
    }

    public function removeHeader(
        string $name
    ): void {
        $name = $this->normalizeName($name);
        unset($this->headers[$name]);
    }

    private function normalizeName(
        string $name
    ): string {
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $response = $next->handle($request);

        foreach($this->headers as $key => $value) {
            if (!$response->hasHeader($key)) {
                $response = $response->withHeader($key, $value);
            }
        }

        return $response;
    }
}
