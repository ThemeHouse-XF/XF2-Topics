<?php

namespace ThemeHouse\Topics\XF\Entity;

use ThemeHouse\Topics\Entity\Topic;
use XF\Mvc\Entity\ArrayCollection;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Entity
 *
 * @property integer topic_id
 * @property array additional_topic_ids
 *
 * @property Topic Topic
 * @property AbstractCollection|Topic[] AdditionalTopics
 * @property array|AbstractCollection|Topic[] topics
 * @property array|AbstractCollection|Topic[] additional_topics
 */
class Thread extends XFCP_Thread
{
    /**
     * @var null
     */
    protected $additionalTopics = null;

    /**
     * @param null $error
     * @return bool
     */
    public function canChangeTopics(&$error = null)
    {
        if (!\XF::options()->thtopics_enableTopics) {
            return false;
        }

        $visitor = \XF::visitor();
        return $visitor->user_id && $visitor->hasNodePermission($this->node_id, 'manageAnyThread');
    }

    /**
     * @return mixed|null
     */
    public function getAdditionalTopics()
    {
        if ($this->additional_topic_ids) {
            return $this->AdditionalTopics;
        }
        return [];
    }

    /**
     * @return mixed|null
     */
    public function getVisitorReadDate()
    {
        $read = parent::getVisitorReadDate();

        if ($read === null) {
            return null;
        }

        $visitor = \XF::visitor();
        if ($visitor->user_id && $this->topic_id && \XF::options()->thtopics_enableTopics) {
            /** @var ArrayCollection $topicRead */
            /** @noinspection PhpUndefinedFieldInspection */
            $topicRead = $visitor->TopicRead;

            $topicRead->filter(function ($read) {
                return $read->topic_id === $this->topic_id || in_array($read->topic_id, $this->additional_topic_ids);
            });

            $readDates = $topicRead->pluckNamed('topic_read_date');
            $readDates[] = $read;
            $read = max($readDates);
        }

        return $read;
    }

    /**
     * @return array|\XF\Mvc\Entity\Entity[]
     */
    public function getTopics()
    {
        $repo = $this->getTopicRepo();
        return $repo->getTopicsForThread($this);
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
     * @param $additionalTopicIds
     * @return bool
     */
    protected function verifyAdditionalTopicIds(&$additionalTopicIds)
    {
        foreach ($additionalTopicIds as $key => $additionalTopicId) {
            if ($additionalTopicId == $this->topic_id) {
                unset($additionalTopicIds[$key]);
            }
        }

        $maxAdditionalTopics = \XF::options()->thtopics_maxAdditionalTopics;

        if (count($additionalTopicIds) > $maxAdditionalTopics) {
            $this->error(\XF::phrase('thtopics_threads_may_only_have_x_additional_topics', [
                'count' => $maxAdditionalTopics
            ]), 'additional_topic_ids');
            return false;
        }

        return true;
    }

    /**
     *
     */
    protected function _preSave()
    {
        if ($this->topic_id && ($this->isChanged(['topic_id', 'node_id']))) {
            if ($this->Topic->node_id !== $this->node_id) {
                $this->topic_id = 0;
            }
        }

        if (!$this->topic_id && $this->additional_topic_ids) {
            $this->additional_topic_ids = [];
        }

        parent::_preSave();
    }

    /**
     * @throws \XF\Db\Exception
     * @throws \XF\PrintableException
     */
    protected function _postSave()
    {
        parent::_postSave();

        $this->updateTopicRecords();
    }

    /**
     * @throws \XF\Db\Exception
     * @throws \XF\PrintableException
     */
    protected function updateTopicRecords()
    {
        if (!\XF::options()->thtopics_enableTopics || !$this->topic_id) {
            return;
        }

        $topicIdsAdded = [];
        $topicIdsRemoved = [];
        if ($this->isUpdate()) {
            if ($this->discussion_state == 'visible') {
                if ($this->isChanged('additional_topic_ids')) {
                    $topicIdsAdded = array_diff(
                        $this->additional_topic_ids,
                        $this->getExistingValue('additional_topic_ids')
                    );
                }
                if ($this->isChanged('topic_id')) {
                    $topicIdsAdded[] = $this->topic_id;
                }
            }

            if ($this->getExistingValue('discussion_state') == 'visible') {
                if ($this->isChanged('additional_topic_ids')) {
                    $topicIdsRemoved = array_diff(
                        $this->getExistingValue('additional_topic_ids'),
                        $this->additional_topic_ids
                    );
                }
                if ($this->isChanged('topic_id')) {
                    $topicIdsRemoved[] = $this->getExistingValue('topic_id');
                }
            }
        }

        // check for thread entering/leaving visible
        $visibilityChange = $this->isStateChanged('discussion_state', 'visible');
        if ($visibilityChange == 'enter' && $this->Forum) {
            $topicIdsAdded = $this->additional_topic_ids;
            $topicIdsAdded[] = $this->topic_id;
        } elseif ($visibilityChange == 'leave' && $this->Forum) {
            $topicIdsRemoved = $this->additional_topic_ids;
            $topicIdsRemoved[] = $this->topic_id;
        }

        if ($topicIdsAdded) {
            $topicIdsAddedStr = $this->db()->quote($topicIdsAdded);
            $this->db()->query("
                UPDATE xf_th_topics_topic
                SET message_count = message_count + ? + 1,
                    last_post_date = GREATEST(last_post_date, ?),
                    last_post_id = IF (last_post_date > ?, last_post_id, ?),
                    last_post_user_id = IF (last_post_date > ?, last_post_user_id, ?),
                    last_post_username = IF (last_post_date > ?, last_post_username, ?),
                    last_thread_title = IF (last_post_date > ?, last_thread_title, ?),
                    last_thread_prefix_id = IF (last_post_date > ?, last_thread_prefix_id, ?),
                    last_post_date_primary = IF (
                        last_post_date_primary > ? OR topic_id != ?,
                        last_post_date_primary,
                        ?)
                WHERE topic_id IN ({$topicIdsAddedStr})
            ", [
                $this->reply_count,
                $this->last_post_date,
                $this->last_post_date,
                $this->last_post_id,
                $this->last_post_date,
                $this->last_post_user_id,
                $this->last_post_date,
                $this->last_post_username,
                $this->last_post_date,
                $this->title,
                $this->last_post_date,
                $this->prefix_id,
                $this->last_post_date,
                $this->topic_id,
                $this->last_post_date,
            ]);
        }
        if ($topicIdsRemoved) {
            $topics = $this->finder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicIdsRemoved)
                ->fetch();

            foreach ($topics as $topic) {
                /** @var Topic $topic */
                $topic->threadRemoved($this);
                $topic->save();
            }
        }

        // general data changes
        if ($this->discussion_state == 'visible'
            && $this->isChanged(['last_post_date', 'reply_count', 'discussion_type'])
        ) {
            $topics = $this->topics;
            if ($topicIdsAdded) {
                $unchangedTopics = [];
                foreach ($topics as $topic) {
                    if (!in_array($topic->topic_id, $topicIdsAdded)) {
                        $unchangedTopics[$topic->topic_id] = $topic;
                    }
                }
                $topics = $unchangedTopics;
            }
            if ($topics) {
                if (!$this->isChanged(['discussion_type', 'title'])
                    && $this->last_post_date >= $this->getExistingValue('last_post_date')) {
                    $topicIdsStr = $this->db()->quote(array_keys($topics));
                    $this->db()->query("
                        UPDATE xf_th_topics_topic
                        SET message_count = GREATEST(0, CAST(message_count AS SIGNED) + ?),
                            last_post_date = GREATEST(last_post_date, ?),
                            last_post_id = IF (last_post_date > ?, last_post_id, ?),
                            last_post_user_id = IF (last_post_date > ?, last_post_user_id, ?),
                            last_post_username = IF (last_post_date > ?, last_post_username, ?),
                            last_thread_title = IF (last_post_date > ?, last_thread_title, ?),
                            last_thread_prefix_id = IF (last_post_date > ?, last_thread_prefix_id, ?),
                            last_post_date_primary = IF (
                                last_post_date_primary > ? OR topic_id != ?,
                                last_post_date_primary,
                                ?)
                        WHERE topic_id IN ({$topicIdsStr})
                    ", [
                        $this->reply_count - $this->getExistingValue('reply_count'),
                        $this->last_post_date,
                        $this->last_post_date,
                        $this->last_post_id,
                        $this->last_post_date,
                        $this->last_post_user_id,
                        $this->last_post_date,
                        $this->last_post_username,
                        $this->last_post_date,
                        $this->title,
                        $this->last_post_date,
                        $this->prefix_id,
                        $this->last_post_date,
                        $this->topic_id,
                        $this->last_post_date,
                    ]);
                } else {
                    foreach ($topics as $topic) {
                        $topic->threadDataChanged($this);
                        $topic->save();
                    }
                }
            }
        }
    }
}
