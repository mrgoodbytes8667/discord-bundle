<?php


namespace Bytes\DiscordBundle\Services;


use Bytes\DiscordResponseBundle\Enums\OAuthPrompts;
use Bytes\DiscordResponseBundle\Enums\OAuthScopes;
use Bytes\DiscordResponseBundle\Enums\Permissions;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use function Symfony\Component\String\u;

/**
 * Class OAuth
 * @package Bytes\DiscordBundle\Services
 *
 * @method array getScopesBot()
 * @method array getScopesLogin()
 * @method array getScopesSlash()
 * @method array getScopesUser()
 */
class OAuth
{
    /**
     * @var string
     */
    private $discordClientId;

    /**
     * @var string
     */
    private $userOAuthRedirect;

    /**
     * @var string
     */
    private $botOAuthRedirect;

    /**
     * @var string
     */
    private $loginOAuthRedirect;

    /**
     * @var string
     */
    private $slashOAuthRedirect;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var array = ['bot' => ['redirects' => ['method' => '', 'route_name' => '', 'url' => ''], 'permissions' => ['add' => [0x0], 'remove' => 0x0]]]
     */
    private array $config;

    /**
     * Cached normalized permissions list
     * @var array
     */
    private $permissions = [];

    /**
     * Cached normalized scopes list
     * @var array
     */
    private $scopes = [];
    private array $defaultScopes;

    /**
     * OAuth constructor.
     * @param Security $security
     * @param UrlGeneratorInterface|null $urlGenerator
     * @param string $discordClientId
     * @param array $config
     * @param bool $user
     */
    public function __construct(Security $security, ?UrlGeneratorInterface $urlGenerator, string $discordClientId, array $config, bool $user)
    {
        $this->discordClientId = $discordClientId;

        $this->userOAuthRedirect = $this->setupRedirect($config['user']['redirects'], $urlGenerator);
        $this->botOAuthRedirect = $this->setupRedirect($config['bot']['redirects'], $urlGenerator);
        $this->loginOAuthRedirect = $this->setupRedirect($config['login']['redirects'], $urlGenerator);
        $this->slashOAuthRedirect = $this->setupRedirect($config['slash']['redirects'], $urlGenerator);

        if ($user) {
            $this->security = $security;
        }
        $this->config = $config;
        $this->defaultScopes = [
            'bot' => OAuthScopes::getBotScopes(),
            'login' => [
                OAuthScopes::IDENTIFY(),
                OAuthScopes::CONNECTIONS(),
                OAuthScopes::GUILDS(),
            ],
            'slash' => OAuthScopes::getSlashScopes(),
            'user' => OAuthScopes::getUserScopes()
        ];
    }

    /**
     * @param array $redirect = ['method' => ['route_name','url'][$any], 'route_name' => '', 'url' => '']
     * @param UrlGeneratorInterface|null $urlGenerator
     * @return string
     */
    protected function setupRedirect(array $redirect, ?UrlGeneratorInterface $urlGenerator)
    {
        switch ($redirect['method']) {
            case 'route_name':
                if (empty($urlGenerator)) {
                    throw new InvalidArgumentException('URLGeneratorInterface cannot be null when a route name is passed');
                }
                return $urlGenerator->generate($redirect['route_name'], [], UrlGeneratorInterface::ABSOLUTE_URL);
                break;
            case 'url':
                return $redirect['url'];
                break;
            default:
                throw new InvalidArgumentException("Param 'redirect' must be one of 'route_name' or 'url'");
                break;
        }
    }

    /**
     * @param string|null $guildId
     * @param string|null $state
     * @return string
     */
    public function getBotAuthorizationUrl(string $guildId = null, ?string $state = null): string
    {
        return $this->getAuthorizationCodeGrantURL(
            [
                Permissions::ADD_REACTIONS(),
                Permissions::VIEW_CHANNEL(),
                Permissions::SEND_MESSAGES(),
                Permissions::MANAGE_MESSAGES(),
                Permissions::READ_MESSAGE_HISTORY(),
                Permissions::EMBED_LINKS(),
                Permissions::USE_EXTERNAL_EMOJIS(),
                Permissions::MANAGE_ROLES(),
            ],
            $this->getBotOAuthRedirect(),
            $this->defaultScopes['bot'],
            $state,
            'bot',
            'code',
            $guildId,
            !empty($guildId)
        );
    }

    /**
     * @param array $permissions = Permissions::all()
     * @param string $redirect
     * @param array $scopes = OAuthScopes::all()
     * @param string|null $state
     * @param string $endpoint
     * @param string $responseType = ['code']
     * @param string|null $guildId
     * @param bool|null $disableGuildSelect
     * @param null $prompt = [OAuthPrompts::none(),OAuthPrompts::consent()]
     *
     * @return string
     */
    public function getAuthorizationCodeGrantURL(array $permissions, string $redirect, array $scopes, ?string $state, string $endpoint, string $responseType = 'code', ?string $guildId = null, ?bool $disableGuildSelect = null, $prompt = null)
    {
        if (empty($prompt)) {
            $prompt = OAuthPrompts::consent();
        }
        if (is_string($prompt)) {
            $prompt = new OAuthPrompts($prompt);
        }
        if (array_key_exists($endpoint, $this->permissions)) {
            $permissions = $this->permissions[$endpoint];
        } else {
            $permissions = $this->normalizePermissions($permissions, $endpoint);
            $this->permissions[$endpoint] = $permissions;
        }
        if (array_key_exists($endpoint, $this->scopes)) {
            $scopes = $this->scopes[$endpoint];
        } else {
            $scopes = $this->normalizeScopes($scopes, $endpoint);
            $this->scopes[$endpoint] = $scopes;
        }
        $query = [
            'client_id' => $this->discordClientId,
            'permissions' => Permissions::getFlags($permissions),
            'redirect_uri' => $redirect,
            'response_type' => $responseType,
            'scope' => OAuthScopes::buildOAuthString($scopes),
            'state' => $state ?? $this->getState($endpoint),
            'prompt' => $prompt->value,
        ];
        if (!empty($guildId)) {
            $query['guild_id'] = $guildId;
            if ($disableGuildSelect === true) {
                $query['disable_guild_select'] = 'true';
            }
        }
        return 'https://discord.com/api/oauth2/authorize?' . http_build_query($query);
    }

    /**
     * Takes the default permissions list and adds/removes any permissions coming from the config
     * @param array $permissions
     * @param string $endpoint
     * @return array
     */
    protected function normalizePermissions(array $permissions, string $endpoint)
    {
        if (array_key_exists('add', $this->config[$endpoint]['permissions'])) {
            $add = $this->config[$endpoint]['permissions']['add'];
            array_walk($add, array($this, 'hydratePermissions'));
            $permissions = array_merge($permissions, $add);
        }

        if (array_key_exists('remove', $this->config[$endpoint]['permissions'])) {
            $remove = $this->config[$endpoint]['permissions']['remove'];
            array_walk($remove, array($this, 'hydratePermissions'));

            $permissions = Arr::where($permissions, function ($value, $key) use ($remove) {
                return !in_array($value, $remove);
            });
        }

        return $permissions;
    }

    /**
     * Takes the default scopes list and adds/removes any scopes coming from the config
     * @param array $scopes
     * @param string $endpoint
     * @return array
     */
    protected function normalizeScopes(array $scopes, string $endpoint)
    {
        if (array_key_exists('add', $this->config[$endpoint]['scopes'])) {
            $add = $this->config[$endpoint]['scopes']['add'];
            array_walk($add, array($this, 'hydrateScopes'));
            $scopes = array_merge($scopes, $add);
        }

        if (array_key_exists('remove', $this->config[$endpoint]['scopes'])) {
            $remove = $this->config[$endpoint]['scopes']['remove'];
            array_walk($remove, array($this, 'hydrateScopes'));

            $scopes = Arr::where($scopes, function ($value, $key) use ($remove) {
                return !in_array($value, $remove);
            });
        }

        return $scopes;
    }

    /**
     * @return string
     */
    public function getBotOAuthRedirect(): string
    {
        return $this->botOAuthRedirect;
    }

    /**
     * @param string|null $guildId
     * @param string|null $state
     * @return string
     */
    public function getSlashAuthorizationUrl(string $guildId = null, ?string $state = null): string
    {
        return $this->getAuthorizationCodeGrantURL(
            [],
            $this->getSlashOAuthRedirect(),
            $this->defaultScopes['slash'],
            $state,
            'slash',
            'code',
            $guildId,
            !empty($guildId)
        );
    }

    /**
     * @return string
     */
    public function getSlashOAuthRedirect(): string
    {
        return $this->slashOAuthRedirect;
    }

    /**
     * @param string|null $state
     * @return string
     */
    public function getUserAuthorizationUrl(?string $state = null): string
    {
        return $this->getAuthorizationCodeGrantURL(
            [],
            $this->getUserOAuthRedirect(),
            $this->defaultScopes['user'],
            $state,
            'user');
    }

    /**
     * @return string
     */
    public function getUserOAuthRedirect(): string
    {
        return $this->userOAuthRedirect;
    }

    /**
     * @param string|null $state
     * @return string
     */
    public function getOAuthLoginUrl(?string $state = null): string
    {
        return $this->getAuthorizationCodeGrantURL(
            [],
            $this->getLoginOAuthRedirect(),
            $this->defaultScopes['login'],
            $state,
            'login',
            'code', null, null, OAuthPrompts::none());
    }

    /**
     * @return string
     */
    public function getLoginOAuthRedirect(): string
    {
        return $this->loginOAuthRedirect;
    }

    /**
     * @param $value
     * @param $key
     */
    protected function hydratePermissions(&$value, $key)
    {
        $value = new Permissions($value);
    }

    /**
     * @param $value
     * @param $key
     */
    protected function hydrateScopes(&$value, $key)
    {
        $value = (new OAuthScopes($value))->value;
    }

    /**
     * @param string $route
     * @return string
     */
    protected function getState(string $route)
    {
        switch ($route) {
            case 'routeOAuthLogin':
                return 'state';
                break;
            default:
                $user = '';
                if (!empty($this->security)) {
                    $user = $this->getUser()->getId();
                }
                return $user;
                break;
        }
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return UserInterface|null
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (empty($this->security)) {
            return null;
        }

        if (null === $token = $this->security->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

    public function __call($name, $arguments)
    {
        $scopesArg = u($name)->after('getScopes')->snake()->toString();
        switch ($scopesArg)
        {
            case 'bot':
            case 'login':
            case 'slash':
            case 'user':
            if (array_key_exists($scopesArg, $this->scopes)) {
                return $this->scopes[$scopesArg];
            } else {
                $scopes = $this->normalizeScopes($this->defaultScopes[$scopesArg], $scopesArg);
                $this->scopes[$scopesArg] = $scopes;
                return $scopes;
            }
        }
    }


}