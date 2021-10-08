<?php

namespace ThemeHouse\Topics\Widget;

use ThemeHouse\Topics\XF\Entity\User;
use XF\Http\Request;
use XF\Widget\AbstractWidget;

/**
 * Class ThreadFilter
 * @package ThemeHouse\Topics\Widget
 */
class ThreadFilter extends AbstractWidget
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'type' => '',
        'max_depth' => 0,
        'visible_filter_topics' => 0,
        'multi_select' => true,
        'style' => '',
        'responsive' => true,
    ];

    /**
     * @return string|\XF\Widget\WidgetRenderer
     */
    public function render()
    {
        if (!\XF::options()->thtopics_allowTopicWidgetsAnyPosition) {
            if (isset($this->contextParams['filterBarVisible']) && !$this->contextParams['filterBarVisible']) {
                return '';
            }
        }

        $options = $this->options;

        $type = $options['type'];

        if (!\XF::options()->thtopics_enableTopics || \XF::options()->thtopic_topicListData === '[]') {
            $type = 'node';
        }

        if (!\XF::options()->thtopics_allowTopicWidgetsAnyPosition && $type === 'node') {
            if (isset($this->contextParams['forum']) || !\XF::options()->thtopics_enableNodeTopics) {
                return '';
            }
        }

        $visibleFilterTopics = $options['visible_filter_topics'];
        $visibleFilterTopicsVertical = $options['visible_filter_topics'];

        if (!$visibleFilterTopics || $visibleFilterTopics > \XF::options()->thtopics_visibleFilterTopics) {
            $visibleFilterTopics = \XF::options()->thtopics_visibleFilterTopics;
        }
        if (!$visibleFilterTopicsVertical || $visibleFilterTopicsVertical > \XF::options()->thtopics_visibleFilterTopicsVertical) {
            $visibleFilterTopicsVertical = \XF::options()->thtopics_visibleFilterTopicsVertical;
        }

        $maxDepth = $options['max_depth'];

        if (!$maxDepth || $maxDepth > \XF::options()->thtopics_filterMaxDepth) {
            $maxDepth = \XF::options()->thtopics_filterMaxDepth;
        } elseif ($maxDepth < 2) {
            $maxDepth = 2;
        }

        /** @var User $visitor */
        $visitor = \XF::visitor();

        if ($type === 'node') {
            /** @noinspection PhpUndefinedFieldInspection */
            $viewParams = [
                'type' => 'node',
                'allTopicsLink' => \XF::app()->router('public')->buildLink('forums/all-forums-filter'),
                'filters' => $visitor->getForumFilters(),
                'filterKey' => 'forums',
                'data' => \XF::app()->thTopicsNodes,
                'visibleFilterTopics' => $visibleFilterTopics,
                'visibleFilterTopicsVertical' => $visibleFilterTopicsVertical,
                'responsive' => $options['responsive'],
                'maxDepth' => $maxDepth,
                'style' => $options['style'],
            ];

            return $this->renderer('thtopics_widget_topic_filter', $viewParams);
        } else {
            $viewParams = [
                'type' => 'thread',
                'allTopicsLink' => \XF::app()->router('public')->buildLink('topics/all-topics-filter'),
                'filters' => $visitor->getTopicFilters(),
                'filterKey' => 'topics',
                'data' => json_decode(\XF::options()->thtopic_topicListData, true),
                'visibleFilterTopics' => $visibleFilterTopics,
                'visibleFilterTopicsVertical' => $visibleFilterTopicsVertical,
                'multiSelect' => $options['multi_select'],
                'responsive' => $options['responsive'],
                'maxDepth' => $maxDepth,
                'style' => $options['style'],
            ];

            return $this->renderer('thtopics_widget_topic_filter', $viewParams);
        }
    }

    /**
     * @param Request $request
     * @param array $options
     * @param null $error
     * @return bool
     */
    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'type' => 'string',
            'max_depth' => 'uint',
            'visible_filter_topics' => 'uint',
            'multi_select' => 'bool',
            'style' => 'str',
            'responsive' => 'bool',
        ]);

        if ($options['max_depth'] && $options['max_depth'] < 2) {
            $options['max_depth'] = 2;
        }

        if ($options['max_depth'] > \XF::options()->thtopics_filterMaxDepth) {
            $options['max_depth'] = \XF::options()->thtopics_filterMaxDepth;
        }

        if (empty($options['visible_filter_topics'])) {
            $options['visible_filter_topics'] = 0;
        }

        if (empty($options['multi_select'])) {
            if ($options['type']) {
                $options['multi_select'] = true;
            } else {
                $options['multi_select'] = false;
            }
        }

        if ($options['style'] === 'horizontal_scroller') {
            if ($options['visible_filter_topics'] > \XF::options()->thtopics_visibleFilterTopics) {
                $options['visible_filter_topics'] = \XF::options()->thtopics_visibleFilterTopics;
            }
        } else {
            if ($options['visible_filter_topics'] > \XF::options()->thtopics_visibleFilterTopicsVertical) {
                $options['visible_filter_topics'] = \XF::options()->thtopics_visibleFilterTopicsVertical;
            }
        }

        return true;
    }
}
