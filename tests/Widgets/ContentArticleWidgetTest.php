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

namespace Widgets;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeTranslatedContentArticleBundle\Widgets\ContentArticleWidget;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \MetaModels\AttributeTranslatedContentArticleBundle\Widgets\ContentArticleWidget
 */
class ContentArticleWidgetTest extends TestCase
{
    public function testNewInstance(): void
    {

        $dcCompat = $this->getMockBuilder(DcCompat::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $connection = $this->createMock(Connection::class);

        $input = $this->getMockBuilder(Adapter::class)
                      ->disableOriginalConstructor()
                      ->setMethods(['get'])
                      ->getMock();

        $input
            ->expects(self::once())
            ->method('get')
            ->willReturn(1);

        $translator = $this->getMockBuilder(TranslatorInterface::class)
                            ->getMock();

        $widget = $this->getMockBuilder(ContentArticleWidget::class)
                       ->setConstructorArgs([null, $dcCompat, $connection, $input, $translator])
                       ->setMethods(['import'])
                       ->getMock();

        $widget
            ->expects(self::any())
            ->method('import')
            ->withConsecutive([Config::class, 'Config']);

        self::assertEmpty($widget->getAttributes());
    }
}
