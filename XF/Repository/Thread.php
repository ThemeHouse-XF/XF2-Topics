<?php

namespace ThemeHouse\Topics\XF\Repository;

use ThemeHouse\Topics\Entity\Topic;
use XF\Entity\User;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Repository
 */
class Thread extends XFCP_Thread
{
    /**
     * @param \XF\Entity\Forum $forum
     * @param array $limits
     * @return \XF\Finder\Thread
     */
    public function findThreadsForForumView(\XF\Entity\Forum $forum, array $limits = [])
    {
        $finder = parent::findThreadsForForumView($forum, $limits);

        if (\XF::options()->thtopics_enableTopics) {
            $finder->with('Topic');
        }

        return $finder;
    }

    /**
     * @return $this|\ThemeHouse\Topics\XF\Finder\Thread
     */
    public function findThreadsWithNoReplies()
    {
        /** @var \ThemeHouse\Topics\XF\Finder\Thread $finder */
        $finder = parent::findThreadsWithNoReplies();

        if (\XF::options()->thtopics_enableTopics) {
            $finder->with('Topic');
        }

        if (\XF::options()->thtopics_topicFilterFindThreadsPages) {
            $finder->filterByTopicsAndForums();
        }

        return $finder;
    }

    /**
     * @param $userId
     * @return $this|\ThemeHouse\Topics\XF\Finder\Thread
     */
    public function findThreadsWithPostsByUser($userId)
    {
        /** @var \ThemeHouse\Topics\XF\Finder\Thread $finder */
        $finder = parent::findThreadsWithPostsByUser($userId);

        if (\XF::options()->thtopics_enableTopics) {
            $finder->with('Topic');
        }

        if (\XF::options()->thtopics_topicFilterFindThreadsPages) {
            $finder->filterByTopicsAndForums();
        }

        return $finder;
    }

    /**
     * @param $userId
     * @return $this|\ThemeHouse\Topics\XF\Finder\Thread
     */
    public function findThreadsStartedByUser($userId)
    {
        /** @var \ThemeHouse\Topics\XF\Finder\Thread $finder */
        $finder = parent::findThreadsStartedByUser($userId);

        if (\XF::options()->thtopics_enableTopics) {
            $finder->with('Topic');
        }

        if (\XF::options()->thtopics_topicFilterFindThreadsPages) {
            $finder->filterByTopicsAndForums();
        }

        return $finder;
    }

    /**
     * @param \XF\Entity\Thread $thread
     * @param null $newRead
     * @return bool
     */
    public function markThreadReadByVisitor(\XF\Entity\Thread $thread, $newRead = null)
    {
        if (parent::markThreadReadByVisitor($thread, $newRead) === false) {
            return false;
        }

        if (\XF::options()->thtopics_enableTopics) {
            /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
            $topic = $thread->Topic;
            $user = \XF::visitor();
            if ($topic && !$this->countUnreadThreadsWithTopicForUser($topic, $thread->Forum, $user)) {
                /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
                $topicRepo = $this->repository('ThemeHouse\Topics:Topic');
                $topicRepo->markTopicReadByVisitor($topic);
            }
        }

        return true;
    }

    /**
     * @param Topic $topic
     * @param \XF\Entity\Forum $forum
     * @param User $user
     * @return int
     */
    public function countUnreadThreadsWithTopicForUser(
        Topic $topic,
        \XF\Entity\Forum $forum,
        User $user
    ) {
        $userId = $user->user_id;
        if (!$userId) {
            return 0;
        }

        $topicRead = $topic->Read[$userId];
        $forumRead = $forum->Read[$userId];
        $cutOff = $this->getReadMarkingCutOff();

        $topicReadDate = $topicRead ? $topicRead->topic_read_date : 0;
        $forumReadDate = $forumRead ? $forumRead->forum_read_date : 0;
        $readDate = max($topicReadDate, $forumReadDate, $cutOff);

        /** @var \ThemeHouse\Topics\XF\Finder\Thread $finder */
        $finder = $this->finder('XF:Thread');
        $finder
            ->where('topic_id', $topic->topic_id)
            ->where('last_post_date', '>', $readDate)
            ->where('discussion_state', 'visible')
            ->where('discussion_type', '<>', 'redirect')
            ->whereOr(
                ["Forum.Read|{$userId}.forum_read_date", null],
                [$finder->expression('%s > %s', 'last_post_date', "Forum.Read|{$userId}.forum_read_date")]
            )
            ->whereOr(
                ["Read|{$userId}.thread_read_date", null],
                [$finder->expression('%s > %s', 'last_post_date', "Read|{$userId}.thread_read_date")]
            )
            ->skipIgnored();

        return $finder->total();
    }
}
