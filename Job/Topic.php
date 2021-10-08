<?php

namespace ThemeHouse\Topics\Job;

use XF\Job\AbstractRebuildJob;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Job
 */
class Topic extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit(
            "
				SELECT topic_id
				FROM xf_th_topics_topic
				WHERE topic_id > ?
				ORDER BY topic_id
			",
            $batch
        ), $start);
    }

    /**
     * @param $id
     * @throws \XF\PrintableException
     */
    protected function rebuildById($id)
    {
        /** @var \ThemeHouse\Topics\Entity\Topic $topic */
        $topic = $this->app->em()->find('ThemeHouse\Topics:Topic', $id);
        if (!$topic) {
            return;
        }

        if ($topic->rebuildCounters()) {
            $topic->save();
        }
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('thtopics_topics');
    }
}
