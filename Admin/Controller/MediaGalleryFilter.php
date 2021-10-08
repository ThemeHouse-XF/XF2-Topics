<?php

namespace ThemeHouse\Topics\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

/**
 * Class MediaGalleryFilter
 * @package ThemeHouse\Topics\Admin\Controller
 */
class MediaGalleryFilter extends AbstractController
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionIndex(ParameterBag $params)
    {
        if ($params['media_gallery_filter_id']) {
            $mediaGalleryFilter = $this->assertMediaGalleryFilterExists($params['media_gallery_filter_id']);
            return $this->redirect($this->buildLink('media-gallery-filters/edit', $mediaGalleryFilter));
        }

        $mediaGalleryFilterRepo = $this->getMediaGalleryFilterRepo();
        $mediaGalleryFilters = $mediaGalleryFilterRepo->findMediaGalleryFiltersForList()->fetch();

        $viewParams = [
            'mediaGalleryFilters' => $mediaGalleryFilters,
            'totalFilters' => $mediaGalleryFilters->count()
        ];
        return $this->view(
            'ThemeHouse\Topics:MediaGalleryFilter\Listing',
            'thtopics_media_gallery_filter_list',
            $viewParams
        );
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \ThemeHouse\Topics\Entity\MediaGalleryFilter|\XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertMediaGalleryFilterExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('ThemeHouse\Topics:MediaGalleryFilter', $id, $with, $phraseKey);
    }

    /**
     * @return \ThemeHouse\Topics\Repository\MediaGalleryFilter|\XF\Mvc\Entity\Repository
     */
    protected function getMediaGalleryFilterRepo()
    {
        return $this->repository('ThemeHouse\Topics:MediaGalleryFilter');
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $mediaGalleryFilter = $this->assertMediaGalleryFilterExists($params['media_gallery_filter_id']);
        return $this->mediaGalleryFilterAddEdit($mediaGalleryFilter);
    }

    /**
     * @param \ThemeHouse\Topics\Entity\MediaGalleryFilter $mediaGalleryFilter
     * @return \XF\Mvc\Reply\View
     */
    protected function mediaGalleryFilterAddEdit(\ThemeHouse\Topics\Entity\MediaGalleryFilter $mediaGalleryFilter)
    {
        /** @var \XF\Repository\Style $styleRepo */
        $styleRepo = \XF::repository('XF:Style');

        $styleChoices = [];
        $styleChoices[0] = \XF::phrase('use_default_style');
        foreach ($styleRepo->getStyleTree(false)->getFlattened() as $entry) {
            if ($entry['record']->user_selectable) {
                $styleChoices[$entry['record']->style_id] = $entry['record']->title;
            }
        }

        $searcher = $this->searcher('XF:User', $mediaGalleryFilter->criteria);

        $viewParams = [
            'styleChoices' => $styleChoices,
            'criteria' => $searcher->getFormCriteria(),
            'sortOrders' => $searcher->getOrderOptions(),
            'mediaGalleryFilter' => $mediaGalleryFilter,
        ];

        return $this->view(
            'ThemeHouse\Topics:MediaGalleryFilter\Edit',
            'thtopics_media_gallery_filter_edit',
            $viewParams + $searcher->getFormData()
        );
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var \ThemeHouse\Topics\Entity\MediaGalleryFilter $mediaGalleryFilter */
        $mediaGalleryFilter = $this->em()->create('ThemeHouse\Topics:MediaGalleryFilter');
        return $this->mediaGalleryFilterAddEdit($mediaGalleryFilter);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['media_gallery_filter_id']) {
            $mediaGalleryFilter = $this->assertMediaGalleryFilterExists($params['media_gallery_filter_id']);
        } else {
            $mediaGalleryFilter = $this->em()->create('ThemeHouse\Topics:MediaGalleryFilter');
        }

        $this->mediaGalleryFilterSaveProcess($mediaGalleryFilter)->run();

        return $this->redirect($this->buildLink('media-gallery-filters') .
            $this->buildLinkHash($mediaGalleryFilter->media_gallery_filter_id));
    }

    /**
     * @param \ThemeHouse\Topics\Entity\MediaGalleryFilter $mediaGalleryFilter
     * @return \XF\Mvc\FormAction
     */
    protected function mediaGalleryFilterSaveProcess(\ThemeHouse\Topics\Entity\MediaGalleryFilter $mediaGalleryFilter)
    {
        $form = $this->formAction();

        $input = $this->filter([
            'display_order' => 'int',
            'active' => 'bool',
            'style_id' => 'int',
            'criteria' => 'array',
        ]);

        $form->basicEntitySave($mediaGalleryFilter, $input);

        $extraInput = $this->filter([
            'title' => 'str'
        ]);
        $form->apply(function () use ($mediaGalleryFilter, $extraInput) {
            $title = $mediaGalleryFilter->getMasterPhrase();
            $title->phrase_text = $extraInput['title'];
            $title->save();
        });

        return $form;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        $mediaGalleryFilter = $this->assertMediaGalleryFilterExists($params['media_gallery_filter_id']);
        if (!$mediaGalleryFilter->preDelete()) {
            return $this->error($mediaGalleryFilter->getErrors());
        }

        if ($this->isPost()) {
            $mediaGalleryFilter->delete();
            return $this->redirect($this->buildLink('media-gallery-filters'));
        } else {
            $viewParams = [
                'mediaGalleryFilter' => $mediaGalleryFilter
            ];
            return $this->view(
                'ThemeHouse\Topics:MediaGalleryFilter\Delete',
                'thtopics_media_gallery_filter_delete',
                $viewParams
            );
        }
    }

    /**
     * @return \XF\Mvc\Reply\Message
     */
    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('ThemeHouse\Topics:MediaGalleryFilter');
    }

    /**
     * @param $action
     * @param ParameterBag $params
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('thtopics');
    }
}
