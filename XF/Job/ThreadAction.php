<?php

namespace ThemeHouse\Topics\XF\Job;

use XF\Entity\Thread;

/**
 * Class ThreadAction
 * @package ThemeHouse\Topics\XF\Job
 */
class ThreadAction extends XFCP_ThreadAction
{
    /**
     * @param Thread $thread
     */
    protected function applyInternalThreadChange(Thread $thread)
    {
        parent::applyInternalThreadChange($thread);

        if ($this->getActionValue('apply_thread_topic')) {
            /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
            $thread->topic_id = intval($this->getActionValue('topic_id'));
        }

        if ($this->getActionValue('apply_thread_topic_additional')) {
            $thread->additional_topic_ids = $this->getActionValue('additional_topic_ids');
        }
    }
}
