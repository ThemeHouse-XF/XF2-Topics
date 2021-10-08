<?php

namespace ThemeHouse\Topics\Job;

use ThemeHouse\Core\Util\Color;
use XF\Job\AbstractRebuildJob;

/**
 * Class CreateForumTopics
 * @package ThemeHouse\Topics\Job
 */
class CreateForumTopics extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $data = $this->getData();

        $nodeIds = array_filter($data['node_ids'], function ($nodeId) use ($start) {
            return $nodeId > $start;
        });

        return array_slice($nodeIds, 0, $batch);
    }

    /**
     * @param $id
     * @throws \XF\PrintableException
     */
    protected function rebuildById($id)
    {
        /** @var \XF\Entity\Forum $forum */
        $forum = $this->app->em()->find('XF:Forum', $id);
        if ($forum) {
            $existingTopic = $this->app->finder('ThemeHouse\Topics:Topic')
                ->where('node_id', '=', $forum->node_id)
                ->fetchOne();

            if (!$existingTopic) {
                $topic = $this->app->em()->create('ThemeHouse\Topics:Topic');

                $name = $forum->title;
                if (strlen($name) > 30) {
                    $name = substr($name, 0, 27) . '…';
                }

                $topic->bulkSet([
                    'title' => $name,
                    'description' => '',
                    'background_color' => Color::getRandomMaterialColor(),
                    'node_id' => $forum->node_id,
                ]);
                $topic->save();
            }
        }
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('forums');
    }
}
