<?php

namespace ThemeHouse\Topics\Notifier\Post;

use ThemeHouse\Topics\XF\Entity\Thread;
use XF\Notifier\Post\AbstractWatch;

/**
 * Class TopicWatch
 * @package ThemeHouse\Topics\Notifier\Post
 */
class TopicWatch extends AbstractWatch
{
    /**
     * @return array
     */
    public function getDefaultWatchNotifyData()
    {
        $post = $this->post;
        /** @var Thread $thread */
        $thread = $post->Thread;

        if (!$thread->topic_id) {
            return [];
        }

        $finder = $this->app()->finder('ThemeHouse\Topics:TopicWatch');

        $finder->where('topic_id', $thread->topic_id)
            ->where('User.user_state', '=', 'valid')
            ->where('User.is_banned', '=', 0)
            ->whereOr(
                ['send_alert', '>', 0],
                ['send_email', '>', 0]
            );

        if ($this->actionType == 'reply') {
            $finder->where('notify_on', 'message');
        } else {
            $finder->where('notify_on', ['thread', 'message']);
        }

        $activeLimit = $this->app()->options()->watchAlertActiveOnly;
        if (!empty($activeLimit['enabled'])) {
            $finder->where('User.last_activity', '>=', \XF::$time - 86400 * $activeLimit['days']);
        }

        $notifyData = [];
        foreach ($finder->fetchColumns(['user_id', 'send_alert', 'send_email']) as $watch) {
            $notifyData[$watch['user_id']] = [
                'alert' => (bool)$watch['send_alert'],
                'email' => (bool)$watch['send_email']
            ];
        }

        return $notifyData;
    }

    /**
     * @return array
     */
    protected function getApplicableActionTypes()
    {
        return ['reply', 'thread'];
    }

    /**
     * @return string
     */
    protected function getWatchEmailTemplateName()
    {
        return 'thtopics_watched_topic_' . ($this->actionType == 'thread' ? 'thread' : 'reply');
    }
}
