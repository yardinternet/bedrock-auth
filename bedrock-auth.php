<?php
/**
 * @package WP Bedrock Auth
 * @version 1.1.1
 */
/*
Plugin Name: WP Bedrock Auth
Plugin URI: https://github.com/aprivette/bedrock-auth
Description: Environment-specific basic auth for the Bedrock WordPress framework.
Author: Adam Privette
Version: 1.1.1
Author URI: https://github.com/aprivette/
*/

namespace BedrockAuth;

use Dotenv\Dotenv;

use function Env\env;

class BasicAuth
{
    public function __construct()
    {
        $this->root_dir = dirname(dirname(ABSPATH));
        $this->dotenv = Dotenv::createUnsafeImmutable($this->root_dir);
    }

    public function initAuth()
    {
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        if (file_exists($this->root_dir . '/.env')) {
            $this->dotenv->load();
            $this->dotenv->required(['BASIC_AUTH_USER', 'BASIC_AUTH_PASS']);

            if (env('BASIC_AUTH_USER') && env('BASIC_AUTH_PASS')) {
                if (strcasecmp(env('BASIC_AUTH_LEVEL'), 'site') === 0) {
                    $this->requireAuth(env('BASIC_AUTH_USER'), env('BASIC_AUTH_PASS'));
                } elseif (strcasecmp(env('BASIC_AUTH_LEVEL'), 'login') === 0 && substr($_SERVER['REQUEST_URI'], 0, 16) == '/wp/wp-login.php') {
                    $this->requireAuth(env('BASIC_AUTH_USER'), env('BASIC_AUTH_PASS'));
                }
            }
        }
    }

    // Adapted from https://gist.github.com/rchrd2/c94eb4701da57ce9a0ad4d2b00794131
    private function requireAuth($user, $pass)
    {
        header('Cache-Control: no-cache, must-revalidate, max-age=0');

        $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));

        $is_not_authenticated = (
            !$has_supplied_credentials ||
            $_SERVER['PHP_AUTH_USER'] != $user ||
            $_SERVER['PHP_AUTH_PW'] != $pass
        );

        if ($is_not_authenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            wp_die(
                'Access denied.',
                'Authorization Required',
                array('response' => 401)
            );
        }
    }
}

$bedrock = new BasicAuth();
$bedrock->initAuth();
