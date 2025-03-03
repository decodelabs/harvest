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
use DecodeLabs\Deliverance\Channel\Stream as Ref5;
use DecodeLabs\Harvest\Response\Stream as Ref6;
use Stringable as Ref7;
use Generator as Ref8;
use Closure as Ref9;
use DecodeLabs\Harvest\Response\Text as Ref10;
use DecodeLabs\Harvest\Response\Html as Ref11;
use DecodeLabs\Harvest\Response\Json as Ref12;
use DecodeLabs\Harvest\Response\Xml as Ref13;
use Psr\Http\Message\UriInterface as Ref14;
use DecodeLabs\Harvest\Response\Redirect as Ref15;
use Traversable as Ref16;
use Psr\Http\Message\ServerRequestInterface as Ref17;
use DecodeLabs\Compass\Ip as Ref18;

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
    public static function stream(Ref5|Ref1|string $body = 'php://memory', int $status = 200, array $headers = []): Ref6 {
        return static::$_veneerInstance->stream(...func_get_args());
    }
    public static function text(Ref7|Ref8|Ref9|string $text, int $status = 200, array $headers = []): Ref10 {
        return static::$_veneerInstance->text(...func_get_args());
    }
    public static function html(Ref7|Ref8|Ref9|string $html, int $status = 200, array $headers = []): Ref11 {
        return static::$_veneerInstance->html(...func_get_args());
    }
    public static function json(mixed $data, int $status = 200, array $headers = []): Ref12 {
        return static::$_veneerInstance->json(...func_get_args());
    }
    public static function xml(Ref7|Ref8|Ref9|string $xml, int $status = 200, array $headers = []): Ref13 {
        return static::$_veneerInstance->xml(...func_get_args());
    }
    public static function redirect(Ref14|string $uri, int $status = 302, array $headers = []): Ref15 {
        return static::$_veneerInstance->redirect(...func_get_args());
    }
    public static function generator(Ref16|Ref9|array $iterator, int $status = 200, array $headers = []): Ref6 {
        return static::$_veneerInstance->generator(...func_get_args());
    }
    public static function liveGenerator(Ref16|Ref9|array $iterator, int $status = 200, array $headers = []): Ref6 {
        return static::$_veneerInstance->liveGenerator(...func_get_args());
    }
    public static function extractIpFromRequest(Ref17 $request): Ref18 {
        return static::$_veneerInstance->extractIpFromRequest(...func_get_args());
    }
};
