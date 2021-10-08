<?php

namespace ThemeHouse\Topics\Listener;

use XF\Template\Templater;

/**
 * Class TemplaterTemplatePreRender
 * @package ThemeHouse\Topics\Listener
 */
class TemplaterTemplatePreRender
{
    /**
     * @var array
     */
    public static $containerParams = [];

    /**
     * @param Templater $templater
     * @param $type
     * @param $template
     * @param array $params
     */
    public static function templaterTemplatePreRender(Templater $templater, &$type, &$template, array &$params)
    {
        if (!empty(\XF::options()->thtopics_enableTopics)) {
            if ($template === 'PAGE_CONTAINER' && !isset($params['noTopic'])) {
                $params = array_merge($params, self::$containerParams);
            }

            if (isset($params['topic'])) {
                if ($template === 'thread_view' && !empty($params['topic'])) {
                    self::$containerParams['topic'] = $params['topic'];
                    self::$containerParams['thread'] = $params['thread'];

                    if ($templater->getStyle()->getProperty('thtopics_threadView_enableTopicBar')) {
                        self::$containerParams['thtopics_showTopicBar'] = true;
                        self::$containerParams['noH1'] = true;
                        self::$containerParams['thtopics_dontHideDescription'] = true;
                        self::$containerParams['resourceThread'] = $params['thread']->discussion_type === 'resource';
                    }

                    if ($templater->getStyle()->getProperty('thtopics_threadView_removeBreadcrumb')) {
                        self::$containerParams['thtopics_hideBreadcrumb'] = true;
                    }
                }
            }
        }
    }
}
