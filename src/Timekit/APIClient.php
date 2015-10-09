<?php

namespace Timekit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class APIClient
{

    private $baseUrl = 'https://api.timekit.io/';

    private $version = 'v2';

    private $debugMode = false;

    private $user;

    private $logger;

    private $outputTimestampFormat;

    public function __construct($timekitApp, $debug = false)
    {
        // Enabled this if you dont have set the default timezone on your server (required for Monolog)
        // date_default_timezone_set('UTC');
        $this->logger = new Logger('Timekit');

        $debug ? $level = Logger::DEBUG : $level = Logger::EMERGENCY;
        $this->logger->pushHandler(new StreamHandler('request.log', $level));

        $this->baseUrl .= $this->version . '/';
        $this->timekitApp = $timekitApp;
        $this->client = new Client([
            'base_url' => $this->baseUrl,
        ]);
        $this->addHeader(['Timekit-App' => $timekitApp]);
    }

    /**
     * Add a header to the Timekit request
     *
     * @param array $headers
     */
    private function addHeader(Array $headers)
    {
        $default = $this->client->getDefaultOption();
        $default['headers'] = array_merge($default['headers'], $headers);
        $this->client->setDefaultOption('headers', $default['headers']);
    }

    /**
     * Set the user when making authenticated requests
     * You need the email and the api token. So you need to save this token when you make an auth request, see method: auth($email, $password)
     * @param $email
     * @param $token
     */

    public function setUser($email, $token)
    {
        $this->user = new User($email, $token);
        $this->logger->addDebug($this->user);
        $this->client->setDefaultOption('auth', [$this->user->getEmail(), $this->user->getToken()]);
    }

    /**
     * Authenticate a user using email and password. This will return a api token that you need to authenticate each request. So save this token somewhere
     *
     * @param $email
     * @param $password
     * @throws TimekitException
     */

    public function auth($email, $password)
    {
        $response = $this->makeRequest('post', 'auth', [], ['email' => $email, 'password' => $password]);
        $this->setUser($email, $response->getData()['api_token']);
    }

    /**
     * If you want to change the input or output format of timestamp you can set it here. Default is: 2004-02-12T15:19:21+00:00 (format: Y-m-d\TH:i:sP, see: http://php.net/manual/en/function.date.php)
     *
     * @param $format
     * @return $this
     */
    public function setTimestampFormat($format)
    {
        $this->addHeader([
            'Timekit-OutputTimestampFormat' => $format,
            'Timekit-InputTimestampFormat'  => $format
        ]);

        return $this;
    }

    /**
     * If you need a response in a specific timezone set it here. Default is UTC
     *
     * @param $timezone
     * @return $this
     */

    public function setTimezone($timezone)
    {
        $this->addHeader(['Timekit-Timezone' => $timezone]);

        return $this;
    }

    public function setTimestampOutputFormat($format)
    {
        $this->addHeader(['Timekit-OutputTimestampFormat' => $format]);
        $this->outputTimestampFormat = $format;
        return $this;
    }

    public function setTimestampInputFormat($format)
    {
        $this->addHeader(['Timekit-InputTimestampFormat' => $format]);
        $this->outputTimestampFormat = $format;
        return $this;
    }

    /**
     * Internal method for make the call to Timekit API
     *
     * @param $method
     * @param $url
     * @param array $params
     * @param array $body
     * @param bool $returnJson
     * @return TimekitResponse
     * @throws TimekitException
     */

    private function makeRequest($method, $url, $params = [], $body = [], $returnJson = true)
    {
        if (!empty($params)) {
            $params = http_build_query($params);
            $url .= '?' . $params;
        }
        $completeUrl = $this->baseUrl . $url;

        if ($this->debugMode) {
            $this->logger->addDebug(sprintf('Calling [%s] %s with %s', strtoupper($method), $completeUrl, print_r($body, true)));
        }

        $body = json_encode($body);
        try {
            /** @var Response $response */
            $response = $this->client->$method($url, ['body' => $body]);
            $code = $response->getStatusCode();
            if ($returnJson) {
                $response = $response->json();
            } else {
                $response = $response->getBody()->getContents();
            }
        } catch (ClientException $exception) {
            $response = $exception->getResponse()->getBody()->getContents();
            $code = $exception->getCode();
            $this->logger->addError(sprintf('Current user: %s', $this->user));
            $this->logger->addError(sprintf("[%s] %s", $code, print_r($response, true)));
            throw new TimekitException($response, $code, $exception);
        }

        if ($this->debugMode) {
            $this->logger->addDebug(sprintf("[%s] Timekit returned: %s", $code, print_r($response, true)) . "\n");
        }

        return new TimekitResponse($response, $code);
    }

    /**
     * Internal method to help with appending id's to urls
     * @param $id
     * @param $resource
     * @return string
     */

    private function appendIfNotNull($id, $resource)
    {
        $url = $id !== null ? $resource . '/' . $id : $resource;

        return $url;
    }

    /**
     * Look for mutual availability for multiple users
     * http://developers.timekit.io/v2/docs/findtime
     *
     * @param array $emails
     * @param array $filters
     * @param string $future
     * @param string $length
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function findtime(Array $emails, $filters = null, $future = '2 days', $length = '30 minutes')
    {
        $json = [
            'emails'  => $emails,
            'future'  => $future,
            'length'  => $length,
            'filters' => $filters
        ];

        $response = $this->makeRequest('post', 'findtime', [], $json);

        return $response;
    }

    /**
     * Get calendars for a google account (only works for a user with at google accounts)
     * http://developers.timekit.io/v2/docs/accountsgooglecalendars
     *
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function accountsGoogleCalendars()
    {
        return $this->makeRequest('get', 'accounts/google/calendars');
    }

    /**
     * Get all accounts for a user
     * http://developers.timekit.io/v2/docs/accounts
     *
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function getAccounts()
    {
        return $this->makeRequest('get', 'accounts');
    }

    /**
     * Redirect to a google signup page
     * http://developers.timekit.io/v2/docs/accountsgooglesignup
     *
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function accountsGoogleSignup()
    {
        return $this->makeRequest('get', 'accounts/google/signup', [], [], false);
    }

    /**
     * Sync a users accounts
     * http://developers.timekit.io/v2/docs/accountssync
     *
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function accountsSync()
    {
        return $this->makeRequest('get', 'accounts/sync');
    }

    /**
     * Get all or a single calendar(s) for a user
     * http://developers.timekit.io/v2/docs/calendars
     *
     * @param null $id
     * @param array $params
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function getCalendars($id = null, $params = [])
    {
        $url = $this->appendIfNotNull($id, 'calendars');

        return $this->makeRequest('get', $url, $params);
    }

    /**
     * Get all contacts for a user
     * http://developers.timekit.io/v2/docs/contacts
     *
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function getContacts()
    {
        return $this->makeRequest('get', 'contacts');
    }

    /**
     * Get all events between start and end timestamps. Default format is: 2004-02-12T15:19:21+00:00
     * http://developers.timekit.io/v2/docs/events
     *
     * @param $start
     * @param $end
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function getEvents($start, $end)
    {
        $params = ['start' => $start, 'end' => $end];

        return $this->makeRequest('get', 'events', $params);
    }

    /**
     * Get the anonymized events for a user between start & end
     * http://developers.timekit.io/v2/docs/eventsavailability
     *
     * @param $start
     * @param $end
     * @param $email
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function eventsAvailability($start, $end, $email)
    {
        $params = ['start' => $start, 'end' => $end, 'email' => $email];

        return $this->makeRequest('get', 'events/availability', $params);
    }

    public function createEvent($start, $end, $what, $where, $participants, $invite = false, $calendar_id)
    {
        $params = compact('start', 'end', 'where', 'what', 'participants', 'invite', 'calendar_id');

        return $this->makeRequest('post', 'events', [], $params);
    }

    /**
     * Create a meeting
     * http://developers.timekit.io/v2/docs/meetings
     *
     * @param $data
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function createMeeting($data)
    {
        return $this->makeRequest('post', 'meetings', [], $data);
    }

    /**
     * Get all or a single meeting(s) for a user
     * http://developers.timekit.io/v2/docs/meetings-1 - all meetings
     * http://developers.timekit.io/v2/docs/meetingstoken - single meeting
     *
     * @param null $token
     * @param array $params
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function getMeetings($token = null, $params = [])
    {
        $url = $this->appendIfNotNull($token, 'meetings');

        return $this->makeRequest('get', $url, $params);
    }

    /**
     * Set the availability for a meeting as a user
     * http://developers.timekit.io/v2/docs/meetingsavailability
     *
     * @param $suggestion_id
     * @param $availability
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function setMeetingAvailability($suggestion_id, $availability)
    {
        $params = ['suggestion_id' => $suggestion_id, 'available' => $availability];

        return $this->makeRequest('post', 'meetings/availability', [], $params);
    }

    /**
     * Book a meeting by selecting a suggestion (a set of start & end times)
     * http://developers.timekit.io/v2/docs/meetingsbook
     *
     * @param $suggestion_id
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function bookMeeting($suggestion_id)
    {
        $body = ['suggestion_id' => $suggestion_id];

        return $this->makeRequest('post', 'meetings/book', [], $body);
    }

    /**
     * Update a meeting
     * http://developers.timekit.io/v2/docs/meetingstoken-1
     *
     * @param $token
     * @param $body
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function editMeeting($token, $body)
    {
        $url = $this->appendIfNotNull($token, 'meetings');

        return $this->makeRequest('put', $url, [], $body);
    }

    /**
     * Get info of the current user
     * http://developers.timekit.io/v2/docs/usersme
     *
     * @param array $params
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function me($params = [])
    {
        return $this->makeRequest('get', 'users/me', $params);
    }

    /**
     * Create a new user on timekit
     * http://developers.timekit.io/v2/docs/users
     *
     * @param $body
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function createUser($body)
    {
        return $this->makeRequest('post', 'users', [], $body);
    }

    /**
     * Update the current user
     * http://developers.timekit.io/v2/docs/usersme-1
     *
     * @param $body
     * @return TimekitResponse
     * @throws TimekitException
     */
    public function updateUser($body)
    {
        return $this->makeRequest('put', 'users/me', [], $body);
    }

    /**
     * Get user properties for the current user
     * http://developers.timekit.io/v2/docs/properties
     *
     * @param null $id
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function getUserProperties($id = null)
    {
        $url = $this->appendIfNotNull($id, 'properties');

        return $this->makeRequest('get', $url);
    }

    /**
     * Set a user property for the current user
     * http://developers.timekit.io/v2/docs/properties-1
     *
     * @param $key
     * @param $value
     * @return TimekitResponse
     * @throws TimekitException
     */

    public function setUserProperty($key, $value)
    {
        $body = ['key' => $key, 'value' => $value];

        return $this->makeRequest('put', 'properties', [], $body);
    }

    /**
     * @return String
     */
    public function getOutputTimestampFormat()
    {
        return $this->outputTimestampFormat;
    }

}