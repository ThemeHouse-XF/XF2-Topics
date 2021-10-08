<?php

namespace ThemeHouse\Topics\Listener;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

/**
 * Class EntityStructure
 * @package ThemeHouse\Topics\Listener
 */
class EntityStructure
{
    /**
     * @param Manager $em
     * @param Structure $structure
     */
    public static function xfUser(Manager $em, Structure &$structure)
    {
        $structure->columns['thtopics_topic_filters'] = [
            'type' => Entity::SERIALIZED,
            'default' => [],
        ];

        $structure->columns['thtopics_forum_filters'] = [
            'type' => Entity::SERIALIZED,
            'default' => [],
        ];

        $structure->relations['TopicRead'] = [
            'entity' => 'ThemeHouse\Topics:TopicRead',
            'type' => Entity::TO_MANY,
            'conditions' => 'user_id',
            'primary' => true
        ];
    }

    /**
     * @param Manager $em
     * @param Structure $structure
     */
    public static function xfThread(Manager $em, Structure &$structure)
    {
        $structure->columns['topic_id'] = [
            'type' => Entity::UINT,
        ];
        $structure->columns['additional_topic_ids'] = [
            'type' => Entity::LIST_COMMA,
            'default' => [],
            'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC],
        ];

        $structure->relations['Topic'] = [
            'entity' => 'ThemeHouse\Topics:Topic',
            'type' => Entity::TO_ONE,
            'conditions' => 'topic_id',
        ];

        $structure->relations['AdditionalTopics'] = [
            'entity' => 'ThemeHouse\Topics:Topic',
            'type' => Entity::TO_MANY,
            'conditions' => [
                ['topic_id', '=', '$additional_topic_ids']
            ],
        ];

        if (is_array($structure->behaviors['XF:Indexable']['checkForUpdates'])) {
            array_push(
                $structure->behaviors['XF:Indexable']['checkForUpdates'],
                'topic_id',
                'additional_topic_ids'
            );
        }

        $structure->getters['topics'] = true;
        $structure->getters['additional_topics'] = true;
    }

    /**
     * @param Manager $em
     * @param Structure $structure
     */
    public static function xfForum(Manager $em, Structure &$structure)
    {
        $structure->relations['Topics'] = [
            'entity' => 'ThemeHouse\Topics:Topic',
            'type' => Entity::TO_MANY,
            'conditions' => 'node_id',
        ];
    }
}
