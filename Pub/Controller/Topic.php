<?php

namespace ThemeHouse\Topics\Pub\Controller;

use XF\Entity\Forum;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Pub\Controller
 */
class Topic extends AbstractController
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\Reroute|\XF\Mvc\Reply\View
     */
    public function actionIndex(ParameterBag $params)
    {
        if (!$this->options()->thtopics_enableTopics) {
            return $this->redirect($this->buildLink('forums/list'));
        }

        $data = json_decode($this->options()->thtopic_topicListData);

        if (!$data) {
            return $this->notFound();
        }

        if (!empty($params['topic_id'])) {
            return $this->rerouteController('XF:Forum', 'all-threads', $params);
        }

        $topics = $this->getTopicRepo()->getTopicsForList();

        if (!$topics->count()) {
            return $this->notFound();
        }

        $viewParams = [
            'topics' => $topics,
            'data' => $data,
        ];
        return $this->view('ThemeHouse\Topics:Topic\List', 'thtopics_topic_list', $viewParams);
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
     * @return \XF\Mvc\Reply\View
     */
    public function actionAllTopicsFilter(ParameterBag $params)
    {
        if (!$this->options()->thtopics_enableTopics) {
            return $this->notFound();
        }

        $data = json_decode($this->options()->thtopic_topicListData);

        $topicRepo = $this->getTopicRepo();
        $topics = $topicRepo->getTopicsForList();

        $viewParams = [
            'topics' => $topics,
            'data' => $data,
        ];
        return $this->view('ThemeHouse\Topics:Topic\AllTopicsFilter', 'thtopics_all_topics_filter', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Reroute|\XF\Mvc\Reply\View
     */
    public function actionPostThreadChooser(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->canCreateThread($error)) {
            return $this->noPermission($error);
        }

        $topics = $this->getTopicRepo()->getTopicsForList();

        $threadTopics = $topics->filter(function (\ThemeHouse\Topics\Entity\Topic $topic) {
            return ($topic->Forum && $topic->Forum->canCreateThread());
        });

        if (!$threadTopics->count()) {
            $this->request->set('no_reroute', true);
            return $this->rerouteController('XF:Forum', 'PostThreadChooser', $params);
        }

        $viewParams = [
            'topics' => $topics,
        ];

        if ($this->isPost()) {
            $viewParams['selectedTopicId'] = $this->filter('topic_id', 'int');
            $viewParams['draftNodeId'] = $this->filter('draft_node_id', 'int');
        } else {
            /** @noinspection PhpUndefinedMethodInspection */
            $filters = \XF::visitor()->getTopicFilters();
            if ($filters && count($filters) == 1) {
                $viewParams['selectedTopicId'] = array_shift($filters);
            }
        }

        return $this->view(
            'ThemeHouse\Topics:Topic\PostThreadChooser',
            'thtopics_topic_post_thread_chooser',
            $viewParams
        );
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCreateThread(ParameterBag $params)
    {
        $topicId = $this->filter('topic_id', 'uint');
        $additionalTopicIds = $this->filter('additional_topic_ids', 'json-array');
        if (!empty($params['topic_id'])) {
            $topicId = $params['topic_id'];
        }

        $topic = $this->assertViewableTopic($topicId);
        if (!$topic->node_id) {
            return $this->notFound();
        }
        /** @var Forum $forum */
        $forum = $topic->Forum;
        if (!$forum->canCreateThread($error)) {
            return $this->noPermission($error);
        }

        $draftNodeId = $this->filter('draft_node_id', 'int');

        return $this->redirect($this->buildLink('forums/post-thread', $forum->Node, [
            'topic_id' => $topicId,
            'additional_topic_ids' => $additionalTopicIds,
            'draft_node_id' => $draftNodeId,
        ]));
    }

    /**
     * @param $topicId
     * @param array $extraWith
     * @return \ThemeHouse\Topics\Entity\Topic
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertViewableTopic($topicId, array $extraWith = [])
    {
        if ($topicId === null) {
            throw new \InvalidArgumentException("Topic ID/name not passed in correctly");
        }

        $finder = $this->em()->getFinder('ThemeHouse\Topics:Topic');
        $finder->with($extraWith);
        $finder->where('topic_id', '=', $topicId);

        /** @var \ThemeHouse\Topics\Entity\Topic $topic */
        $topic = $finder->fetchOne();
        if (!$topic) {
            throw $this->exception($this->notFound(\XF::phrase('thtopics_requested_topic_not_found')));
        }
        if (!$topic->canView($error)) {
            throw $this->exception($this->noPermission($error));
        }

        return $topic;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionWatch(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return $this->noPermission();
        }

        $topic = $this->assertViewableTopic($params['topic_id']);
        if (!$topic->canWatch($error)) {
            return $this->noPermission($error);
        }

        if ($this->isPost()) {
            if ($this->filter('stop', 'bool')) {
                $notifyType = 'delete';
            } else {
                $notifyType = $this->filter('notify', 'str');
                if ($notifyType != 'thread' && $notifyType != 'message') {
                    $notifyType = '';
                }

                if ($topic->allowed_watch_notifications == 'none') {
                    $notifyType = '';
                } elseif ($topic->allowed_watch_notifications == 'thread' && $notifyType == 'message') {
                    $notifyType = 'thread';
                }
            }

            $sendAlert = $this->filter('send_alert', 'bool');
            $sendEmail = $this->filter('send_email', 'bool');

            /** @var \ThemeHouse\Topics\Repository\TopicWatch $topicRepo */
            $topicRepo = $this->repository('ThemeHouse\Topics:TopicWatch');
            $topicRepo->setWatchState($topic, $visitor, $notifyType, $sendAlert, $sendEmail);

            $redirect = $this->redirect($this->buildLink('topics', $topic));
            $redirect->setJsonParam('switchKey', $notifyType == 'delete' ? 'watch' : 'unwatch');
            return $redirect;
        } else {
            $viewParams = [
                'topic' => $topic,
                'isWatched' => !empty($topic->Watch[$visitor->user_id])
            ];
            return $this->view('ThemeHouse\Topics:Topic\Watch', 'thtopics_topic_watch', $viewParams);
        }
    }

    /**
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionPrefixes()
    {
        $this->assertPostOnly();

        $topicId = $this->filter('val', 'uint');
        $topic = $this->assertViewableTopic($topicId, ['Forum']);

        $viewParams = [
            'topic' => $topic,
            'forum' => $topic->Forum,
            'prefixes' => $topic->Forum->getUsablePrefixes()
        ];
        return $this->view('XF:Forum\Prefixes', 'forum_prefixes', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionMarkRead(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return $this->noPermission();
        }

        $markDate = $this->filter('date', 'uint');
        if (!$markDate) {
            $markDate = \XF::$time;
        }

        $topicRepo = $this->getTopicRepo();

        $topicId = $this->filter('topic_id', 'uint');
        if (!empty($params['topic_id'])) {
            $topicId = $params['topic_id'];
        }

        if ($topicId) {
            $topic = $this->assertViewableTopic($topicId);
        } else {
            $topic = null;
        }

        if ($this->isPost()) {
            if ($topic) {
                $topicRepo->markTopicReadByVisitor($topic, $markDate);

                return $this->redirect(
                    $this->getDynamicRedirect($this->buildLink('topics', $topic)),
                    \XF::phrase('thtopics_topic_x_marked_as_read', ['topic' => $topic->title])
                );
            } else {
                $topicRepo->markTopicsReadByVisitor($markDate);

                return $this->redirect(
                    $this->getDynamicRedirect($this->buildLink('topics')),
                    \XF::phrase('thtopics_all_topics_marked_as_read')
                );
            }
        } else {
            $viewParams = [
                'topic' => $topic,
                'date' => $markDate,
                'redirect' => $this->getDynamicRedirect(null, true),
            ];
            return $this->view('ThemeHouse\Topics:Topic\MarkRead', 'thtopics_topic_mark_read', $viewParams);
        }
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \ThemeHouse\Topics\Entity\Topic
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertTopicExists($id, $with = null, $phraseKey = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assertRecordExists('ThemeHouse\Topics:Topic', $id, $with, $phraseKey);
    }

    /**
     * @return \ThemeHouse\Topics\XF\Repository\Thread
     */
    protected function getThreadRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:Thread');
    }
}
