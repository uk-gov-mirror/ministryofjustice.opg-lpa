<?php
/**
 * Created by PhpStorm.
 * User: seemamenon
 * Date: 17/02/2019
 * Time: 16:09
 */

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\User\User;
use Application\View\Helper\AccountInfo;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Twig\Environment as TwigEnvironment;
use Twig_Template;
use Laminas\Mvc\Console\View\Renderer;
use Laminas\Router\RouteMatch;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\View\Helper\RendererInterface as RendererInterface;

class AccountInfoTest extends MockeryTestCase
{
    /**
     * @var AuthenticationService|MockInterface
     */
    private $authenticationService;

    /**
     * @var Container|MockInterface
     */
    private $userDetailSession;

    /**
     * @var ViewModel|MockInterface
     */
    private $viewModel;

    /**
     * @var RouteMatch|MockInterface
     */
    private $routeMatch;

    /**
     * @var LpaApplicationService|MockInterface
     */
    private $lpaApplicationService;

    /**
     * @var TwigEnvironment|MockInterface
     */
    private $viewRenderer;

    /**
     * @var Twig_Template|MockInterface
     */
    private $twigTemplate;

    /**
     * @var AuthenticationService|MockInterface
     */
    private $identity;

    public function setUp() : void
    {
        parent::setUp();

        $this->viewModel = new ViewModel();
        $this->userDetailSession = new Container('AccountInfoTest');
        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $this->viewRenderer = Mockery::mock(RendererInterface::class);
        $this->twigTemplate = Mockery::mock(Twig_Template::class);
    }

    public function testInvoke():void
    {
        $view = Mockery::mock(Renderer::class);
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnTrue();
        $this->userDetailSession->user = json_decode('{"name":{"title":"Mr","first":"Test","last":"User"}}');
        $this->lpaApplicationService->shouldReceive('getLpaSummaries')->once()->andReturn(['total'=>0]);
        $this->twigTemplate->shouldReceive('render')
                            ->once()
                            ->withArgs([['view' => $view, 'hasOneOrMoreLPAs'=>false]])
                            ->andReturn("test content");
        $this->viewRenderer->shouldReceive('loadTemplate')
                            ->once()
                            ->withArgs(['account-info/account-info.twig'])
                            ->andReturn($this->twigTemplate);

        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            null,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $accountInfo->setView($view);
        $this->expectOutputString("test content");

        $accountInfo();
    }

    public function testInvokeNoIdentity():void
    {
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnFalse();
        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            null,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $accountInfo();
    }

    public function testInvokeUsername():void
    {
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnTrue();
        $this->userDetailSession->user = new User(['name' => new Name(['first' => 'firstname', 'last' => 'lastname'])]);
        $this->lpaApplicationService->shouldReceive('getLpaSummaries')->once()->andReturn(['total'=>1]);
        $this->twigTemplate->shouldReceive('render')
                            ->once()
                            ->withArgs([['view' => null, 'name'=>'firstname lastname', 'hasOneOrMoreLPAs' => true]])
                            ->andReturn("test content");
        $this->viewRenderer->shouldReceive('loadTemplate')
                            ->once()
                            ->withArgs(['account-info/account-info.twig'])
                            ->andReturn($this->twigTemplate);

        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            null,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $this->expectOutputString("test content");

        $accountInfo();
    }

    public function testInvokeLastLogin():void
    {
        $layoutChildren = new ViewModel();
        $layoutChildren->setVariable("user", ['lastLogin'=>'2019-02-19']);
        $this->viewModel->addChild($layoutChildren, null, null);
        $this->userDetailSession->user = json_decode('{"name":{"title":"Mr","first":"Test","last":"User"}}');
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnTrue();
        $this->lpaApplicationService->shouldReceive('getLpaSummaries')->once()->andReturn(['total'=>1]);
        $this->twigTemplate->shouldReceive('render')
            ->once()->withArgs([['view' => null, 'lastLogin'=>'2019-02-19', 'hasOneOrMoreLPAs' => true]])
            ->andReturn("test content");
        $this->viewRenderer->shouldReceive('loadTemplate')
                            ->once()
                            ->withArgs(['account-info/account-info.twig'])
                            ->andReturn($this->twigTemplate);

        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            null,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $this->expectOutputString("test content");

        $accountInfo();
    }

    public function testInvokeRouteMatch():void
    {
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnTrue();
        $this->userDetailSession->user = new User(['name' => new Name(['first' => 'firstname', 'last' => 'lastname'])]);
        $this->routeMatch->shouldReceive('getMatchedRouteName')->once()->andReturn("test");
        $this->lpaApplicationService->shouldReceive('getLpaSummaries')->once()->andReturn(['total'=>1]);
        $this->twigTemplate->shouldReceive('render')
            ->once()
            ->withArgs([['view' => null, 'name'=>'firstname lastname', 'route' => 'test', 'hasOneOrMoreLPAs' => true]])
            ->andReturn("test content");
        $this->viewRenderer->shouldReceive('loadTemplate')
                            ->once()
                            ->withArgs(['account-info/account-info.twig'])
                            ->andReturn($this->twigTemplate);

        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            $this->routeMatch,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $this->expectOutputString("test content");

        $accountInfo();
    }

    public function testInvokeHasMoreThanOneLpa():void
    {
        $this->authenticationService->shouldReceive('hasIdentity')->once()->andReturnTrue();
        $this->userDetailSession->user = new User(['name' => new Name(['first' => 'firstname', 'last' => 'lastname'])]);
        $this->lpaApplicationService->shouldReceive('getLpaSummaries')->once()->andReturn(['total'=>2]);
        $this->twigTemplate->shouldReceive('render')->once()
                            ->withArgs([['view' => null, 'name'=>'firstname lastname', 'hasOneOrMoreLPAs' => true]])
                            ->andReturn("test content");
        $this->viewRenderer->shouldReceive('loadTemplate')
                            ->once()
                            ->withArgs(['account-info/account-info.twig'])
                            ->andReturn($this->twigTemplate);

        $accountInfo = new AccountInfo(
            $this->authenticationService,
            $this->userDetailSession,
            $this->viewModel,
            null,
            $this->lpaApplicationService,
            $this->viewRenderer
        );

        $this->expectOutputString("test content");

        $accountInfo();
    }

    public function tearDown() : void
    {
        //  Clear the user details session
        $this->userDetailSession->getManager()->getStorage()->clear('AccountInfoTest');
    }
}
