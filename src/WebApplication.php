<?php

namespace framework\web;

use framework\Application;
use framework\web\routing\Router;
use Override;

/**
 * The base class for all applications.
 * It supports component binding
 * and singleton.
 * 
 * Known Components
 * @property components\UrlManager $url URL manager component
 * @property components\AssetManager $assets Asset manager component
 * @property components\WidgetManager $widgets Widget manager component
 * @property Router $router
 */
class WebApplication extends Application
{
    public string $route;
    public string $method;

    /**
     * Private constructor to enforce singleton
     */
    private function __construct($route, $method)
    {
        $this->route = $route;
        $this->method = $method;
    }

    public function init()
    {
        parent::init();
        \framework\models\Model::registerTypeTransformer(\framework\web\request\UploadedFile::class, new \framework\web\transformers\UploadedFileTransformer());
    }

    public function run()
    {
        $executor = new Executor($this->router);

        $executor->execute($this->url->path(), $this->method);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(string $route, string $method): WebApplication
    {
        if (static::$instance === null) {
            static::$instance = new static($route, $method);
        }

        return static::$instance;
    }

    public static function flushInstance()
    {
        static::$instance = null;
    }

    #[Override]
    public function registerRoutes(string $dir): void
    {
        $path = $dir . DIRECTORY_SEPARATOR . 'routes.php';
        $app = $this;

        require_once $path;

        if (isset($router)) {
            $this->router->mount('/', $router);
        }
    }

    #[Override]
    public function registerResources(string $namespace, string $dir): void
    {
        $this->config->set("paths.$namespace", $dir);
    }
}