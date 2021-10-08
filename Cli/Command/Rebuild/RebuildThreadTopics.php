<?php

namespace ThemeHouse\Topics\Cli\Command\Rebuild;

use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

/**
 * Class RebuildThreadTopics
 * @package ThemeHouse\Topics\Cli\Command\Rebuild
 */
class RebuildThreadTopics extends AbstractRebuildCommand
{
    /**
     * @return string
     */
    protected function getRebuildName()
    {
        return 'topics-thread-associations';
    }

    /**
     * @return string
     */
    protected function getRebuildDescription()
    {
        return 'Associates all threads with topics.';
    }

    /**
     * @return string
     */
    protected function getRebuildClass()
    {
        return 'ThemeHouse\Topics:FixThreadTopics';
    }
}
