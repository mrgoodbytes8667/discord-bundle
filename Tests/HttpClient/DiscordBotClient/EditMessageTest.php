<?php

namespace Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient;

use Bytes\DiscordBundle\Tests\Fixtures\Fixture;
use Bytes\DiscordBundle\Tests\MockHttpClient\MockClient;
use Bytes\DiscordBundle\Tests\MockHttpClient\MockJsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Class EditMessageTest
 * @package Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient
 */
class EditMessageTest extends TestDiscordBotClientCase
{
    use MessageProviderTrait;

    /**
     * @dataProvider provideCreateEditMessage
     */
    public function testEditMessage($channel, $message, $content, $tts)
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-channel-messages-success.json'),
        ]));

        $response = $client->editMessage($channel, $message, $content);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-channel-messages-success.json'));
    }

    /**
     * @dataProvider provideInvalidChannelValidContent
     * @param $channel
     * @param $content
     * @param $tts
     */
    public function testEditMessageBadChannelArgument($channel, $content, $tts)
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->setupClient(MockClient::emptyBadRequest());

        $client->editMessage($channel, '456', $content);
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     */
    public function testEditMessageFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $client = $this->setupClient(MockClient::emptyError($code));

        $client->editMessage('123', '456', 'content');
    }
}

