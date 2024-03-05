<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Marc Reimann <reimann@mediendepot-ruhr.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\Attribute;

use Contao\ContentModel;
use Contao\Controller;
use Contao\System;
use MetaModels\AttributeTranslatedContentArticleBundle\Widgets\ContentArticleWidget;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\ITranslatedMetaModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is the AttributeTranslatedContentArticle class for handling article fields.
 */
class TranslatedContentArticle extends TranslatedReference
{
    /**
     * Array of Call Ids.
     *
     * @var array
     */
    private static array $arrCallIds = [];

    /**
     * {@inheritdoc}
     */
    protected function getValueTable()
    {
        // Needed to fake implement ITranslate.
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef              = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'translatedcontentarticle';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        // Needed to fake implement BaseComplex.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($arrIds)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * {@inheritdoc}
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }

    /**
     * {@inheritdoc}
     */
    public function searchForInLanguages($strPattern, $arrLanguages = [])
    {
        // Needed to fake implement ITranslate.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD)
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $strTable       = $this->getMetaModel()->getTableName();
        $strColumn      = $this->getColName();
        $model          = $this->getMetaModel();
        $arrData        = [];
        $contentArticle = new ContentArticleWidget();
        $rootTable      = $contentArticle->getRootMetaModelTable($strTable);

        if ($model instanceof ITranslatedMetaModel) {
            $strLanguage         = $strLangCode;
            $strFallbackLanguage = $model->getMainLanguage();
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $strLanguage = $model->isTranslated() ? $strLangCode : '';
            /** @psalm-suppress DeprecatedMethod */
            $strFallbackLanguage = $model->isTranslated() ? $model->getFallbackLanguage() : '';
        }

        foreach ($arrIds as $intId) {
            // Continue if it's a recursive call
            $strCallId = $strTable . '_' . $strColumn . '_' . $strLanguage . '_' . $intId;
            if (isset(static::$arrCallIds[$strCallId])) {
                $arrData[$intId]['value'] = \sprintf('RECURSION: %s', $strCallId);
                continue;
            }
            static::$arrCallIds[$strCallId] = true;

            // Generate list for backend.
            $isBackend = (bool) System::getContainer()
                ->get('contao.routing.scope_matcher')
                ?->isBackendRequest(
                    System::getContainer()->get('request_stack')?->getCurrentRequest() ?? Request::create('')
                );

            if ($isBackend) {
                $elements = $contentArticle->getContentTypesByRecordId($intId, $rootTable, $strColumn, $strLanguage);
                $content  = '';
                if (\count($elements)) {
                    $content .= '<ul class="elements_container">';
                    foreach ($elements as $element) {
                        // @codingStandardsIgnoreStart - one line template.
                        $content .= \sprintf(
                            '<li><div class="cte_type%s"><img src="system/themes/flexible/icons/%s.svg" width="16" height="16"> %s</div></li>',
                            $element['isInvisible'] ? ' unpublished' : ' published',
                            $element['isInvisible'] ? 'invisible' : 'visible',
                            $element['name']
                        );
                        // @codingStandardsIgnoreEnd
                    }
                    $content .= '</ul>';
                }

                if (!empty($content)) {
                    $arrData[$intId]['value'] = [$content];
                } else {
                    $arrData[$intId]['value'] = [];
                }
            }

            // Generate output for frontend.
            $isFrontend = (bool) System::getContainer()
                ->get('contao.routing.scope_matcher')
                ?->isFrontendRequest(
                    System::getContainer()->get('request_stack')?->getCurrentRequest() ?? Request::create('')
                );

            if ($isFrontend) {
                $objContent         = ContentModel::findPublishedByPidAndTable((int) $intId, $strTable);
                $arrContent         = [];
                $arrContentFallback = [];

                if ($objContent !== null) {
                    while ($objContent->next()) {
                        /** @psalm-suppress UndefinedMagicPropertyFetch */
                        if ($objContent->mm_slot === $strColumn && $objContent->mm_lang === $strLanguage) {
                            $arrContent[] = Controller::getContentElement($objContent->current());
                        } elseif (
                            /** @psalm-suppress UndefinedMagicPropertyFetch */
                            $objContent->mm_slot === $strColumn
                                  && $strLanguage !== $strFallbackLanguage
                                  && $objContent->mm_lang === $strFallbackLanguage
                        ) {
                            $arrContentFallback[] = Controller::getContentElement($objContent->current());
                        }
                    }
                }

                if (!empty($arrContent)) {
                    $arrData[$intId]['value'] = $arrContent;
                } elseif (!empty($arrContentFallback)) {
                    $arrData[$intId]['value'] = $arrContentFallback;
                } else {
                    $arrData[$intId]['value'] = [];
                }
            }

            unset(static::$arrCallIds[$strCallId]);
        }

        return $arrData;
    }
}
