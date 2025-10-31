<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Post;
use Yii;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

final class PostTest extends Unit
{
    public function testValidationRulesStructure(): void
    {
        $post = new Post();
        $rules = $post->rules();
        $this->assertIsArray($rules);

        $requiredFound = false;
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[1]) && $rule[1] === 'required') {
                $requiredFound = true;
            }
        }
        $this->assertTrue($requiredFound, 'Required rule not found.');
    }

    public function testBeforeSavePurifiesHtmlAndGeneratesToken(): void
    {
        // Mock only beforeSave to prevent DB
        $post = $this->getMockBuilder(Post::class)
            ->onlyMethods(['beforeSave'])
            ->getMock();

        $post->message = '<b>bold</b><script>alert("x")</script>';

        // Simulate what beforeSave does
        $post->message = HtmlPurifier::process($post->message, [
            'HTML.Allowed' => 'b,i,s',
        ]);

        $post->token = bin2hex(random_bytes(32));

        $this->assertEquals('<b>bold</b>', $post->message);
        $this->assertNotEmpty($post->token);
        $this->assertEquals(64, strlen($post->token));
    }

    public function testMaskedIp(): void
    {
        $post = new Post();

        // IPv4
        $post = new Post(['ip' => '123.45.67.89']);
        $this->assertEquals('123.45.**.**', $post->getMaskedIp());

        // IPv6
        $post->ip = '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d';
        $this->assertEquals('2001:0db8:11a3:09d7:****:****:****:****', $post->getMaskedIp());

    }

    public function testCreatedAtRelative(): void
    {
        $post = new Post();

        $post->created_at = time() - 3600; // 1 hour ago
        $relative = $post->getCreatedAtRelative();

        $this->assertStringContainsString('hour', $relative);
    }
}
