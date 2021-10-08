<?php

namespace ThemeHouse\Topics\XF\Service\Thread;

use ThemeHouse\Topics\Entity\Topic;

/**
 * Class Creator
 * @package ThemeHouse\Topics\XF\Service\Thread
 */
class Creator extends XFCP_Creator
{
    /**
     * @param $topicId
     */
    public function setTopicId($topicId)
    {
        if ($topicId instanceof Topic) {
            $topicId = $topicId->topic_id;
        }
        $this->thread->set('topic_id', $topicId);
    }

    /**
     * @param array $additionalTopicIds
     */
    public function setAdditionalTopicIds(array $additionalTopicIds)
    {
        if (\XF::options()->thtopics_maxAdditionalTopics) {
            $this->thread->set('additional_topic_ids', $additionalTopicIds);
        }
    }
}
