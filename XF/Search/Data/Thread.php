<?php

namespace ThemeHouse\Topics\XF\Search\Data;

use XF\Mvc\Entity\Entity;
use XF\Search\IndexRecord;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Search\Data
 */
class Thread extends XFCP_Thread
{
    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);

        $get[] = 'Topic';

        return $get;
    }

    /**
     * @param Entity $entity
     * @return null|IndexRecord
     */
    public function getIndexData(Entity $entity)
    {
        $indexData = parent::getIndexData($entity);

        /** @var \ThemeHouse\Topics\XF\Entity\Thread $entity */
        if ($indexData && $entity->topic_id) {
            $topics = $entity->additional_topic_ids;
            $topics[] = $entity->topic_id;

            /** @noinspection PhpUndefinedMethodInspection */
            $this->indexTopics($indexData, $topics);
        }

        return $indexData;
    }

    /**
     * @param IndexRecord $indexData
     * @param array $topics
     * @param bool $withMetadata
     */
    public function indexTopics(IndexRecord $indexData, array $topics, $withMetadata = true)
    {
        if ($topics) {
            $topicIds = [];
            foreach ($topics as $topicId) {
                $topicIds[] = $topicId;
            }

            if ($withMetadata) {
                $indexData->metadata['thtopic'] = $topicIds;
            }
        }
    }
}
