<?php

namespace Bytes\DiscordBundle\Tests\HttpClient\DiscordBotResponse;

use Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient\TestDiscordBotClientCase;
use Bytes\DiscordResponseBundle\Objects\Slash\ApplicationCommand;
use Bytes\DiscordResponseBundle\Objects\Slash\ApplicationCommandOption;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class CreateCommandTest
 * @package Bytes\DiscordBundle\Tests\HttpClient\DiscordBotResponse
 */
class CreateCommandTest extends TestDiscordBotClientCase
{
    /**
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCreateCommand()
    {
        $command = $this
            ->setupResponse('HttpClient/add-command-success.json', type: ApplicationCommand::class)
            ->deserialize();

        $this->validateCommand($command);
    }

    /**
     * @param $command
     */
    protected function validateCommand($command)
    {
        $this->assertInstanceOf(ApplicationCommand::class, $command);
        $this->assertEquals('846542216677566910', $command->getId());
        $this->assertEquals('sample', $command->getName());
        $this->assertEquals('Sample', $command->getDescription());
        $this->assertCount(1, $command->getOptions());
        $options = $command->getOptions();
        $option = array_shift($options);
        $this->assertInstanceOf(ApplicationCommandOption::class, $option);
        $this->assertCount(3, $option->getChoices());
    }
}


