<?php


namespace Bytes\DiscordBundle\Tests\MockHttpClient;


use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MockJsonTooManyRetriesResponse
 * @package Bytes\DiscordBundle\Tests\MockHttpClient
 */
class MockJsonTooManyRetriesResponse extends MockResponse
{
    /**
     * MockJsonTooManyRetriesResponse constructor.
     * @param float|null $retryAfter Number in seconds
     * @param array $info = ResponseInterface::getInfo()
     * @throws Exception
     *
     * @see ResponseInterface::getInfo() for possible info, e.g. "response_headers"
     */
    public function __construct(?float $retryAfter = null, array $info = [])
    {
        if (($retryAfter ?? 0) <= 0) {
            $retryAfter = rand(0, 2000) / 1000;
        }

        $reset = new DateTime();
        $reset->add(new DateInterval(sprintf('PT%dS', ceil($retryAfter))));

        $body = json_encode(["message" => "You are being rate limited.", "retry_after" => $retryAfter, "global" => false]);
        $info['response_headers']['Content-Type'] = 'application/json';
        $info['response_headers']['X-RateLimit-Limit'] = 5;
        $info['response_headers']['X-RateLimit-Remaining'] = 0;
        $info['response_headers']['X-RateLimit-Reset'] = $reset->getTimestamp();
        $info['response_headers']['X-RateLimit-Reset-After'] = $retryAfter;
        $info['response_headers']['X-RateLimit-Bucket'] = 'abcd1234';
        $info['http_code'] = Response::HTTP_TOO_MANY_REQUESTS;
        parent::__construct($body, $info);
    }
}
