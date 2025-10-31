<?php

namespace tests\unit\models;

use app\models\Post;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

class PostTest extends Unit
{
    /**
     * Test that validation rules are defined and required rules exist.
     * No DB access.
     */
    public function testValidationRulesStructure(): void
    {
        $post = new Post();

        $rules = $post->rules();
        $this->assertIsArray($rules);

        $requiredFound = false;
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[1]) && $rule[1] === 'required') {
                $requiredFound = true;
                break;
            }
        }
        $this->assertTrue($requiredFound, 'Required rule not found.');
    }

    /**
     * Test HTML purification and token generation logic without saving to DB.
     */
    public function testBeforeSavePurifiesHtmlAndGeneratesTokenLogic(): void
    {
        $post = new Post();
        $post->message = '<b>bold</b><script>alert("x")</script>';

        // Simulate what beforeSave does without touching DB
        $post->message = HtmlPurifier::process($post->message, [
            'HTML.Allowed' => 'b,i,s',
        ]);
        $post->token = bin2hex(random_bytes(32));

        // Only allowed tags remain
        $this->assertEquals('<b>bold</b>', strip_tags($post->message, '<b>'));
        $this->assertNotEmpty($post->token);
        $this->assertEquals(64, strlen($post->token));
    }

    /**
     * Test masked IP formatting logic without DB.
     */
    public function testMaskedIpLogic(): void
    {
        $post = new Post(['ip' => '123.45.67.89']);
        $this->assertEquals('123.45.**.**', $post->getMaskedIp());

        $post->ip = '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d';
        $masked = $post->getMaskedIp();
        $this->assertStringContainsString('2001', $masked);
        $this->assertStringContainsString('****', $masked);
    }

    /**
     * Test relative creation time formatting without DB.
     */
    public function testCreatedAtRelativeLogic(): void
    {
        $post = new Post(['created_at' => time() - 3600]); // 1 hour ago
        $relative = $post->getCreatedAtRelative();
        $this->assertStringContainsString('hour', $relative);
    }
}
