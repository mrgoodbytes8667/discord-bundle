<?php


namespace Bytes\DiscordBundle\HttpClient\Token;


use Bytes\ResponseBundle\HttpClient\Response\TokenResponse;

/**
 * Class DiscordUserTokenResponse
 * @package Bytes\DiscordBundle\HttpClient\Token
 */
class DiscordUserTokenResponse extends TokenResponse
{
    /**
     * Identifier used for differentiating different token providers
     * @var string
     */
    protected static $identifier = 'DISCORD';

    /**
     * @var string
     */
    protected static $tokenSource = 'user';
}
