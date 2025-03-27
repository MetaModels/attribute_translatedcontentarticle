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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use MetaModels\AttributeTranslatedContentArticleBundle\MetaModelsAttributeTranslatedContentArticleBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        /** @psalm-suppress UndefinedClass */
        return [
            BundleConfig::create(MetaModelsAttributeTranslatedContentArticleBundle::class)
                        ->setLoadAfter(
                            [
                                MetaModelsCoreBundle::class,
                            ]
                        )
                        ->setReplace(['metamodelsattribute_translatedarticle'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        if (false === ($resolved = $resolver->resolve(__DIR__ . '/../Resources/config/routing.yml'))) {
            return null;
        }

        return $resolved->load(__DIR__ . '/../Resources/config/routing.yml');
    }
}
