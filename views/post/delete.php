<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Post */

$this->title = 'Удаление поста';
?>

<div class="post-delete">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Вы действительно хотите удалить этот пост?</p>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?= Html::encode($model->author) ?></h5>
            <p class="card-text"><?= $model->message ?></p>
            <small class="text-muted">
                <?= Yii::$app->formatter->asRelativeTime($model->created_at) ?> |
                <?= Html::encode($model->getMaskedIp()) ?>
            </small>
        </div>
    </div>

    <?= $this->render('_delete_form', ['model' => $model]) ?>
</div>
