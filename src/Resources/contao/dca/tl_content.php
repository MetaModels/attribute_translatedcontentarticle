<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use Contao\Input;
use MetaModels\AttributeTranslatedContentArticleBundle\Table\ArticleContent;

$GLOBALS['TL_DCA']['tl_content']['fields']['mm_slot'] = [
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['mm_lang'] = [
    'sql' => "varchar(5) NOT NULL default ''",
];

$strModule      = Input::get('do');
$strTable       = Input::get('table');
$strLangSupport = Input::get('langSupport');

// Change TL_Content for the article popup
if (
    \substr($strModule, 0, 10) == 'metamodel_'
    && $strTable == 'tl_content'
    && $strLangSupport == '1'
) {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable']                                =
        Input::get('ptable');
    $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][]                   =
        [
            ArticleContent::class,
            'save'
        ];
    $GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][]                     =
        [
            ArticleContent::class,
            'updateCopyAndCutData'
        ];
    $GLOBALS['TL_DCA']['tl_content']['config']['oncut_callback'][]                      =
        [
            ArticleContent::class,
            'updateCopyAndCutData'
        ];
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][]                     =
        [
            ArticleContent::class,
            'checkPermission'
        ];
    $GLOBALS['TL_DCA']['tl_content']['list']['operations']['toggle']['button_callback'] =
        [
            ArticleContent::class,
            'toggleIcon'
        ];
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['filter'][]                     =
        [
            'mm_slot=?',
            Input::get('slot')
        ];
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['filter'][]                     =
        [
            'mm_lang=?',
            Input::get('lang')
        ];
    $GLOBALS['TL_DCA']['tl_content']['list']['global_operations']['addMainLangContent'] =
        [
            'label'      => &$GLOBALS['TL_LANG']['tl_content']['addMainLangContent'],
            'href'       => 'key=addMainLangContent',
            'class'      => 'header_new',
            'attributes' => 'onclick="Backend.getScrollOffset()"',
        ];
}
