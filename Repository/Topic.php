<?php

namespace ThemeHouse\Topics\Repository;

use ThemeHouse\Topics\Util\Color;
use XF\Entity\Forum;
use XF\Entity\Thread;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Repository;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Repository
 */
class Topic extends Repository
{
    /**
     * @var
     */
    protected static $topicUtil;
    /**
     * @var
     */
    protected static $topics;

    /**
     * @var
     */
    protected $sortOrder;

    /**
     * @param string $url
     * @param array $params
     * @param null $data
     * @param string $routeType
     * @return mixed|string
     */
    public function getTopicFilterUrl($url = 'forums', $params = [], $data = null, $routeType = 'public')
    {
        $router = $this->app()->router($routeType);

        $queryStrings = [];

        /** @noinspection PhpUndefinedMethodInspection */
        $forumFilters = \XF::visitor()->getForumFilters();
        if ($forumFilters) {
            $forums = $this->em->getFinder('XF:Forum')
                ->where('node_id', '=', $forumFilters)
                ->fetch();

            $urlBits = [];
            foreach ($forums as $forum) {
                $urlBits[] = $router->prepareStringForUrl($forum->title) . ".{$forum->node_id}";
            }

            $queryStrings[] = 'forums=' . implode(',', $urlBits);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $topicFilters = \XF::visitor()->getTopicFilters();
        if ($topicFilters) {
            $topics = $this->em->getFinder('ThemeHouse\Topics:Topic')
                ->where('topic_id', '=', $topicFilters)
                ->fetch();

            $urlBits = [];
            foreach ($topics as $topic) {
                $urlBits[] = $router->prepareStringForUrl($topic->title) . ".{$topic->topic_id}";
            }

            $queryStrings[] = 'topics=' . implode(',', $urlBits);
        }

        $queryString = implode('&', $queryStrings);

        $routerUrl = $router->buildLink($url, $data, $params);

        return $routerUrl . (strlen($queryString) ? ((strpos(
                    $routerUrl,
                    '?'
                ) ? '&' : '?') . "{$queryString}") : '');
    }

    /**
     * @param Thread $thread
     * @return array|\XF\Mvc\Entity\Entity[]
     */
    public function getTopicsForThread(Thread $thread)
    {
        $topics = $this->getTopics()->toArray();

        $topics = array_filter($topics, function ($topic) use ($thread) {
            /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
            return $topic->topic_id == $thread->topic_id || in_array($topic->topic_id, $thread->additional_topic_ids);
        });

        return $topics;
    }

    /**
     * @return ArrayCollection
     */
    public function getTopics()
    {
        if (self::$topics === null) {
            $sortOrder = $this->getSortOrder();

            self::$topics = $this->finder('ThemeHouse\Topics:Topic')
                ->with('Forum')
                ->setDefaultOrder($sortOrder)
                ->fetch();
        }
        return self::$topics;
    }

    /**
     * @return array
     */
    protected function getSortOrder()
    {
        if (!$this->sortOrder) {
            $options = $this->app()->options();

            $defaultSortField = $options->thtopics_defaultSortField;
            $defaultSortOrder = $options->thtopics_defaultSortOrder ?: 'ASC';

            $sortOrder = [$defaultSortField, $defaultSortOrder];

            if (in_array($defaultSortField, ['last_post_date', 'message_count', 'discussion_count'])) {
                $defaultSortOrder = $options->thtopics_defaultSortOrder ?: 'DESC';
                $sortOrder = [
                    $sortOrder,
                    ['Forum.' . $defaultSortField, $defaultSortOrder]
                ];
            } elseif ($defaultSortField === 'last_post_date_primary') {
                $defaultSortOrder = $options->thtopics_defaultSortOrder ?: 'DESC';
                $sortOrder = [
                    $sortOrder,
                    ['Forum.' . 'last_post_date', $defaultSortOrder]
                ];
            }

            $this->sortOrder = $sortOrder;
        }

        return $this->sortOrder;
    }

    /**
     * @param ArrayCollection|array $threads
     * @param null $stickyThreads
     * @param bool $hydrateTopics
     */
    public function hydrateTopicsForThreads($threads, $stickyThreads = null, $hydrateTopics = false)
    {
        $topics = [];
        $topicIds = [];

        if ($hydrateTopics) {
            foreach ($threads as $thread) {
                if ($thread->topic_id && !in_array($thread->topic_id, $topicIds)) {
                    $topicIds[] = $thread->topic_id;
                }
            }
            if ($stickyThreads) {
                foreach ($stickyThreads as $thread) {
                    if ($thread->topic_id && !in_array($thread->topic_id, $topicIds)) {
                        $topicIds[] = $thread->topic_id;
                    }
                }
            }
        } else {
            foreach ($threads as $thread) {
                if ($thread->topic_id) {
                    $topics[$thread->topic_id] = $thread->Topic;
                }
            }
            if ($stickyThreads) {
                foreach ($stickyThreads as $thread) {
                    if ($thread->topic_id) {
                        $topics[$thread->topic_id] = $thread->Topic;
                    }
                }
            }
        }
        foreach ($threads as $thread) {
            if (!empty($thread->additional_topic_ids)) {
                foreach ($thread->additional_topic_ids as $topicId) {
                    if (!isset($topics[$topicId]) && !in_array($topicId, $topicIds)) {
                        $topicIds[] = $topicId;
                    }
                }
            }
        }
        if ($stickyThreads) {
            foreach ($stickyThreads as $thread) {
                if (!empty($thread->additional_topic_ids)) {
                    foreach ($thread->additional_topic_ids as $topicId) {
                        if (!isset($topics[$topicId]) && !in_array($topicId, $topicIds)) {
                            $topicIds[] = $topicId;
                        }
                    }
                }
            }
        }

        if ($topicIds) {
            $fetchedTopics = $this->finder('ThemeHouse\Topics:Topic')
                ->where('topic_id', $topicIds)
                ->with('Forum')
                ->fetch();

            foreach ($fetchedTopics as $topicId => $topic) {
                $topics[$topicId] = $topic;
            }
        }

        if ($hydrateTopics) {
            foreach ($threads as $thread) {
                if ($thread->topic_id && !empty($topics[$thread->topic_id])) {
                    $thread->hydrateRelation('Topic', $topics[$thread->topic_id]);
                }
            }
            if ($stickyThreads) {
                foreach ($stickyThreads as $thread) {
                    if ($thread->topic_id && !empty($topics[$thread->topic_id])) {
                        /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
                        $thread->hydrateRelation('Topic', $topics[$thread->topic_id]);
                    }
                }
            }
        }

        foreach ($threads as $thread) {
            if (!empty($thread->additional_topic_ids)) {
                $additionalTopics = [];
                foreach ($thread->additional_topic_ids as $topicId) {
                    if ($topicId && !empty($topics[$topicId])) {
                        $additionalTopics[$topicId] = $topics[$topicId];
                    }
                }
                $additionalTopics = new ArrayCollection($additionalTopics);
                $thread->hydrateRelation('AdditionalTopics', $additionalTopics);
            }
        }
        if ($stickyThreads) {
            foreach ($stickyThreads as $thread) {
                if (!empty($thread->additional_topic_ids)) {
                    $additionalTopics = [];
                    foreach ($thread->additional_topic_ids as $topicId) {
                        if ($topicId && !empty($topics[$topicId])) {
                            $additionalTopics[$topicId] = $topics[$topicId];
                        }
                    }
                    $additionalTopics = new ArrayCollection($additionalTopics);
                    /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
                    $thread->hydrateRelation('AdditionalTopics', $additionalTopics);
                }
            }
        }
    }

    /**
     * @param bool $filterViewable
     * @return array|ArrayCollection
     */
    public function getTopicsForList($filterViewable = true)
    {
        /** @var ArrayCollection $topics */
        $topics = $this->getTopics();

        foreach ($topics as $key => $topic) {
            if ($filterViewable && ($topic->Forum && !$topic->Forum->canView())) {
                $topics->offsetUnset($key);
            }
        }

        return $topics;
    }

    /**
     * @return \XF\Mvc\Entity\Finder
     */
    public function findTopicsForWatchedList()
    {
        $visitor = \XF::visitor();
        $userId = $visitor->user_id;

        $finder = $this->finder('ThemeHouse\Topics:Topic');
        $finder
            ->with('Watch|' . $userId, true)
            ->setDefaultOrder('last_post_date', 'DESC');

        return $finder;
    }

    /**
     * @param \ThemeHouse\Topics\Entity\Topic $topic
     * @param null $newRead
     * @return bool
     */
    public function markTopicReadByVisitor(\ThemeHouse\Topics\Entity\Topic $topic, $newRead = null)
    {
        $userId = \XF::visitor()->user_id;
        if (!$userId) {
            return false;
        }

        if ($newRead === null) {
            $newRead = \XF::$time;
        }

        $cutOff = \XF::$time - $this->options()->readMarkingDataLifetime * 86400;
        if ($newRead <= $cutOff) {
            return false;
        }

        $read = $topic->Read[$userId];
        if ($read && $newRead <= $read->topic_read_date) {
            return false;
        }

        $this->db()->insert('xf_th_topics_topic_read', [
            'topic_id' => $topic->topic_id,
            'user_id' => $userId,
            'topic_read_date' => $newRead
        ], false, 'topic_read_date = VALUES(topic_read_date)');

        return true;
    }

    /**
     * @param null $newRead
     * @return bool
     */
    public function markTopicsReadByVisitor($newRead = null)
    {
        $userId = \XF::visitor()->user_id;
        if (!$userId) {
            return false;
        }

        if ($newRead === null) {
            $newRead = \XF::$time;
        }

        $cutOff = \XF::$time - $this->options()->readMarkingDataLifetime * 86400;
        if ($newRead <= $cutOff) {
            return false;
        }

        $topics = $this->getTopics();
        foreach ($topics as $topic) {
            $read = $topic->Read[$userId];
            if ($read && $newRead <= $read->topic_read_date) {
                return false;
            }

            $this->db()->insert('xf_th_topics_topic_read', [
                'topic_id' => $topic->topic_id,
                'user_id' => $userId,
                'topic_read_date' => $newRead
            ], false, 'topic_read_date = VALUES(topic_read_date)');
        }

        return true;
    }

    /**
     * @param Forum $forum
     * @return null|\XF\Mvc\Entity\Entity
     */
    public function getTopicForForum(Forum $forum)
    {
        return $this->finder('ThemeHouse\Topics:Topic')
            ->where('node_id', '=', $forum->node_id)
            ->fetchOne();
    }

    /**
     * @param $topicId
     * @return null|\XF\Mvc\Entity\Entity
     */
    public function getTopicById($topicId)
    {
        return $this->finder('ThemeHouse\Topics:Topic')
            ->where('topic_id', '=', $topicId)
            ->fetchOne();
    }

    /**
     * @throws \XF\PrintableException
     */
    public function rebuildTopicCache()
    {
        $topics = $this->finder('ThemeHouse\Topics:Topic')->fetch();

        $cssCache = [];
        foreach ($topics as $topic) {
            if ($css = $this->generateCssForTopic($topic)) {
                $cssCache[] = $css;
            }
        }

        $this->buildTopicsLessTemplate($cssCache);
    }

    /**
     * @param \ThemeHouse\Topics\Entity\Topic $topic
     * @return bool|string
     */
    protected function generateCssForTopic(\ThemeHouse\Topics\Entity\Topic $topic)
    {
        if ($topic->background_color) {
            $isLight = Color::isColorLight($topic->background_color);
            $params = [
                'topic' => [
                    'topic_id' => $topic->topic_id,
                    'isLight' => $isLight,
                    'colors' => [
                        'background' => $topic->background_color,
                        'text' => $isLight ? '@xf-thtopics_textColorForLightBg' : '@xf-thtopics_textColorForDarkBg'
                    ]
                ]
            ];

            $templater = \XF::app()->templater();

            $css = $templater->renderMacro('public:thtopics_topic_generator_macros', 'generator', $params);

            return $css;
        } else {
            return false;
        }
    }

    /**
     * @param array $cssCache
     * @return null|\XF\Mvc\Entity\Entity
     * @throws \XF\PrintableException
     */
    protected function buildTopicsLessTemplate(array $cssCache)
    {
        $css = implode("\n", $cssCache);

        /** @var \XF\Entity\Template $template */
        $template = $this->finder('XF:Template')->where('style_id', '=', 0)
            ->where('title', '=', 'thtopics_topic_cache.less')
            ->fetchOne();

        if (empty($template)) {
            $template = $this->em->create('XF:Template');
            $template->bulkSet([
                'type' => 'public',
                'title' => 'thtopics_topic_cache.less',
                'style_id' => 0,
            ]);
        }

        $template->template = "<xf:comment>Do not directly edit this template, doing so will cause issues when you edit your topics.</xf:comment>\n\n" . $css;
        $template->addon_id = '';
        $template->last_edit_date = \XF::$time;
        $template->save();

        return $template;
    }
}
