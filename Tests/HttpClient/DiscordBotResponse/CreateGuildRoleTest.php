<?php

namespace Bytes\DiscordBundle\Tests\HttpClient\DiscordBotResponse;

use Bytes\DiscordBundle\Tests\HttpClient\DiscordBotClient\TestDiscordBotClientCase;
use Bytes\DiscordResponseBundle\Enums\JsonErrorCodes;
use Bytes\DiscordResponseBundle\Objects\Role;
use Spatie\Enum\Phpunit\EnumAssertions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class CreateGuildRoleTest
 * @package Bytes\DiscordBundle\Tests\HttpClient\DiscordBotResponse
 */
class CreateGuildRoleTest extends TestDiscordBotClientCase
{
    use EnumAssertions;

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testCreateGuildRole()
    {
        $role = $this
            ->setupResponse('HttpClient/create-guild-role-success.json', type: Role::class)
            ->deserialize();

        $this->assertInstanceOf(Role::class, $role);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testCreateGuildNameBypassValidationError()
    {
        $response = $this
            ->setupResponse(content: '{"code": 50035, "errors": {"name": {"_errors": [{"code": "BASE_TYPE_MAX_LENGTH", "message": "Must be 100 or fewer in length."}]}}, "message": "Invalid Form Body"}',
                code: Response::HTTP_BAD_REQUEST, type: Role::class);
        $this->assertResponseHasContent($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_BAD_REQUEST);

        /** @var Role $error */
        $error = $response->deserialize(false);

        $this->assertSameEnumValue(JsonErrorCodes::INVALID_FORM_BODY(), $error->getCode());
        $this->assertEquals(50035, $error->getCode());
        $this->assertEquals("Invalid Form Body", $error->getMessage());
    }
}