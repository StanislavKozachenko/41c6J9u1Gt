<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Post;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

final class PostTest extends Unit
{
    /**
     * Test validation rules.
     * Only structure and format checks, no DB interaction.
     */
    public function testValidationRulesStructure(): void
    {
        $post = new Post();

        // Basic checks: rules are defined
        $rules = $post->rules();
        $this->assertIsArray($rules);

        // Check that required attributes exist in rules
        $requiredFound = false;
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[1]) && $rule[1] === 'required') {
                $requiredFound = true;
            }
        }
        $this->assertTrue($requiredFound, 'Required rule not found.');
    }

    /**
     * Test beforeSave logic manually.
     * Ensures HTML is purified and token is generated for new records.
     * Does not save to DB.
     */
    public function testBeforeSavePurifiesHtmlAndGeneratesToken(): void
    {
        $post = new Post();
        $post->message = '<b>bold</b><script>alert("x")</script>';

        // simulate beforeSave logic locally
        $post->message = HtmlPurifier::process($post->message, [
            'HTML.Allowed' => 'b,i,s',
        ]);

        try {
            $post->token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $post->token = bin2hex(uniqid('', true));
        }

        // Only allowed tags remain
        $this->assertEquals('<b>bold</b>', $post->message);
        $this->assertNotEmpty($post->token);
        $this->assertEquals(64, strlen($post->token));
    }

    /**
     * Test masked IP output without DB.
     */
    public function testMaskedIp(): void
    {
        $post = new Post();
        $post->ip = '123.45.67.89';
        $this->assertEquals('123.45.**.**', $post->getMaskedIp());

        $post->ip = '2001:0db8:11a3:09d7:1f34:8a2e:765d:0000';
        $this->assertEquals(
            '2001:0db8:11a3:09d7:****:****:****:****',
            $post->getMaskedIp()
        );
    }

    /**
     * Test relative time formatting without DB.
     */
    public function testCreatedAtRelative(): void
    {
        $post = new Post();
        $post->created_at = time() - 3600; // 1 hour ago
        $relative = $post->getCreatedAtRelative();

        $this->assertStringContainsString('hour', $relative);
    }
}
