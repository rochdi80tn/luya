<?php

namespace luyatests\core\web;

use luya\web\Request;
use luya\web\Composition;

/**
 * removed tests to implemented here (if not already).
 *
 *
 $parts = Yii::$app->composition->get();

 $this->assertArrayHasKey('langShortCode', $parts);
 $this->assertArrayHasKey('foo', $parts);
 $this->assertArrayHasKey('bar', $parts);

 $this->assertEquals('de', $parts['langShortCode']);
 $this->assertEquals('de', Yii::$app->composition->getLanguage());
 $this->assertEquals('1234', $parts['foo']);
 $this->assertEquals('luya09', $parts['bar']);

 *
 * @author nadar
 */
class CompositionTest extends \luyatests\LuyaWebTestCase
{
    private function resolveHelper($url, $compUrl)
    {
        $request = new Request();
        $request->pathInfo = $url;
        
        $composition = new Composition($request);
        $composition->pattern = $compUrl;
        
        return $composition->getResolvedPathInfo($request);
    }
    
    public function testEmptyRouteResolver()
    {
        $resolve = $this->resolveHelper('ch/', '<countryShortCode:[a-z]{2}>');
        $this->assertEquals('', $resolve->resolvedPath);
        $this->assertEquals(['countryShortCode' => 'ch'], $resolve->resolvedValues);
    }
    
    public function testResolvedPaths()
    {
        $request = new Request();
        $request->pathInfo = 'de/hello/world';

        $composition = new \luya\web\Composition($request);

        $resolver = $composition->getResolvedPathInfo($request);
        
        $this->assertEquals('hello/world', $resolver->resolvedPath);
        $this->assertSame(['langShortCode' => 'de'], $resolver->resolvedValues);
        $this->assertSame(['langShortCode'], $resolver->resolvedKeys);
    }

    public function testMultipleResolvedPaths()
    {
        $request = new Request();
        $request->pathInfo = 'ch/de/hello/world';

        $composition = new \luya\web\Composition($request);
        $composition->pattern = '<countryShortCode:[a-z]{2}>/<langShortCode:[a-z]{2}>';

        $resolver = $composition->getResolvedPathInfo($request);
        $resolve = $resolver->resolvedPath;
        $resolved = $resolver->resolvedValues;

        $this->assertEquals('hello/world', $resolve);
        $this->assertEquals(true, is_array($resolved));
        $this->assertEquals(2, count($resolved));
        $this->assertArrayHasKey('countryShortCode', $resolved);
        $this->assertEquals('ch', $resolved['countryShortCode']);
        $this->assertArrayHasKey('langShortCode', $resolved);
        $this->assertEquals('de', $resolved['langShortCode']);
    }

   

    public function testGetResolvedPathInfo()
    {
        $comp = '<countryShortCode:[a-z]{2}>';

        $resolve = $this->resolveHelper('ch/de/hello/world', $comp);
        $this->assertEquals('de/hello/world', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);

        $resolve = $this->resolveHelper('ch/de/hello', $comp);
        $this->assertEquals('de/hello', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);

        $resolve = $this->resolveHelper('ch/de', $comp);
        $this->assertEquals('de', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);

        $resolve = $this->resolveHelper('ch/', $comp);
        $this->assertEquals('', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);

        $comp = '<countryShortCode:[a-z]{2}>/<do:[a-z]{2}>';

        $resolve = $this->resolveHelper('ch/de/hello/world', $comp);
        $this->assertEquals('hello/world', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);
        $this->assertEquals('de', $resolve->resolvedValues['do']);

        $resolve = $this->resolveHelper('ch/de/hello', $comp);
        $this->assertEquals('hello', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);
        $this->assertEquals('de', $resolve->resolvedValues['do']);

        $resolve = $this->resolveHelper('ch/de', $comp);
        $this->assertEquals('', $resolve->resolvedPath);
        $this->assertEquals('ch', $resolve->resolvedValues['countryShortCode']);
        $this->assertEquals('de', $resolve->resolvedValues['do']);

        // this rule wont match the the composition pattern, therfore the composition would not apply ore remove
        // any data from the path and returns the default values from the composition.
        $resolve = $this->resolveHelper('ch/', $comp);
        $this->assertEquals('ch', $resolve->resolvedPath);
        $this->assertEquals('en', $resolve->resolvedValues['langShortCode']);
    }
    
    public function testMultiDomainMapping()
    {
        $request = new Request();
        $request->pathInfo = 'foo/bar';
        $request->hostInfo = 'example.fr';
        
        $composition = new \luya\web\Composition($request, [
            'hostInfoMapping' => ['example.fr' => ['langShortCode' => 'fr', 'x' => 'y']]
        ]);
        
        $resolv = $composition->getResolvedPathInfo($request);
        
        $this->assertSame(['langShortCode' => 'fr', 'x' => 'y'], $resolv->resolvedValues);
    }
    
    public function testGetDefaultLanguage()
    {
        $request = new Request();
        $comp = new Composition($request);
        $this->assertEquals('en', $comp->getDefaultLangShortCode());
        
        // test route override
        $override = $comp->createRoute(['langShortCode' => 'us']);
        
        $this->assertEquals('us', $override);
        
        // as override does not set/change the base value
        $this->assertEquals('en', $comp->getLanguage());
        $this->assertEquals('en', $comp['langShortCode']);
        $this->assertTrue(isset($comp['langShortCode']));
        $comp['fooCode'] = 'bar';
        $this->assertEquals('bar', $comp['fooCode']);
    }
    
    public function testGetKeys()
    {
        $request = new Request();
        $comp = new Composition($request);
        $this->assertArrayHasKey('langShortCode', $comp->getKeys());
    }
    
    
    public function testNewDefaultMethods()
    {
        $request = new Request();
        $request->pathInfo = 'fr/hello-world';
        $comp = new Composition($request);
        $comp->hidden = false;
        
        $this->assertSame('en', $comp->getDefaultLangShortCode());
        $this->assertSame('fr', $comp->getLangShortCode());
        $this->assertSame('fr', $comp->getPrefixPath());
    }
    
    public function testNewDefaultMehtodsPattern()
    {
        // change pathInfo and pattern
        $request = new Request();
        $request->pathInfo = 'de/ch/hello-world';
        $comp = new Composition($request);
        $comp->pattern = '<langShortCode:[a-z]{2}>/<countryShortCode:[a-z]{2}>';
        $comp->hidden = false;
        
        $this->assertSame('en', $comp->getDefaultLangShortCode());
        $this->assertSame('de', $comp->getLangShortCode());
        $this->assertSame('de/ch', $comp->getPrefixPath());
    }
    
    public function testExtractCompositionData()
    {
        $request = new Request();
        $request->pathInfo = 'de-ch/hello-world';
        
        $comp = new Composition($request);
        $comp->pattern = '<langShortCode:[a-z]{2}>-<countryShortCode:[a-z]{2}>';
        $result = $comp->getResolvedPathInfo($request);
        
        
        $this->assertSame('hello-world', $result->resolvedPath);
        $this->assertSame([
            'langShortCode' => 'de',
            'countryShortCode' => 'ch',
        ], $result->resolvedValues);
    }
    
    public function testRemoval()
    {
        $request = new Request();
        $request->pathInfo = 'foo/bar';
        $request->hostInfo = 'example.fr';
        $comp = new Composition($request);
        $comp->hidden = false;
        
        $this->assertEquals('this-should/be-left', $comp->removeFrom('en/this-should/be-left'));
    }
    
    public function testAllowedHosts()
    {
        $request = new Request();
        $comp = new Composition($request);
        $this->assertTrue($comp->isHostAllowed(['localhost']));
    }
    
    public function testAllowedHostsItems()
    {
        $request = new Request();
        $request->hostInfo = 'http://www.foobar.com';
        $comp = new Composition($request);
        $this->assertTrue($comp->isHostAllowed(['www.foobar.com']));
    }
    
    public function testAllowedHostsItemsWildcard()
    {
        $request = new Request();
        $request->hostInfo = 'http://www.foobar.com';
        $comp = new Composition($request);
        $this->assertTrue($comp->isHostAllowed(['*.foobar.com']));
        $this->assertFalse($comp->isHostAllowed(['luya.io']));
        $this->assertTrue($comp->isHostAllowed(['luya.io', 'www.foobar.com']));
    }
    
    public function testAllowedHostsExceptions()
    {
        $request = new Request();
        $comp = new Composition($request);
        $this->assertFalse($comp->isHostAllowed(['foobar.com']));
    }
    /**
     * @expectedException Exception
     */
    public function testExceptionOnInit()
    {
        $request = new Request();
        $comp = new Composition($request, ['default' => ['noLangShortCode' => 'ch']]);
    }
    
    /**
     * @expectedException Exception
     */
    public function testNotAllowedUnset()
    {
        $request = new Request();
        $comp = new Composition($request);
        unset($comp['langShortCode']);
    }
}
