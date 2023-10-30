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

class Harvest implements Proxy
{
    use ProxyTrait;

    const VENEER = 'DecodeLabs\\Harvest';
    const VENEER_TARGET = Inst::class;

    public static Inst $instance;

    public static function createUri(string $uri = ''): Ref0 {
        return static::$instance->createUri(...func_get_args());
    }
    public static function createStream(string $content = ''): Ref1 {
        return static::$instance->createStream(...func_get_args());
    }
    public static function createStreamFromFile(Ref2|string $filename, string $mode = 'r'): Ref1 {
        return static::$instance->createStreamFromFile(...func_get_args());
    }
    public static function createStreamFromResource($resource): Ref1 {
        return static::$instance->createStreamFromResource(...func_get_args());
    }
    public static function createRequestFromEnvironment(?string $method = NULL, $uri = NULL, ?array $server = NULL): Ref3 {
        return static::$instance->createRequestFromEnvironment(...func_get_args());
    }
};
