<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="page-quiz-container">

    <div class="page-quiz-img">
        <picture>
            <source media="(max-width: 760px)" srcset="<?=SITE_TEMPLATE_PATH?>/img/quiz414.jpg" />
            <img src="<?=SITE_TEMPLATE_PATH?>/img/quiz1920.jpg" alt="Опрос" />
        </picture>          
    </div>
    
    <div>
        <? if ($arResult["isFormErrors"] == "Y") { ?>
            <?=$arResult["FORM_ERRORS_TEXT"];?>
        <? } ?>
    </div>    
    
    <div class="page-quiz-content">
        <div class="survey__intro">
            <h1>БЛАГОДАРЯ ВАМ МЫ СТАНОВИМСЯ ЛУЧШЕ!</h1>
            <p class="survey__intro-text">Опрос займет всего 2 минуты</p>
        </div>
        
        <?=$arResult["FORM_HEADER"]?>
        <? $counter = 0; ?>

        <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) { ?>
            <div class="survey-form__item">
                <?if ($arResult["arAnswers"][$FIELD_SID][0]["FIELD_TYPE"] !== "checkbox") {?>
                    <p class="survey-form__question"><?=$arQuestion["CAPTION"]?></p>
                <?}?>
                <? if ($arResult["arAnswers"][$FIELD_SID][0]["FIELD_TYPE"] == "radio") { ?>
                    <div class="survey-form__answer">
                        <div class="recommendation-rating">
                            <span class="recommendation-label recommendation-label__no">1 – точно нет</span>
                            <div class="recommendation-rating__list">
                                <?=$arQuestion["HTML_CODE"]?>
                            </div>
                            <span class="recommendation-label recommendation-label__yes">10 – точно да</span>
                        </div>
                    </div>
                <? }elseif ($arResult["arAnswers"][$FIELD_SID][0]["FIELD_TYPE"] == "checkbox") { ?>
                    <div class="form-row form-row-checkbox checkbox-point-wrap">
                        <?=$arQuestion["HTML_CODE"]?>                
                    </div>
                <?}?>
                
                <?if ($arResult["arAnswers"][$FIELD_SID][0]["FIELD_TYPE"] == "textarea") { ?>
                    <div class="survey-form__answer">
                        <textarea class="form-textarea" name="form_textarea_<?= $arResult["QUESTIONS"][$FIELD_SID]["STRUCTURE"][0]["ID"] ?>" id="comment2" rows="5" placeholder="Ваши комментарии"></textarea>
                    </div>
                <? } ?>
            </div>
        <? } ?>

        <div class="survey-form__item">
            <p class="survey-form__question">Оставьте, пожалуйста, контакты (имя и телефон / почта). Так мы сможем дать обратную связь по вашим замечаниям</p>
            <div class="survey-form__answer">
                <div class="survey-contact-row">        
                    <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) { ?>
                        <?if($arQuestion["STRUCTURE"][0]["ID"] == 363){?>
                            <input type="text" class="form-input" name="form_text_363" placeholder="Имя">
                        <? }elseif($arQuestion["STRUCTURE"][0]["ID"] == 364){ ?>
                            <input type="text" class="form-input"  name="form_text_364" placeholder="Телефон или эл. почта">
                        <? } ?>
                    <? } ?>
                </div>
            </div>
        </div>
        
        <div class="survey-form__submit-box">
            <input id="sendForm" class="button survey-form__submit-button" type="submit" value="Отправить" name="web_form_submit">
            <p class="survey-form__agreement">Нажимая нa кнопку «Отправить ответы», я соглашаюсь на обработку моих персональных данных и ознакомлен(а) с <a href="/politika-konfidencialnosti/index.php">условиями конфиденциальности</a>.</p>
        </div>        

        <?=$arResult["FORM_FOOTER"]?>
    </div>    
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('.topic_select').on("change", function(){
            var city = $(this).val();
            
            $('.city_list').hide();
            $('.city_'+city).show();
        })
    })
</script>

<? if ($_REQUEST['formresult'] == "addok") { ?>
    <script>
        $(document).ready(function() {
            $(".popup-survey-sent").addClass("_opened");
        });
    </script>
<? } ?>

<script>
    $(document).ready(function() {
        $("form").addClass("survey-form");
    });
</script>
