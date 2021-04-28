<?php

namespace Bytes\DiscordBundle\Tests\HttpClient\DiscordTokenClient;

use Bytes\DiscordBundle\Tests\DiscordClientSetupTrait;
use Bytes\DiscordBundle\Tests\HttpClient\TestDiscordClientTrait;
use Bytes\DiscordBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\DiscordBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\DiscordBundle\Tests\TestUrlGeneratorTrait;
use Bytes\DiscordResponseBundle\Objects\Guild;
use Bytes\DiscordResponseBundle\Objects\PartialGuild;
use Bytes\DiscordResponseBundle\Objects\Token;
use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\ByteString;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class DiscordTokenClientTest
 * @package Bytes\DiscordBundle\Tests\HttpClient
 *
 * @requires PHPUnit >= 9
 */
class DiscordTokenClientTest extends TestHttpClientCase
{
    use TestUrlGeneratorTrait, DiscordClientSetupTrait {
        DiscordClientSetupTrait::setupTokenClient as setupClient;
    }

    /**
     * @return \Generator
     */
    public function provideTokenExchangeResponses()
    {
        yield ['error' => 'invalid_request', 'description' => 'Invalid "code" in request.', 'filename' => 'HttpClient/token-exchange-failure-400-invalid-code.json'];
        yield ['error' => 'invalid_request', 'description' => 'Invalid "redirect_uri" in request.', 'filename' => 'HttpClient/token-exchange-failure-400-invalid-redirect.json'];
    }

    /**
     *
     */
    public function testTokenExchange()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/token-exchange-with-guild-success.json')
        ]));

        $code = ByteString::fromRandom(30);
        $redirect = 'https://www.example.com';


        $response = $client->tokenExchange($code, url: $redirect);
        $this->assertInstanceOf(Token::class, $response);

        $this->assertEquals('Bearer', $response->getTokenType());
        $this->assertEquals('v6XtvrnWt1D3R6YFzSejQoBv6oVW5W', $response->getAccessToken());
        $this->assertEquals(new \DateInterval('PT604800S'), $response->getExpiresIn());
        $this->assertEquals('tDpAVPmhq4PZqeXiXCTV6mRGvhgDu9', $response->getRefreshToken());
        $this->assertEquals('identify connections guilds bot applications.commands', $response->getScope());

        $this->assertInstanceOf(PartialGuild::class, $response->getGuild());
        $this->assertInstanceOf(Guild::class, $response->getGuild());

        // Test some fields that will only be present for full guild objects
        $this->assertCount(2, $response->getGuild()->getRoles());
        $this->assertEquals('711392223308032631', $response->getGuild()->getSystemChannelId());

        $this->assertNull($response->getMessage());
        $this->assertNull($response->getCode());
        $this->assertNull($response->getRetryAfter());
        $this->assertNull($response->getGlobal());
    }

    /**
     *
     */
    public function testRefreshTokenExchange()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/token-exchange-with-guild-success.json')
        ]));

        $code = ByteString::fromRandom(30);
        $redirect = 'https://www.example.com';


        $response = $client->tokenExchange($code, url: $redirect, scopes: [], grantType: OAuthGrantTypes::refreshToken());
        $this->assertInstanceOf(Token::class, $response);

        $this->assertEquals('Bearer', $response->getTokenType());
        $this->assertEquals('v6XtvrnWt1D3R6YFzSejQoBv6oVW5W', $response->getAccessToken());
        $this->assertEquals(new \DateInterval('PT604800S'), $response->getExpiresIn());
        $this->assertEquals('tDpAVPmhq4PZqeXiXCTV6mRGvhgDu9', $response->getRefreshToken());
        $this->assertEquals('identify connections guilds bot applications.commands', $response->getScope());

        $this->assertInstanceOf(PartialGuild::class, $response->getGuild());
        $this->assertInstanceOf(Guild::class, $response->getGuild());

        // Test some fields that will only be present for full guild objects
        $this->assertCount(2, $response->getGuild()->getRoles());
        $this->assertEquals('711392223308032631', $response->getGuild()->getSystemChannelId());

        $this->assertNull($response->getMessage());
        $this->assertNull($response->getCode());
        $this->assertNull($response->getRetryAfter());
        $this->assertNull($response->getGlobal());
    }

    /**
     * @dataProvider provideTokenExchangeResponses
     *
     * @param string $error
     * @param string $description
     * @param string $filename
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testTokenExchangeBadRequestsGeneric(string $error, string $description, string $filename)
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($filename, Response::HTTP_BAD_REQUEST)
        ]));

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', Response::HTTP_BAD_REQUEST));

        $code = ByteString::fromRandom(30);
        $redirect = 'https://www.example.com';

        $client->tokenExchange($code, $redirect);
    }

    /**
     * @dataProvider provideTokenExchangeResponses
     *
     * @param string $error
     * @param string $description
     * @param string $filename
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testTokenExchangeBadRequestsExplicit(string $error, string $description, string $filename)
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($filename, Response::HTTP_BAD_REQUEST)
        ]));

        $code = ByteString::fromRandom(30);
        $redirect = 'https://www.example.com';

        try {
            $client->tokenExchange($code, $redirect);
        } catch (ClientExceptionInterface $exception) {
            $json = $exception->getResponse()->getContent(false);
            $response = $this->serializer->deserialize($json, Token::class, 'json');
            $this->assertEquals($error, $response->getError());
            $this->assertEquals($description, $response->getErrorDescription());

            $this->assertNull($response->getTokenType());
            $this->assertNull($response->getAccessToken());
            $this->assertNull($response->getExpiresIn());
            $this->assertNull($response->getRefreshToken());
            $this->assertEmpty($response->getScope());
            $this->assertNull($response->getGuild());
        }
    }
}