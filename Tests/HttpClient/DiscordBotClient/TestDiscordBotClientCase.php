<?php


namespace Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient;


use Bytes\DiscordBundle\Tests\CommandProviderTrait;
use Bytes\DiscordBundle\Tests\DiscordClientSetupTrait;
use Bytes\DiscordBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\DiscordBundle\Tests\JsonErrorCodesProviderTrait;

/**
 * Class TestDiscordBotClientCase
 * @package Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient
 */
class TestDiscordBotClientCase extends TestHttpClientCase
{
    use CommandProviderTrait, JsonErrorCodesProviderTrait, DiscordClientSetupTrait {
        DiscordClientSetupTrait::setupBotClient as setupClient;
    }
}
