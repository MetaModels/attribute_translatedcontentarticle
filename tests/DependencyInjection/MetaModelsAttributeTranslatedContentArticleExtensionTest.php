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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace DependencyInjection;

// phpcs:disable
use MetaModels\AttributeTranslatedContentArticleBundle\Controller\Backend\MetaModelController;
use MetaModels\AttributeTranslatedContentArticleBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedContentArticleBundle\EventListener\BackendEventListener;
use MetaModels\AttributeTranslatedContentArticleBundle\EventListener\GetOptionsListener;
use MetaModels\AttributeTranslatedContentArticleBundle\DependencyInjection\MetaModelsAttributeTranslatedContentArticleExtension;
use MetaModels\AttributeTranslatedContentArticleBundle\FileUsage\FileUsageProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
// phpcs:enable

/**
 * phpcs:disable
 * @covers \MetaModels\AttributeTranslatedContentArticleBundle\DependencyInjection\MetaModelsAttributeContentArticleExtension
 * phpcs:enable
 *
 * @SuppressWarnings(PHPMD.LongClassName)
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
            AttributeTypeFactory::class,
            MetaModelController::class,
            FileUsageProvider::class
        ];

        self::assertCount(count($expectedDefinitions), $container->getDefinitions());
        foreach ($expectedDefinitions as $expectedDefinition) {
            self::assertTrue($container->hasDefinition($expectedDefinition));
        }
    }
}
