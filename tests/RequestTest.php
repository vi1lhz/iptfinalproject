<?php

namespace Tests;

use Core\Request;

class RequestTest extends TestCase
{
    private function make(string $uri, string $script): Request
    {
        return new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'SCRIPT_NAME' => $script]);
    }

    public function testPathWithBuiltInServer(): void
    {
        $r = $this->make('/items/abc?x=1', '/index.php');
        $this->assertSame('', $r->basePath());
        $this->assertSame('/items/abc', $r->path());
    }

    public function testPathInXamppSubfolderWithPrettyUrls(): void
    {
        // htdocs/lootvault + root .htaccess forwarding to /public
        $r = $this->make('/lootvault/items/abc', '/lootvault/public/index.php');
        $this->assertSame('/lootvault', $r->basePath());
        $this->assertSame('/items/abc', $r->path());
    }

    public function testPathWhenVisitingPublicFolderDirectly(): void
    {
        $r = $this->make('/lootvault/public/browse', '/lootvault/public/index.php');
        $this->assertSame('/lootvault/public', $r->basePath());
        $this->assertSame('/browse', $r->path());
    }

    public function testHomePageInSubfolder(): void
    {
        $this->assertSame('/', $this->make('/lootvault/', '/lootvault/public/index.php')->path());
        $this->assertSame('/', $this->make('/lootvault', '/lootvault/public/index.php')->path());
    }

    public function testInputIsTrimmedAndBodyWinsOverQuery(): void
    {
        $r = new Request(['q' => 'from query'], ['q' => '  from body  '], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/']);
        $this->assertSame('from body', $r->input('q'));
        $this->assertSame('fallback', $r->input('missing', 'fallback'));
    }

    public function testOnlyReturnsRequestedKeys(): void
    {
        $r = new Request(['a' => 1, 'b' => 2, 'c' => 3], [], ['REQUEST_URI' => '/']);
        $this->assertSame(['a' => 1, 'c' => 3], $r->only(['a', 'c']));
    }

    public function testFileReturnsNullWhenNotUploaded(): void
    {
        $r = new Request([], [], ['REQUEST_URI' => '/'], ['image' => ['error' => UPLOAD_ERR_NO_FILE]]);
        $this->assertNull($r->file('image'));
    }
}
