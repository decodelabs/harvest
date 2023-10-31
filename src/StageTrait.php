<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Exceptional;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Throwable;

trait StageTrait
{
    protected ?Stage $next = null;

    /**
     * Process middleware
     */
    public function handle(Request $request): Response
    {
        return $this->getMiddleware()->process($request, $this->getNext());
    }

    /**
     * Get next stage
     */
    public function getNext(): Stage
    {
        if (!$this->next) {
            return new class() implements Stage {
                public function handle(Request $request): Response
                {
                    throw $this->getException();
                }

                public function getMiddleware(): Middleware
                {
                    throw $this->getException();
                }

                public function getNext(): Stage
                {
                    throw $this->getException();
                }

                public function count(): int
                {
                    return 0;
                }

                private function getException(): Throwable
                {
                    throw Exceptional::NotFound([
                        'message' => 'No middleware could handle the current request',
                        'http' => 404
                    ]);
                }
            };
        }

        return $this->next;
    }

    /**
     * Count stages
     */
    public function count(): int
    {
        if (!$this->next) {
            return 0;
        }

        return 1 + $this->next->count();
    }
}
