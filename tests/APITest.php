<?php


use Timekit\APIClient;

class APITest extends PHPUnit_Framework_TestCase
{

    /**
     * @var APIClient
     */
    private $api;
    private $docBrown;
    private $timebirdCPH;

    public function setUp()
    {
        $this->api = new APIClient(getenv('APP_ID'), true);
        $this->docBrown = [
            'email' => 'doc.brown@timekit.io',
            'password' => getenv('DOCBROWN_PASSWORD'),
            'token' => getenv('DOCBROWN_TOKEN')
        ];

        $this->timebirdCPH = [
            'email' => 'timebirdcph@gmail.com',
            'token' => getenv('TIMEBIRDCPH_TOKEN')
        ];
    }

    /**
     * @test
     */
    public function can_set_user_via_password()
    {
        $this->api->auth($this->docBrown['email'], $this->docBrown['password']);
        $response = $this->api->findtime(['marty.mcfly@timekit.io']);

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_set_user_via_token()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->findtime(['marty.mcfly@timekit.io']);

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_users_me()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->me(['include' => 'calendars']);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Dr. Emmett', $response->getData()['first_name']);
        $this->assertNotNull($response->getData()['calendars']);
    }

    /**
     * @test
     */
    public function can_call_accounts()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->getAccounts();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_accounts_google_signup()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->accountsGoogleSignup();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * test
     */
    public function can_call_accounts_google_calendars()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->accountsGoogleCalendars();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * test
     */
    public function can_call_accounts_sync()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->accountsSync();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_calendars()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->getCalendars();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_calendars_with_id()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->getCalendars(32470);

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function call_call_contacts()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->getContacts();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_events()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->getEvents(0, 100000000000);

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_call_events_availability()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $response = $this->api->eventsAvailability(0, 100000000000, 'marty.mcfly@timekit.io');

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_post_meetings()
    {
        $this->api->setUser($this->docBrown['email'], $this->docBrown['token']);
        $data = [
            'what'        => 'Random meeting ' . uniqid(),
            'where'       => 'Nowhere',
            'suggestions' =>
                [
                    [
                        'start' => '2015-05-30T12:00:00.000Z',
                        'end'   => '2015-05-30T13:00:00.000Z',
                    ],
                    [
                        'start' => '2015-05-29T12:00:00.000Z',
                        'end'   => '2015-05-29T13:00:00.000Z',
                    ]
                ]
        ];
        $response = $this->api->createMeeting($data);

        $this->assertEquals(201, $response->getCode());
    }

    /**
     * @test
     */
    public function can_set_timeformat()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $this->api->setTimestampFormat('d-m-Y H:i:sa');
        $response = $this->api->getEvents(0, 100000000000000000);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('14-10-2014 14:30:00pm', $response->getData()[0]['start']);
    }

    /**
     * @test
     */
    public function can_get_meetings()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->getMeetings();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_get_meetings_by_token()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->getMeetings('wusZZllTbrTC', ['suggestions.answers.users']);

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function can_get_meetings_and_include()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->getMeetings(null, ['include' => 'suggestions.answers.users']);

        $this->assertEquals(200, $response->getCode());
        $this->assertArrayHasKey('suggestions', $response->getData()[0]);
    }

    /**
     * @test
     */
    public function can_set_availability()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->setMeetingAvailability(1, true);

        $this->assertEquals(204, $response->getCode());
    }

    /**
     * Disabled for now! Need some mocking
     */
    public function can_book_meeting()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->bookMeeting(1);

        $this->assertEquals(204, $response->getCode());
    }

    /**
     * @test
     */
    public function can_put_meeting()
    {
        $what = 'new what ' . uniqid();
        $where = 'new where ' . uniqid();

        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->editMeeting('wusZZllTbrTC', ['what' => $what, 'where' => $where]);

        $this->assertEquals(204, $response->getCode());
    }

    /**
     * @test
     */
    public function create_user()
    {
        $response = $this->api->createUser(['first_name' => 'Peter', 'last_name' => 'Hansen', 'email' => uniqid() . '@timekit.io', 'timezone' => 'Europe/Copenhagen']);

        $this->assertEquals(201, $response->getCode());
    }

    /**
     * @test
     */
    public function get_user_properties()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->getUserProperties();

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function get_user_property()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->getUserProperties('timebirdcphgmailcom');

        $this->assertEquals(200, $response->getCode());
    }

    /**
     * @test
     */
    public function set_user_property()
    {
        $this->api->setUser($this->timebirdCPH['email'], $this->timebirdCPH['token']);
        $response = $this->api->setUserProperty('timebirdcphgmailcom', true);

        $this->assertEquals(204, $response->getCode());
    }

}