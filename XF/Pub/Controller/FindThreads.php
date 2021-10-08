<?php

namespace ThemeHouse\Topics\XF\Pub\Controller;

/**
 * Class FindThreads
 * @package ThemeHouse\Topics\XF\Pub\Controller
 */
class FindThreads extends XFCP_FindThreads
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionUnanswered()
    {
        $return = parent::actionUnanswered();
        /** @noinspection PhpUndefinedMethodInspection */
        $return->setJsonParam(
            'href',
            \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl('find-threads/unanswered')
        );
        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionContributed()
    {
        $return = parent::actionContributed();
        /** @noinspection PhpUndefinedMethodInspection */
        $return->setJsonParam(
            'href',
            \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl('find-threads/contributed')
        );
        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionStarted()
    {
        $return = parent::actionStarted();
        /** @noinspection PhpUndefinedMethodInspection */
        $return->setJsonParam(
            'href',
            \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl('find-threads/started')
        );
        return $return;
    }
}
