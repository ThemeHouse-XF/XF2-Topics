<?php

namespace ThemeHouse\Topics\XF\Admin\Controller;

use ThemeHouse\Core\Util\Color;
use XF\Entity\AbstractNode;
use XF\Entity\Node;
use XF\Mvc\FormAction;

/**
 * Class Forum
 * @package ThemeHouse\Topics\XF\Admin\Controller
 */
class Forum extends XFCP_Forum
{
    /**
     * @param FormAction $form
     * @param Node $node
     * @param AbstractNode $data
     */
    protected function saveTypeData(FormAction $form, Node $node, AbstractNode $data)
    {
        if (\XF::options()->thtopics_enableTopics && \XF::options()->thtopic_topicListData !== '[]') {
            if (is_null($data->node_id)) {
                $form->complete(function () use ($data) {
                    /** @var \ThemeHouse\Topics\Entity\Topic $topic */
                    $topic = $this->app->em()->create('ThemeHouse\Topics:Topic');
                    $topic->bulkSet([
                        'title' => $data->title,
                        'description' => '',
                        'background_color' => Color::getRandomMaterialColor(),
                        'node_id' => $data->node_id,
                    ]);
                    $topic->save();
                });
            }
        }

        parent::saveTypeData($form, $node, $data);
    }
}
