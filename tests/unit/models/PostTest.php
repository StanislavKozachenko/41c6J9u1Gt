<?php

namespace tests\unit\models;

use app\models\Post;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

class PostTest extends Unit
{
    /**
     * Test validation rules structure without DB.
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
            }
        }

        $this->assertTrue($requiredFound, 'Required rule not found.');
    }

    /**
     * Test masked IP logic (IPv4 and IPv6) without DB.
     */
    public function testMaskedIpLogic(): void
    {
        $post = new Post(['ip' => '123.45.67.89']);
        $this->assertEquals('123.45.**.**', $post->getMaskedIp());

        $post->ip = '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d';
        $this->assertEquals('2001:0db8:11a3:09d7:****:****:****:****', $post->getMaskedIp());
    }

    /**
     * Test relative time formatting logic without DB.
     */
    public function testCreatedAtRelativeLogic(): void
    {
        $post = new Post(['created_at' => time() - 3600]); // 1 hour ago
        $relative = $post->getCreatedAtRelative();

        $this->assertStringContainsString('hour', $relative);
    }
}
