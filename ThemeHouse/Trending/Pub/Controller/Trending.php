<?php

namespace ThemeHouse\Topics\ThemeHouse\Trending\Pub\Controller;

use XF\Mvc\ParameterBag;

/**
 * Class Trending
 * @package ThemeHouse\Topics\ThemeHouse\Trending\Pub\Controller
 */
class Trending extends XFCP_Trending
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionIndex(ParameterBag $params)
    {
        $return = parent::actionIndex($params);
        /** @noinspection PhpUndefinedMethodInspection */
        $return->setJsonParam('href', \XF::repository('ThemeHouse\Topics:Topic')->getTopicFilterUrl(
            'trending',
            [],
            $this->assertTrendingExists($params['trending_id'])
        ));
        return $return;
    }
}
