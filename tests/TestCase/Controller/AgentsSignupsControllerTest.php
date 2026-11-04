<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AgentsSignupsController: public signup form posting to the backend API.
 */
class AgentsSignupsControllerTest extends TestCase
{
    use HttpClientTrait;
    use IntegrationTestTrait;

    // No database: the signup pages are public and only talk to the backend API.

    private const VALID = [
        'first_name' => 'Jane',
        'last_name' => 'Tan',
        'email' => 'jane.tan@example.com',
        'company_name' => 'Jane Travel Pte Ltd',
        'country' => 'Singapore',
        'contact_no' => '+65 6000 0000',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableRetainFlashMessages();
    }

    public function testSignupFormRenders(): void
    {
        $this->get('/signup');

        $this->assertResponseOk();
        $this->assertInstanceOf(\App\Form\SignupForm::class, $this->viewVariable('signupForm'));
    }

    public function testInvalidSignupDoesNotCallApi(): void
    {
        // With a mock registered, any other HTTP request throws (-> 500), so a 200 proves no API call.
        $this->mockClientGet('http://api.test/never-called', $this->newClientResponse(500));
        $this->enableCsrfToken();
        $this->post('/signup', ['first_name' => 'Jane', 'email' => 'not-an-email']);

        $this->assertResponseOk();
        $this->assertNoRedirect();
        $this->assertFlashMessage('The information could not be saved. Please, try again.');
        $errors = $this->viewVariable('signupForm')->getErrors();
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('company_name', $errors);
        $this->assertArrayHasKey('country', $errors);
    }

    public function testValidSignupPostsToApiAndRedirects(): void
    {
        $this->mockClientPost('http://api.test/signup', $this->newClientResponse(200, ['Content-Type: application/json'], '{"status":1000}'));
        $this->enableCsrfToken();
        $this->post('/signup', self::VALID);

        $this->assertRedirectContains('/signup/thank-you');
    }

    public function testApiRejectionShowsErrors(): void
    {
        $this->mockClientPost('http://api.test/signup', $this->newClientResponse(200, ['Content-Type: application/json'], json_encode([
            'status' => 2001,
            'response' => ['data' => ['error' => ['email' => ['unique' => 'This email is already registered.']]]],
        ])));
        $this->enableCsrfToken();
        $this->post('/signup', self::VALID);

        $this->assertResponseOk();
        $this->assertNoRedirect();
        $this->assertFlashMessage('The information could not be saved. Please, try again.');
        $this->assertSame(
            ['unique' => 'This email is already registered.'],
            $this->viewVariable('signupForm')->getErrors()['email'] ?? null
        );
    }

    public function testThankYouRenders(): void
    {
        $this->get('/signup/thank-you');

        $this->assertResponseOk();
    }
}
