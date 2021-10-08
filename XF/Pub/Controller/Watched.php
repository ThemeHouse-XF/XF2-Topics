<?php

namespace ThemeHouse\Topics\XF\Pub\Controller;

use ThemeHouse\Topics\Entity\TopicWatch;

/**
 * Class Watched
 * @package ThemeHouse\Topics\XF\Pub\Controller
 */
class Watched extends XFCP_Watched
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionThreads()
    {
        $return = parent::actionThreads();
        /** @noinspection PhpUndefinedMethodInspection */
        $return->setJsonParam('href', \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl('watched/threads'));
        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionTopics()
    {
        if (!$this->options()->thtopics_enableTopics) {
            return $this->notFound();
        }

        $this->setSectionContext('forums');

        /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
        $topicRepo = $this->repository('ThemeHouse\Topics:Topic');
        $topicFinder = $topicRepo->findTopicsForWatchedList();

        $topics = $topicFinder->fetch();

        $viewParams = [
            'topics' => $topics
        ];
        return $this->view('ThemeHouse\Topics:Watched\Topics', 'thtopics_watched_topics_list', $viewParams);
    }

    /**
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionTopicsManage()
    {
        if (!$this->options()->thtopics_enableTopics) {
            return $this->notFound();
        }

        $this->setSectionContext('forums');

        if (!$state = $this->filter('state', 'str')) {
            return $this->redirect($this->buildLink('watched/topics'));
        }

        if ($this->isPost()) {
            /** @var \ThemeHouse\Topics\Repository\TopicWatch $topicWatchRepo */
            $topicWatchRepo = $this->repository('ThemeHouse\Topics:TopicWatch');

            if ($topicWatchRepo->isValidWatchState($state)) {
                $topicWatchRepo->setWatchStateForAll(\XF::visitor(), $state);
            }

            return $this->redirect($this->buildLink('watched/topics'));
        } else {
            $viewParams = [
                'state' => $state
            ];
            return $this->view('ThemeHouse\Topics:Watched\TopicsManage', 'thtopics_watched_topics_manage', $viewParams);
        }
    }

    /**
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionTopicsUpdate()
    {
        if (!$this->options()->thtopics_enableTopics) {
            return $this->notFound();
        }

        $this->assertPostOnly();
        $this->setSectionContext('forums');

        if ($action = $this->filter('action', 'str')) {
            $topicIds = $this->filter('topic_ids', 'array-uint');
            $visitor = \XF::visitor();

            foreach ($topicIds as $topicId) {
                /** @var TopicWatch $watch */
                $watch = $this->em()->find('ThemeHouse\Topics:TopicWatch', [
                    'topic_id' => $topicId,
                    'user_id' => $visitor->user_id
                ]);
                if (!$watch) {
                    continue;
                }

                switch ($action) {
                    case 'email':
                    case 'no_email':
                        $watch->send_email = ($action == 'email');
                        $watch->save();
                        break;

                    case 'alert':
                    case 'no_alert':
                        $watch->send_alert = ($action == 'alert');
                        $watch->save();
                        break;

                    case 'delete':
                        $watch->delete();
                        break;
                }
            }
        }

        return $this->redirect($this->buildLink('watched/topics'));
    }
}
