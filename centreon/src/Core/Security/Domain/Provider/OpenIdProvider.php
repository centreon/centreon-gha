<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

declare(strict_types=1);

namespace Core\Security\Domain\Provider;

use CentreonUserLog;
use Pimple\Container;
use Centreon\Domain\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\Response;
use Core\Domain\Configuration\User\Model\NewUser;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Security\Domain\Authentication\Model\ProviderToken;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Security\Domain\Authentication\Model\AuthenticationTokens;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Centreon\Domain\Contact\Interfaces\ContactServiceInterface;
use Core\Security\Domain\Authentication\SSOAuthenticationException;
use Security\Domain\Authentication\Interfaces\OpenIdProviderInterface;
use Core\Security\Domain\ProviderConfiguration\OpenId\Model\Configuration;
use Security\Domain\Authentication\Interfaces\ProviderConfigurationInterface;
use Core\Application\Configuration\User\Repository\WriteUserRepositoryInterface;
use Core\Security\Domain\Authentication\AuthenticationException;
use Core\Security\Domain\ProviderConfiguration\OpenId\Exceptions\OpenIdConfigurationException;

class OpenIdProvider implements OpenIdProviderInterface
{
    use LoggerTrait;

    /**
     * @var Configuration
     */
    private Configuration $configuration;

    /**
     * @var ProviderToken
     */
    private ProviderToken $providerToken;

    /**
     * @var null|ProviderToken
     */
    private ?ProviderToken $refreshToken = null;

    /**
     * @var array<string,mixed>
     */
    private array $userInformations = [];

    /**
     * @var string
     */
    private string $username;

    /**
     * @var \Centreon
     */
    private \Centreon $legacySession;

    /**
     * @var CentreonUserLog
     */
    private CentreonUserLog $centreonLog;

    /**
     * Array of information store in id_token JWT Payload
     *
     * @var array<string,mixed>
     */
    private array $idTokenPayload = [];

    /**
     * Content of the connexion token response.
     *
     * @var array<string,mixed>
     */
    private array $connectionTokenResponseContent = [];

    /**
     * @param HttpClientInterface $client
     */
    public function __construct(
        private HttpClientInterface $client,
        private UrlGeneratorInterface $router,
        private ContactServiceInterface $contactService,
        private Container $dependencyInjector,
        private WriteUserRepositoryInterface $userRepository
    ) {
        $pearDB = $this->dependencyInjector['configuration_db'];
        $this->centreonLog = new CentreonUserLog(-1, $pearDB);
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @inheritDoc
     */
    public function getProviderToken(): ProviderToken
    {
        return $this->providerToken;
    }

    /**
     * @inheritDoc
     */
    public function getProviderRefreshToken(): ?ProviderToken
    {
        return $this->refreshToken;
    }

    /**
     * @inheritDoc
     */
    public function setConfiguration(ProviderConfigurationInterface $configuration): void
    {
        if (!is_a($configuration, Configuration::class)) {
            throw new \InvalidArgumentException('Bad provider configuration');
        }
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function canCreateUser(): bool
    {
        return $this->configuration->isAutoImportEnabled();
    }

    /**
     * {@inheritDoc}
     * @throws SSOAuthenticationException
     */
    public function createUser(): void
    {
        $this->info('Auto import starting...', [
            "user" => $this->username
        ]);
        $this->validateAutoImportAttributesOrFail();

        $user = new NewUser(
            $this->username,
            $this->userInformations[$this->configuration->getUserNameBindAttribute()],
            $this->userInformations[$this->configuration->getEmailBindAttribute()],
        );
        $user->setContactTemplate($this->configuration->getContactTemplate());
        $this->userRepository->create($user);
        $this->info('Auto import complete', [
            "user_alias" => $this->username,
            "user_fullname" => $this->userInformations[$this->configuration->getUserNameBindAttribute()],
            "user_email" => $this->userInformations[$this->configuration->getEmailBindAttribute()]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getLegacySession(): \Centreon
    {
        return $this->legacySession;
    }

    /**
     * @inheritDoc
     */
    public function setLegacySession(\Centreon $legacySession): void
    {
        $this->legacySession = $legacySession;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return Configuration::NAME;
    }

    /**
     * @inheritDoc
     */
    public function canRefreshToken(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @throws SSOAuthenticationException
     * @throws OpenIdConfigurationException
     */
    public function authenticateOrFail(?string $authorizationCode, string $clientIp): void
    {
        $this->info('Start authenticating user...', [
            'provider' => Configuration::NAME
        ]);
        if (empty($authorizationCode)) {
            $this->error(
                'No authorization code returned from external provider',
                [
                    'provider' => Configuration::NAME
                ]
            );
            throw SSOAuthenticationException::noAuthorizationCode(Configuration::NAME);
        }

        if ($this->configuration->getTokenEndpoint() === null) {
            throw OpenIdConfigurationException::missingTokenEndpoint();
        }
        if (
            $this->configuration->getIntrospectionTokenEndpoint() === null
            && $this->configuration->getUserInformationEndpoint() === null
        ) {
            throw OpenIdConfigurationException::missingInformationEndpoint();
        }

        $this->verifyThatClientIsAllowedToConnectOrFail($clientIp);

        $this->sendRequestForConnectionTokenOrFail($authorizationCode);
        $this->createAuthenticationTokens();
        if ($this->providerToken->isExpired() && $this->refreshToken->isExpired()) {
            throw SSOAuthenticationException::tokensExpired(Configuration::NAME);
        }
        if ($this->configuration->getIntrospectionTokenEndpoint() !== null) {
            $this->sendRequestForIntrospectionTokenOrFail();
        }

        if (array_key_exists("id_token", $this->connectionTokenResponseContent)) {
            $this->idTokenPayload = $this->extractTokenPayload($this->connectionTokenResponseContent["id_token"]);
        }
        $this->username = $this->getUsernameFromLoginClaim();
    }

    /**
     * @inheritDoc
     */
    public function getUser(): ?ContactInterface
    {
        $this->info('Searching user : ' . $this->username);
        $user = $this->contactService->findByName($this->username);
        if ($user === null) {
            $user = $this->contactService->findByEmail($this->username);
        }

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function refreshToken(AuthenticationTokens $authenticationTokens): AuthenticationTokens
    {
        if ($authenticationTokens->getProviderRefreshToken() === null) {
            throw SSOAuthenticationException::noRefreshToken();
        }
        $this->info(
            'Refreshing token using refresh token',
            [
                'refresh_token' => substr($authenticationTokens->getProviderRefreshToken()->getToken(), -10)
            ]
        );
        // Define parameters for the request
        $data = [
            "grant_type" => "refresh_token",
            "refresh_token" => $authenticationTokens->getProviderRefreshToken()->getToken(),
            "scope" => !empty($this->configuration->getConnectionScopes())
                ? implode(' ', $this->configuration->getConnectionScopes())
                : null
        ];

        $response = $this->sendRequestToTokenEndpoint($data);

        // Get the status code and throw an Exception if not a 200
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logErrorForInvalidStatusCode($statusCode, Response::HTTP_OK);
            $this->logExceptionInLoginLogFile(
                "Unable to get Refresh Token Information: %s, message: %s",
                SSOAuthenticationException::requestForRefreshTokenFail()
            );
            throw SSOAuthenticationException::requestForRefreshTokenFail();
        }
        $content = json_decode($response->getContent(false), true);
        if (empty($content) || array_key_exists('error', $content)) {
            $this->logErrorInLoginLogFile('Refresh Token Info:', $content);
            $this->logErrorFromExternalProvider($content);
            throw SSOAuthenticationException::errorFromExternalProvider(Configuration::NAME);
        }
        $this->logAuthenticationInfo('Token Access Information:', $content);
        $creationDate = new \DateTime();
        $providerTokenExpiration = (new \DateTime())->add(new \DateInterval('PT' . $content ['expires_in'] . 'S'));
        $this->providerToken =  new ProviderToken(
            $authenticationTokens->getProviderToken()->getId(),
            $content['access_token'],
            $creationDate,
            $providerTokenExpiration
        );
        if (array_key_exists('refresh_token', $content)) {
            $expirationDelay = $content['expires_in'] + 3600;
            if (array_key_exists('refresh_expires_in', $content)) {
                $expirationDelay = $content['refresh_expires_in'];
            }
            $refreshTokenExpiration = (new \DateTime())
                ->add(new \DateInterval('PT' . $expirationDelay . 'S'));
            $this->refreshToken = new ProviderToken(
                null,
                $content['refresh_token'],
                $creationDate,
                $refreshTokenExpiration
            );
        }

        return new AuthenticationTokens(
            $authenticationTokens->getUserId(),
            $authenticationTokens->getConfigurationProviderId(),
            $authenticationTokens->getSessionToken(),
            $this->providerToken,
            $this->refreshToken
        );
    }

    /**
     * @inheritDoc
     */
    public function getUserInformation(): array
    {
        return $this->userInformations;
    }

    /**
     * @inheritDoc
     */
    public function getIdTokenPayload(): array
    {
        return $this->idTokenPayload;
    }

    /**
     * Extract Payload from JWT token
     *
     * @param string $token
     * @return array<string,mixed>
     * @throws SSOAuthenticationException
     */
    private function extractTokenPayload(string $token): array
    {
        try {
            $tokenParts = explode(".", $token);
            return json_decode(base64_decode($tokenParts[1]), true);
        } catch (\Throwable $ex) {
            $this->error(
                SSOAuthenticationException::unableToDecodeIdToken()->getMessage(),
                ['trace' => $ex->getTraceAsString()]
            );
            throw SSOAuthenticationException::unableToDecodeIdToken();
        }
    }

    /**
     * Get Connection Token from OpenId Provider.
     *
     * @param string $authorizationCode
     * @throws SSOAuthenticationException
     */
    private function sendRequestForConnectionTokenOrFail(string $authorizationCode): void
    {
        $this->info('Send request to external provider for connection token...');

        // Define parameters for the request
        $redirectUri = $this->router->generate(
            'centreon_security_authentication_openid_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $data = [
            "grant_type" => "authorization_code",
            "code" => $authorizationCode,
            "redirect_uri" => $redirectUri
        ];

        $response = $this->sendRequestToTokenEndpoint($data);

        // Get the status code and throw an Exception if not a 200
        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logErrorForInvalidStatusCode($statusCode, Response::HTTP_OK);
            $this->logExceptionInLoginLogFile(
                "Unable to get Token Access Information: %s, message: %s",
                SSOAuthenticationException::requestForConnectionTokenFail()
            );
            throw SSOAuthenticationException::requestForConnectionTokenFail();
        }
        $content = json_decode($response->getContent(false), true);
        if (empty($content) || array_key_exists('error', $content)) {
            $this->logErrorInLoginLogFile('Connection Token Info: ', $content);
            $this->logErrorFromExternalProvider($content);
            throw SSOAuthenticationException::errorFromExternalProvider(Configuration::NAME);
        }
        $this->logAuthenticationInfo('Token Access Information:', $content);
        $this->connectionTokenResponseContent = $content;
    }

    /**
     * Create Authentication Tokens
     */
    private function createAuthenticationTokens(): void
    {
        $creationDate = new \DateTime();
        $expirationDelay = array_key_exists('expires_in', $this->connectionTokenResponseContent)
            ? $this->connectionTokenResponseContent['expires_in']
            : 3600;
        $providerTokenExpiration = (new \DateTime())->add(
            new \DateInterval('PT' . $expirationDelay . 'S')
        );
        $this->providerToken =  new ProviderToken(
            null,
            $this->connectionTokenResponseContent['access_token'],
            $creationDate,
            $providerTokenExpiration
        );
        if (array_key_exists('refresh_token', $this->connectionTokenResponseContent)) {
            $expirationDelay = $this->connectionTokenResponseContent['expires_in'] + 3600;
            if (array_key_exists('refresh_expires_in', $this->connectionTokenResponseContent)) {
                $expirationDelay = $this->connectionTokenResponseContent['refresh_expires_in'];
            }
            $refreshTokenExpiration = (new \DateTime())
                ->add(new \DateInterval('PT' . $expirationDelay . 'S'));
            $this->refreshToken = new ProviderToken(
                null,
                $this->connectionTokenResponseContent['refresh_token'],
                $creationDate,
                $refreshTokenExpiration
            );
        }
    }

    /**
     * Send a request to get introspection token information.
     * @throws SSOAuthenticationException
     */
    private function sendRequestForIntrospectionTokenOrFail(): void
    {
        $this->info('Sending request for introspection token information');
        // Define parameters for the request
        $data = [
            "token" => $this->providerToken->getToken(),
            "client_id" => $this->configuration->getClientId(),
            "client_secret" => $this->configuration->getClientSecret()
        ];
        $headers = [
            'Authorization' => 'Bearer ' . trim($this->providerToken->getToken())
        ];
        try {
            $response = $this->client->request(
                'POST',
                $this->configuration->getBaseUrl() . '/'
                . ltrim($this->configuration->getIntrospectionTokenEndpoint(), '/'),
                [
                    'headers' => $headers,
                    'body' => $data,
                    'verify_peer' => $this->configuration->verifyPeer()
                ]
            );
        } catch (\Exception $e) {
            $this->logExceptionInLoginLogFile("Unable to get Introspection Information: %s, message: %s", $e);
            $this->error(sprintf(
                "[Error] Unable to get Token Introspection Information:, message: %s",
                $e->getMessage()
            ));
            throw SSOAuthenticationException::requestForIntrospectionTokenFail();
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logErrorForInvalidStatusCode($statusCode, Response::HTTP_OK);
            $this->logExceptionInLoginLogFile(
                "Unable to get Introspection Information: %s, message: %s",
                SSOAuthenticationException::requestForIntrospectionTokenFail()
            );
            throw SSOAuthenticationException::requestForIntrospectionTokenFail();
        }
        $content = json_decode($response->getContent(false), true);
        if (empty($content) || array_key_exists('error', $content)) {
            $this->logErrorInLoginLogFile('Introspection Token Info: ', $content);
            $this->logErrorFromExternalProvider($content);
            throw SSOAuthenticationException::errorFromExternalProvider(Configuration::NAME);
        }
        $this->logAuthenticationInfo('Token Introspection Information: ', $content);
        $this->userInformations = $content;
    }

    /**
     * Send a request to get user information.
     * @throws SSOAuthenticationException
     */
    private function sendRequestForUserInformationOrFail(): void
    {
        $this->info('Send Request for User Information...');
        $headers = [
            'Authorization' => "Bearer " . trim($this->providerToken->getToken())
        ];
        $url = str_starts_with($this->configuration->getUserInformationEndpoint(), '/')
            ? $this->configuration->getBaseUrl() . $this->configuration->getUserInformationEndpoint()
            : $this->configuration->getUserInformationEndpoint();
        try {
            $response = $this->client->request(
                'GET',
                $url,
                [
                    'headers' => $headers,
                    'verify_peer' => $this->configuration->verifyPeer()
                ]
            );
        } catch (\Exception $ex) {
            throw SSOAuthenticationException::requestForUserInformationFail();
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $this->logErrorForInvalidStatusCode($statusCode, Response::HTTP_OK);
            $this->logExceptionInLoginLogFile(
                "Unable to get User Information: %s, message: %s",
                SSOAuthenticationException::requestForUserInformationFail()
            );
            throw SSOAuthenticationException::requestForUserInformationFail();
        }
        $content = json_decode($response->getContent(false), true);
        if (empty($content) || array_key_exists('error', $content)) {
            $this->logErrorInLoginLogFile('User Information Info: ', $content);
            $this->logErrorFromExternalProvider($content);
            throw SSOAuthenticationException::errorFromExternalProvider(Configuration::NAME);
        }
        $this->logAuthenticationInfo('User Information: ', $content);
        $this->userInformations = $content;
    }

    /**
     * Validate that Client IP is allowed to connect to external provider.
     *
     * @param string $clientIp
     * @throws SSOAuthenticationException
     */
    private function verifyThatClientIsAllowedToConnectOrFail(string $clientIp): void
    {
        $this->info('Check Client IP from blacklist/whitelist addresses');
        foreach ($this->configuration->getBlacklistClientAddresses() as $blackListedAddress) {
            if ($blackListedAddress !== "" && preg_match('/' . $blackListedAddress . '/', $clientIp)) {
                $this->error('IP Blacklisted', [ 'ip' => '...' . substr($clientIp, -5)]);
                throw SSOAuthenticationException::blackListedClient();
            }
        }

        foreach ($this->configuration->getTrustedClientAddresses() as $trustedClientAddress) {
            if (
                $trustedClientAddress !== ""
                && preg_match('/' . $trustedClientAddress . '/', $clientIp)
            ) {
                $this->error('IP not  Whitelisted', [ 'ip' => '...' . substr($clientIp, -5)]);
                throw SSOAuthenticationException::notWhiteListedClient();
            }
        }
    }

    /**
     * Return username from login claim.
     *
     * @return string
     * @throws SSOAuthenticationException
     */
    private function getUsernameFromLoginClaim(): string
    {
        $loginClaim = ! empty($this->configuration->getLoginClaim())
            ? $this->configuration->getLoginClaim()
            : Configuration::DEFAULT_LOGIN_CLAIM;
        if (
            !array_key_exists($loginClaim, $this->userInformations)
            && $this->configuration->getUserInformationEndpoint() !== null
        ) {
            $this->sendRequestForUserInformationOrFail();
        }
        if (!array_key_exists($loginClaim, $this->userInformations)) {
            $this->centreonLog->insertLog(
                CentreonUserLog::TYPE_LOGIN,
                "[Openid] [Error] Unable to get login from claim: " . $loginClaim
            );
            $this->error('Login Claim not found', ['login_claim' => $loginClaim]);
            throw SSOAuthenticationException::loginClaimNotFound(Configuration::NAME, $loginClaim);
        }
        return $this->userInformations[$loginClaim];
    }

    /**
     * Define authentication type based on configuration
     *
     * @param array<string,mixed> $data
     * @return ResponseInterface
     * @throws SSOAuthenticationException
     */
    private function sendRequestToTokenEndpoint(array $data): ResponseInterface
    {
        $headers = [
            'Content-Type' => "application/x-www-form-urlencoded"
        ];

        if ($this->configuration->getAuthenticationType() === Configuration::AUTHENTICATION_BASIC) {
            $headers['Authorization'] = "Basic " . base64_encode(
                $this->configuration->getClientId() . ":" . $this->configuration->getClientSecret()
            );
        } else {
            $data["client_id"] = $this->configuration->getClientId();
            $data["client_secret"] = $this->configuration->getClientSecret();
        }

        $url = str_starts_with($this->configuration->getTokenEndpoint(), '/')
            ? $this->configuration->getBaseUrl() . $this->configuration->getTokenEndpoint()
            : $this->configuration->getTokenEndpoint();

        // Send the request to IDP
        try {
            return $this->client->request(
                'POST',
                $url,
                [
                    'headers' => $headers,
                    'body' => $data,
                    'verify_peer' => $this->configuration->verifyPeer()
                ]
            );
        } catch (\Exception $e) {
            $this->logExceptionInLoginLogFile('Unable to get Token Access Information: %s, message: %s', $e);
            if (array_key_exists('refresh_token', $data)) {
                $this->error(
                    sprintf("[Error] Unable to get Token Refresh Information:, message: %s", $e->getMessage())
                );
                throw SSOAuthenticationException::requestForRefreshTokenFail();
            } else {
                $this->error(
                    sprintf("[Error] Unable to get Token Access Information:, message: %s", $e->getMessage())
                );
                throw SSOAuthenticationException::requestForConnectionTokenFail();
            }
        }
    }

    /**
     * Validate that auto import attributes are present in user informations from provider
     * @throws SSOAuthenticationException
     */
    private function validateAutoImportAttributesOrFail(): void
    {
        $missingAttributes = [];
        if (! array_key_exists($this->configuration->getEmailBindAttribute(), $this->userInformations)) {
            $missingAttributes[] = $this->configuration->getEmailBindAttribute();
        }
        if (! array_key_exists($this->configuration->getUserNameBindAttribute(), $this->userInformations)) {
            $missingAttributes[] = $this->configuration->getUserNameBindAttribute();
        }

        if (! empty($missingAttributes)) {
            $ex = SSOAuthenticationException::autoImportBindAttributeNotFound($missingAttributes);
            $this->logExceptionInLoginLogFile(
                "Some bind attributes can't be found in user information: %s, message: %s",
                $ex
            );
            throw $ex;
        }
    }

    /**
     * Log error when response from external provider contains error or is empty
     *
     * @param array<string,string> $content
     */
    private function logErrorFromExternalProvider(array $content): void
    {
        $this->error(
            'error from external provider :' . (array_key_exists('error', $content)
                ? $content['error']
                : 'No content in response')
        );
    }

    /**
     * Log error when response from external provider has an invalid status code
     *
     * @param integer $codeReceived
     * @param integer $codeExpected
     */
    private function logErrorForInvalidStatusCode(int $codeReceived, int $codeExpected): void
    {
        $this->error(
            sprintf(
                "invalid status code return by external provider, [%d] returned, [%d] expected",
                $codeReceived,
                $codeExpected
            )
        );
    }

    /**
     * Log error in login.log file
     *
     * @param string $message
     * @param array<string,string> $content
     */
    private function logErrorInLoginLogFile(string $message, array $content): void
    {
        if (array_key_exists('error', $content)) {
            $this->centreonLog->insertLog(
                CentreonUserLog::TYPE_LOGIN,
                "[Openid] [Error] $message" . json_encode($content)
            );
        }
    }

    /**
     * Log Authentication informations
     *
     * @param string $message
     * @param array<string,string> $content
     */
    private function logAuthenticationInfo(string $message, array $content): void
    {
        if (isset($content['jti'])) {
            $content['jti'] = substr($content['jti'], -10);
        }
        if (isset($content['access_token'])) {
            $content['access_token'] = substr($content['access_token'], -10);
        }
        if (isset($content['refresh_token'])) {
            $content['refresh_token'] = substr($content['refresh_token'], -10);
        }
        if (isset($content['id_token'])) {
            $content['id_token'] = substr($content['id_token'], -10);
        }
        if (isset($content['provider_token'])) {
            $content['provider_token'] = substr($content['provider_token'], -10);
        }
        $this->centreonLog->insertLog(
            CentreonUserLog::TYPE_LOGIN,
            "[Openid] [Debug] $message " . json_encode($content)
        );
        $this->debug('Authentication informations : ', $content);
    }

    /**
     * Log Exception in login.log file
     *
     * @param string $message
     * @param \Exception $e
     */
    private function logExceptionInLoginLogFile(string $message, \Exception $e): void
    {
        $this->centreonLog->insertLog(
            CentreonUserLog::TYPE_LOGIN,
            sprintf(
                "[Openid] [Error] $message",
                get_class($e),
                $e->getMessage()
            )
        );
    }
}
