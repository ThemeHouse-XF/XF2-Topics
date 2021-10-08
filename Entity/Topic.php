<?php

namespace ThemeHouse\Topics\Entity;

use ThemeHouse\Core\Util\Color;
use XF\Draft;
use XF\Entity\Forum;
use XF\Entity\Node;
use XF\Entity\Thread;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Entity
 *
 * COLUMNS
 * @property integer topic_id
 * @property integer topic_group_id
 * @property string title
 * @property string description
 * @property string background_color
 * @property string extra_class
 * @property boolean additional_selectable
 * @property integer node_id
 *
 * @property integer discussion_count
 * @property integer message_count
 *
 * @property integer last_post_id
 * @property integer last_post_date
 * @property integer last_post_user_id
 * @property string last_post_username
 * @property integer last_post_date_primary
 * @property integer last_thread_title
 * @property integer last_thread_prefix_id
 *
 * RELATIONS
 * @property Forum Forum
 * @property Node Node
 * @property TopicGroup TopicGroup
 * @property ArrayCollection DraftThreads
 * @property ArrayCollection Watch
 * @property ArrayCollection Read
 *
 * GETTERS
 * @property Draft draft_thread
 * @property int list_date_limit_days
 * @property string default_sort_order
 * @property string default_sort_direction
 * @property string allowed_watch_notifications
 * @property int default_prefix_id
 * @property array field_cache
 * @property array last_post_cache
 */
class Topic extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_th_topics_topic';
        $structure->shortName = 'ThemeHouse\Topics:Topic';
        $structure->primaryKey = 'topic_id';

        $structure->columns = [
            'topic_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'topic_group_id' => ['type' => self::UINT, 'default' => 0],
            'title' => ['type' => self::STR, 'required' => true, 'maxLength' => 30],
            'description' => ['type' => self::STR, 'default' => ''],
            'background_color' => ['type' => self::STR, 'default' => Color::getRandomMaterialColor()],
            'extra_class' => ['type' => self::STR, 'default' => '', 'maxLength' => 50],
            'additional_selectable' => ['type' => self::BOOL, 'default' => 1],
            'node_id' => ['type' => self::UINT, 'default' => 0],

            /* Topic Stats */
            'discussion_count' => ['type' => self::UINT, 'forced' => true, 'default' => 0],
            'message_count' => ['type' => self::UINT, 'forced' => true, 'default' => 0],

            /* Last Post Cache */
            'last_post_id' => ['type' => self::UINT, 'default' => 0],
            'last_post_date' => ['type' => self::UINT, 'default' => 0],
            'last_post_user_id' => ['type' => self::UINT, 'default' => 0],
            'last_post_username' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'last_post_date_primary' => ['type' => self::UINT, 'default' => 0],
            'last_thread_title' => ['type' => self::STR, 'maxLength' => 150, 'default' => ''],
            'last_thread_prefix_id' => ['type' => self::UINT, 'default' => 0],
        ];

        $structure->getters = [
            'allowed_watch_notifications' => true,
            'default_prefix_id' => true,
            'default_sort_order' => true,
            'default_sort_direction' => true,
            'draft_thread' => true,
            'field_cache' => true,
            'last_post_cache' => true,
            'list_date_limit_days' => true,
        ];

        $structure->relations = [
            'DraftThreads' => [
                'entity' => 'XF:Draft',
                'type' => self::TO_MANY,
                'conditions' => [
                    ['draft_key', '=', 'topic-', '$topic_id']
                ],
                'key' => 'user_id',
            ],
            'Forum' => [
                'entity' => 'XF:Forum',
                'type' => self::TO_ONE,
                'conditions' => 'node_id',
            ],
            'Node' => [
                'entity' => 'XF:Node',
                'type' => self::TO_ONE,
                'conditions' => 'node_id',
            ],
            'Read' => [
                'entity' => 'ThemeHouse\Topics:TopicRead',
                'type' => self::TO_MANY,
                'conditions' => 'topic_id',
                'key' => 'user_id',
            ],
            'Watch' => [
                'entity' => 'ThemeHouse\Topics:TopicWatch',
                'type' => self::TO_MANY,
                'conditions' => 'topic_id',
                'key' => 'user_id',
            ],
            'TopicGroup' => [
                'entity' => 'ThemeHouse\Topics:TopicGroup',
                'type' => self::TO_ONE,
                'conditions' => 'topic_group_id',
            ],
        ];

        return $structure;
    }

    /**
     * @param null $error
     * @return bool
     */
    public function canCreatePoll(&$error = null)
    {
        if ($forum = $this->getForum()) {
            return $forum->canCreatePoll($error);
        } else {
            return false;
        }
    }

    /**
     * @return null|Forum
     */
    protected function getForum()
    {
        if ($this->node_id) {
            return $this->Forum;
        } else {
            return null;
        }
    }

    /**
     * @param string $error
     * @return bool
     */
    public function canCreateThread(&$error = null)
    {
        if ($forum = $this->getForum()) {
            return $forum->canCreateThread($error);
        } else {
            return false;
        }
    }

    /**
     * @param null $error
     * @return bool
     */
    public function canEditTags(&$error = null)
    {
        if ($forum = $this->getForum()) {
            return $forum->canEditTags($error);
        } else {
            return false;
        }
    }

    /**
     * @param null $error
     * @return bool
     */
    public function canView(&$error = null)
    {
        if (!$this->node_id) {
            return true;
        }

        if (!$this->Forum || !$this->Forum->canView($error)) {
            return false;
        }

        return true;
    }

    /**
     * @param null $error
     * @return bool
     */
    public function canWatch(&$error = null)
    {
        return \XF::visitor()->user_id ? true : false;
    }

    /**
     * @return int
     */
    public function getListDateLimitDays()
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getDefaultSortOrder()
    {
        return 'last_post_date';
    }

    /**
     * @return string
     */
    public function getDefaultSortDirection()
    {
        return 'desc';
    }

    /**
     * @return string
     */
    public function getAllowedWatchNotifications()
    {
        return 'all';
    }

    /**
     * @return array
     */
    public function getLastPostCache()
    {
        return [
            'last_post_id' => $this->last_post_id,
            'last_post_date' => $this->last_post_date,
            'last_post_user_id' => $this->last_post_user_id,
            'last_post_username' => $this->last_post_username,
            'last_post_date_primary' => $this->last_post_date_primary,
            'last_thread_title' => $this->last_thread_title,
            'last_thread_prefix_id' => $this->last_thread_prefix_id
        ];
    }

    /**
     * @return array
     */
    public function getUsablePrefixes()
    {
        if ($forum = $this->getForum()) {
            return $forum->getUsablePrefixes();
        } else {
            return [];
        }
    }

    /**
     * @return int
     */
    public function getDefaultPrefixId()
    {
        if ($forum = $this->getForum()) {
            return $forum->default_prefix_id;
        } else {
            return 0;
        }
    }

    /**
     * @return array
     */
    public function getFieldCache()
    {
        if ($forum = $this->getForum()) {
            return $forum->field_cache;
        } else {
            return null;
        }
    }

    /**
     * @param $topics
     * @return string
     */
    public function getChildClassList($topics)
    {
        $ids = [];
        foreach ($topics as $topic) {
            if ($this->additional_selectable && $this->topic_id !== $topic->topic_id) {
                $ids[] = $topic->topic_id;
            }
        }

        if (!empty($ids)) {
            return "thTopicList__topic--child" . implode(" thTopicList__topic--child", $ids);
        } else {
            return '';
        }
    }

    /**
     * @return bool
     */
    public function isUnread()
    {
        if (!$this->discussion_count) {
            return false;
        }

        $cutOff = \XF::$time - $this->app()->options()->readMarkingDataLifetime * 86400;
        if ($this->last_post_date_primary < $cutOff) {
            return false;
        }

        $visitor = \XF::visitor();
        if ($visitor->user_id) {
            $topicRead = $this->Read[$visitor->user_id];
            $forumRead = $this->Forum->Read[$visitor->user_id];
            if (!$topicRead && !$forumRead) {
                return true;
            } elseif (!$topicRead) {
                return ($forumRead->forum_read_date < $this->last_post_date_primary);
            } elseif (!$forumRead) {
                return ($topicRead->topic_read_date < $this->last_post_date_primary);
            }
            return ($topicRead->topic_read_date < $this->last_post_date_primary && $forumRead->forum_read_date < $this->last_post_date_primary);
        } else {
            return true;
        }
    }

    /**
     * @param Thread $thread
     * @param bool $isAdditional
     */
    public function threadDataChanged(Thread $thread, $isAdditional = false)
    {
        $isRedirect = $thread->discussion_type == 'redirect';
        $wasRedirect = $thread->getExistingValue('discussion_type') == 'redirect';

        if ($isRedirect && !$wasRedirect) {
            // this is like the thread being deleted for counter purposes
            $this->threadRemoved($thread);
        } else {
            if (!$isRedirect && $wasRedirect) {
                // like being added
                $this->threadAdded($thread, $isAdditional);
            } else {
                if ($isRedirect) {
                    return;
                }
            }
        }

        $this->message_count += $thread->reply_count - $thread->getExistingValue('reply_count');

        if ($thread->last_post_date >= $this->last_post_date) {
            $this->last_post_date = $thread->last_post_date;
            $this->last_post_id = $thread->last_post_id;
            $this->last_post_user_id = $thread->last_post_user_id;
            $this->last_post_username = $thread->last_post_username;
            $this->last_thread_title = $thread->title;
            $this->last_thread_prefix_id = $thread->prefix_id;

            if (!$isAdditional && $thread->last_post_date >= $this->last_post_date_primary) {
                $this->last_post_date_primary = $thread->last_post_date;
            }
        } else {
            if ($thread->getExistingValue('last_post_id') == $this->last_post_id) {
                $this->rebuildLastPost();
            }
        }
    }

    /**
     * @param Thread $thread
     */
    public function threadRemoved(Thread $thread)
    {
        if ($thread->discussion_type == 'redirect') {
            // if this was changed, it used to count so we need to continue
            if (!$thread->isChanged('discussion_type')) {
                return;
            }
        }

        $this->discussion_count--;
        $this->message_count -= 1 + $thread->reply_count;

        if ($thread->last_post_id == $this->last_post_id) {
            $this->rebuildLastPost();
        }
    }

    /**
     *
     */
    public function rebuildLastPost()
    {
        $thread = $this->db()->fetchRow("
			SELECT *
			FROM xf_thread
			WHERE topic_id = ? OR FIND_IN_SET(?, additional_topic_ids)
				AND discussion_state = 'visible'
				AND discussion_type <> 'redirect'
			ORDER BY last_post_date DESC
			LIMIT 1
		", [$this->topic_id, $this->topic_id]);

        if ($thread) {
            $this->last_post_id = $thread['last_post_id'];
            $this->last_post_date = $thread['last_post_date'];
            $this->last_post_user_id = $thread['last_post_user_id'];
            $this->last_post_username = $thread['last_post_username'];
            $this->last_thread_title = $thread['title'];
            $this->last_thread_prefix_id = $thread['prefix_id'];
            $this->last_post_date_primary = $thread['last_post_date'];
        } else {
            $this->last_post_id = 0;
            $this->last_post_date = 0;
            $this->last_post_user_id = 0;
            $this->last_post_username = '';
            $this->last_thread_title = '';
            $this->last_thread_prefix_id = 0;
            $this->last_post_date_primary = 0;
        }

        if ($thread && $thread['topic_id'] != $this->topic_id) {
            $thread = $this->db()->fetchRow("
                SELECT *
                FROM xf_thread
                WHERE topic_id = ?
                    AND discussion_state = 'visible'
                    AND discussion_type <> 'redirect'
                ORDER BY last_post_date DESC
                LIMIT 1
            ", $this->topic_id);
            if ($thread) {
                $this->last_post_date_primary = $thread['last_post_date'];
            } else {
                $this->last_post_date_primary = 0;
            }
        }
    }

    /**
     * @param Thread $thread
     * @param bool $isAdditional
     */
    public function threadAdded(Thread $thread, $isAdditional = false)
    {
        if ($thread->discussion_type == 'redirect') {
            return;
        }

        $this->discussion_count++;
        $this->message_count += 1 + $thread->reply_count;

        if ($thread->last_post_date >= $this->last_post_date) {
            $this->last_post_date = $thread->last_post_date;
            $this->last_post_id = $thread->last_post_id;
            $this->last_post_user_id = $thread->last_post_user_id;
            $this->last_post_username = $thread->last_post_username;
            $this->last_thread_title = $thread->title;
            $this->last_thread_prefix_id = $thread->prefix_id;
        }

        if (!$isAdditional && $thread->last_post_date >= $this->last_post_date_primary) {
            $this->last_post_date_primary = $thread->last_post_date;
        }
    }

    /**
     * @return bool
     */
    public function rebuildCounters()
    {
        $counters = $this->db()->fetchRow("
            SELECT COUNT(*) AS discussion_count,
                COUNT(*) + COALESCE(SUM(reply_count), 0) AS message_count
            FROM xf_thread
            WHERE (topic_id = ? || FIND_IN_SET(?, additional_topic_ids))
                AND discussion_state = 'visible'
                AND discussion_type <> 'redirect'
        ", [$this->topic_id, $this->topic_id]);

        $this->discussion_count = $counters['discussion_count'];
        $this->message_count = $counters['message_count'];

        $this->rebuildLastPost();

        return true;
    }

    /**
     * @throws \XF\PrintableException
     */
    protected function _postSave()
    {
        if ($this->isInsert()) {
            /** @var \XF\Entity\Option $option */
            $option = $this->em()->find('XF:Option', 'thtopic_topicListData');
            $value = json_decode($option->option_value);
            $value[] = ['id' => $this->topic_id, 'children' => []];
            $option->option_value = json_encode($value);
            $option->save();
        }

        $this->getTopicRepo()->rebuildTopicCache();
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic
     */
    protected function getTopicRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:Topic');
    }

    /**
     * @throws \XF\PrintableException
     */
    protected function _postDelete()
    {
        $db = $this->db();

        $db->update('xf_thread', ['topic_id' => 0], "topic_id = {$this->topic_id}");

        $db->rawQuery("
            UPDATE xf_thread
            SET additional_topic_ids = TRIM(BOTH ','
            FROM REPLACE(CONCAT(',', additional_topic_ids, ','), ',{$this->topic_id},', ','))
            WHERE FIND_IN_SET('{$this->topic_id}', additional_topic_ids)
        ");

        /** @var \XF\Entity\Option $option */
        $option = $this->em()->find('XF:Option', 'thtopic_topicListData');
        $value = json_decode($option->option_value);
        $newValue = [];
        foreach ($value as $topic) {
            if (isset($topic->id) && $topic->id !== $this->topic_id) {
                $newChildren = [];
                if (isset($topic->children)) {
                    foreach ($topic->children as $child) {
                        if (isset($child->id) && $child->id !== $this->topic_id) {
                            $newChildren[] = [
                                'id' => $child->id,
                                'children' => isset($child->children) ? $child->children : []
                            ];
                        }
                    }
                }
                $newValue[] = ['id' => $topic->id, 'children' => $newChildren];
            }
        }
        $option->option_value = json_encode($newValue);
        $option->save();

        $this->getTopicRepo()->rebuildTopicCache();
    }
}
