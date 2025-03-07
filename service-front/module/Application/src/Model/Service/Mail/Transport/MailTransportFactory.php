<?php

namespace Application\Model\Service\Mail\Transport;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use SendGrid as SendGridClient;
use Twig\Environment as TwigEnvironment;
use Application\View\Helper\LocalViewRenderer as LocalViewRenderer;
use RuntimeException;

class MailTransportFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $emailConfig = $container->get('Config')['email'];
        $sendGridConfig = $emailConfig['sendgrid'];

        if (!isset($sendGridConfig['key'])) {
            throw new RuntimeException('Sendgrid settings not found');
        }

        $client = new SendGridClient($sendGridConfig['key']);

        /** @var TwigEnvironment $emailRenderer */
        $emailRenderer = $container->get('TwigEmailRenderer');

        return new MailTransport($client->client, new LocalViewRenderer($emailRenderer), $emailConfig);
    }
}
