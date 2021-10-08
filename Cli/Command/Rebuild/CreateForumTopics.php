<?php

namespace ThemeHouse\Topics\Cli\Command\Rebuild;

use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

/**
 * Class CreateForumTopics
 * @package ThemeHouse\Topics\Cli\Command\Rebuild
 */
class CreateForumTopics extends AbstractRebuildCommand
{
    /**
     * @return string
     */
    protected function getRebuildName()
    {
        return 'topics-create-forum-topics';
    }

    /**
     * @return string
     */
    protected function getRebuildDescription()
    {
        return 'Creates topics for all forums.';
    }

    /**
     * @return string
     */
    protected function getRebuildClass()
    {
        return 'ThemeHouse\Topics:CreateForumTopics';
    }
}
