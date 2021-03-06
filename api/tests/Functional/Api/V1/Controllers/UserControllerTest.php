<?php

namespace App\Functional\Api\V1\Controllers;

use Hash;
use App\User;
use App\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Functional\Api\V1\Controllers\LoginTrait;

class UserControllerTest extends TestCase
{
    use DatabaseMigrations, LoginTrait;

    public function setUp()
    {
        parent::setUp();

        $user = new User([
            'name' => 'Test',
            'email' => 'test@email.com',
            'password' => '123456',
            'is_activated' => true
        ]);

        $user->save();

        $user = new User([
            'name' => 'Admin',
            'email' => 'admin@email.com',
            'password' => '123456',
            'is_superadmin' => true,
            'is_activated' => true
        ]);

        $user->save();

        $user = new User([
            'name' => 'Test',
            'email' => 'test2@email.com',
            'password' => '123456',
            'is_activated' => true,
        ]);

        $user->save();
    }

    public function testMe()
    {
        $login = $this->login('test@email.com', '123456');
        $headers = ['Authorization' => 'Bearer '.$login['token']];
        $response = $this->withHeaders($headers)->json('GET', '/api/auth/me');
        $response
            ->assertJsonStructure(['data' => ['id', 'avatar', 'name']])
            ->assertStatus(200);
    }

    public function testShow()
    {
        $response = $this->withHeaders([])->json('GET', '/api/users/1');
        $response
            ->assertJsonStructure(['data' => ['id', 'avatar', 'name']])
            ->assertStatus(200);
    }

    public function testShow404()
    {
        $response = $this->withHeaders([])->json('GET', '/api/users/3515295123952512');
        $response
            ->assertJsonStructure(['error'])
            ->assertStatus(404);
    }

    public function testIndex()
    {
        $response = $this->withHeaders([])->json('GET', '/api/users');
        $response
            ->assertJsonStructure(['data', 'links'])
            ->assertStatus(200);
    }

    public function testUpdateProfileCValidationError()
    {
        $login = $this->login('test@email.com', '123456');
        $headers = [
            'Authorization' => 'Bearer ' . $login['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];
        $data = ['_method' => 'PUT'];
        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'],
                $data
            );
        
        $response->assertStatus(422);
    }

    public function testUpdateProfile()
    {
        $login = $this->login('test@email.com', '123456');
        $headers = [
            'Authorization' => 'Bearer ' . $login['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];
        $name = str_random();
        $avatar = 'https://s3.amazonaws.com/uifaces/faces/twitter/abinav_t/128.jpg';
        $data = ['_method' => 'PUT', 'name' => $name, 'avatar' => $avatar];
        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'],
                $data
            );
        
        $response
            ->assertJson(['data' => [
                'id' => $login['user']['id'],
                'name' => $name,
                'avatar' => $avatar
            ]])
            ->assertStatus(200);
    }

    public function testUpdateProfileBadUser()
    {
        $login = $this->login('test@email.com', '123456');
        $login2 = $this->login('test2@email.com', '123456');

        $headers = [
            'Authorization' => 'Bearer ' . $login2['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];

        $name = str_random();
        $avatar = 'https://s3.amazonaws.com/uifaces/faces/twitter/abinav_t/128.jpg';
        $data = ['_method' => 'PUT', 'name' => $name, 'avatar' => $avatar];
        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'],
                $data
            );
        
        $response
            ->assertJsonStructure(['error', 'status'])
            ->assertStatus(401);
    }

    public function testUpdatePassword()
    {
        $login = $this->login('test@email.com', '123456');

        $headers = [
            'Authorization' => 'Bearer ' . $login['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];
        
        $avatar = 'https://s3.amazonaws.com/uifaces/faces/twitter/abinav_t/128.jpg';
        $data = [
            '_method' => 'PUT',
            'password' => 123456,
            'new_password' => 654321,
            'new_password_confirmation' => 654321
        ];

        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'].'/password',
                $data
            );
        
        $response
            ->assertJsonStructure(['data' => ['id', 'name', 'avatar']])
            ->assertStatus(200);
    }

    public function testUpdateAccountSuccess()
    {
        $login = $this->login('test@email.com', '123456');

        $headers = [
            'Authorization' => 'Bearer ' . $login['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];

        $data = [
            'email' => str_random(10).'@gmail.com',
        ];

        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'].'/account',
                $data
            );
        
        $response->assertStatus(200);
    }

    public function testUpdateAccountFailure()
    {
        $login = $this->login('test@email.com', '123456');

        $headers = [
            'Authorization' => 'Bearer ' . $login['token'],
            'Content-Type' => 'x-www-form-urlencoded',
        ];

        $data = [
            'email' => 'test@email.com',
        ];

        $response = $this
            ->withHeaders($headers)
            ->json(
                'PUT',
                '/api/users/'.$login['user']['id'].'/account',
                $data
            );
        
        $response->assertStatus(422);
    }
}
