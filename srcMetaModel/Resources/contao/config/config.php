<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */


// Register backend form fields.
$GLOBALS['BE_FFL']['MetaModelAttributeArticle'] =
    'MetaModels\\AttributeTranslatedContentArticleBundle\\Widgets\\ArticleWidget';


// Register hooks.
$GLOBALS['TL_HOOKS']['initializeSystem'][] = [
    'MetaModels\\AttributeTranslatedContentArticleBundle\\Table\\MetaModelAttributeTranslatedContentArticle',
    'initializeSystem'
];
