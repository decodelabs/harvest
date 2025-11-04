<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Kingdom\Runtime;

use DecodeLabs\Harvest as HarvestService;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Harvest\ResponseHandler;
use DecodeLabs\Kingdom\Runtime;
use DecodeLabs\Kingdom\RuntimeMode;

class Harvest implements Runtime
{
    public RuntimeMode $mode {
        get => RuntimeMode::Http;
    }

    protected Dispatcher $dispatcher;
    protected Request $request;

    public function __construct(
        protected Profile $profile,
        protected HarvestService $harvest
    ) {
        $this->dispatcher = new Dispatcher($this->profile);
    }

    public function initialize(): void
    {
        $this->request = $this->harvest->createRequestFromEnvironment();
    }

    public function run(): void
    {
        $response = $this->dispatcher->handle($this->request);

        $handler = new ResponseHandler();
        $handler->sendResponse($this->request, $response);
    }

    public function shutdown(): never
    {
        exit;
    }
}
