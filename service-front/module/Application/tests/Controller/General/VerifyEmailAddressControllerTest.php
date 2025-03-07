<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\VerifyEmailAddressController;
use ApplicationTest\Controller\AbstractControllerTest;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

class VerifyEmailAddressControllerTest extends AbstractControllerTest
{
    protected function getController(string $controllerName)
    {
        /** @var VerifyEmailAddressController $controller */
        $controller = parent::getController($controllerName);

        $controller->setUserService($this->userDetails);

        return $controller;
    }

    public function testIndexAction()
    {
        /** @var VerifyEmailAddressController $controller */
        $controller = $this->getController(VerifyEmailAddressController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testVerifyActionInvalidToken()
    {
        /** @var VerifyEmailAddressController $controller */
        $controller = $this->getController(VerifyEmailAddressController::class);

        $response = new Response();

        $this->sessionManager->shouldReceive('initialise')->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('InvalidToken')->once();
        $this->userDetails->shouldReceive('updateEmailUsingToken')
            ->withArgs(['InvalidToken'])->andReturn(false)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')
            ->withArgs(['There was an error updating your email address'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['login'])->andReturn($response)->once();

        $result = $controller->verifyAction();

        $this->assertEquals($response, $result);
    }

    public function testVerifyActionValidToken()
    {
        /** @var VerifyEmailAddressController $controller */
        $controller = $this->getController(VerifyEmailAddressController::class);

        $response = new Response();

        $this->sessionManager->shouldReceive('initialise')->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('ValidToken')->once();
        $this->userDetails->shouldReceive('updateEmailUsingToken')
            ->withArgs(['ValidToken'])->andReturn(true)->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')
            ->withArgs(['Your email address was successfully updated. Please login with your new address.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['login'])->andReturn($response)->once();

        $result = $controller->verifyAction();

        $this->assertEquals($response, $result);
    }
}
