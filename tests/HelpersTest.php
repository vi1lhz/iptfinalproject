<?php

namespace Tests;

use Core\Env;
use Core\Response;

class HelpersTest extends TestCase
{
    public function testMoneyFormatsPesos(): void
    {
        $this->assertSame('₱1,999.00', money(1999));
        $this->assertSame('₱0.50', money('0.5'));
    }

    public function testEscapePreventsXss(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
    }

    public function testStrLimit(): void
    {
        $this->assertSame('Hello…', str_limit('Hello world', 5));
        $this->assertSame('Hi', str_limit('Hi', 5));
    }

    public function testEnvParsesBooleans(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($file, "# comment\nTEST_FLAG=true\nTEST_NAME=\"Loot Vault\"\n");
        Env::load($file);
        unlink($file);
        $this->assertTrue(Env::get('TEST_FLAG'));
        $this->assertSame('Loot Vault', Env::get('TEST_NAME'));
        $this->assertSame('default', Env::get('TEST_MISSING', 'default'));
    }

    public function testJsonResponse(): void
    {
        $response = Response::json(['price' => '₱499']);
        $this->assertSame('application/json', $response->headers()['Content-Type']);
        $this->assertContains('₱499', $response->content());
    }
}
