<?php

namespace ThemeHouse\Topics\XF\Repository;

/**
 * Class Forum
 * @package ThemeHouse\Topics\XF\Repository
 */
/**
 * Class Forum
 * @package ThemeHouse\Topics\XF\Repository
 */
class Forum extends XFCP_Forum
{
    /**
     * @var null
     */
    protected static $fullNodeListForForumFilter = null;

    /**
     * @param \XF\Entity\Forum $forum
     * @param null $newRead
     * @return bool
     */
    public function markForumReadByVisitor(\XF\Entity\Forum $forum, $newRead = null)
    {
        $userId = \XF::visitor()->user_id;
        if (!$userId) {
            return false;
        }

        if ($newRead === null) {
            $newRead = max(\XF::$time, $forum->last_post_date);
        }

        if (parent::markForumReadByVisitor($forum, $newRead) === false) {
            return false;
        }

        if (\XF::options()->thtopics_enableTopics) {
            /** @var \ThemeHouse\Topics\XF\Entity\Forum $forum */
            $topics = $forum->Topics;

            foreach ($topics as $topic) {
                $read = $topic->Read[$userId];
                if ($read && $newRead <= $read->topic_read_date) {
                    continue;
                }

                $this->db()->insert('xf_th_topics_topic_read', [
                    'topic_id' => $topic->topic_id,
                    'user_id' => $userId,
                    'topic_read_date' => $newRead
                ], false, 'topic_read_date = VALUES(topic_read_date)');
            }
        }

        return true;
    }

    /**
     * @param bool $filterViewable
     * @return \XF\Tree
     */
    public function getNodeTreeForFilterList($filterViewable = true)
    {
        /** @var \XF\Repository\Node $nodeRepo */
        $nodeRepo = $this->app()->repository('XF:Node');

        if (self::$fullNodeListForForumFilter === null) {
            $nodes = $nodeRepo->getFullNodeList();
            $nodes = $nodes->filter(function (\XF\Entity\Node $node) {
                return ($node->node_type_id === 'Forum' || $node->node_type_id === 'Category');
            });
            $nodeRepo->loadNodeTypeDataForNodes($nodes);
            self::$fullNodeListForForumFilter = $nodes;
        } else {
            $nodes = self::$fullNodeListForForumFilter;
        }

        if ($filterViewable) {
            $nodes = $nodeRepo->filterViewable($nodes);
        }
        return $nodeRepo->createNodeTree($nodes);
    }
}
