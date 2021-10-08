<?php

namespace ThemeHouse\Topics\Entity;

use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Entity
 *
 * COLUMNS
 * @property integer topic_group_id
 * @property string title
 *
 * RELATIONS
 * @property ArrayCollection Topics
 *
 * GETTERS
 * @property array topic_ids
 */
class TopicGroup extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_th_topics_topic_group';
        $structure->shortName = 'ThemeHouse\Topics:TopicGroup';
        $structure->primaryKey = 'topic_group_id';

        $structure->columns = [
            'topic_group_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'required' => true, 'maxLength' => 30],
        ];

        $structure->getters = [
            'topic_ids' => true,
        ];

        $structure->relations = [
            'Topics' => [
                'entity' => 'ThemeHouse\Topics:Topic',
                'type' => self::TO_MANY,
                'conditions' => 'topic_group_id',
                'key' => 'topic_id',
            ],
        ];

        return $structure;
    }

    /**
     * @return array
     */
    public function getTopicIds()
    {
        $topics = $this->Topics;

        if ($topics->count()) {
            return $topics->keys();
        } else {
            return [0];
        }
    }

    /**
     *
     */
    protected function _postDelete()
    {
        $db = $this->db();

        $db->update('xf_th_topics_topic', ['topic_group_id' => 0], "topic_group_id = {$this->topic_group_id}");
    }

    /**
     * @return \ThemeHouse\Topics\Repository\TopicGroup
     */
    protected function getTopicGroupRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:TopicGroup');
    }
}
