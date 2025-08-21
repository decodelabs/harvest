<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Transformer;

use Closure;
use DecodeLabs\Archetype;
use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Response\Html as HtmlResponse;
use DecodeLabs\Harvest\Response\Json as JsonResponse;
use DecodeLabs\Harvest\ResponseProxy;
use DecodeLabs\Harvest\Transformer;
use DecodeLabs\Tagged\Markup;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use ReflectionFunction;

/**
 * @implements Transformer<mixed>
 */
class Generic implements Transformer
{
    public function __construct(
        protected Archetype $archetype
    ) {
    }

    public function transform(
        ServerRequest $request,
        mixed $response
    ): PsrResponse {
        if ($response instanceof Closure) {
            $ref = new ReflectionFunction($response);

            if ($ref->getNumberOfParameters() > 0) {
                throw Exceptional::UnexpectedValue(
                    'Closure response must not accept any parameters'
                );
            }

            $response = $response();
        }

        if ($response instanceof PsrResponse) {
            return $response;
        }

        if ($response instanceof ResponseProxy) {
            return $response->toHttpResponse();
        }

        if (
            is_object($response) &&
            // @phpstan-ignore-next-line
            $response::class !== 'Generic'
        ) {
            $class = $this->archetype->tryResolve(Transformer::class, get_class($response));

            if ($class) {
                return new $class()->transform($request, $response);
            }
        }

        if ($response instanceof Markup) {
            return new HtmlResponse($response);
        }

        if (is_iterable($response)) {
            return new JsonResponse($response);
        }

        $response = Coercion::toString($response);
        return new HtmlResponse($response);
    }
}
