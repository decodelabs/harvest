<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Tests\NullArrayKeys;

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

function expectSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException('Unexpected null-key regression result: ' . var_export($actual, true));
    }
}

try {
    $response = new \DecodeLabs\Harvest\Response\Text((static function (): \Generator {
        yield null => 'discarded';
        yield '' => 'empty';
        yield 'named' => 'discarded';
        yield 'named' => ' named';
        yield '0' => ' zero';
        yield 0 => ' final';
    })());
    expectSame('empty named final', (string)$response->getBody());
    expectSame('plain', (string)(new \DecodeLabs\Harvest\Response\Text('plain'))->getBody());
    expectSame('', (string)(new \DecodeLabs\Harvest\Response\Text((static function (): \Generator { yield from []; })()))->getBody());
} finally {
    restore_error_handler();
}

echo "Null array-key regression checks passed.\n";
