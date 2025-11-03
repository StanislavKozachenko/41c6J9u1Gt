<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Post */

$this->title = 'Редактирование поста';
?>
<div class="post-edit">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_edit_form', [
            'model' => $model,
            'action' => ['post/edit', 'id' => $model->id, 'token' => $model->token],
    ]) ?>

</div>
