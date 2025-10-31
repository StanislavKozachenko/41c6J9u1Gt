<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\base\Security;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\HtmlPurifier;

/**
 * Class Post
 *
 * ActiveRecord model for user messages (posts).
 *
 * @property int $id
 * @property string $author_name
 * @property string $email
 * @property string $message
 * @property string $ip
 * @property int $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 * @property string $token
 */
class Post extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%post}}';
    }

    /**
     * Automatically handle created_at and updated_at timestamps.
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => time(),
            ],
        ];
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            [['author_name', 'email', 'message'], 'required'],
            ['author_name', 'string', 'min' => 2, 'max' => 15],
            ['message', 'string', 'min' => 5, 'max' => 1000],
            ['email', 'email'],
            ['message', 'match', 'pattern' => '/^(?!\s*$).+$/', 'message' => 'Message cannot be empty or whitespace only.'],
            ['ip', 'ip'],
        ];
    }

    /**
     * Attribute labels.
     */
    public function attributeLabels(): array
    {
        return [
            'author_name' => 'Author Name',
            'email' => 'E-mail',
            'message' => 'Message',
            'ip' => 'IP Address',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Purify allowed HTML before saving.
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Allow only safe HTML tags
        $this->message = HtmlPurifier::process($this->message, [
            'HTML.Allowed' => 'b,i,s',
        ]);

        // Fill IP if missing
        if (!$this->ip) {
            $this->ip = Yii::$app->request->userIP ?? '0.0.0.0';
        }

        // Generate token for edit/delete links
        if ($this->isNewRecord) {
            try {
                $this->token = (new Security())->generateRandomString(64);
            } catch (Exception $e) {
                // Logging the error
                Yii::error('Failed to generate token: ' . $e->getMessage());

                // secure unique string
                $this->token = bin2hex(uniqid((string)mt_rand(), true));
            }
        }

        return true;
    }

    /**
     * Soft delete method.
     */
    public function softDelete(): bool
    {
        $this->deleted_at = time();

        try {
            return $this->save(false, ['deleted_at']);
        } catch (\yii\db\Exception $e) {
            // Logging the error
            Yii::error('Failed to soft delete post: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count how many posts were made by the same IP.
     */
    public function getAuthorPostCount(): int
    {
        return self::find()
            ->where(['ip' => $this->ip])
            ->andWhere(['deleted_at' => null])
            ->count();
    }

    /**
     * Get masked IP for display.
     */
    public function getMaskedIp(): string
    {
        if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $this->ip);
            return sprintf('%s.%s.**.**', $parts[0], $parts[1]);
        }

        if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', inet_ntop(inet_pton($this->ip)));
            $parts = array_map(fn($p) => str_pad($p, 4, '0', STR_PAD_LEFT), $parts);
            return implode(':', array_slice($parts, 0, 4)) . ':****:****:****:****';
        }

        return '';
    }

    /**
     * Get relative creation time (e.g. "10 minutes ago").
     */
    public function getCreatedAtRelative(): string
    {
        return Yii::$app->formatter->asRelativeTime($this->created_at);
    }
}
