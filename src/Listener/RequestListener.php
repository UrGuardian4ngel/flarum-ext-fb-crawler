<?php

namespace UrGuardian4ngel\FacebookCrawler\Listener;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

use UrGuardian4ngel\FacebookCrawler\Middleware\RespondWithOpenGraphTags;

class RequestListener
{
    /**
     * @var \Flarum\Foundation\Application $app
     */
    private $app;

    /**
     * @param \Flarum\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureMiddleware::class, [$this, 'configureMiddleware']);
    }

    /**
     * @param ConfigureMiddleware $event
     */
    public function configureMiddleware(ConfigureMiddleware $event)
    {
        if (false === $event->isForum()) {
            return;
        }

        $middleware = $this->app->make(RespondWithOpenGraphTags::class);
        $event->pipe($middleware);
    }
}
