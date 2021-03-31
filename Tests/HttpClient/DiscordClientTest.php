<?php

namespace Bytes\DiscordBundle\Tests\HttpClient;

use Bytes\DiscordBundle\Tests\DiscordClientSetupTrait;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Internet;
use InvalidArgumentException;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DiscordClientTest
 * @package Bytes\DiscordBundle\Tests\HttpClient
 */
class DiscordClientTest extends TestHttpClientCase
{
    use TestDiscordClientTrait, ExpectDeprecationTrait, DiscordClientSetupTrait {
        DiscordClientSetupTrait::setupBaseClient as setupClient;
    }

    /**
     * @group legacy
     */
    public function testRequest()
    {
        $this->expectDeprecation('Since fakerphp/faker 1.14: Accessing property "word" is deprecated, use "word()" instead.');
        $this->expectDeprecation('Since fakerphp/faker 1.14: Accessing property "safeEmailDomain" is deprecated, use "safeEmailDomain()" instead.');
        /** @var Generator|Internet $faker */
        $faker = Factory::create();
        $content = $faker->randomHtml();

        $client = $this->setupClient(new MockHttpClient([
            new MockResponse($content),
        ]));

        $response = $client->request($faker->url());

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, $content);
    }

    /**
     * @dataProvider provideInvalidUrls
     * @param $url
     */
    public function testRequestInvalidUrl($url)
    {
        $client = $this->setupClient(new MockHttpClient());

        $this->expectException(InvalidArgumentException::class);
        $client->request($url);
    }

    /**
     * @return \Generator
     */
    public function provideInvalidUrls()
    {
        yield ['url' => ''];
        yield ['url' => null];
        yield ['url' => []];
        yield ['url' => 1];
        yield ['url' => new DateTime()];
    }
}
