<?php

namespace tests\unit\models;

use app\models\Post;
use Codeception\Test\Unit;
use yii\helpers\HtmlPurifier;

class PostTest extends Unit
{
    /**
     * Test validation rules structure.
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
}
