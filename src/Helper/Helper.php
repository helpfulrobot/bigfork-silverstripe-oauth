<?php

namespace Bigfork\SilverStripeOAuth\Client\Helper;

use Config;
use Controller;
use Director;
use Injector;

class Helper
{
    /**
     * @var string
     */
    private static $default_redirect_uri;

    /**
     * @param string $provider
     * @param array $scopes
     * @return string
     */
    public static function buildAuthorisationUrl($provider, array $scopes = [])
    {
        $controller = Injector::inst()->get('Bigfork\SilverStripeOAuth\Client\Control\Controller');
        $data = [
            'provider' => $provider,
            'scope' => $scopes
        ];

        return Controller::join_links(
            Director::absoluteBaseURL(),
            $controller->Link(),
            'authenticate/?' . http_build_query($data)
        );
    }

    /**
     * Adds the redirectUri option to each of the configured provider's service
     * configs: the redirectUri is required on construction
     */
    public static function addRedirectUriToConfigs()
    {
        $factoryConfig = Config::inst()->get('Injector', 'ProviderFactory');
        $providers = $factoryConfig['properties']['providers'];

        foreach ($providers as $name => $spec) {
            // If this is not a service definition, skip it
            if (strpos($spec, '%$') !== 0) {
                continue;
            }

            // Trim %$ServiceName to ServiceName
            $serviceName = substr($spec, 2);
            $serviceConfig = (array)Config::inst()->get('Injector', $serviceName);

            if (!empty($serviceConfig)) {
                $serviceConfig = static::addRedirectUriToServiceConfig($serviceConfig);
                Config::inst()->update('Injector', $serviceName, $serviceConfig);
                Injector::inst()->load(array($serviceName => $serviceConfig));
            }
        }
    }

    /**
     * Add in the redirectUri option to this service's constructor options
     * 
     * @param array $config
     * @return array
     */
    protected static function addRedirectUriToServiceConfig(array $config)
    {
        if (!empty($config['constructor']) && is_array($config['constructor'])) {
            $key = key($config['constructor']); // Key may be non-numeric

            if (!isset($config['constructor'][$key]['redirectUri'])) {
                $config['constructor'][$key]['redirectUri'] = static::getRedirectUri();
            }
        }

        return $config;
    }

    /**
     * @return string
     */
    protected static function getRedirectUri()
    {
        $configUri = Config::inst()->get(__CLASS__, 'default_redirect_uri');
        if ($configUri) {
            return $configUri;
        }

        $controller = Injector::inst()->get('Bigfork\SilverStripeOAuth\Client\Control\Controller');
        return Controller::join_links($controller->AbsoluteLink(), 'callback/');
    }
}
