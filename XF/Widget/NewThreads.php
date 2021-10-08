<?php

namespace ThemeHouse\Topics\XF\Widget;

use XF\Mvc\Entity\AbstractCollection;
use XF\Widget\WidgetRenderer;

/**
 * Class NewThreads
 * @package ThemeHouse\Topics\XF\Widget
 */
class NewThreads extends XFCP_NewThreads
{
    /**
     * @return WidgetRenderer
     */
    public function render()
    {
        $renderer = parent::render();

        if ($renderer instanceof WidgetRenderer && \XF::options()->thtopics_showNewThreadsPage) {
            $options = $this->options;
            $limit = $options['limit'];

            $router = $this->app->router('public');

            $viewParams = $renderer->getViewParams();

            $viewParams['link'] = $router->buildLink('whats-new/threads', null, ['skip' => 1]);
            /** @var AbstractCollection $threads */
            $threads = $viewParams['threads'];
            $viewParams['hasMore'] = $threads->count() === $limit;

            $renderer->setViewParams($viewParams);
        }

        return $renderer;
    }
}
