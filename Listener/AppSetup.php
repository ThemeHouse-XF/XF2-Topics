<?php

namespace ThemeHouse\Topics\Listener;

use XF\App;
use XF\Container;

/**
 * Class AppSetup
 * @package ThemeHouse\Topics\Listener
 */
class AppSetup
{
    /**
     * @param App $app
     * @throws \XF\Db\Exception
     */
    public static function appSetup(App $app)
    {
        $app->offsetSet('thTopicsNodes', $app->fromRegistry(
            'thTopicsNodes',
            function (Container $c) {
                return $c['em']->getRepository('XF:Node')->rebuildTopicsCache();
            }
        ));
        $app->offsetSet('thTopicsWidgetPositions', function (Container $c) {
            return [
                'find_threads_sidebar',
                'find_threads_sidenav',
                'forum_new_posts_sidebar',
                'forum_view_sidebar',
                'thtopics_all_threads_above_threads',
                'thtopics_all_threads_below_threads',
                'thtopics_all_threads_sidebar',
                'thtopics_find_threads_above_threads',
                'thtopics_find_threads_below_threads',
                'thtopics_find_threads_sidebar',
                'thtopics_forum_new_posts_above_threads',
                'thtopics_forum_new_posts_below_threads',
                'thtopics_forum_new_threads_sidebar',
                'thtopics_forum_new_threads_above_threads',
                'thtopics_forum_new_threads_below_threads',
                'thtopics_forum_view_above_threads',
                'thtopics_forum_view_below_threads',
                'thtopics_new_posts_above_threads',
                'thtopics_new_posts_below_threads',
                'thtopics_new_posts_sidebar',
                'thtopics_new_threads_above_threads',
                'thtopics_new_threads_below_threads',
                'thtopics_new_threads_sidebar',
                'thtrendingsidebar',
                'thtrending_trending_above_threads',
                'thtrending_trending_below_threads',
                'thtrending_trending_sidebar',
            ];
        });
    }
}
