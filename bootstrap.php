<?php

use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use UrGuardian4ngel\FacebookCrawler\Listener\RequestListener;

return function (Application $app, Dispatcher $events) {
    // Register extension view root directory.
    $app['view']->addNamespace('uga.fb_crawler', __DIR__.'/views');

    // Register middleware to handle Facebook Crawler requests.
    $events->subscribe(RequestListener::class);
};
