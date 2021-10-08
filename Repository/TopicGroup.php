<?php

namespace ThemeHouse\Topics\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class TopicGroup
 * @package ThemeHouse\Topics\Repository
 */
class TopicGroup extends Repository
{
    /**
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    public function getTopicGroups()
    {
        return $this->finder('ThemeHouse\Topics:TopicGroup')
            ->order(['topic_group_id'])
            ->fetch();
    }

    /**
     * @return Finder
     */
    public function findTopicGroupsForList()
    {
        return $this->finder('ThemeHouse\Topics:TopicGroup')->order(['topic_group_id']);
    }
}
