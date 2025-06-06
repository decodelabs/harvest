<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Carbon\CarbonImmutable as Carbon;
use DateTimeInterface;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Cookie\ParameterTrait;
use DecodeLabs\Harvest\Cookie\SameSite;
use DecodeLabs\Nuance\Dumpable;
use DecodeLabs\Nuance\Entity\NativeObject as NuanceEntity;
use Stringable;

class Cookie implements
    Stringable,
    Dumpable
{
    use ParameterTrait;

    public string $name {
        set {
            if(!preg_match('/^[!#$%&x\'*+\-.^_`|~0-9A-Za-z]+$/', $value)) {
                throw Exceptional::InvalidArgument(
                    'Invalid cookie name'
                );
            }

            $this->name = $value;
        }
    }

    public string $value {
        set {
            if(preg_match('/[\x00-\x1F\x7F]/', $value)) {
                throw Exceptional::InvalidArgument(
                    'Cookie value cannot contain control characters'
                );
            }

            $this->value = (string)preg_replace_callback(
                '/[\s",;\\\\\'()<>@,;:\\"\/\[\]?={}\t]/',
                fn($v) => rawurlencode($v[0]),
                $value
            );
        }
    }





    public static function parse(
        string $cookie
    ): self {
        $parts = preg_split('/(?<!\\\\);(?=(?:[^"]*"[^"]*")*[^"]*$)/', $cookie);

        if($parts === false) {
            throw Exceptional::InvalidArgument(
                'Invalid cookie string'
            );
        }

        $main = explode('=', trim((string)array_shift($parts)), 2);

        $output = new self(
            name: array_shift($main),
            value: (string)preg_replace('/^"(.*)"$/', '$1', (string)array_shift($main))
        );

        foreach ($parts as $part) {
            $set = explode('=', trim((string)$part), 2);
            $key = strtolower((string)array_shift($set));
            $value = trim((string)array_shift($set));

            switch ($key) {
                case 'max-age':
                    $output->maxAge = (int)$value;
                    break;

                case 'expires':
                    $output->expires = new Carbon($value);
                    break;

                case 'domain':
                    $output->domain = $value;
                    break;

                case 'path':
                    $output->path = $value;
                    break;

                case 'secure':
                    $output->secure = true;
                    break;

                case 'httponly':
                    $output->httpOnly = true;
                    break;

                case 'samesite':
                    $output->sameSite = SameSite::fromName($value);
                    break;

                case 'partitioned':
                    $output->partitioned = true;
                    break;
            }
        }

        return $output;
    }


    public function __construct(
        string $name,
        string $value,
        ?string $domain = null,
        ?string $path = null,
        string|DateTimeInterface|null $expires = null,
        ?int $maxAge = null,
        bool $secure = false,
        bool $httpOnly = false,
        string|SameSite|null $sameSite = null,
        bool $partitioned = false
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->domain = $domain;
        $this->path = $path;
        $this->httpOnly = $httpOnly;
        $this->secure = $secure;
        $this->maxAge = $maxAge;
        $this->sameSite = $sameSite;
        $this->partitioned = $partitioned;
    }


    public function expire(): void
    {
        $this->expires = null;
        $this->maxAge = 0;
    }

    public function decode(): string
    {
        return rawurldecode($this->value);
    }


    public function __toString(): string
    {
        $output = $this->name . '=' . $this->value;

        if($this->domain !== null) {
            $output .= '; Domain=' . $this->domain;
        }

        if($this->path !== null) {
            $output .= '; Path=' . $this->path;
        }

        if($this->expires !== null) {
            $output .= '; Expires=' . $this->expires->toRfc7231String();
        }

        if($this->maxAge !== null) {
            $output .= '; Max-Age=' . $this->maxAge;
        }

        if($this->secure) {
            $output .= '; Secure';
        }

        if($this->httpOnly) {
            $output .= '; HttpOnly';
        }

        if($this->sameSite !== null) {
            $output .= '; SameSite=' . $this->sameSite->getName();
        }

        if($this->partitioned) {
            $output .= '; Partitioned';
        }

        return $output;
    }

    public function toNuanceEntity(): NuanceEntity
    {
        $entity = new NuanceEntity($this);

        $entity->setProperty('name', $this->name);
        $entity->setProperty('value', $this->value);

        if ($this->domain !== null) {
            $entity->setProperty('domain', $this->domain);
        }

        if ($this->path !== null) {
            $entity->setProperty('path', $this->path);
        }

        if ($this->expires !== null) {
            $entity->setProperty('expires', $this->expires);
        }

        if ($this->maxAge !== null) {
            $entity->setProperty('maxAge', $this->maxAge);
        }

        if ($this->secure) {
            $entity->setProperty('secure', true);
        }

        if ($this->httpOnly) {
            $entity->setProperty('httpOnly', true);
        }

        if ($this->sameSite !== null) {
            $entity->setProperty('sameSite', $this->sameSite->getName());
        }

        if ($this->partitioned) {
            $entity->setProperty('partitioned', true);
        }

        return $entity;
    }

}
