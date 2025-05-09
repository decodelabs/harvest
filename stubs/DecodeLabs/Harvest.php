<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Harvest\Context as Inst;
use DecodeLabs\Harvest\Cookie\Collection as CookiesPlugin;
use DecodeLabs\Harvest\Profile as Ref0;
use DecodeLabs\Singularity\Url as Ref1;
use Psr\Http\Message\StreamInterface as Ref2;
use DecodeLabs\Atlas\File as Ref3;
use DecodeLabs\Harvest\Request as Ref4;
use DecodeLabs\Harvest\Transport as Ref5;
use Psr\Http\Message\ServerRequestInterface as Ref6;
use Psr\Http\Message\ResponseInterface as Ref7;
use DecodeLabs\Deliverance\Channel\Stream as Ref8;
use DecodeLabs\Harvest\Response\Stream as Ref9;
use Stringable as Ref10;
use Generator as Ref11;
use Closure as Ref12;
use DecodeLabs\Harvest\Response\Text as Ref13;
use DecodeLabs\Harvest\Response\Html as Ref14;
use DecodeLabs\Harvest\Response\Json as Ref15;
use DecodeLabs\Harvest\Response\Xml as Ref16;
use Psr\Http\Message\UriInterface as Ref17;
use DecodeLabs\Harvest\Response\Redirect as Ref18;
use Traversable as Ref19;
use DecodeLabs\Compass\Ip as Ref20;

class Harvest implements Proxy
{
    use ProxyTrait;

    public const Veneer = 'DecodeLabs\\Harvest';
    public const VeneerTarget = Inst::class;

    protected static Inst $_veneerInstance;
    public static CookiesPlugin $cookies;

    public static function loadDefaultProfile(): Ref0 {
        return static::$_veneerInstance->loadDefaultProfile();
    }
    public static function createUri(string $uri = ''): Ref1 {
        return static::$_veneerInstance->createUri(...func_get_args());
    }
    public static function createStream(string $content = ''): Ref2 {
        return static::$_veneerInstance->createStream(...func_get_args());
    }
    public static function createStreamFromFile(Ref3|string $filename, string $mode = 'r'): Ref2 {
        return static::$_veneerInstance->createStreamFromFile(...func_get_args());
    }
    public static function createStreamFromResource($resource): Ref2 {
        return static::$_veneerInstance->createStreamFromResource(...func_get_args());
    }
    public static function createRequestFromEnvironment(?string $method = NULL, $uri = NULL, ?array $server = NULL): Ref4 {
        return static::$_veneerInstance->createRequestFromEnvironment(...func_get_args());
    }
    public static function createTransport(?string $name = NULL): Ref5 {
        return static::$_veneerInstance->createTransport(...func_get_args());
    }
    public static function transform(Ref6 $request, mixed $response): Ref7 {
        return static::$_veneerInstance->transform(...func_get_args());
    }
    public static function stream(Ref8|Ref2|string $body = 'php://memory', int $status = 200, array $headers = []): Ref9 {
        return static::$_veneerInstance->stream(...func_get_args());
    }
    public static function text(Ref10|Ref11|Ref12|string $text, int $status = 200, array $headers = []): Ref13 {
        return static::$_veneerInstance->text(...func_get_args());
    }
    public static function html(Ref10|Ref11|Ref12|string $html, int $status = 200, array $headers = []): Ref14 {
        return static::$_veneerInstance->html(...func_get_args());
    }
    public static function json(mixed $data, int $status = 200, array $headers = []): Ref15 {
        return static::$_veneerInstance->json(...func_get_args());
    }
    public static function xml(Ref10|Ref11|Ref12|string $xml, int $status = 200, array $headers = []): Ref16 {
        return static::$_veneerInstance->xml(...func_get_args());
    }
    public static function redirect(Ref17|string $uri, int $status = 302, array $headers = []): Ref18 {
        return static::$_veneerInstance->redirect(...func_get_args());
    }
    public static function generator(Ref19|Ref12|array $iterator, int $status = 200, array $headers = []): Ref9 {
        return static::$_veneerInstance->generator(...func_get_args());
    }
    public static function liveGenerator(Ref19|Ref12|array $iterator, int $status = 200, array $headers = []): Ref9 {
        return static::$_veneerInstance->liveGenerator(...func_get_args());
    }
    public static function extractIpFromRequest(Ref6 $request): Ref20 {
        return static::$_veneerInstance->extractIpFromRequest(...func_get_args());
    }
};
