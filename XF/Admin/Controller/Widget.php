<?php

namespace ThemeHouse\Topics\XF\Admin\Controller;

use XF\Mvc\Reply\View;

/**
 * Class Widget
 * @package ThemeHouse\Topics\XF\Admin\Controller
 */
class Widget extends XFCP_Widget
{
    /**
     * @param \XF\Entity\Widget $widget
     * @return View
     */
    protected function widgetAddEdit(\XF\Entity\Widget $widget)
    {
        $reply = parent::widgetAddEdit($widget);

        if (!\XF::options()->thtopics_allowTopicWidgetsAnyPosition) {
            if ($reply instanceof View && $reply->getParam('widgetPositions')) {
                $widgetPositions = $reply->getParam('widgetPositions');

                if ($widget->definition_id === 'thtopics_thread_filter') {
                    $newWidgetPositions = [];
                    /** @noinspection PhpUndefinedFieldInspection */
                    $topicsWidgetPositions = \XF::app()->thTopicsWidgetPositions;

                    foreach ($widgetPositions as $positionId => $widgetPosition) {
                        if (in_array($positionId, $topicsWidgetPositions)) {
                            $newWidgetPositions[$positionId] = $widgetPosition;
                        }
                    }

                    $reply->setParam('widgetPositions', $newWidgetPositions);
                }
            }
        }

        return $reply;
    }
}
