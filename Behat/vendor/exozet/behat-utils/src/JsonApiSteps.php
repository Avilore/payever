<?php

namespace Exozet\Behat\Utils\Base;

use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\PyStringNode;

trait JsonApiSteps {

    /**
     * @Then /^Attribut "([^"]*)" hat den Wert "([^"]*)"$/
     * @Then /^the attribute "([^"]*)" has the value "([^"]*)"$/
     */
    public function theAttributeKeyHasTheValue($propertyName, $expectedValue) {
        $currentValue = json_decode($this->getSession()->getDriver()->getContent(), true);
        $expectedValues = array();
        $expectedValues[$propertyName] = $expectedValue;

        $this->assertDeepArray($currentValue, $expectedValues, array($propertyName));
    }

    /**
     * @Then /^das Objekt "([^"]*)" beinhaltet:$/
     * @Then /^the object "([^"]*)" contains:$/
     */
    public function objectKeyContains($propertyName, TableNode $valueTable = null, PyStringNode $pyStringNode = null){

        $currentValue = $this->getJsonApiValue($propertyName);
        $expectedValues = null;
        if ($pyStringNode != null) {
            $expectedValues = json_decode($pyStringNode->getRaw(), true);

            if ($expectedValues === null) {
                throw new \Exception('Invalid JSON at: ' . $pyStringNode->getRaw());
            }
        }

        if ($valueTable != null) {
            foreach ($valueTable as $valueHash) {
                if (isset($valueHash['Attribut'])) {
                    $expectedValues[$valueHash['Attribut']] = $valueHash['Wert'];
                }
                if (isset($valueHash['attribute'])) {
                    $expectedValues[$valueHash['attribute']] = $valueHash['value'];
                }
            }
        }
        $this->assertDeepArray($currentValue, $expectedValues, array($propertyName));
    }

    /**
     * @Then /^das JSON beinhaltet:$/
     * @Then /^the JSON contains:$/
     */
    public function jsonContains(TableNode $valueTable = null, PyStringNode $pyStringNode = null)
    {
        $currentValue = json_decode($this->getSession()->getDriver()->getContent(), true);
        $expectedValues = null;

        if ($pyStringNode != null) {
            $expectedValues = json_decode($pyStringNode->getRaw(), true);

            if ($expectedValues === null) {
                throw new \Exception('Invalid JSON at: ' . $pyStringNode->getRaw());
            }
        }

        if ($valueTable != null) {
            foreach ($valueTable as $valueHash) {
                if (isset($valueHash['Attribut'])) {
                    $expectedValues[$valueHash['Attribut']] = $valueHash['Wert'];
                }
                if (isset($valueHash['attribute'])) {
                    $expectedValues[$valueHash['attribute']] = $valueHash['value'];
                }
            }
        }

        $this->assertDeepArray($currentValue, $expectedValues, array());
    }

    /**
     * @Given /^(?:|ich) folgende Daten als JSON per "(?P<method>[^"]+)" nach "(?P<page>[^"]+)" schicke:$/
     * @Given /^(?:|I) send the following JSON data with "(?P<method>[^"]+)" to "(?P<page>[^"]+)":$/
     */
    public function sendTheFollowingJsonDataWithMethodToPage($method, $page, TableNode $valueTable)
    {
        $values = array();
        foreach ($valueTable as $valueHash) {
            if (isset($valueHash['Attribut'])) {
                $values[$valueHash['Attribut']] = $valueHash['Wert'];
            }
            if (isset($valueHash['attribute'])) {
                $values[$valueHash['attribute']] = $valueHash['value'];
            }
        }

        $baseUrl = $this->getMinkParameter('base_url');

        $driver = $this->getSession()->getDriver();
        /** @var \Behat\Mink\Driver\Goutte\Client $client */
        $client = $driver->getClient();

        $uri = $baseUrl . $page;
        $query = array();
        $files = array();

        /*
         * FIXME: BrowserKitDriver says serverParameters is PRIVATE for whatever reasons. Thus we have no way but
         * reflection to access this value.
         */
        $class = new \ReflectionClass("Behat\Mink\Driver\BrowserKitDriver");
        $property = $class->getProperty("serverParameters");
        $property->setAccessible(true);
        $server = $property->getValue($driver);
        $server['CONTENT_TYPE'] = "application/json";

        $content = json_encode($values);
        $changeHistory = true;

        try {
            $client->request(strtoupper($method), $uri, $query, $files, $server, $content, $changeHistory);
        } catch (\Exception $e) {
            /* swallow the exception, since we want to keep status codes and the response */
        }
    }

    /**
     * @Given /^(?:|ich) folgende Daten als Formular per "(?P<method>[^"]+)" nach "(?P<page>[^"]+)" schicke:$/
     * @Given /^(?:|I) send the following form data with "(?P<method>[^"]+)" to "(?P<page>[^"]+)":$/
     */
    public function sendTheFollowingFormDataWithMethodToPage($method, $page, TableNode $valueTable)
    {
        $values = array();
        foreach ($valueTable as $valueHash) {
            if (isset($valueHash['Attribut'])) {
                $values[$valueHash['Attribut']] = $valueHash['Wert'];
            }
            if (isset($valueHash['attribute'])) {
                $values[$valueHash['attribute']] = $valueHash['value'];
            }
        }

        $baseUrl = $this->getMinkParameter('base_url');

        $driver = $this->getSession()->getDriver();
        /** @var \Behat\Mink\Driver\Goutte\Client $client */
        $client = $driver->getClient();

        $uri = $baseUrl . $page;
        $query = array();
        $files = array();

        /*
         * FIXME: BrowserKitDriver says serverParameters is PRIVATE for whatever reasons. Thus we have no way but
         * reflection to access this value.
         */
        $class = new \ReflectionClass("Behat\Mink\Driver\BrowserKitDriver");
        $property = $class->getProperty("serverParameters");
        $property->setAccessible(true);
        $server = $property->getValue($driver);
        $server['CONTENT_TYPE'] = "application/x-www-form-urlencoded";

        $content = http_build_query($values);
        $changeHistory = true;

        try {
            $client->request(strtoupper($method), $uri, $query, $files, $server, $content, $changeHistory);
        } catch (\Exception $e) {
            /* swallow the exception, since we want to keep status codes and the response */
        }
    }

    /**
     * FIXME: should be part of an utility class
     * @param $propertyName
     * @return mixed
     */
    protected function getJsonApiValue($propertyName) {
        $value = @json_decode($this->getSession()->getDriver()->getContent(), true);
        if ($value === null) {
            throw new \InvalidArgumentException("The response is not valid JSON!");
        }
        if (!array_key_exists($propertyName, $value)) {
            throw new \InvalidArgumentException("The response does not contain a '" . $propertyName . "' key.");
        }
        return $value[$propertyName];
    }

    /**
     * FIXME: should be part of an utility class
     * @param $actual
     * @param $expected
     * @param $path
     * @throws \Exception
     */
    protected function assertDeepArray($actual, $expected, $path) {
        if (is_array($expected)) {
            foreach ($expected as $key => $value) {
                $subPath = $path;
                $subPath[] = $key;
                if (!array_key_exists($key, $actual)) {
                    throw new \Exception("At " . implode('.', $subPath) . " we expect any value, but the key is not set. Expected Key: '" . $key . "'");
                }


                $this->assertDeepArray($actual[$key], $value, $subPath);
            }
        } else {

            $actualDate = null;
            /* handle 2015-12-17T10:46:00+01:00 */
            if (!is_array($actual)) {
                $actualDate = \DateTime::createFromFormat("Y-m-d\\TH:i:sO", $actual);
                if (!$actualDate) {
                    /* handle 2015-12-17T10:46:00.000+01:00 */
                    $actualDate = \DateTime::createFromFormat("Y-m-d\\TH:i:s.uO", $actual);
                }
            }

            $expectedDate = null;
            /* handle 2015-12-17T10:46:00+01:00 */
            if (!is_array($expected)) {
                $expectedDate = \DateTime::createFromFormat("Y-m-d\\TH:i:sO", $expected);
                if (!$expectedDate) {
                    /* handle 2015-12-17T10:46:00.000+01:00 */
                    $expectedDate = \DateTime::createFromFormat("Y-m-d\\TH:i:s.uO", $expected);
                }
            }

            if ($expected !== '*' && $actualDate && $expectedDate) {
                /* handle two dates */
                $actualDate->setTimezone(new \DateTimeZone("UTC"));
                $expectedDate->setTimezone(new \DateTimeZone("UTC"));

                if ($actualDate->format(\DateTime::RFC3339) != $expectedDate->format(\DateTime::RFC3339)) {
                    throw new \Exception("At " . implode('.', $path) . " we have the date " . var_export($actual, true) . ", but we expect " . var_export($expected, true) . "");
                }
            } elseif ($actual !== $expected && $expected !== '*') {
                /* everything except dates */
                throw new \Exception("At " . implode('.', $path) . " we have the value " . var_export($actual, true) . ", but we expect " . var_export($expected, true) . "");
            }
        }
    }
}
