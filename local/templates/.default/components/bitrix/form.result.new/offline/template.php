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
            <p class="survey__intro-text">Поделитесь, пожалуйста, впечатлениями о посещении магазина. <br>Опрос займет всего 2 минуты</p>
        </div>
        
        <?=$arResult["FORM_HEADER"]?>
        <? $counter = 0; ?>
        <div class="survey-form__item">
            <p class="survey-form__question">*В КАКОМ МАГАЗИНЕ ВЫ СОВЕРШИЛИ ПОКУПКУ?</p>
        
            <div class="select-container">
                <select id="uf_topic" class="topic_select custom-select">
                    <option value="1">Москва</option>
                    <option value="2">Екатеринбург</option>
                    <option value="3">Иркутск</option>
                    <option value="4">Казань</option>
                    <option value="5">Краснодар</option>
                    <option value="6">Красноярск</option>
                    <option value="7">Новосибирск</option>
                    <option value="8">Пермь</option>
                    <option value="9">Ростов-на-Дону</option>
                    <option value="10">Санкт-Петербург</option>
                    <option value="11">Сочи</option>
                    <option value="12">Хабаровск</option>
                </select>
            </div>        
        </div>
        
        <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) { ?>
            <?if($arQuestion["CAPTION"] == "В каком магазине вы совершили покупку?*"){?>
                <div class="survey-form__item">
                    <? if ($arResult["arAnswers"][$FIELD_SID][0]["FIELD_TYPE"] == "radio") { ?>
                        <div class="survey-form__answer">
                            <div class="recommendation-rating__list_">
                                <?foreach($arQuestion["STRUCTURE"] as $shop){
                                    if(strpos($shop['MESSAGE'], "Москва") !== false){$city = 1;}                                    
                                    if(strpos($shop['MESSAGE'], "Екатеринбург") !== false){$city = 2;}                                    
                                    if(strpos($shop['MESSAGE'], "Иркутск") !== false){$city = 3;}                                    
                                    if(strpos($shop['MESSAGE'], "Казань") !== false){$city = 4;}                                    
                                    if(strpos($shop['MESSAGE'], "Краснодар") !== false){$city = 5;}                                    
                                    if(strpos($shop['MESSAGE'], "Красноярск") !== false){$city = 6;}                                    
                                    if(strpos($shop['MESSAGE'], "Новосибирск") !== false){$city = 7;}                                    
                                    if(strpos($shop['MESSAGE'], "Пермь") !== false){$city = 8;}                                    
                                    if(strpos($shop['MESSAGE'], "Ростов-на-Дону") !== false){$city = 9;}                                    
                                    if(strpos($shop['MESSAGE'], "Санкт-Петербург") !== false){$city = 10;}                                    
                                    if(strpos($shop['MESSAGE'], "Сочи") !== false){$city = 11;}                                    
                                    if(strpos($shop['MESSAGE'], "Хабаровск") !== false){$city = 12;}?>
                                    
                                    <div style="<?if($city > 1){echo "display: none";}?>" class="city_list city_<?=$city?>">
                                        <input class="input-radio" id="<?=$shop['ID']?>" type="radio" name="form_radio_SIMPLE_QUESTION_296" value="<?=$shop['ID']?>">
                                        <label class="label-radio sorting-label-item" for="<?=$shop['ID']?>"><?=trim(explode(".",$shop['MESSAGE'])[1])?></label>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    <?}?> 
                </div>
            <?}else{?>
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
        <? } ?>

        <div class="survey-form__item">
            <p class="survey-form__question">Оставьте, пожалуйста, контакты (имя и телефон / почта). Так мы сможем дать обратную связь по вашим замечаниям</p>
            <div class="survey-form__answer">
                <div class="survey-contact-row">        
                    <? foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) { ?>
                        <?if($arQuestion["STRUCTURE"][0]["ID"] == 134){?>
                            <input type="text" class="form-input" name="form_text_134" placeholder="Имя">
                        <? }elseif($arQuestion["STRUCTURE"][0]["ID"] == 135){ ?>
                            <input type="text" class="form-input"  name="form_text_135" placeholder="Телефон или эл. почта">
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