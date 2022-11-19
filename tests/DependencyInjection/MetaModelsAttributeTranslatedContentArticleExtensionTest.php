<?php

declare(strict_types=1);

/**
 * This file is part of MetaModels/attribute_contentarticle.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeContentArticle
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 */

namespace DependencyInjection;

use MetaModels\AttributeTranslatedContentArticleBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedContentArticleBundle\EventListener\BackendEventListener;
use MetaModels\AttributeTranslatedContentArticleBundle\EventListener\GetOptionsListener;
use MetaModels\AttributeTranslatedContentArticleBundle\EventListener\InitializeListener;
use MetaModels\AttributeTranslatedContentArticleBundle\DependencyInjection\MetaModelsAttributeTranslatedContentArticleExtension;
use MetaModels\AttributeTranslatedContentArticleBundle\Table\ArticleContent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \MetaModels\AttributeTranslatedContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension
 */
class MetaModelsAttributeTranslatedContentArticleExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new MetaModelsAttributeTranslatedContentArticleExtension();
        $extension->load([], $container);

        $expectedDefinitions = [
            'service_container',
            BackendEventListener::class,
            GetOptionsListener::class,
            InitializeListener::class,
            AttributeTypeFactory::class,
            ArticleContent::class
        ];

        self::assertCount(count($expectedDefinitions), $container->getDefinitions());
        foreach ($expectedDefinitions as $expectedDefinition) {
            self::assertTrue($container->hasDefinition($expectedDefinition));
        }
    }
}
