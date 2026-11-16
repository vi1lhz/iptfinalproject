<?php

namespace Tests;

use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Models\User;
use Core\Auth;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Core\Session;

class MiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION = [];
        Auth::reset();
        $this->freshDatabase();
    }

    private function next(): callable
    {
        return fn () => new Response('passed', 200);
    }

    private function post(array $body): Request
    {
        return new Request([], $body, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/x']);
    }

    public function testCsrfRejectsMissingToken(): void
    {
        $this->assertThrows(HttpException::class, fn () => (new CsrfMiddleware())->handle($this->post([]), $this->next()));
    }

    public function testCsrfRejectsWrongToken(): void
    {
        Session::csrfToken();
        $this->assertThrows(HttpException::class, fn () => (new CsrfMiddleware())->handle($this->post(['_csrf' => 'forged']), $this->next()));
    }

    public function testCsrfAcceptsValidToken(): void
    {
        $response = (new CsrfMiddleware())->handle($this->post(['_csrf' => Session::csrfToken()]), $this->next());
        $this->assertSame('passed', $response->content());
    }

    public function testCsrfIgnoresGetRequests(): void
    {
        $get = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/x']);
        $this->assertSame('passed', (new CsrfMiddleware())->handle($get, $this->next())->content());
    }

    public function testAuthRedirectsGuestsToLogin(): void
    {
        $response = (new AuthMiddleware())->handle(new Request([], [], ['REQUEST_URI' => '/cart']), $this->next());
        $this->assertSame(302, $response->status());
        $this->assertContains('/login', $response->headers()['Location']);
        $this->assertSame('/cart', Session::get('intended'));
    }

    public function testAuthLetsLoggedInUsersThrough(): void
    {
        $id = User::register(['username' => 'ana', 'email' => 'ana@mail.test', 'password' => 'secret123']);
        Auth::login(User::find($id));
        $this->assertSame('passed', (new AuthMiddleware())->handle(new Request([], [], ['REQUEST_URI' => '/cart']), $this->next())->content());
    }

    public function testAdminBlocksNormalUsers(): void
    {
        $id = User::register(['username' => 'ana', 'email' => 'ana@mail.test', 'password' => 'secret123']);
        Auth::login(User::find($id));
        $e = $this->assertThrows(HttpException::class, fn () => (new AdminMiddleware())->handle(new Request(), $this->next()));
        $this->assertSame(403, $e->status());
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        User::register(['username' => 'ana', 'email' => 'ana@mail.test', 'password' => 'secret123']);
        $this->assertFalse(Auth::attempt('ana@mail.test', 'wrong'));
        $this->assertTrue(Auth::attempt('ana@mail.test', 'secret123'));
    }
}
