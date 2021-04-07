<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\PageHistory;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PageHistoryTest extends MockeryTestCase
{
    public function testNoPagesVisited() : void
    {
        $pageHistory = new PageHistory();

        // when no pages are visited, previous path should be null
        $this->assertNull($pageHistory->getPreviousPath('/foo/bar'));
    }

    public function testVisitDifferentPages() : void
    {
        $firstPagePath = '/foo/bar';
        $secondPagePath = '/bar/baz';
        $thirdPagePath = '/bax/bim';

        $pageHistory = new PageHistory();

        // visit first page
        $pageHistory->visit($firstPagePath);

        // visit second page, different from first page
        $pageHistory->visit($secondPagePath);

        // the previous path should be the first page's path if we are
        // on the second page
        $actual = $pageHistory->getPreviousPath($secondPagePath);
        $this->assertEquals($firstPagePath, $actual);

        // visit third page
        $pageHistory->visit($thirdPagePath);

        // previous path should now have changed to the second page's path
        $actual = $pageHistory->getPreviousPath($thirdPagePath);
        $this->assertEquals($secondPagePath, $actual);
    }

    public function testRefreshPage() : void
    {
        $firstPagePath = '/foo/bar';
        $secondPagePath = '/bar/baz';
        $thirdPagePath = '/bax/bim';

        $pageHistory = new PageHistory();

        // visit first page
        $pageHistory->visit($firstPagePath);

        // visit second page, different from first page
        $pageHistory->visit($secondPagePath);

        // simulate refresh of second page
        $pageHistory->visit($secondPagePath);

        // the previous path should be still be first page's path as we are
        // still on the second page after a refresh
        $actual = $pageHistory->getPreviousPath($secondPagePath);
        $this->assertEquals($firstPagePath, $actual);

        // simulate refresh of second page
        $pageHistory->visit($secondPagePath);

        // the previous path should be still be first page's path as we are
        // still on the second page after a refresh
        $actual = $pageHistory->getPreviousPath($secondPagePath);
        $this->assertEquals($firstPagePath, $actual);

        // visit third page
        $pageHistory->visit($thirdPagePath);

        // the previous page path should now legitimately change to second
        // page's path as we've left the second page
        $actual = $pageHistory->getPreviousPath($thirdPagePath);
        $this->assertEquals($secondPagePath, $actual);
    }

    public function testRefreshSinglePage() : void
    {
        $firstPagePath = '/foo/bar';
        $secondPagePath = '/bar/baz';

        $pageHistory = new PageHistory();

        // visit first page
        $pageHistory->visit($firstPagePath);

        // should be null, as we've only visited one page
        $this->assertEquals(null, $pageHistory->getPreviousPath($firstPagePath));

        // simulate refresh of first page
        $pageHistory->visit($firstPagePath);

        // the previous path should be still be null as we've still only visited one page
        $this->assertEquals(null, $pageHistory->getPreviousPath($firstPagePath));

        // visit second page
        $pageHistory->visit($secondPagePath);

        // previous path should now legitimately be the first page, as we've
        // left it
        $this->assertEquals($firstPagePath, $pageHistory->getPreviousPath($secondPagePath));
    }

    public function testInstantiationWithHistory() : void
    {
        $firstPagePath = '/foo/bar';
        $secondPagePath = '/bar/baz';
        $thirdPagePath = '/bax/bim';

        // instantiate history; most recently visited first page, before
        // that we visited the second page (NB the front of the
        // list represents the most-recently visited page)
        $pageHistory = new PageHistory([$firstPagePath, $secondPagePath]);

        // refresh the first page
        $pageHistory->visit($firstPagePath);

        // previous path should be the second page's path
        $actual = $pageHistory->getPreviousPath($firstPagePath);
        $this->assertEquals($secondPagePath, $actual);

        // visit third page
        $pageHistory->visit($thirdPagePath);

        // previous path should now legitimately be the first page path
        $actual = $pageHistory->getPreviousPath($thirdPagePath);
        $this->assertEquals($firstPagePath, $actual);
    }

    public function testGetHistoryAsArray() : void
    {
        $firstPagePath = '/foo/bar';
        $secondPagePath = '/bar/baz';

        $pageHistory = new PageHistory();

        // visit first page
        $pageHistory->visit($firstPagePath);

        // visit second page, different from first page
        $pageHistory->visit($secondPagePath);

        $actual = $pageHistory->asArray();
        $this->assertEquals([$secondPagePath, $firstPagePath], $actual);
    }
}
