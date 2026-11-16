<?php

namespace Tests;

use Core\Validator;

class ValidatorTest extends TestCase
{
    public function setUp(): void
    {
        $db = $this->freshDatabase();
        $db->insert('users', ['username' => 'taken', 'email' => 'taken@mail.test', 'password' => 'x', 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function testRequired(): void
    {
        $v = Validator::make(['name' => '   '], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertSame('Name is required.', $v->errors()['name']);
    }

    public function testEmail(): void
    {
        $this->assertTrue(Validator::make(['email' => 'nope'], ['email' => 'email'])->fails());
        $this->assertFalse(Validator::make(['email' => 'ana@mail.ph'], ['email' => 'email'])->fails());
    }

    public function testMinAndMaxForStringsAndNumbers(): void
    {
        $this->assertTrue(Validator::make(['password' => 'short'], ['password' => 'min:8'])->fails());
        $this->assertTrue(Validator::make(['price' => '15'], ['price' => 'numeric|min:20'])->fails());
        $this->assertFalse(Validator::make(['price' => '20'], ['price' => 'numeric|min:20'])->fails());
        $this->assertTrue(Validator::make(['price' => '600000'], ['price' => 'numeric|max:500000'])->fails());
    }

    public function testOptionalFieldsSkipRulesWhenEmpty(): void
    {
        $this->assertFalse(Validator::make(['game_id' => ''], ['game_id' => 'integer|exists:games,id'])->fails());
    }

    public function testInRule(): void
    {
        $this->assertFalse(Validator::make(['m' => 'gcash'], ['m' => 'in:gcash,maya,card'])->fails());
        $this->assertTrue(Validator::make(['m' => 'paypal'], ['m' => 'in:gcash,maya,card'])->fails());
    }

    public function testConfirmed(): void
    {
        $this->assertTrue(Validator::make(['password' => 'secret123', 'password_confirmation' => 'other'], ['password' => 'confirmed'])->fails());
        $this->assertFalse(Validator::make(['password' => 'secret123', 'password_confirmation' => 'secret123'], ['password' => 'confirmed'])->fails());
    }

    public function testUniqueAgainstDatabase(): void
    {
        $this->assertTrue(Validator::make(['email' => 'taken@mail.test'], ['email' => 'unique:users,email'])->fails());
        $this->assertFalse(Validator::make(['email' => 'free@mail.test'], ['email' => 'unique:users,email'])->fails());
    }

    public function testExistsAgainstDatabase(): void
    {
        $this->assertTrue(Validator::make(['user' => '999'], ['user' => 'exists:users,id'])->fails());
        $this->assertFalse(Validator::make(['user' => '1'], ['user' => 'exists:users,id'])->fails());
    }

    public function testOnlyFirstErrorPerFieldIsReported(): void
    {
        $v = Validator::make(['username' => ''], ['username' => 'required|min:3|alpha_dash']);
        $this->assertCount(1, $v->errors());
        $this->assertSame('Username is required.', $v->errors()['username']);
    }

    public function testUnknownRuleThrows(): void
    {
        $this->assertThrows(\InvalidArgumentException::class, fn () => Validator::make(['a' => 'x'], ['a' => 'banana']));
    }
}
