<?php

namespace ThemeHouse\Topics\Listener;

use ThemeHouse\Topics\Entity\Topic;
use ThemeHouse\Topics\XF\Repository\Forum;
use XF\Container;
use XF\Entity\Node;
use XF\Template\Templater;

/**
 * Class TemplaterSetup
 * @package ThemeHouse\Topics\Listener
 */
class TemplaterSetup
{
    /**
     * @param Container $container
     * @param Templater $templater
     */
    public static function templaterSetup(Container $container, Templater &$templater)
    {
        $templater->addFunction('th_thread_topic_titles_json', function (Templater $templater, &$escape) {
            /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
            $topicRepo = \XF::repository('ThemeHouse\Topics:Topic');
            $topics = $topicRepo->getTopicsForList();

            $topicObj = [];
            foreach ($topics as $topic) {
                /** @var Topic $topic */
                $topicObj[$topic->topic_id] = [
                    'title' => $topic->title,
                ];
            }

            $escape = false;

            return json_encode($topicObj);
        });

        $templater->addFunction('th_node_topic_titles_json', function (Templater $templater, &$escape) {
            /** @var Forum $forumRepo */
            $forumRepo = \XF::repository('XF:Forum');
            $nodeTree = $forumRepo->getNodeTreeForFilterList();
            $nodes = $nodeTree->getFlattened();

            $nodeObj = [];
            foreach ($nodes as $node) {
                /** @var Node $node */
                $nodeObj[$node['record']->node_id] = [
                    'title' => $node['record']->title,
                ];
            }

            $escape = false;

            return json_encode($nodeObj);
        });

        $templater->addFunction('th_topics_json_raw', function (Templater $templater, &$escape, $topics, $cast = null) {
            if (empty($topics)) {
                return '[]';
            }

            if ($cast) {
                foreach ($topics as &$topic) {
                    if ($cast == 'int') {
                        $topic = (int)$topic;
                    }
                }
            }

            return json_encode($topics);
        });

        $templater->addFunction('th_topics_topic_url', function (Templater $templater, &$escape, Topic $topic) {
            return \XF::app()->router()->buildLink('topics', $topic);
        });
    }
}
