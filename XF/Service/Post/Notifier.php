<?php

namespace ThemeHouse\Topics\XF\Service\Post;

/**
 * Class Notifier
 * @package ThemeHouse\Topics\XF\Service\Post
 */
class Notifier extends XFCP_Notifier
{
    /**
     * @return array
     */
    protected function loadNotifiers()
    {
        $notifiers = parent::loadNotifiers();

        if (\XF::options()->thtopics_enableTopics) {
            $notifiers['topicWatch'] = $this->app->notifier(
                'ThemeHouse\Topics:Post\TopicWatch',
                $this->post,
                $this->actionType
            );
        }

        return $notifiers;
    }
}
