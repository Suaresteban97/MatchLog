<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Requests\User\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    #[Test] 
    public function it_hashes_passwords_correctly()
    {
        $password = 'securepassword';
        $hashedPassword = Hash::make($password);
        
        $this->assertNotEquals($password, $hashedPassword);
        $this->assertTrue(Hash::check($password, $hashedPassword));
    }

    #[Test] 
    public function it_generates_unique_tokens()
    {
        $token1 = Str::random(60);
        $token2 = Str::random(60);
        
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(60, strlen($token1));
        $this->assertEquals(60, strlen($token2));
    }

    #[Test] 
    public function it_validates_register_request()
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'password' => '12345',
        ];
        
        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
