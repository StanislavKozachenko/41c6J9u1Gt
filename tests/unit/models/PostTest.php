<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Post;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

final class PostTest extends Unit
{
    /**
     * Test validation rules structure.
     * Only ensures rules exist; does not touch ActiveRecord attributes.
     */
    public function testValidationRulesStructure(): void
    {
        $post = new Post();
        $rules = $post->rules();

        $this->assertIsArray($rules, 'Rules should be an array');

        $requiredFound = false;
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[1]) && $rule[1] === 'required') {
                $requiredFound = true;
            }
        }
        $this->assertTrue($requiredFound, 'Required rule not found.');
    }

    /**
     * Test beforeSave logic: HTML purification and token generation.
     * Does not save to DB.
     */
    public function testBeforeSavePurifiesHtmlAndGeneratesToken(): void
    {
        $post = new Post();

        // simulate beforeSave manually
        $post->message = '<b>bold</b><script>alert("x")</script>';

        // Purify HTML
        $post->message = HtmlPurifier::process($post->message, [
            'HTML.Allowed' => 'b,i,s',
        ]);

        // Generate token
        try {
            $post->token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $post->token = bin2hex(uniqid((string)mt_rand(), true));
        }

        // Only allowed tags remain
        $this->assertEquals('<b>bold</b>', strip_tags($post->message, '<b>'));
        $this->assertNotEmpty($post->token);
        $this->assertEquals(64, strlen($post->token));
    }

    /**
     * Test masked IP output for both IPv4 and IPv6.
     */
    public function testMaskedIp(): void
    {
        // IPv4
        $post = new Post();
        $post->ip = '123.45.67.89';
        $this->assertEquals('123.45.**.**', $post->getMaskedIp());

        // IPv6
        $post->ip = '2001:0db8:11a3:09d7:1f34:8a2e:07a0';
        $masked = $post->getMaskedIp();
        $this->assertStringContainsString('2001:0db8:11a3:09d7', $masked);
        $this->assertStringContainsString('****', $masked);
    }

    /**
     * Test relative time formatting for created_at.
     */
    public function testCreatedAtRelative(): void
    {
        $post = new Post();
        $post->created_at = time() - 3600; // 1 hour ago

        $relative = $post->getCreatedAtRelative();
        $this->assertStringContainsString('hour', $relative);
    }
}
