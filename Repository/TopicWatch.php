<?php

namespace ThemeHouse\Topics\Repository;

use ThemeHouse\Topics\Entity\Topic;
use XF\Entity\User;
use XF\Mvc\Entity\Repository;

/**
 * Class TopicWatch
 * @package ThemeHouse\Topics\Repository
 */
class TopicWatch extends Repository
{
    /**
     * @param Topic $topic
     * @param User $user
     * @param null $notifyType
     * @param null $sendAlert
     * @param null $sendEmail
     * @throws \XF\PrintableException
     */
    public function setWatchState(Topic $topic, User $user, $notifyType = null, $sendAlert = null, $sendEmail = null)
    {
        if (!$topic->topic_id || !$user->user_id) {
            throw new \InvalidArgumentException("Invalid topic or user");
        }

        $watch = $this->em->find('ThemeHouse\Topics:TopicWatch', [
            'topic_id' => $topic->topic_id,
            'user_id' => $user->user_id
        ]);

        switch ($notifyType) {
            case 'message':
            case 'thread':
            case '':
            case null:
                if (!$watch) {
                    /** @var \ThemeHouse\Topics\Entity\TopicWatch $watch */
                    $watch = $this->em->create('ThemeHouse\Topics:TopicWatch');
                    $watch->topic_id = $topic->topic_id;
                    $watch->user_id = $user->user_id;
                }
                if ($notifyType !== null) {
                    $watch->notify_on = $notifyType;
                }
                if ($sendAlert !== null) {
                    $watch->send_alert = $sendAlert;
                }
                if ($sendEmail !== null) {
                    $watch->send_email = $sendEmail;
                }
                $watch->save();
                break;

            case 'delete':
                if ($watch) {
                    $watch->delete();
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown notify type '$notifyType'");
        }
    }

    /**
     * @param User $user
     * @param $state
     * @return int
     */
    public function setWatchStateForAll(User $user, $state)
    {
        if (!$user->user_id) {
            throw new \InvalidArgumentException("Invalid user");
        }

        $db = $this->db();

        switch ($state) {
            case 'watch_email':
            case 'email':
                return $db->update('xf_th_topics_topic_watch', ['send_email' => 1], 'user_id = ?', $user->user_id);

            case 'watch_no_email':
            case 'no_email':
                return $db->update('xf_th_topics_topic_watch', ['send_email' => 0], 'user_id = ?', $user->user_id);

            case 'watch_alert':
            case 'alert':
                return $db->update('xf_th_topics_topic_watch', ['send_alert' => 1], 'user_id = ?', $user->user_id);

            case 'watch_no_alert':
            case 'no_alert':
                return $db->update('xf_th_topics_topic_watch', ['send_alert' => 0], 'user_id = ?', $user->user_id);

            case 'delete':
            case 'stop':
            case '':
                return $db->delete('xf_th_topics_topic_watch', 'user_id = ?', $user->user_id);

            default:
                throw new \InvalidArgumentException("Unknown state '$state'");
        }
    }

    /**
     * @param $state
     * @return bool
     */
    public function isValidWatchState($state)
    {
        switch ($state) {
            case 'watch_email':
            case 'email':
            case 'watch_no_email':
            case 'no_email':
            case 'watch_alert':
            case 'alert':
            case 'watch_no_alert':
            case 'no_alert':
            case 'delete':
            case 'stop':
            case '':
                return true;

            default:
                return false;
        }
    }
}
