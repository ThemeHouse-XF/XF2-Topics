<?php

namespace ThemeHouse\Topics\XF\Pub\Controller;

use ThemeHouse\Topics\Entity\Topic;
use XF\Entity\Draft;
use XF\Entity\FindNew;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;

/**
 * Class Forum
 * @package ThemeHouse\Topics\XF\Pub\Controller
 */
class Forum extends XFCP_Forum
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Reroute|View
     */
    public function actionIndex(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        if ($params->node_id || $params->node_name) {
            return $this->rerouteController('XF:Forum', 'Forum', $params);
        }

        if ($this->responseType == 'rss') {
            return $this->getForumRss();
        }

        switch ($this->options()->forumsDefaultPage) {
            case 'thtopic_topic_list':
                return $this->rerouteController('ThemeHouse\Topics:Topic', 'index');

            case 'thtopic_new_threads':
                return $this->rerouteController(__CLASS__, 'newthreads');

            case 'thtopic_all_threads':
                return $this->rerouteController(__CLASS__, 'allthreads');

            default:
                return parent::actionIndex($params);
        }
    }

    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionNewPosts(ParameterBag $params)
    {
        $reply = parent::actionNewPosts($params);

        if ($reply instanceof View && $reply->getParam('findNew')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $reply->setJsonParam(
                'href',
                \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl('forums/new-posts')
            );

            if ($reply->getParam('threads') && $this->options()->thtopics_enableTopics) {
                $this->getTopicRepo()->hydrateTopicsForThreads($reply->getParam('threads'), [], true);
            }
        }

        return $reply;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic
     */
    protected function getTopicRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:Topic');
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionNewThreads(ParameterBag $params)
    {
        if ($params['node_id'] || $params['node_name']) {
            $forum = $this->assertViewableForum($params['node_id'] ?: $params['node_name']);
            return $this->redirectPermanently($this->buildLink('forums', $forum));
        }

        if ($this->options()->forumsDefaultPage != 'thtopic_new_threads') {
            if ($this->options()->thtopics_showNewThreadsPage) {
                return $this->redirectPermanently($this->buildLink('whats-new/threads'));
            } else {
                return $this->notFound();
            }
        }

        /** @var \XF\ControllerPlugin\FindNew $findNewPlugin */
        $findNewPlugin = $this->plugin('XF:FindNew');

        /** @var \ThemeHouse\Topics\XF\FindNew\Thread $handler */
        $handler = $findNewPlugin->getFindNewHandler('thread');
        if (!$handler) {
            return $this->noPermission();
        }

        $handler->setOrderBy('post_date', 'DESC');

        $page = $this->filterPage();
        $perPage = $handler->getResultsPerPage();

        $findNewId = $this->filter('f', 'uint');

        if ($this->options()->forumsDefaultPage == 'thtopic_new_threads') {
            if (!$findNewId && $page == 1) {
                $selfRoute = 'forums';
            } else {
                $selfRoute = 'forums/new-threads';
            }
        } else {
            $selfRoute = 'forums/new-threads';
        }

        $this->assertCanonicalUrl($this->buildLink($selfRoute));

        $findNew = $findNewPlugin->getFindNewRecord($findNewId, 'thread');
        if (!$findNew) {
            $filters = $findNewPlugin->getRequestedFilters($handler);
            /** @var FindNew $findNew */
            $findNew = $findNewPlugin->runFindNewSearch($handler, $filters);

            if ($this->filter('save', 'bool') && $this->isPost()) {
                $findNewPlugin->saveDefaultFilters($handler, $filters);
            }

            if ($findNewPlugin->findNewRequiresSaving($findNew)) {
                $findNew->save();

                if ($this->isPost()) {
                    return $this->redirect($this->buildLink('forums/new-threads', null, [
                        'f' => $findNew->find_new_id
                    ]));
                }
            }

            $page = 1;
        } else {
            $remove = $this->filter('remove', 'str');
            if ($remove) {
                $filters = $findNew->filters;
                unset($filters[$remove]);

                /** @var FindNew $findNew */
                $findNew = $findNewPlugin->runFindNewSearch($handler, $filters);
                if ($findNewPlugin->findNewRequiresSaving($findNew)) {
                    $findNew->save();

                    return $this->redirect($this->buildLink('forums/new-threads', null, [
                        'f' => $findNew->find_new_id
                    ]));
                }
            }
        }

        if ($findNew->result_count) {
            $this->assertValidPage($page, $perPage, $findNew->result_count, 'forums/new-threads');

            $pageIds = $findNew->getPageResultIds($page, $perPage);
            $threads = $handler->getPageResults($pageIds);
        } else {
            $threads = [];
        }

        $canInlineMod = false;
        foreach ($threads as $thread) {
            if ($thread->canUseInlineModeration()) {
                $canInlineMod = true;
                break;
            }
        }

        if ($this->options()->thtopics_enableTopics) {
            $this->getTopicRepo()->hydrateTopicsForThreads($threads, null, true);
        }

        $viewParams = [
            'findNew' => $findNew,
            'originalFindNewId' => $findNewId,
            'page' => $page,
            'perPage' => $perPage,
            'selfRoute' => $selfRoute,

            'threads' => $threads,
            'canInlineMod' => $canInlineMod
        ];
        return $this->view('ThemeHouse\Topics:Forum\NewThreads', 'thtopics_forum_new_threads', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionForum(ParameterBag $params)
    {
        $reply = parent::actionForum($params);

        if ($reply instanceof View) {
            if (($reply->getParam('threads') || $reply->getParam('stickyThreads'))
                && $this->options()->thtopics_enableTopics) {
                $this->getTopicRepo()->hydrateTopicsForThreads(
                    $reply->getParam('threads'),
                    $reply->getParam('stickyThreads')
                );
            }
        }

        return $reply;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     */
    public function actionAllForumsFilter(ParameterBag $params)
    {
        /** @var \ThemeHouse\Topics\XF\Repository\Forum $forumRepo */
        $forumRepo = $this->getForumRepo();
        $nodeTree = $forumRepo->getNodeTreeForFilterList();

        $viewParams = [
            'nodeTree' => $nodeTree,
        ];
        return $this->view('XF:Forum\AllForumsFilter', 'thtopics_all_forums_filter', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|\XF\Mvc\Reply\Reroute|View
     */
    public function actionAllThreads(ParameterBag $params)
    {
        if ($this->options()->forumsDefaultPage != 'thtopic_all_threads'
            && !$this->options()->thtopics_showAllThreadsPage) {
            return $this->rerouteController('XF:WhatsNewPost', 'index');
        }

        $viewableNodes = $this->getNodeRepo()->getNodeList();

        /** @var \ThemeHouse\Topics\XF\Finder\Thread $threadList */
        $threadList = \XF::finder('XF:Thread')
            ->where('node_id', '=', $viewableNodes->pluckNamed('node_id'))
            ->with('full');

        $threadList->setDefaultOrder('last_post_date', 'DESC');

        /** @noinspection PhpUndefinedFieldInspection */
        $page = $this->filterPage($params->page);
        $perPage = $this->options()->discussionsPerPage;

        /** @var \ThemeHouse\Topics\XF\Entity\Forum $forum */
        $forum = $this->em()->create('XF:Forum');

        $filters = $this->getForumFilterInput($forum);
        $this->applyForumFilters($forum, $threadList, $filters);
        $this->applyDateLimitFilters($forum, $threadList, $filters);

        $threadList->limitByPage($page, $perPage);

        /** @var \XF\Entity\Thread[] $threads */
        $threads = $threadList->fetch()->filterViewable();
        $totalThreads = $threadList->total();

        $canInlineMod = false;
        foreach ($threads as $thread) {
            if ($thread->canUseInlineModeration()) {
                $canInlineMod = true;
                break;
            }
        }

        if (!empty($filters['starter_id'])) {
            $starterFilter = \XF::app()->find('XF:User', $filters['starter_id']);
        } else {
            $starterFilter = null;
        }

        $isDateLimited = (empty($filters['no_date_limit']) && (!empty($filters['last_days']) || $forum->list_date_limit_days));
        $threadEndOffset = ($page - 1) * $perPage + count($threads);
        $showDateLimitDisabler = ($isDateLimited && $threadEndOffset >= $totalThreads);

        if ($this->options()->thtopics_enableTopics) {
            $this->getTopicRepo()->hydrateTopicsForThreads($threads);
        }

        $viewParams = [
            'canInlineMod' => $canInlineMod,

            'threads' => $threads,

            'page' => $page,
            'perPage' => $perPage,
            'total' => $totalThreads,

            'filters' => $filters,
            'starterFilter' => $starterFilter,
            'showDateLimitDisabler' => $showDateLimitDisabler,

            'sortInfo' => $this->getEffectiveSortInfo($forum, $filters),
        ];

        list($viewParams['topic'], $viewParams['forum']) = $this->getTHQuickThreadViewParams();

        $return = $this->view('XF:Forum\AllThreads', 'thtopics_forum_all_threads', $viewParams);
        $return->setJsonParam('href', $this->getTopicRepo()->getTopicFilterUrl('forums/all-threads', $filters));
        return $return;
    }

    /**
     * @param \XF\Entity\Forum $forum
     * @param \XF\Finder\Thread $threadFinder
     * @param array $filters
     */
    protected function applyForumFilters(\XF\Entity\Forum $forum, \XF\Finder\Thread $threadFinder, array $filters)
    {
        if ($this->options()->thtopics_topicFilterForumPages) {
            /** @var \ThemeHouse\Topics\XF\Finder\Thread $threadFinder */
            $threadFinder->filterByTopicsAndForums(!$forum->node_id);
        }

        parent::applyForumFilters($forum, $threadFinder, $filters);
    }

    /**
     * @return array
     */
    protected function getTHQuickThreadViewParams()
    {
        $forum = null;
        $topic = null;

        /** @noinspection PhpUndefinedMethodInspection */
        $forumFilters = \XF::visitor()->getForumFilters($this->request);
        /** @noinspection PhpUndefinedMethodInspection */
        $topicFilters = \XF::visitor()->getTopicFilters($this->request);

        if (count($forumFilters) === 1) {
            $nodeId = reset($forumFilters);
            /** @var \ThemeHouse\Topics\XF\Entity\Forum $forum */
            $forum = $this->finder('XF:Forum')
                ->where('node_id', $nodeId)
                ->fetchOne();
        }

        if (count($topicFilters) === 1) {
            $topicId = reset($topicFilters);
            /** @var Topic $topic */
            $topic = $this->finder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicId)
                ->with('Forum')
                ->fetchOne();
            if ($topic && $topic->Forum && ($forum === null || $topic->Forum->node_id === $forum->node_id)) {
                $forum = $topic->Forum;
            } else {
                $forum = null;
            }
        } elseif (count($topicFilters) > 1) {
            $groupedTopics = $this->finder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicFilters)
                ->with('Forum')
                ->fetch()
                ->groupBy('node_id');
            if (count($groupedTopics) == 1) {
                $topics = reset($groupedTopics);
                $topic = reset($topics);
                if ($topic && $topic->Forum && (!isset($forum) || $forum === null || $topic->Forum->node_id === $forum->node_id)) {
                    $forum = $topic->Forum;
                } else {
                    $forum = null;
                }
                $topic = null;
            }
        }

        return [$topic, $forum];
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|\XF\Mvc\Reply\Reroute|View
     */
    public function actionFilters(ParameterBag $params)
    {
        if (!$params['node_id'] && !$params['node_name']) {
            return $this->rerouteController(__CLASS__, 'thfilter');
        }

        return parent::actionFilters($params);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionThFilter(ParameterBag $params)
    {
        /** @var \ThemeHouse\Topics\XF\Entity\Forum $forum */
        $forum = $this->em()->create('XF:Forum');

        $filters = $this->getForumFilterInput($forum);

        if ($this->filter('apply', 'bool')) {
            if (!empty($filters['last_days'])) {
                unset($filters['no_date_limit']);
            }
            return $this->redirect($this->buildLink("forums/all-threads", null, $filters));
        }

        if (!empty($filters['starter_id'])) {
            $starterFilter = $this->em()->find('XF:User', $filters['starter_id']);
        } else {
            $starterFilter = null;
        }

        $prefixes = $this->finder('XF:ThreadPrefix')
            ->order('materialized_order')
            ->fetch();

        $viewParams = [
            'forum' => null,
            'prefixes' => $prefixes->groupBy('prefix_group_id'),
            'filters' => $filters,
            'starterFilter' => $starterFilter
        ];
        return $this->view('XF:Forum\Filters', 'forum_filters', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|View
     */
    public function actionList(ParameterBag $params)
    {
        if ($this->options()->thtopics_redirectForumList && $this->options()->thtopic_topicListData !== '[]') {
            return $this->redirect($this->buildLink('topics'), false, Redirect::PERMANENT);
        }

        return parent::actionList($params);
    }

    /**
     * @return \XF\Mvc\Reply\Reroute|View
     */
    public function actionPostThreadChooser()
    {
        if ($this->options()->thtopics_enableTopics && $this->options()->thtopics_replaceNewThread && $this->options()->thtopic_topicListData !== '[]') {
            if (!$this->request->exists('no_reroute')) {
                return $this->rerouteController('ThemeHouse\Topics:Topic', 'PostThreadChooser');
            }
        }

        return parent::actionPostThreadChooser();
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws \XF\PrintableException
     */
    public function actionPostThread(ParameterBag $params)
    {
        if ($this->options()->thtopics_enableTopics && $params['node_id']) {
            $draftNodeId = $this->filter('draft_node_id', 'int');
            $userId = \XF::visitor()->user_id;
            if ($draftNodeId && $userId) {
                /** @var Draft $draft */
                $draft = $this->finder('XF:Draft')
                    ->where('user_id', $userId)
                    ->where('draft_key', 'forum-' . $draftNodeId)
                    ->fetchOne();
                if ($draft) {
                    $draft->draft_key = 'forum-' . $params['node_id'];
                    $draft->save();
                }
            }
        }

        $reply = parent::actionPostThread($params);

        if ($this->options()->thtopics_enableTopics && $reply instanceof View && $reply->getParam('forum')) {
            $forum = $reply->getParam('forum');

            $additionalTopicIds = $this->filter('additional_topic_ids', 'array');
            $topics = $this->getTopicRepo()->getTopicsForList();

            $topicId = $this->filter('topic_id', 'int');
            if ($topicId) {
                if (!$forum->Topics->offsetExists($topicId)) {
                    $topicId = 0;
                }
            }

            $reply->setParams([
                'selectedTopicId' => $topicId,
                'topics' => ($forum && !empty($forum->Topics)) ? $forum->Topics : $topics,
                'selectedAdditionalTopicIds' => $additionalTopicIds,
                'additionalTopics' => $topics,
            ]);
        }

        return $reply;
    }

    /**
     * @param \XF\Entity\Forum $forum
     * @return \XF\Service\Thread\Creator
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function setupThreadCreate(\XF\Entity\Forum $forum)
    {
        /** @var \ThemeHouse\Topics\XF\Service\Thread\Creator $creator */
        $creator = parent::setupThreadCreate($forum);

        if ($this->options()->thtopics_enableTopics) {
            $topicId = $this->filter('topic_id', 'uint');
            if ($topicId) {
                $creator->setTopicId($topicId);

                if ($this->options()->thtopics_maxAdditionalTopics) {
                    $additionalTopicIds = $this->filter('additional_topic_ids', 'json-array');
                    if (!empty($additionalTopicIds)) {
                        $creator->setAdditionalTopicIds($additionalTopicIds);
                    }
                }
            } else {
                $visitor = \XF::visitor();

                if (!$visitor->hasNodePermission($forum->node_id, 'th_postThreadWithoutTopic')) {
                    /** @var \ThemeHouse\Topics\XF\Entity\Forum $forum */
                    /** @noinspection PhpUndefinedMethodInspection */
                    if ($forum->Topics->count()) {
                        throw $this->exception($this->error(\XF::phrase('thtopics_please_select_a_topic')));
                    }
                }
            }
        }

        return $creator;
    }
}
