<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;
use DecodeLabs\Nuance\Dumpable;
use DecodeLabs\Nuance\Entity\NativeObject as NuanceEntity;
use DecodeLabs\Slingshot;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use ReflectionProperty;

class Deferred implements
    Stage,
    Dumpable
{
    use StageTrait;

    public string $name {
        get => $this->type;
    }

    /**
     * @var string|class-string<PsrMiddleware>
     */
    protected string $type;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $parameters = null;

    protected bool $optional = false;


    public ?PsrMiddleware $middleware {
        get {
            $ref = new ReflectionProperty($this, 'middleware');

            if ($ref->isInitialized($this)) {
                return $this->middleware;
            }

            $slingshot = new Slingshot(
                parameters: $this->parameters ?? []
            );

            return $this->middleware = $slingshot->tryResolveNamedInstance(
                interface: PsrMiddleware::class,
                name: $this->type
            );
        }
    }



    /**
     * @param string|class-string<PsrMiddleware> $type
     * @param array<string,mixed>|null $parameters
     */
    public function __construct(
        string $type,
        ?array $parameters = null
    ) {
        $this->optional = str_starts_with($type, '?');
        $type = ltrim($type, '?');
        [$type, $priority] = explode(':', $type, 2) + [$type, null];

        $this->type = (string)$type;
        $this->parameters = $parameters;

        if (
            $priority !== null &&
            is_numeric($priority)
        ) {
            $this->priority = (int)$priority;
        }
    }


    public function toNuanceEntity(): NuanceEntity
    {
        $entity = new NuanceEntity($this);
        $entity->itemName = $this->group->name;

        $entity->setProperty('name', $this->name);
        $entity->setProperty('parameters', $this->parameters, 'protected');
        $entity->setProperty('optional', $this->optional, 'protected');

        $entity->meta['priority'] = $this->priority;
        return $entity;
    }
}
