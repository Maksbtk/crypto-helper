<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $USER;
?>
    <aside class="profile-sidebar">
        <div class="profile-sidebar-sticky">
            <header class="profile-sidebar-header">
                <h1 class="profile-sidebar-title">Кабинет</h1>


                <form data-action="/ajax/setAvatar.php" id="update-user-info" enctype="multipart/form-data">
                    <input type="hidden" name="user-id" value="<?=$arParams['USER_ID']?>">
                    <input type="hidden" id="delete-user-photo" name="delete-photo" value="n">

                    <div <?if (!$USER->IsAuthorized()):?>style="display: none;"<?endif;?> class="profile-user-picture <?if(!$arParams['USER_AVATAR']):?>_empty<?endif;?>">
                        <?if(!$arParams['USER_AVATAR']){?>

                            <div style="cursor: pointer;" class="upload-userpic-button"></div>
                        <?}else{?>
                            <div style="cursor: pointer;">
                                <img style="cursor: pointer;" id="userPicture" src="<?=$arParams['USER_AVATAR']['src']?>" alt="<?=$arParams['USER_NAME']?>"  width="100" height="100" data-popup="popup-change-avatar">
                            </div>

                        <?}?>
                        <input style="display: none;" type="file" name="personal-photo" id="uploadProfilePic" accept=".jpg,.jpeg,.png,.webp">

                    </div>
                </form>

               <!-- <div class="profile-user-picture">
                    <label for="uploadProfilePic" style="display: none;">
                        <input type="file" name="personal-photo" id="uploadProfilePic">
                    </label>
                    <img src="demo-pics/profile.jpg" alt="Анастасия" width="100" height="100" style="display: none;">
                </div>-->

                <div class="h2 profile-user-name">Привет, <span><?=$arParams['USER_NAME']?>!</span></div>

            </header>

            <nav class="profile-user-nav">
                <?if (!empty($arResult)):?>
                <ul class="profile-user-menu">
                    <?foreach($arResult as $arItem):?>
                        <?
                        if($arParams["MAX_LEVEL"] == 1 && $arItem["DEPTH_LEVEL"] > 1) {
                            continue;
                        }
                        ?>
                        <li class="profile-user-menu-item <?if($arItem["SELECTED"]):?>active<?endif;?>">
                            <a href="<?=$arItem["LINK"]?>" class="profile-user-menu-link">
                                <?=$arItem["TEXT"]?>
                            </a>
                        </li>
                    <?endforeach?>
                </ul>
                <?endif?>
                <a <?if (!$USER->IsAuthorized()):?>style="display: none;"<?endif;?> href="/?logout=yes&<?=bitrix_sessid_get()?>" class="profile-nav-logout-link">Выйти</a>
            </nav>
        </div>
    </aside>

<!-- попап Добавить/изменить аватар -->
<div class="popup popup-vertical popup-change-avatar">
    <div class="popup__backdrop" data-close-popup></div>
    <div class="popup-body">
        <button class="button-close-popup" data-close-popup></button>
        <div class="popup-content">

            <div class="in-popup-change-avatar">
                <div class="h2 change-avatar-title">Изменение аватара</div>

                <div class="change-avater-buttons">
                    <label <?/*for="uploadProfilePic"*/?> class="button button-upload-picture white-color-font">
                        <?/*<input id="uploadProfilePic" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp">*/?>
                        <span>Выбрать фото</span>
                    </label>
                    <button class="button button-secondary button-cancel-avatar" data-close-popup>Отмена</button>
                    <button class="button button-secondary button-remove-avatar">Удалить</button>
                </div>

            </div>

        </div>
    </div>
</div>

