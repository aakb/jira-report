<?php

namespace Itk\JiraBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAware;

class JiraService extends ContainerAware {
  protected $container;

  /**
   * Constructor.
   */
  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function get($path) {
    $stack = HandlerStack::create();
    $token = $this->container->get('security.token_storage')->getToken();

    $middleware = new Oauth1([
      'consumer_key'    => $this->container->getParameter('jira_oauth_customer_key'),
      'private_key_file' => $this->container->getParameter('jira_oauth_pem_path'),
      'private_key_passphrase' => '',
      'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
      'token'           => $token->getAccessToken(),
      'token_secret'    => $token->getTokenSecret(),
    ]);
    $stack->push($middleware);

    $client = new Client([
      'base_uri' => $this->container->getParameter('jira_url'),
      'handler' => $stack
    ]);

    // Set the "auth" request option to "oauth" to sign using oauth
    try {
      $response = $client->get($path, ['auth' => 'oauth']);

      if ($body = $response->getBody()) {
        return json_decode($body);
      }
    } catch (RequestException $e) {
        throw $e;
    }
  }
}
