<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class JiraApiClient
{
    private $jiraApiUrl;
    private $authEmail;
    private $authToken;

    protected $requestMethod = 'GET';
    protected $methodName;
    protected $methodParams = [];
    protected $params = [];
    protected $postFields = [];

    private $jiraAuthUser = [];

    public function __construct(
        private readonly RequestStack  $requestStack,
        private readonly ContainerBagInterface $parameters
    )
    {
        $this->authEmail = $parameters->get('app.jira_auth_email');
        $this->authToken = $parameters->get('app.jira_auth_token');
        $this->jiraApiUrl = $parameters->get('app.jira_url') . 'rest/api/3/';

    }

    public function execute()
    {
        $this->prepareMethodParams();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->jiraApiUrl . $this->methodName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode("$this->authEmail:$this->authToken"),
            "Accept: application/json",
            "Content-Type: application/json"
        ]);

            if ('POST' == $this->requestMethod) {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if ('PATCH' == $this->requestMethod) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            }
            if ('PUT' == $this->requestMethod) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            }
            if ('DELETE' == $this->requestMethod) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
            if (in_array($this->requestMethod, ['POST', 'PATCH', 'PUT'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->getPostFields()));
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    public function jiraAuthUser(): void
    {
        $this->requestStack->getSession()->clear();
        if ($this->requestStack->getSession()->has('jiraAuthUser') && !empty($this->requestStack->getSession()->get('jiraAuthUser'))) {
            $this->jiraAuthUser = $this->requestStack->getSession()->get('jiraAuthUser');
        } else {
            $this->methodName = 'myself';
            $this->jiraAuthUser = $this->execute();
            if (!$this->jiraAuthUser) {
                throw new Exception('Failed to retrieve Jira auth user data.');
            }
            $this->requestStack->getSession()->set('jiraAuthUser', $this->jiraAuthUser);
        }
    }

    public function prepareMethodParams(): void
    {
        foreach ($this->methodParams as $key => $value) {
            $this->methodName = str_replace($key, $value, $this->methodName);
        }
    }

    public function getSessionUser(): ?array
    {
        return $this->requestStack->getSession()->get('jiraAuthUser', null);
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getContainerBag(): ContainerBagInterface
    {
        return $this->parameters;
    }

    /**
     * Get the value of methodParams
     */
    public function getMethodParams()
    {
        return $this->methodParams;
    }

    /**
     * Get the value of params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the value of postFields
     */
    public function getPostFields()
    {
        return $this->postFields;
    }


    /**
     * Set the value of methodParams
     */
    public function setMethodName(string $methodName): self
    {
        $this->methodName = $methodName;

        return $this;
    }

    /**
     * Set the value of methodParams
     */
    public function setMethodParams($methodParams): self
    {
        $this->methodParams = $methodParams;

        return $this;
    }


    /**
     * Set the value of params
     */
    public function setParams($params): self
    {
        $this->params = $params;

        return $this;
    }


    /**
     * Set the value of postFields
     */
    public function setPostFields($postFields): self
    {
        $this->postFields = $postFields;

        return $this;
    }

    /**
     * Set the value of requestMethod
     */
    public function setRequestMethod($requestMethod): self
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }
}
