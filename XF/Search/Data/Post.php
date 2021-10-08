<?php

namespace ThemeHouse\Topics\XF\Search\Data;

use XF\Http\Request;
use XF\Mvc\Entity\Entity;
use XF\Search\IndexRecord;
use XF\Search\Query\Query;

/**
 * Class Post
 * @package ThemeHouse\Topics\XF\Search\Data
 */
class Post extends XFCP_Post
{
    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);

        $get[] = 'Thread.Topic';

        return $get;
    }

    /**
     * @return array
     */
    public function getSearchFormData()
    {
        $data = parent::getSearchFormData();

        $data['topics'] = $this->getSearchAbleTopics();

        return $data;
    }

    /**
     * @return mixed
     */
    protected function getSearchableTopics()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return \XF::repository('ThemeHouse\Topics:Topic')->getTopicsForList();
    }

    /**
     * @param Entity $entity
     * @return null|IndexRecord
     */
    public function getIndexData(Entity $entity)
    {
        $indexData = parent::getIndexData($entity);

        if (!$indexData) {
            return $indexData;
        }

        /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
        /** @noinspection PhpUndefinedFieldInspection */
        $thread = $entity->Thread;

        if ($thread->topic_id) {
            $topics = $thread->additional_topic_ids;
            $topics[] = $thread->topic_id;

            /** @noinspection PhpUndefinedMethodInspection */
            $this->indexTopics($indexData, $topics);
        }

        return $indexData;
    }

    /**
     * @param IndexRecord $indexData
     * @param array $topicIds
     * @param bool $withMetadata
     */
    public function indexTopics(IndexRecord $indexData, array $topicIds, $withMetadata = true)
    {
        if ($topicIds && $withMetadata) {
            $indexData->metadata['thtopic'] = $topicIds;
        }
    }

    /**
     * @param Query $query
     * @param Request $request
     * @param array $urlConstraints
     */
    public function applyTypeConstraintsFromInput(Query $query, Request $request, array &$urlConstraints)
    {
        parent::applyTypeConstraintsFromInput($query, $request, $urlConstraints);

        if (!$request->filter('c.thread', 'uint')) {
            $topicIds = $request->filter('c.topics', 'array-uint');
            $topicIds = array_unique($topicIds);

            if ($topicIds && reset($topicIds)) {
                $query->withMetadata('thtopic', $topicIds);
            }
        }
    }
}
