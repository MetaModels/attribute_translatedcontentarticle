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

namespace MetaModels\AttributeTranslatedContentArticleBundle\Test;

use MetaModels\AttributeTranslatedContentArticleBundle\Attribute\TranslatedContentArticle;
use MetaModels\AttributeTranslatedContentArticleBundle\Attribute\AttributeTypeFactory;
use PHPUnit\Framework\TestCase;
/**
 * This class tests if the deprecated autoloader works.
 *
 * @package MetaModels\AttributeTranslatedContentArticleBundle\Test
 */
class DeprecatedAutoloaderTest extends TestCase
{
    /**
     * TableTestes of old classes to the new one.
     *
     * @var array
     */
    private static $classes = [
        'MetaModels\Attribute\TranslatedContentArticle\TranslatedContentArticle' => TranslatedContentArticle::class,
        'MetaModels\Attribute\Article\AttributeTypeFactory'                      => AttributeTypeFactory::class,
    ];
    /**
     * Provide the alias class map.
     *
     * @return array
     */
    public function provideAliasClassMap()
    {
        $values = [];
        foreach (static::$classes as $tableText => $class) {
            $values[] = [$tableText, $class];
        }
        return $values;
    }
    /**
     * Test if the deprecated classes are aliased to the new one.
     *
     * @param string $oldClass Old class name.
     * @param string $newClass New class name.
     *
     * @dataProvider provideAliasClassMap
     */
    public function testDeprecatedClassesAreAliased($oldClass, $newClass)
    {
        $this->assertTrue(class_exists($oldClass), sprintf('Class tableMulti "%s" is not found.', $oldClass));
        $oldClassReflection = new \ReflectionClass($oldClass);
        $newClassReflection = new \ReflectionClass($newClass);
        $this->assertSame($newClassReflection->getFileName(), $oldClassReflection->getFileName());
    }
}
