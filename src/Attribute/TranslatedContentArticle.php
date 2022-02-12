<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2022 The MetaModels team.
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
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */


namespace MetaModels\AttributeTranslatedContentArticleBundle\Attribute;

use Contao\Controller;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\ITranslatedMetaModel;

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
    private static $arrCallIds = [];

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
        // Generate only for frontend (speeds up the backend a little)
        if (TL_MODE == 'BE') {
            return [];
        }

        $strTable  = $this->getMetaModel()->getTableName();
        $strColumn = $this->getColName();
        $model     = $this->getMetaModel();
        if ($this->getMetaModel() instanceof ITranslatedMetaModel) {
            $strLanguage         = $strLangCode;
            $strFallbackLanguage = $model->getMainLanguage();
        } else {
            $strLanguage         = $model->isTranslated() ? $strLangCode : '';
            $strFallbackLanguage = $model->isTranslated() ? $model->getFallbackLanguage() : '';
        }

        $arrData = [];

        foreach ($arrIds as $intId) {
            // Continue if it's a recursive call
            $strCallId = $strTable . '_' . $strColumn . '_' . $strLanguage . '_' . $intId;
            if (isset(static::$arrCallIds[$strCallId])) {
                $arrData[$intId]['value'] = sprintf('RECURSION: %s', $strCallId);
                continue;
            }
            static::$arrCallIds[$strCallId] = true;

            $objContent         = \ContentModel::findPublishedByPidAndTable($intId, $strTable);
            $arrContent         = [];
            $arrContentFallback = [];

            if ($objContent !== null) {
                while ($objContent->next()) {
                    if ($objContent->mm_slot == $strColumn && $objContent->mm_lang == $strLanguage) {
                        $arrContent[] = Controller::getContentElement($objContent->current());
                    } elseif ($objContent->mm_slot == $strColumn
                              && $strLanguage != $strFallbackLanguage
                              && $objContent->mm_lang == $strFallbackLanguage
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

            unset(static::$arrCallIds[$strCallId]);
        }

        return $arrData;
    }
}
