<?php

namespace ThemeHouse\Topics\Job;

use XF\Job\AbstractRebuildJob;

/**
 * Class FixThreadTopics
 * @package ThemeHouse\Topics\Job
 */
class BatchModeration extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        $data = $this->getData();

        switch ($data['location']) {
            default:
            case 'forum':
                $where = "node_id = {$data['source_forum']}";
                break;

            case 'topic':
                $where = "topic_id = {$data['source_topic']}";
                break;

            case 'additional_topic':
                $where = "FIND_IN_SET({$data['source_topic']}, additional_topic_ids)";
                break;

            case 'prefix':
                $where = "prefix_id = {$data['prefix_id']}";
        }

        return $db->fetchAllColumn($db->limit(
            "
				SELECT thread_id
				FROM xf_thread
				WHERE {$where} AND thread_id > ?
				ORDER BY thread_id
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
        /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
        $thread = $this->app->finder('XF:Thread')->with('Forum')->where('thread_id', '=', $id)->fetchOne();
        $data = $this->getData();

        switch ($data['action']) {
            case 'add_additional':
                $secondaries = $thread->additional_topic_ids;
                $secondaries[] = $data['target_topic'];
                $secondaries = array_unique($secondaries);
                sort($secondaries);
                $thread->additional_topic_ids = $secondaries;
                $thread->save(false, false);
                break;

            case 'remove_additional':
                $secondaries = $thread->additional_topic_ids;

                if (($position = array_search($data['target_topic'], $secondaries)) !== false) {
                    unset($secondaries[$position]);
                    $secondaries = array_values($secondaries);
                }
                $secondaries = array_unique($secondaries);
                sort($secondaries);
                $thread->additional_topic_ids = $secondaries;
                $thread->save(false, false);
                break;
        }
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('threads');
    }
}
