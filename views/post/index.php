<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $posts app\models\Post[] */
/* @var $model app\models\Post */
/* @var $action array */

$this->title = 'StoryValut App';
?>

<div class="site-index">
    <div class="row">
        <!-- Posts list -->
        <div class="col-md-8">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?= Html::encode($post->author) ?></h5>
                            <p class="card-text"><?= $post->message ?></p>
                            <p>
                                <small class="text-muted">
                                    <?= Yii::$app->formatter->asRelativeTime($post->created_at) ?> |
                                    <?= Html::encode($post->getMaskedIp()) ?> |
                                    <?= $post->getAuthorPostCount() ?> <?= \yii\helpers\Inflector::pluralize('post', $post->getAuthorPostCount()) ?>
                                </small>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts yet.</p>
            <?php endif; ?>
        </div>

        <!-- Post form -->
        <div class="col-md-4">
            <?php $form = ActiveForm::begin([
                    'id' => 'post-form',
                    'action' => $action,
            ]) ?>

            <?= $form->field($model, 'author')->textInput() ?>
            <?= $form->field($model, 'email')->textInput() ?>
            <?= $form->field($model, 'message')->textarea(['rows' => 5]) ?>
            <?= $form->field($model, 'captcha')->widget(\yii\captcha\Captcha::class, [
                    'captchaAction' => 'post/captcha',
                    'imageOptions' => ['alt' => 'captcha'],
            ]) ?>

            <div class="form-group">
                <?= Html::submitButton('Отправить', ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>
