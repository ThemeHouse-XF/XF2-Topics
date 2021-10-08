<?php

namespace ThemeHouse\Topics\XF\ControllerPlugin;

use XF\FindNew\AbstractHandler;

/**
 * Class FindNew
 * @package ThemeHouse\Topics\XF\ControllerPlugin
 */
class FindNew extends XFCP_FindNew
{
    /**
     * @param AbstractHandler $handler
     * @return mixed|null
     */
    public function getRequestedFilters(AbstractHandler $handler)
    {
        $filters = parent::getRequestedFilters($handler);

        if ($handler->getContentType() === 'thread') {
            /** @noinspection PhpUndefinedMethodInspection */
            $forumFilters = \XF::visitor()->getForumFilters($this->request);
            /** @noinspection PhpUndefinedMethodInspection */
            $topicFilters = \XF::visitor()->getTopicFilters($this->request);

            if ($forumFilters) {
                $filters['node_ids'] = $forumFilters;
            }

            if ($topicFilters) {
                $filters['topic_ids'] = $topicFilters;
            }
        }

        return $filters;
    }
}
