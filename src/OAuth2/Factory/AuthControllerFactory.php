<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2\Factory;

use Jield\ApiTools\OAuth2\Controller\AuthController;
use Jield\ApiTools\OAuth2\Provider\UserId;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use OAuth2\Server as OAuth2Server;
use Psr\Container\ContainerInterface;

class AuthControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array $options
     * @return AuthController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authController = new AuthController(
            $this->getOAuth2ServerFactory($container),
            $container->get(UserId::class)
        );

        $authController->setApiProblemErrorResponse(
            $this->marshalApiProblemErrorResponse($container)
        );

        return $authController;
    }

    /**
     * @param null|string $name
     * @param null|string $requestedName
     * @return AuthController
     */
    public function createService(ServiceLocatorInterface $controllers, $name = null, $requestedName = null)
    {
        $requestedName = $requestedName ?: AuthController::class;

        return $this($controllers, $requestedName);
    }

    /**
     * Retrieve the OAuth2\Server factory.
     *
     * For BC purposes, if the OAuth2Server service returns an actual
     * instance, this will wrap it in a closure before returning it.
     *
     * @return callable
     */
    private function getOAuth2ServerFactory(ContainerInterface $container)
    {
        $oauth2ServerFactory = $container->get('Jield\ApiTools\OAuth2\Service\OAuth2Server');
        if (! $oauth2ServerFactory instanceof OAuth2Server) {
            return $oauth2ServerFactory;
        }

        return fn() => $oauth2ServerFactory;
    }

    /**
     * Determine whether or not to render API Problem error responses.
     *
     * @return bool
     */
    private function marshalApiProblemErrorResponse(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        return isset($config['api-tools-oauth2']['api_problem_error_response'])
            && $config['api-tools-oauth2']['api_problem_error_response'] === true;
    }
}
