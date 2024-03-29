<?php


namespace Bytes\DiscordBundle\Resources\config;


use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * @param RoutingConfigurator $routes
 */
return function (RoutingConfigurator $routes) {
    //@Route("/user/redirect", name="responsebundle_oauth_handler")
    $routes->add('bytes_discord_oauth_login_handler_redirect', '/login/handler')
        ->controller(['bytes_discord.oauth_controller.login', 'handlerAction']);

    foreach (['bot', 'login', 'user'] as $type) {
        //@Route("/redirect", name="responsebundle_oauth_redirect")
        $routes->add(sprintf('bytes_discord_oauth_%s_redirect', $type), sprintf('/%s', $type))
            ->controller([sprintf('bytes_discord.oauth_controller.%s', $type), 'redirectAction']);
    }
};