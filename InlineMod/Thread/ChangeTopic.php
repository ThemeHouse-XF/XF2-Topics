<?php

namespace ThemeHouse\Topics\InlineMod\Thread;

use ThemeHouse\Topics\XF\Entity\Thread;
use XF\Http\Request;
use XF\InlineMod\AbstractAction;
use XF\Mvc\Controller;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Entity;

/**
 * Class ChangeTopic
 * @package ThemeHouse\Topics\InlineMod\Thread
 */
class ChangeTopic extends AbstractAction
{
    /**
     * @var
     */
    protected $targetTopicId;
    /**
     * @var
     */
    protected $targetTopic;


    /**
     * @return array
     */
    public function getBaseOptions()
    {
        return [
            'topic_id' => 0,
            'additional_topic_ids' => [],
            'check_topic_viewable' => true,
            'check_all_same_topic' => false
        ];
    }

    /**
     * @return \XF\Phrase
     */
    public function getTitle()
    {
        return \XF::phrase('thtopics_change_topics...');
    }

    /**
     * @param AbstractCollection $entities
     * @param Request $request
     * @return array
     */
    public function getFormOptions(AbstractCollection $entities, Request $request)
    {
        $options = [
            'topic_id' => $request->filter('topic_id', 'uint'),
            'additional_topic_ids' => $request->filter('additional_topic_ids', 'json-array'),
            'notify_watchers' => $request->filter('notify_watchers', 'bool'),
            'alert' => $request->filter('starter_alert', 'bool'),
            'alert_reason' => $request->filter('starter_alert_reason', 'str')
        ];

        return $options;
    }

    /**
     * @param AbstractCollection $entities
     * @param Controller $controller
     * @return null|\XF\Mvc\Reply\View
     */
    public function renderForm(AbstractCollection $entities, Controller $controller)
    {
        $topicRepo = $this->getTopicRepo();
        $topics = $topicRepo->getTopicsForList();

        $viewParams = [
            'topics' => $topics,
            'threads' => $entities,
            'total' => count($entities),
            'first' => $entities->first()
        ];
        return $controller->view(
            'ThemeHouse\Topics:Public:InlineMod\Thread\ChangeTopic',
            'thtopics_inline_mod_change_topics',
            $viewParams
        );
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic
     */
    protected function getTopicRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return \XF::repository('ThemeHouse\Topics:Topic');
    }

    /**
     * @param AbstractCollection $entities
     * @param array $options
     * @param $error
     * @return bool
     */
    protected function canApplyInternal(AbstractCollection $entities, array $options, &$error)
    {
        $result = parent::canApplyInternal($entities, $options, $error);

        if ($result) {
            if ($options['topic_id']) {
                /** @var \ThemeHouse\Topics\Entity\Topic $topic */
                $topic = $this->getTopic($options['topic_id']);
                if (!$topic) {
                    return false;
                }

                if ($options['check_topic_viewable'] && !$topic->canView($error)) {
                    return false;
                }

                if ($options['check_all_same_topic']) {
                    $allSame = true;
                    foreach ($entities as $entity) {
                        /** @var Thread $entity */
                        if ($entity->topic_id != $options['topic_id']) {
                            $allSame = false;
                            break;
                        }
                    }

                    if ($allSame) {
                        $error = \XF::phraseDeferred('all_threads_in_destination_forum');
                        return false;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $topicId
     * @return null|Entity
     */
    protected function getTopic($topicId)
    {
        $topicId = intval($topicId);

        if ($this->targetTopicId && $this->targetTopicId == $topicId) {
            return $this->targetTopic;
        }
        if (!$topicId) {
            return null;
        }

        $topic = $this->app()->em()->find('ThemeHouse\Topics:Topic', $topicId);
        if (!$topic) {
            throw new \InvalidArgumentException("Invalid target topic ($topicId)");
        }

        $this->targetTopicId = $topicId;
        $this->targetTopic = $topic;

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->targetTopic;
    }

    /**
     * @param Entity $entity
     * @param array $options
     * @param null $error
     * @return bool
     */
    protected function canApplyToEntity(Entity $entity, array $options, &$error = null)
    {
        /** @var \ThemeHouse\Topics\XF\Entity\Thread $entity */
        return $entity->canChangeTopics($error);
    }

    /**
     * @param Entity $entity
     * @param array $options
     * @throws \XF\PrintableException
     */
    protected function applyToEntity(Entity $entity, array $options)
    {
        /** @var \ThemeHouse\Topics\Entity\Topic $topic */
        $topic = $this->getTopic($options['topic_id']);

        /** @var \ThemeHouse\Topics\Service\Thread\TopicChanger $changer */
        $changer = $this->app()->service('ThemeHouse\Topics:Thread\TopicChanger', $entity);

        $changer->changeTopics($topic, $options['additional_topic_ids']);

        if ($topic) {
            $this->returnUrl = $this->app()->router('public')->buildLink('forums', $topic->Forum);
        } else {
            $this->returnUrl = $this->app()->router('public')->buildLink('forums');
        }
    }
}
