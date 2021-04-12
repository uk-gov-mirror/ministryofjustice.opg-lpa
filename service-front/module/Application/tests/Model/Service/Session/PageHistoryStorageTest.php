<?php
namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\PageHistoryStorage;
use Application\Model\Service\Session\PageHistory;
use Laminas\Session\Container;
use Laminas\Uri\Http as HttpUri;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PageHistoryStorageTest extends MockeryTestCase
{
    public function testSaveAndRetrievePath() : void
    {
        $previousPath = '/foo/bar';
        $currentPath = '/bar/baz';
        $currentQuery = 'a=1&b=2';
        $lastVisited = $currentPath . '?' . $currentQuery;
        $expectedHistory = [$lastVisited, $previousPath];

        # set up test mocks
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('offsetGet')
            ->withArgs(['history'])
            ->andReturn([$previousPath]);

        $pageHistory = Mockery::mock(PageHistory::class);
        $pageHistory->shouldReceive('init')
            ->withArgs([[$previousPath]]);

        # system under test
        $storage = new PageHistoryStorage($container, $pageHistory);

        # save the path
        $uri = Mockery::mock(HttpUri::class);
        $uri->shouldReceive('getQuery')->andReturn($currentQuery);
        $uri->shouldReceive('getPath')->andReturn($currentPath);

        $pageHistory->shouldReceive('visit')->withArgs([$lastVisited]);
        $pageHistory->shouldReceive('asArray')->andReturn($expectedHistory);

        $container->shouldReceive('offsetSet')->withArgs([
            'history',
            $expectedHistory
        ]);

        $storage->saveCurrentPath($uri);

        # retrieve it
        $pageHistory->shouldReceive('getPreviousPath')
            ->withArgs([$lastVisited])
            ->andReturn($previousPath);

        $actual = $storage->getPreviousPath();
        $this->assertEquals($actual, $previousPath);
    }
}
