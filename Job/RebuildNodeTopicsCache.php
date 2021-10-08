<?php

namespace ThemeHouse\Topics\Job;

use ThemeHouse\Topics\XF\Repository\Node;
use XF\Job\AbstractJob;

/**
 * Class RebuildNodeTopicsCache
 * @package ThemeHouse\Topics\Job
 */
class RebuildNodeTopicsCache extends AbstractJob
{
    /**
     * @var array
     */
    protected $defaultData = [];

    /**
     * @param int $maxRunTime
     * @return \XF\Job\JobResult
     */
    public function run($maxRunTime)
    {
        /** @var Node $nodeRepo */
        $nodeRepo = \XF::repository('XF:Node');
        $nodeRepo->rebuildTopicsCache();

        return $this->complete();
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        $actionPhrase = \XF::phrase('rebuilding');
        $typePhrase = \XF::phrase('nodes');
        return sprintf('%s... %s', $actionPhrase, $typePhrase);
    }

    /**
     * @return bool
     */
    public function canCancel()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canTriggerByChoice()
    {
        return false;
    }
}
