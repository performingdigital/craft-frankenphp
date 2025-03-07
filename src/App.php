<?php

namespace Performing\CraftFrankenPhp;

use Craft;
use craft\helpers\Cp;
use yii\web\UploadedFile;

final class App
{
    /**
     * Craft instance
     */
    private $instance;

    /**
     * Definitions of components to reset after each request
     */
    private $definitions;

    /**
     * Max number of requests before the application is reloaded
     */
    private $maxRequests;

    /**
     * Components to reset after each request
     */
    private $services = [
        "request",
        "cache",
        "urlManager",
        "response",
        "sites",
        "user",
        "users",
        "redis",
        "view"
    ];

    public function __construct()
    {
        // Load the Craft Application
        $this->instance = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

        // Saving components definitions to reload them later in each request
        $this->definitions = $this->instance->getComponents(true);

        // Get the max number of requests before the application is reloaded
        $this->maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
    }

    private function handler()
    {
        $this->instance->get('session')->open();

        foreach ($this->services as $id) {
            $this->instance->clear($id);
            $this->instance->set($id, $this->definitions[$id]);
        }

        // Fix @web alias because it will not be updated by the request component
        Craft::setAlias('@web', $this->instance->getRequest()->getHostInfo() . $this->instance->getRequest()->getBaseUrl());

        // Fix requestedSite static property because it will not be reset and the same site will be used each time
        Cp::clearRequestedSite();

        // Reset uploaded files component
        UploadedFile::reset();

        // Finally, run the application in a try catch to handle exceptions properly
        try {
            $this->instance->run();
        } catch (\Throwable $e) {
            $this->instance->get('errorHandler')->handleException($e);
        }

        // Close the session to avoid session before next request
        $this->instance->get('session')->close();
    }

    public function run()
    {
        for ($nbRequests = 0; !$this->maxRequests || $nbRequests < $this->maxRequests; ++$nbRequests) {
            $keepRunning = \frankenphp_handle_request($this->handler(...));

            // Reset VarDumper handler
            \Symfony\Component\VarDumper\VarDumper::setHandler(function ($var) {
                  $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
                  $cloner->addCasters(\Symfony\Component\VarDumper\Caster\ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
                  $this->instance->getDumper()->dump($cloner->cloneVar($var));
            });

            // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
            gc_collect_cycles();

            if (!$keepRunning) {
                    break;
            }
        }
    }
}
