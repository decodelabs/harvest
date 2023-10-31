<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Request\Factory;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Message\UploadedFile;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Singularity\Url;
use DecodeLabs\Singularity\Url\Http as HttpUrl;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class Environment implements ServerRequestFactoryInterface
{
    /**
     * @param string|UriInterface $uri
     * @param array<string, mixed>|null $server
     */
    public function createServerRequest(
        ?string $method = null,
        $uri = null,
        ?array $server = null
    ): Request {
        $method ??= $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $server = $this->prepareServerData(array_merge($_SERVER, $server ?? []));
        $files = $this->prepareFiles($_FILES);
        $headers = $this->extractHeaders($server);
        $uri ??= $this->extractUri($server, $headers);

        if (array_key_exists('cookie', $headers)) {
            $cookies = $this->parseCookies($headers['cookie']);
        }

        return new Request(
            method: $method,
            uri: $uri,
            body: 'php://input',
            headers: $headers,
            cookies: $cookies ?? $_COOKIE,
            files: $files,
            server: $server,
            protocol: $this->extractProtocol($server)
        );
    }

    /**
     * Normalize $_SERVER or equivalent
     *
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    public function prepareServerData(
        array $server
    ): array {
        if (
            function_exists('apache_request_headers') &&
            false !== ($apache = apache_request_headers())
        ) {
            $apache = array_change_key_case($apache, CASE_LOWER);

            if (isset($apache['authorization'])) {
                $server['HTTP_AUTHORIZATION'] = $apache['authorization'];
            }
        }

        return $server;
    }

    /**
     * Normalize $_FILES or equivalent
     *
     * @param array<string, string|UploadedFileInterface|array<string, mixed>> $files
     * @return array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    public function prepareFiles(
        array $files
    ): array {
        $output = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $output[$key] = $value;
            } elseif (
                is_array($value) &&
                isset($value['tmp_name'])
            ) {
                if (is_array($value['tmp_name'])) {
                    /** @var array<string, string|UploadedFileInterface|array<string, mixed>> $value */
                    $output = array_merge($output, $this->normalizeNestedFiles($value));
                } else {
                    $output[$key] = $this->createUploadedFile($value);
                }
            } elseif (is_array($value)) {
                /** @var array<string, string|UploadedFileInterface|array<string, mixed>> $value */
                $output[$key] = $this->prepareFiles($value);
            } else {
                throw Exceptional::InvalidArgument(
                    'Invalid $_FILES array',
                    null,
                    $files
                );
            }
        }

        /** @var array<string, UploadedFileInterface|array<string, UploadedFileInterface>> $output */
        return $output;
    }

    /**
     * Create uploadFile object
     *
     * @param array<string, mixed> $file
     */
    public function createUploadedFile(
        array $file
    ): UploadedFileInterface {
        return new UploadedFile(
            Coercion::toString($file['tmp_name']),
            Coercion::toIntOrNull($file['size']),
            Coercion::toInt($file['error']),
            Coercion::toStringOrNull($file['name']),
            Coercion::toStringOrNull($file['type'])
        );
    }

    /**
     * Normalize nested files
     *
     * @param array<string, string|UploadedFileInterface|array<string, mixed>> $files
     * @return array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    protected function normalizeNestedFiles(
        array $files
    ): array {
        $output = [];

        foreach (array_keys(Coercion::toArray($files['tmp_name'])) as $key) {
            $output[Coercion::toString($key)] = $this->createUploadedFile([
                'tmp_name' => Coercion::toArray($files['tmp_name'])[$key],
                'size' => Coercion::toArray($files['size'])[$key],
                'error' => Coercion::toArray($files['error'])[$key],
                'name' => Coercion::toArray($files['name'])[$key],
                'type' => Coercion::toArray($files['type'])[$key]
            ]);
        }

        return $output;
    }


    /**
     * Prepare header list from $_SERVER
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    public function extractHeaders(
        array $server
    ): array {
        $headers = [];

        foreach ($server as $key => $value) {
            if (strpos($key, 'REDIRECT_') === 0) {
                $key = substr($key, 9);

                if (array_key_exists($key, $server)) {
                    continue;
                }
            }

            if (
                $value !== '' &&
                strpos($key, 'HTTP_') === 0
            ) {
                $name = strtr(strtolower(substr($key, 5)), '_', '-');
                $headers[$name] = Coercion::toString($value);
                continue;
            }

            if (
                $value !== '' &&
                strpos($key, 'CONTENT_') === 0
            ) {
                $name = 'content-' . strtolower(substr($key, 8));
                $headers[$name] = Coercion::toString($value);
                continue;
            }
        }

        return $headers;
    }


    /**
     * Prepare URI from env
     *
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     */
    public function extractUri(
        array $server,
        array $headers
    ): Url {
        [$host, $port] = $this->extractHostAndPort($server, $headers);
        $relative = $this->extractRelative($server);
        $parts = explode('#', $relative, 2);
        $relative = (string)array_shift($parts);
        $fragment = array_shift($parts);
        $parts = explode('?', $relative, 2);
        $path = array_shift($parts);
        $query = array_shift($parts);

        return new HttpUrl(
            scheme: $this->extractScheme($server, $headers),
            host: $host,
            port: $port,
            path: $path,
            query: $query,
            fragment: $fragment
        );
    }

    /**
     * Extract scheme from env
     *
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     */
    public function extractScheme(
        array $server,
        array $headers
    ): string {
        $output = 'http';
        $headers = array_change_key_case($headers, CASE_LOWER);

        if (
            ($server['HTTPS'] ?? 'off') !== 'off' ||
            ($headers['x-forwarded-proto'] ?? null) === 'https'
        ) {
            $output = 'https';
        }

        return $output;
    }

    /**
     * Extract host from env
     *
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     * @return array{string, ?int}
     */
    public function extractHostAndPort(
        array $server,
        array $headers
    ): array {
        if (
            isset($headers['host']) ||
            isset($headers['x-original-host'])
        ) {
            $host = $headers['host'] ?? $headers['x-original-host'];
            $port = null;

            if (preg_match('|\:(\d+)$|', $host, $matches)) {
                $host = substr($host, 0, -(strlen($matches[1]) + 1));
                $port = (int)$matches[1];
            }
        } elseif (
            !isset($server['SERVER_NAME']) &&
            isset($server['SERVER_ADDR'])
        ) {
            $host = $server['SERVER_ADDR'];
            $port = null;
        } else {
            $host = $server['SERVER_NAME'] ?? '';
            $port = $server['SERVER_PORT'] ?? null;
        }

        return [
            Coercion::toString($host),
            Coercion::toIntOrNull($port)
        ];
    }

    /**
     * Extract path, query and fragment from env
     *
     * @param array<string, mixed> $server
     */
    public function extractRelative(
        array $server
    ): string {
        $iisRewrite = $server['IIS_WasUrlRewritten'] ?? null;
        $unencoded = $server['UNENCODED_URL'] ?? null;

        if (
            $iisRewrite === '1' &&
            $unencoded !== null
        ) {
            return Coercion::toString($unencoded);
        }

        $output = Coercion::toStringOrNull(
            $server['HTTP_X_REWRITE_URL'] ??
            $server['HTTP_X_ORIGINAL_URL'] ??
            $server['REQUEST_URI'] ??
            null
        );

        if ($output !== null) {
            return (string)preg_replace('#^[^/:]+://[^/]+#', '', $output);
        }

        return Coercion::toString($server['ORIG_PATH_INFO'] ?? '/');
    }



    /**
     * Extract protocol from env
     *
     * @param array<string, mixed> $server
     */
    public function extractProtocol(
        array $server
    ): string {
        if (null === ($output = Coercion::toStringOrNull($server['SERVER_PROTOCOL'] ?? null))) {
            return 'http';
        }

        if (!preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $output, $matches)) {
            throw Exceptional::UnexpectedValue(
                'Unrecognized HTTP protocal version: ' . $output
            );
        }

        return $matches['version'];
    }


    /**
     * Convert cookie header to array
     *
     * @return array<string, string>
     */
    public function parseCookies(string $string): array
    {
        preg_match_all('(
            (?:^\\n?[ \t]*|;[ ])
            (?P<name>[!#$%&\'*+-.0-9A-Z^_`a-z|~]+)
            =
            (?P<DQUOTE>"?)
                (?P<value>[\x21\x23-\x2b\x2d-\x3a\x3c-\x5b\x5d-\x7e]*)
            (?P=DQUOTE)
            (?=\\n?[ \t]*$|;[ ])
        )x', $string, $matches, PREG_SET_ORDER);

        $cookies = [];

        foreach ($matches as $match) {
            $cookies[$match['name']] = urldecode($match['value']);
        }

        return $cookies;
    }
}
