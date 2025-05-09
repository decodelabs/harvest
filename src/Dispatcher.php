<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Stage\Closure as ClosureStage;
use Fiber;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Throwable;

class Dispatcher implements PsrHandler
{
    public function __construct(
        private Profile $profile
    ) {}

    /**
     * Begin stage stack navigation
     */
    public function handle(
        PsrRequest $request
    ): PsrResponse {
        if ($this->profile->isEmpty()) {
            throw Exceptional::Setup(
                message: 'No middleware stack has been set'
            );
        }


        $stages = $this->profile->toList();

        $stages[] = new ClosureStage(function (
            PsrRequest $request,
            PsrHandler $handler
        ) {
            throw Exceptional::NotFound(
                message: 'No middleware could handle the current request',
                http: 404
            );
        });

        $stack = [];
        $pos = -1;
        $shift = 1;
        $response = null;

        while (
            $pos + $shift >= 0 &&
            isset($stages[$pos + $shift])
        ) {
            $e = null;
            $pos += $shift;

            if ($shift > 0) {
                $stage = $stages[$pos];

                $stack[$pos] = [
                    'stage' => $stage,
                    'fiber' => $fiber = new Fiber([$stage, 'run'])
                ];
            } else {
                [
                    'stage' => $stage,
                    'fiber' => $fiber
                ] = $stack[$pos];
            }

            /**
             * @var Fiber<PsrRequest,PsrResponse,PsrResponse,PsrRequest> $fiber
             */

            try {
                if ($fiber->isSuspended()) {
                    $request = $fiber->resume($response);
                } else {
                    $request = $fiber->start($request);
                }
            } catch (Throwable $e) {
                $request = null;
                array_pop($stack);
                $pos--;

                if (empty($stack)) {
                    throw $e;
                }

                while (!empty($stack)) {
                    [
                        'stage' => $stage,
                        'fiber' => $fiber
                    ] = $stack[$pos];


                    try {
                        $request = $fiber->throw($e);
                        break;
                    } catch (Throwable $e) {
                        if ($pos === 0) {
                            throw $e;
                        }
                    }

                    array_pop($stack);
                    $pos--;
                }
            }

            if ($request) {
                $shift = 1;

                if (!isset($stages[$pos + $shift])) {
                    throw Exceptional::NotFound(
                        message: 'No middleware could handle the current request',
                        http: 404
                    );
                }

                continue;
            }


            if (!$fiber->isTerminated()) {
                throw Exceptional::Runtime(
                    message: 'Middleware stack has been corrupted'
                );
            }

            $response = $fiber->getReturn();
            array_pop($stack);
            $shift = -1;

            if (empty($stack)) {
                break;
            }
        }

        if (!$response instanceof PsrResponse) {
            throw Exceptional::Runtime(
                message: 'Middleware stack has been corrupted'
            );
        }

        return $response;
    }
}
