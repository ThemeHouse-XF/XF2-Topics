<?php

namespace ThemeHouse\Topics\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Pub\Controller
 */
class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionIndex(ParameterBag $params)
    {
        $reply = parent::actionIndex($params);

        if ($reply instanceof View && $reply->getParam('thread')) {
            /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
            $thread = $reply->getParam('thread');

            if ($thread->topic_id) {
                $topic = $thread->Topic;
                $reply->setParam('topic', $topic);
            }
        }

        return $reply;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionChangeTopics(ParameterBag $params)
    {
        /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
        /** @noinspection PhpUndefinedFieldInspection */
        $thread = $this->assertViewableThread($params->thread_id);
        if (!$thread->canChangeTopics($error)) {
            return $this->noPermission($error);
        }

        if ($this->isPost()) {
            $topicId = $this->filter('topic_id', 'uint');
            $additionalTopicIds = $this->filter('additional_topic_ids', 'json-array');

            if ($position = array_search($topicId, $additionalTopicIds)) {
                unset($additionalTopicIds[$position]);
                $additionalTopicIds = array_values($additionalTopicIds);
            }

            $topic = null;
            if ($topicId) {
                /** @var \ThemeHouse\Topics\Entity\Topic $topic */
                $topic = $this->app()->em()
                    ->find('ThemeHouse\Topics:Topic', $topicId);
            }

            if ($topic && !$topic->canView()) {
                return $this->error(\XF::phrase('thtopics_requested_topic_not_found'));
            }

            $topicChanger = $this->setupThreadTopicChanger($thread);
            $topicChanger->changeTopics($topic, $additionalTopicIds);

            return $this->redirect($this->buildLink('threads', $thread));
        }

        $forum = $thread->Forum;
        $topics = $this->getTopicRepo()->getTopics();

        $viewParams = [
            'thread' => $thread,
            'forum' => $forum,
            'topic' => $thread->Topic,
            'topics' => $topics,
            'prefixes' => $forum->getUsablePrefixes(),
        ];
        return $this->view('XF:Thread\Move', 'thtopics_thread_change_topics', $viewParams);
    }

    /**
     * @param \XF\Entity\Thread $thread
     * @return \ThemeHouse\Topics\Service\Thread\TopicChanger
     */
    protected function setupThreadTopicChanger(\XF\Entity\Thread $thread)
    {
        $options = $this->filter([
            'starter_alert' => 'bool',
            'starter_alert_reason' => 'str',
            'prefix_id' => 'uint'
        ]);

        $redirectType = $this->filter('redirect_type', 'str');
        if ($redirectType == 'permanent') {
            $options['redirect'] = true;
            $options['redirect_length'] = 0;
        } elseif ($redirectType == 'temporary') {
            $options['redirect'] = true;
            $options['redirect_length'] = $this->filter('redirect_length', 'timeoffset');
        } else {
            $options['redirect'] = false;
            $options['redirect_length'] = 0;
        }

        /** @var \ThemeHouse\Topics\Service\Thread\TopicChanger $topicChanger */
        $topicChanger = $this->service('ThemeHouse\Topics:Thread\TopicChanger', $thread);

        if ($options['starter_alert']) {
            $topicChanger->setSendAlert(true, $options['starter_alert_reason']);
        }

        if ($options['redirect']) {
            $topicChanger->setRedirect(true, $options['redirect_length']);
        }

        if ($options['prefix_id'] !== null) {
            $topicChanger->setPrefix($options['prefix_id']);
        }

        $topicChanger->addExtraSetup(function ($thread) {
            $thread->title = $this->filter('title', 'str');
        });

        return $topicChanger;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic
     */
    protected function getTopicRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:Topic');
    }
}
