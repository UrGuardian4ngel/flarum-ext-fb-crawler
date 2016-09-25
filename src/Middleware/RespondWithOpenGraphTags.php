<?php

namespace UrGuardian4ngel\FacebookCrawler\Middleware;

use Flarum\Core\Discussion;
use Flarum\Core\Repository\DiscussionRepository;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;

class RespondWithOpenGraphTags
{
    /** @var SettingsRepositoryInterface */
    private $settings;

    /** @var Factory */
    private $view;

    /** @var DiscussionRepository */
    private $discussions;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Factory $view
     * @param DiscussionRepository $discussions
     */
    public function __construct(SettingsRepositoryInterface $settings, Factory $view, DiscussionRepository $discussions)
    {
        $this->settings = $settings;
        $this->view = $view;
        $this->discussions = $discussions;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $out)
    {
        if (false === $this->supports($request)) {
            return $next = $out($request, $response);
        }

        try {
            $discussion = $this->getDiscussionFromRequest($request);
        } catch (ModelNotFoundException $e) {
            throw new RouteNotFoundException(null, 404, $e);
        }

        $discussionTitle = $discussion->getAttribute('title');
        $forumTitle = sprintf('%s - %s', $discussionTitle, $this->settings->get('forum_title'));
        $view = $this->view->make('uga.fb_crawler::og_tags')
            ->with('title', $forumTitle)
            ->with('ogTitle', $discussionTitle)
            ->with('discussion', $discussion->toArray());

        $html = $view->render();
        return new HtmlResponse($html);
    }

    /**
     * Check if this middleware should act on the given request.
     *
     * @param Request $request
     * @return bool
     */
    protected function supports(Request $request)
    {
        // @todo Check if Facebook crawler should be allowed in the settings repository.

        $userAgent = $request->getHeaderLine('User-Agent');
        if (false === $this->isFacebookCrawler($userAgent)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user agent is identified as the Facebook Crawler.
     *
     * @param string $userAgent
     * @return bool
     *
     * @link https://developers.facebook.com/docs/sharing/webmasters/crawler#identify
     */
    private function isFacebookCrawler($userAgent)
    {
        return (false !== strpos($userAgent, 'facebookexternalhit/'))
            || (false !== strpos($userAgent, 'Facebot'));
    }

    /**
     * Try to load a {@link Discussion} entity from the requested url.
     *
     * @param Request $request
     * @return Discussion
     * @throws ModelNotFoundException
     */
    private function getDiscussionFromRequest(Request $request)
    {
        if (false === preg_match('~^/d/(\d+)~', $request->getUri()->getPath(), $matches)) {
            throw new ModelNotFoundException();
        }

        $discussionId = (int) $matches[1];
        return $this->discussions->findOrFail($discussionId);
    }
}
