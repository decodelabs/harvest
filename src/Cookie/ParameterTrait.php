<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Cookie;

use Carbon\CarbonImmutable as Carbon;
use DateTimeInterface;
use DecodeLabs\Exceptional;

trait ParameterTrait
{
    public ?string $domain = null {
        set {
            if(
                $value !== null &&
                !preg_match('/^\.?(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $value)
            ) {
                throw Exceptional::InvalidArgument(
                    'Invalid cookie domain'
                );
            }

            $this->domain = $value;
        }
    }

    public ?string $path = null {
        set {
            if(
                $value !== null &&
                !preg_match('/^\/[\/\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]*$/', $value)
            ) {
                throw Exceptional::InvalidArgument(
                    'Invalid cookie path'
                );
            }

            $this->path = $value;
        }
    }

    public ?Carbon $expires = null {
        set(
            string|DateTimeInterface|null $value
        ) {
            if(is_string($value)) {
                $value = new Carbon($value);
            } elseif($value instanceof DateTimeInterface) {
                $value = Carbon::instance($value);
            }

            $this->expires = $value;
        }
    }

    public ?int $maxAge = null;

    public bool $secure = false;

    public bool $httpOnly = false;

    public ?SameSite $sameSite = null {
        set(
            string|SameSite|null $value
        ) {
            if(is_string($value)) {
                $value = SameSite::fromName($value);
            }

            if($value === SameSite::None) {
                $this->secure = true;
            }

            $this->sameSite = $value;
        }
    }

    public bool $partitioned = false {
        set {
            if($value) {
                $this->secure = true;
            }

            $this->partitioned = $value;
        }
    }
}
