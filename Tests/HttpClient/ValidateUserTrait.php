<?php


namespace Bytes\DiscordBundle\Tests\HttpClient;


use Bytes\DiscordResponseBundle\Objects\User;

/**
 * Trait ValidateUserTrait
 * @package Bytes\DiscordBundle\Tests\HttpClient
 *
 * @method assertInstanceOf(string $expected, $actual, string $message = '')
 * @method assertEquals($expected, $actual, string $message = '')
 */
trait ValidateUserTrait
{

    /**
     * @param $user
     * @param $id
     * @param $username
     * @param $avatar
     * @param $discriminator
     * @param $flags
     * @param $bot
     */
    protected function validateUser($user, $id, $username, $avatar, $discriminator, $flags, $bot = null)
    {
        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($avatar, $user->getAvatar());
        $this->assertEquals($discriminator, $user->getDiscriminator());
        $this->assertEquals($flags, $user->getPublicFlags());
        $this->assertEquals($bot, $user->getBot());
    }
}