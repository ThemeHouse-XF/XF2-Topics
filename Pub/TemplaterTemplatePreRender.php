<?php

namespace ThemeHouse\Topics\Pub;

use ThemeHouse\Topics\Entity\Topic;
use ThemeHouse\Topics\XF\Entity\Forum;
use XF\Template\Templater;

/**
 * Class TemplaterTemplatePreRender
 * @package ThemeHouse\Topics\Pub
 */
class TemplaterTemplatePreRender
{
    /**
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function thtopicsFilterbarTopics(Templater $templater, &$type, &$template, array &$params)
    {
        if (!empty(\XF::options()->thtopics_enableTopics) && !isset($params['topics'])) {
            /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
            $topicRepo = \XF::repository('ThemeHouse\Topics:Topic');
            $topics = $topicRepo->getTopicsForList();

            $params['topics'] = $topics;
        }

        if (!isset($params['nodeTree'])) {
            /** @var \ThemeHouse\Topics\XF\Repository\Forum $forumRepo */
            $forumRepo = \XF::repository('XF:Forum');
            $nodeTree = $forumRepo->getNodeTreeForFilterList();

            $params['nodeTree'] = $nodeTree;
        }
    }

    /**
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function thtrendingTrendingViewThread(Templater $templater, &$type, &$template, array &$params)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $forumFilters = \XF::visitor()->getForumFilters();

        /** @noinspection PhpUndefinedMethodInspection */
        $topicFilters = \XF::visitor()->getTopicFilters();

        if (count($forumFilters) === 1) {
            $nodeId = reset($forumFilters);
            /** @var Forum $forum */
            $forum = \XF::em()->getFinder('XF:Forum')
                ->where('node_id', $nodeId)
                ->with('Watch|' . \XF::visitor()->user_id, true)
                ->fetchOne();
        }

        if (count($topicFilters) === 1) {
            $topicId = reset($topicFilters);
            /** @var Topic $topic */
            $topic = \XF::em()->getFinder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicId)
                ->with('Forum')
                ->fetchOne();

            if ($topic && $topic->Forum && (!isset($forum) || $forum === null || $topic->Forum->node_id === $forum->node_id)) {
                $forum = $topic->Forum;
            } else {
                $forum = null;
            }
        } elseif (count($topicFilters) > 1) {
            $groupedTopics = \XF::em()->getFinder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicFilters)
                ->with('Forum')
                ->fetch()
                ->groupBy('node_id');
            if (count($groupedTopics) == 1) {
                $topics = reset($groupedTopics);
                $topic = reset($topics);
                if ($topic && $topic->Forum && (!isset($forum) || $forum === null || $topic->Forum->node_id === $forum->node_id)) {
                    $forum = $topic->Forum;
                } else {
                    $forum = null;
                }
                $topic = null;
            }
        }

        if (isset($topic)) {
            $params['topic'] = $topic;
        }

        if (isset($forum)) {
            $params['forum'] = $forum;
        }
    }
}
