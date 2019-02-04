<?php

namespace Rhubarb\RestApi;

use Slim\App;

interface RhubarbApiModule
{
    public function registerErrorHandlers(App $app);

    public function registerMiddleware(App $app);

    public function registerRoutes(App $app);
}
