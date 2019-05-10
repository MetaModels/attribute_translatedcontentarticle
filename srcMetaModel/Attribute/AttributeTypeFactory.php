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

namespace MetaModels\AttributeTranslatedContentArticleBundle\Attribute;

use MetaModels\Attribute\AbstractAttributeTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Attribute type factory for article attributes.
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{
    /**
     * Event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritDoc}
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();

        $this->typeName        = 'translatedcontentarticle';
        $this->typeIcon        = 'bundles/metamodelsattributtranslatedcontentearticle/article.png';
        $this->typeClass       = TranslatedContentArticle::class;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->eventDispatcher);
    }
}
