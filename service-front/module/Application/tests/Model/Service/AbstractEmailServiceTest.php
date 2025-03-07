<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Mockery;
use Mockery\MockInterface;
use Twig\Environment as TwigEnvironment;

class AbstractEmailServiceTest extends AbstractServiceTest
{
    /**
     * @var $twigEmailRenderer TwigEnvironment|MockInterface
     */
    protected $twigEmailRenderer;

    /**
     * @var $mailTransport MailTransport|MockInterface
     */
    protected $mailTransport;

    public function setUp() : void
    {
        parent::setUp();

        $this->twigEmailRenderer = Mockery::mock(TwigEnvironment::class);

        $this->mailTransport = Mockery::mock(MailTransport::class);
    }

    public function testConstructor() : void
    {
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            ['test' => 'config'],
            $this->twigEmailRenderer,
            $this->mailTransport
        );

        $this->assertEquals($this->authenticationService, $service->getAuthenticationService());
        $this->assertEquals(['test' => 'config'], $service->getConfig());
        $this->assertEquals($this->twigEmailRenderer, $service->getTwigEmailRenderer());
        $this->assertEquals($this->mailTransport, $service->getMailTransport());
    }
}
