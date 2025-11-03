<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model app\models\Post */
/* @var $action array */

$form = ActiveForm::begin([
        'id' => 'post-form',
        'action' => $action,
        'enableClientValidation' => true,
        'enableAjaxValidation' => false,
        'errorCssClass' => 'text-danger'
]) ?>

<?= $form->field($model, 'author')->textInput() ?>
<?= $form->field($model, 'email')->textInput() ?>
<?= $form->field($model, 'message')->textarea(['rows' => 5]) ?>
<?= $form->field($model, 'verificationCode')->widget(\yii\captcha\Captcha::class, [
        'captchaAction' => 'post/captcha',
        'imageOptions' => ['alt' => 'captcha', 'title' => 'Click to refresh'],
]) ?>

<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? 'Отправить' : 'Обновить', [
            'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
    ]) ?>
</div>

<?php ActiveForm::end() ?>
