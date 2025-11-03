<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model app\models\Post */

$form = ActiveForm::begin([
        'id' => 'delete-form',
        'action' => ['post/delete', 'id' => $model->id, 'token' => $model->token],
        'method' => 'post',
]);
?>

<div class="form-group">
    <?= Html::submitButton('Подтвердить удаление', ['class' => 'btn btn-danger']) ?>
    <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
