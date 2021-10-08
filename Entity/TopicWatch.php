<?php

namespace ThemeHouse\Topics\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int user_id
 * @property int topic_id
 * @property string notify_on
 * @property bool send_alert
 * @property bool send_email
 *
 * RELATIONS
 * @property \XF\Entity\Forum Forum
 * @property \XF\Entity\User User
 */
class TopicWatch extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_th_topics_topic_watch';
        $structure->shortName = 'ThemeHouse\Topics:TopicWatch';
        $structure->primaryKey = ['user_id', 'topic_id'];

        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'topic_id' => ['type' => self::UINT, 'required' => true],
            'notify_on' => [
                'type' => self::STR,
                'default' => '',
                'allowedValues' => ['', 'thread', 'message']
            ],
            'send_alert' => ['type' => self::BOOL, 'default' => false],
            'send_email' => ['type' => self::BOOL, 'default' => false]
        ];

        $structure->getters = [];

        $structure->relations = [
            'Topic' => [
                'entity' => 'ThemeHouse\Topics:Topic',
                'type' => self::TO_ONE,
                'conditions' => 'topic_id',
                'primary' => true
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
        ];

        return $structure;
    }
}
