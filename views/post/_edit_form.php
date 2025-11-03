<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model app\models\Post */
/* @var $action array */

$form = ActiveForm::begin([
    'id' => 'post-edit-form',
    'action' => $action,
    'enableClientValidation' => true,
    'enableAjaxValidation' => false,
    'errorCssClass' => 'text-danger'
]);
?>

<!-- Информация о посте -->
<div class="mb-3">
    <p><strong>Автор:</strong> <?= Html::encode($model->author) ?></p>
    <p><strong>E-mail:</strong> <?= Html::encode($model->email) ?></p>
    <p><strong>Создано:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?> (<?= $model->getCreatedAtRelative() ?>)</p>
    <p><strong>IP:</strong> <?= Html::encode($model->getMaskedIp()) ?></p>
</div>

<!-- При редактировании доступно только поле message -->
<?= $form->field($model, 'message')->textarea(['rows' => 5]) ?>

<div class="form-group">
    <?= Html::submitButton('Обновить', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end() ?>
