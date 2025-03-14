<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Harvest\Context as Inst;
use DecodeLabs\Singularity\Url as Ref0;
use Psr\Http\Message\StreamInterface as Ref1;
use DecodeLabs\Atlas\File as Ref2;
use DecodeLabs\Harvest\Request as Ref3;
use DecodeLabs\Harvest\Transport as Ref4;
use Psr\Http\Message\ServerRequestInterface as Ref5;
use Psr\Http\Message\ResponseInterface as Ref6;
use DecodeLabs\Deliverance\Channel\Stream as Ref7;
use DecodeLabs\Harvest\Response\Stream as Ref8;
use Stringable as Ref9;
use Generator as Ref10;
use Closure as Ref11;
use DecodeLabs\Harvest\Response\Text as Ref12;
use DecodeLabs\Harvest\Response\Html as Ref13;
use DecodeLabs\Harvest\Response\Json as Ref14;
use DecodeLabs\Harvest\Response\Xml as Ref15;
use Psr\Http\Message\UriInterface as Ref16;
use DecodeLabs\Harvest\Response\Redirect as Ref17;
use Traversable as Ref18;
use DecodeLabs\Compass\Ip as Ref19;

class Harvest implements Proxy
{
    use ProxyTrait;

    public const Veneer = 'DecodeLabs\\Harvest';
    public const VeneerTarget = Inst::class;

    protected static Inst $_veneerInstance;

    public static function createUri(string $uri = ''): Ref0 {
        return static::$_veneerInstance->createUri(...func_get_args());
    }
    public static function createStream(string $content = ''): Ref1 {
        return static::$_veneerInstance->createStream(...func_get_args());
    }
    public static function createStreamFromFile(Ref2|string $filename, string $mode = 'r'): Ref1 {
        return static::$_veneerInstance->createStreamFromFile(...func_get_args());
    }
    public static function createStreamFromResource($resource): Ref1 {
        return static::$_veneerInstance->createStreamFromResource(...func_get_args());
    }
    public static function createRequestFromEnvironment(?string $method = NULL, $uri = NULL, ?array $server = NULL): Ref3 {
        return static::$_veneerInstance->createRequestFromEnvironment(...func_get_args());
    }
    public static function createTransport(?string $name = NULL): Ref4 {
        return static::$_veneerInstance->createTransport(...func_get_args());
    }
    public static function transform(Ref5 $request, mixed $response): Ref6 {
        return static::$_veneerInstance->transform(...func_get_args());
    }
    public static function stream(Ref7|Ref1|string $body = 'php://memory', int $status = 200, array $headers = []): Ref8 {
        return static::$_veneerInstance->stream(...func_get_args());
    }
    public static function text(Ref9|Ref10|Ref11|string $text, int $status = 200, array $headers = []): Ref12 {
        return static::$_veneerInstance->text(...func_get_args());
    }
    public static function html(Ref9|Ref10|Ref11|string $html, int $status = 200, array $headers = []): Ref13 {
        return static::$_veneerInstance->html(...func_get_args());
    }
    public static function json(mixed $data, int $status = 200, array $headers = []): Ref14 {
        return static::$_veneerInstance->json(...func_get_args());
    }
    public static function xml(Ref9|Ref10|Ref11|string $xml, int $status = 200, array $headers = []): Ref15 {
        return static::$_veneerInstance->xml(...func_get_args());
    }
    public static function redirect(Ref16|string $uri, int $status = 302, array $headers = []): Ref17 {
        return static::$_veneerInstance->redirect(...func_get_args());
    }
    public static function generator(Ref18|Ref11|array $iterator, int $status = 200, array $headers = []): Ref8 {
        return static::$_veneerInstance->generator(...func_get_args());
    }
    public static function liveGenerator(Ref18|Ref11|array $iterator, int $status = 200, array $headers = []): Ref8 {
        return static::$_veneerInstance->liveGenerator(...func_get_args());
    }
    public static function extractIpFromRequest(Ref5 $request): Ref19 {
        return static::$_veneerInstance->extractIpFromRequest(...func_get_args());
    }
};
