<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    // Test successful registration
    public function testRegisterUserWithValidData()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(201); // Created
        $response->assertJsonStructure(['ok', 'message', 'data' => ['id', 'name', 'email']]);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']); // Check if user is in the DB
    }

    // Test registration with invalid data
    public function testRegisterUserWithInvalidData()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email', // Invalid email format
            'password' => 'short', // Password too short
            'password_confirmation' => 'short',
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(400); // Bad request
        $response->assertJsonStructure(['ok', 'message', 'data']);
        $this->assertArrayHasKey('email', $response->json()['data']); // Ensure email validation error is present
        $this->assertArrayHasKey('password', $response->json()['data']); // Ensure password validation error is present
    }

    // Test registration with duplicate email
    public function testRegisterUserWithDuplicateEmail()
    {
        // Create an existing user
        User::factory()->create(['email' => 'duplicate@example.com']);

        $data = [
            'name' => 'John Doe',
            'email' => 'duplicate@example.com', // Same email as the existing user
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(400); // Bad request due to duplicate email
        $this->assertArrayHasKey('email', $response->json()['data']); // Ensure email error is present
    }

    // Test login with valid credentials
    public function testLoginWithValidCredentials()
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $data = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(200); // OK
        $response->assertJsonStructure(['ok', 'message', 'data' => ['id', 'name', 'email']]);
    }

    // Test login with invalid credentials
    public function testLoginWithInvalidCredentials()
    {
        $data = [
            'email' => 'wrongemail@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(404); // Not found
        $response->assertJson(['ok' => false, 'message' => 'User not found']);
    }

    // Test search with valid data
    public function testSearchUsersWithValidData()
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $data = ['search' => 'John'];

        $response = $this->postJson('/api/search', $data);

        $response->assertStatus(200); // OK
        $response->assertJsonStructure(['ok', 'message', 'data']);
        $this->assertCount(1, $response->json()['data']); // Only John Doe should be returned
    }

    // Test search with invalid data
    public function testSearchWithInvalidData()
    {
        $data = [];

        $response = $this->postJson('/api/search', $data);

        $response->assertStatus(400); // Validation error
        $response->assertJsonStructure(['ok', 'message', 'data']);
        $this->assertArrayHasKey('search', $response->json()['data']); // Ensure search validation error is present
    }
}
