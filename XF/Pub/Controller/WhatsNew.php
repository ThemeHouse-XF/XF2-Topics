<?php

namespace ThemeHouse\Topics\XF\Pub\Controller;

/**
 * Class WhatsNew
 * @package ThemeHouse\Topics\XF\Pub\Controller
 */
class WhatsNew extends XFCP_WhatsNew
{
    /**
     * @return \XF\Mvc\Reply\Redirect
     */
    public function actionThreads()
    {
        if ($this->options()->thtopics_showNewThreadsPage) {
            return $this->redirectPermanently(
                $this->buildLink('whats-new/threads')
            );
        } else {
            return $this->notFound();
        }
    }
}
