<?php

namespace ThemeHouse\Topics\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null topic_read_id
 * @property int user_id
 * @property int topic_id
 * @property int topic_read_date
 *
 * RELATIONS
 * @property \XF\Entity\User User
 * @property \ThemeHouse\Topics\Entity\Topic Topic
 */
class TopicRead extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_th_topics_topic_read';
        $structure->shortName = 'ThemeHouse\Topics:TopicRead';
        $structure->primaryKey = 'topic_read_id';

        $structure->columns = [
            'topic_read_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'topic_id' => ['type' => self::UINT, 'required' => true],
            'topic_read_date' => ['type' => self::UINT, 'required' => true]
        ];

        $structure->getters = [];

        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'Topic' => [
                'entity' => 'ThemeHouse\Topics:Topic',
                'type' => self::TO_ONE,
                'conditions' => 'topic
                _id',
                'primary' => true
            ],
        ];

        return $structure;
    }
}
