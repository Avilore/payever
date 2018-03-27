<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;
use \Exozet\Behat\Utils\Base\JsonApiSteps;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    use \Exozet\Behat\Utils\Base\JsonApiSteps;

    private $parameters;
    private $sessions = array();
    private $response;
    private $requestHeaders;
    private $lastResponse;
    private $oauthToken;
    private $redirectUrl;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @BeforeScenario
     */
    public function before($event)
    {
        if (empty($this->parameters['instances'])) {
            return; //Local Setup doesn't need to create sessions - use default
        }
        foreach ($this->parameters['instances'] as $browserName => $capabilities) {
            $driver = new Selenium2Driver($browserName, $capabilities, 'http://' . $this->parameters['key'] . ':' . $this->parameters['secret'] . '@' . $this->parameters['wd_host']);
            $session = new Session($driver);
            $this->getMink()->registerSession($browserName, $session);
            $this->sessions[] = $browserName;
        }
        $this->getMink()->setDefaultSessionName(key($this->parameters['instances']));
    }

    /**
     * @AfterScenario
     */
    public function tearDown(ScenarioEvent $event)
    {
        if (empty($this->parameters['instances'])) {
            return; // Local Setup doesn't need to transmit data to testingbot
        }
        $url = $this->getSession()->getCurrentUrl();
        $parts = explode("/", $url);
        $sessionID = $parts[sizeof($parts) - 1];
        $data = array(
            'session_id' => $sessionID,
            'client_key' => $this->parameters['key'],
            'client_secret' => $this->parameters['secret'],
            'success' => ($event->getResult() === 0) ? true : false,
            'name' => $event->getScenario()->getTitle()
        );
        $this->apiCall($data);
    }

    public function jqueryWait($duration = 1000)
    {
        $this->getSession()->wait($duration, '(0 === jQuery.active)');
    }

    protected function apiCall(array $postData)
    {
        $data = http_build_query($postData);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "http://testingbot.com/hq");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        curl_close($curl);
    }

    /**
     * @Given /^I have the payload:$/
     */
    public function iHaveThePayload(PyStringNode $requestPayload)
    {
        $this->requestPayload = $requestPayload;
    }

    /**
     * @When /^I request "(GET|PUT|POST|DELETE|PATCH) ([^"]*)"$/
     */
    public function iRequest($httpMethod, $resource)
    {
        $method = strtoupper($httpMethod);
        // Construct request
        $this->lastRequest = new Request($method, $resource, $this->requestHeaders, $this->requestPayload);
        $options = array();
        if ($this->authUser) {
            $options = ['auth' => [$this->authUser, $this->authPassword]];
        }
        try {
            // Send request
            $this->lastResponse = $this->client->send($this->lastRequest, $options);
        } catch (\BadResponseException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw $e;
            }
            $this->lastResponse = $e->getResponse();
            throw new \Exception('Bad response.');
        }
    }

    /**
     * @Then /^the response status code should be (?P<code>\d+)$/
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        $response = $this->getLastResponse();
        assertEquals($statusCode,
            $response->getStatusCode(),
            sprintf('Expected status code "%s" does not match observed status code "%s"', $statusCode, $response->getStatusCode()));
    }

    /**
     * @Given /^I get oauth token in response property "([^"]*)"$/
     */
    public function iGetOauthTokenInResponseProperty($property)
    {
        $payload = $this->getScopePayload();
        $oauthToken = $this->arrayGet($payload, $property);
    }



    protected function getResponse()
    {
        if (!$this->response) {
            throw new Exception("You must first make a request to check a response.");
        }
        return $this->response;
    }

    /**
     * @Given /^I set the "([^"]*)" header to be "([^"]*)"$/
     */
    public function iSetTheHeaderToBe($headerName, $value)
    {
        $this->requestHeaders[$headerName] = $value;
    }

    /**
     * @Given /^I set the "([^"]*)" header with oauth_token$/
     */
    public function iSetTheHeaderWithOauth_token($headerName)
    {
        $this->requestHeaders[$headerName] = $this->oauthToken;
    }

    /**
     * @Given /^I get redirect url in response property "([^"]*)"$/
     */
    public function iGetRedirectUrlInResponseProperty($property)
    {
        $payload = $this->getScopePayload();
        $redirectUrl = $this->arrayGet($payload, $property);
    }

}