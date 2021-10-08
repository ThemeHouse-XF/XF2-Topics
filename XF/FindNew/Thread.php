<?php

namespace ThemeHouse\Topics\XF\FindNew;

use ThemeHouse\Topics\Pub\Controller\WhatsNewThread;
use ThemeHouse\Topics\Repository\Topic;
use XF\Entity\FindNew;
use XF\Mvc\Controller;
use XF\Mvc\Reply\View;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\FindNew
 */
class Thread extends XFCP_Thread
{
    /**
     * @var array
     */
    protected $order = [];
    /**
     * @var string
     */
    protected $route = '';

    /**
     * @return string
     */
    public function getRoute()
    {
        if ($this->route) {
            return $this->route;
        }

        return parent::getRoute();
    }

    /**
     * @param $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param Controller $controller
     * @param FindNew $findNew
     * @param array $results
     * @param $page
     * @param $perPage
     * @return View
     */
    public function getPageReply(Controller $controller, FindNew $findNew, array $results, $page, $perPage)
    {
        $reply = parent::getPageReply($controller, $findNew, $results, $page, $perPage);

        if ($reply instanceof View && $reply->getParam('findNew')) {
            if ($controller instanceof WhatsNewThread) {
                $reply->setViewClass('ThemeHouse\Topics:WhatsNew\Threads');
                $reply->setTemplateName('thtopics_whats_new_threads');
                $route = 'whats-new/threads';
            } else {
                $route = 'whats-new/posts';
            }

            /** @var Topic $topicRepo */
            $topicRepo = \XF::repository('ThemeHouse\Topics:Topic');
            /** @noinspection PhpUndefinedMethodInspection */
            $reply->setJsonParam(
                'href',
                $topicRepo->getTopicFilterUrl($route)
            );

            if ($reply->getParam('threads') && \XF::options()->thtopics_enableTopics) {
                $topicRepo->hydrateTopicsForThreads($reply->getParam('threads'));
            }
        }

        return $reply;
    }

    /**
     * @param $order
     * @param $direction
     */
    public function setOrderBy($order, $direction)
    {
        $this->order = [$order, $direction];
    }

    /**
     * @param \XF\Finder\Thread $threadFinder
     * @param array $filters
     */
    protected function applyFilters(\XF\Finder\Thread $threadFinder, array $filters)
    {
        /** @var \ThemeHouse\Topics\XF\Finder\Thread $threadFinder */
        parent::applyFilters($threadFinder, $filters);

        if ($this->order) {
            $threadFinder->resetOrder();
            $threadFinder->order($this->order);
        }

        if (\XF::options()->thtopics_topicFilterWhatsNewPages) {
            $threadFinder->filterByTopicsAndForums();
        }
    }
}
