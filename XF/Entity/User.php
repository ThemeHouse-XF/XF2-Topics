<?php

namespace ThemeHouse\Topics\XF\Entity;

/**
 * Class User
 * @package ThemeHouse\Topics\XF\Entity
 *
 * @property array thtopics_topic_filters
 * @property array thtopics_forum_filters
 */

use XF\Http\Request;

/**
 * Class User
 * @package ThemeHouse\Topics\XF\Entity
 *
 * @property array thtopics_forum_filters
 */
class User extends XFCP_User
{
    /**
     * @var array
     */
    protected $topicFilters = [];
    /**
     * @var array
     */
    protected $forumFilters = [];

    /**
     * @param Request|null $request
     * @param \XF\Entity\Forum|null $forum
     * @return array
     */
    public function getForumFilters(Request $request = null, \XF\Entity\Forum $forum = null)
    {
        if (!\XF::options()->thtopics_enableNodeTopics) {
            return [];
        }

        if (!$request) {
            $request = $this->app()->request();
        }

        if ($forum && $forum->node_id) {
            return [$forum->node_id];
        }

        if ($this->forumFilters) {
            return $this->forumFilters;
        }

        $defaultFilters = $this->getDefaultForumFilters();

        $nodeIds = $request->filter('forums', 'str', implode(',', $defaultFilters));
        $nodeIds = array_filter(explode(',', $nodeIds));

        foreach ($nodeIds as &$nodeId) {
            if (strpos($nodeId, '.') != false) {
                $parts = explode('.', $nodeId);
                $nodeId = intval($parts[1]);
            } else {
                $nodeId = intval($nodeId);
            }
        }

        $this->setForumFilters($nodeIds);

        return $this->forumFilters;
    }

    /**
     * @param $nodeIds
     */
    public function setForumFilters($nodeIds)
    {
        if (!\XF::options()->thtopics_enableNodeTopics) {
            return;
        }

        $nodeIds = array_filter($nodeIds);
        $this->forumFilters = $nodeIds;

        $currentValue = $this->getDefaultForumFilters();

        if ($currentValue !== $nodeIds) {
            if ($this->user_id) {
                $this->fastUpdate('thtopics_forum_filters', $nodeIds);
            } else {
                \XF::app()->session()->set('thtopics_forum_filters', $nodeIds);
            }
        }
    }

    /**
     * @return array
     */
    protected function getDefaultForumFilters()
    {
        if (!$this->user_id) {
            return \XF::app()->session()->get('thtopics_forum_filters') ?: [];
        }

        return $this->thtopics_forum_filters ?: [];
    }

    /**
     * @param Request|null $request
     * @param \XF\Entity\Forum|null $forum
     * @return array
     */
    public function getTopicFilters(Request $request = null, \XF\Entity\Forum $forum = null)
    {
        if (!\XF::options()->thtopics_enableTopics) {
            return [];
        }

        if (!$request) {
            $request = $this->app()->request();
        }

        if ($this->topicFilters) {
            return $this->topicFilters;
        }

        $defaultFilters = $this->getDefaultTopicFilters();

        $topicIds = $request->filter('topics', 'str', implode(',', $defaultFilters));
        $topicIds = array_filter(explode(',', $topicIds));

        foreach ($topicIds as &$topicId) {
            if (strpos($topicId, '.') != false) {
                $parts = explode('.', $topicId);
                $topicId = intval($parts[1]);
            } else {
                $topicId = intval($topicId);
            }
        }

        $this->setTopicFilters($topicIds);

        return $this->topicFilters;
    }

    /**
     * @param $topicIds
     */
    public function setTopicFilters($topicIds)
    {
        if (!\XF::options()->thtopics_enableTopics) {
            return;
        }

        $topicIds = array_filter($topicIds);
        $this->topicFilters = $topicIds;

        $currentValue = $this->getDefaultTopicFilters();

        if ($currentValue !== $topicIds) {
            if ($this->user_id) {
                $this->fastUpdate('thtopics_topic_filters', $topicIds);
            } else {
                \XF::app()->session()->set('thtopics_topic_filters', $topicIds);
            }
        }
    }

    /**
     * @return array
     */
    protected function getDefaultTopicFilters()
    {
        if (!$this->user_id) {
            return \XF::app()->session()->get('thtopics_topic_filters') ?: [];
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return $this->thtopics_topic_filters ?: [];
    }
}
