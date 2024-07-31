<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title itemscope itemtype="http://schema.org/WPHeader">maksv</title>

  <link rel="preload" href="/local/templates/pwa/fonts/TT_BelleYou_Next_Regular.woff2" as="font"><!--предзагрузка основного шрифта -->

  <link rel="stylesheet" href="/local/templates/pwa/css/normalize.css">
  <link rel="stylesheet" href="/local/templates/pwa/css/fonts.css">
  <link rel="stylesheet" href="/local/templates/pwa/css/defaults.css">
</head>
<body>

  <style type=text/css>
    .belleyou-empty {
        width: 100vw;
        height: 100vh;

        background-color: #cdcdcb;
        background-image: url(/local/templates/pwa/img/404-desktop.jpg);
        background-position: center bottom;
        background-repeat: no-repeat;
        background-size: cover;
    }
    .belleyou-empty-inner {
        padding: 180px 0 0 120px;

        font-size: 18px;
        line-height: 27px;
        color: var(--font-color-secondary);
    }
    .belleyou-empty-inner svg {
        display: inline-block;
        width: 217px;
        margin-bottom: 30px;
    }
    .belleyou-empty-inner h1 {
        display: none;
    }
    .belleyou-empty-inner h2 {
        margin-top: 0;
        margin-bottom: 30px;

        font-size: 28px;
        line-height: 30px;
    }
    .belleyou-empty-inner .time {
        text-decoration: underline;
    }

    @media only screen and (max-width: 1439px) {
        .belleyou-empty-inner {
            padding: 120px 0 0 80px;
            font-size: 16px;
            line-height: 20px;
        }
        .belleyou-empty-inner svg {
            width: 146px;
            margin-bottom: 20px;
        }
        .belleyou-empty-inner h2 {
            margin-bottom: 20px;

            font-size: 20px;
            line-height: 24px;
        }    
    }
    @media only screen and (max-width: 1023px) {
        .belleyou-empty-inner {
            padding: 80px 0 0 55px;
        }
    }

    @media only screen and (max-width: 760px) {

        .belleyou-empty {
            background-color: #fff;
            background-image: url(/local/templates/pwa/img/404-mobile.jpg);
            background-size: contain;
        }
        .belleyou-empty-inner {
            max-width: 390px;
            margin: 0 auto;
            padding: 50px 15px 0;

            font-size: 16px;
            line-height: 20px;
            text-align: center;
        }    


    }
    
  </style>

<div class="belleyou-empty">
  <div class="belleyou-empty-inner">
    <svg xmlns="http://www.w3.org/2000/svg" width="217" fill="none" viewBox="0 0 217 60"><mask style="mask-type:luminance" id="a" width="217" height="60" x="0" y="0" maskUnits="userSpaceOnUse"><path fill="#fff" d="M217 0H0v60h217V0z"/></mask><g fill="#6B3D2E" mask="url(#a)"><path d="M69.657-4.551h-4.923v49.63h4.923v-49.63zM81.99-4.551h-4.924v49.63h4.923v-49.63zM211.435 18.302v16.477a6.661 6.661 0 0 1-1.863 4.632 6.39 6.39 0 0 1-4.513 1.96 6.39 6.39 0 0 1-4.565-1.931 6.66 6.66 0 0 1-1.891-4.66V18.301h-4.841v16.477c0 3.06 1.19 5.993 3.308 8.156a11.183 11.183 0 0 0 7.989 3.379 11.183 11.183 0 0 0 7.937-3.407 11.661 11.661 0 0 0 3.281-8.127V18.301h-4.842zM13.783 17.827a13.612 13.612 0 0 0-8.941 3.295V-4.615H0v49.63h4.842V42.79a13.612 13.612 0 0 0 8.941 3.296 13.65 13.65 0 0 0 5.316-1.093 13.884 13.884 0 0 0 4.501-3.086 14.214 14.214 0 0 0 3.002-4.61 14.45 14.45 0 0 0 1.046-5.431 14.319 14.319 0 0 0-4.106-9.93 13.737 13.737 0 0 0-9.759-4.11zm0 23.43a8.946 8.946 0 0 1-6.375-2.702 9.326 9.326 0 0 1 0-13.016 8.945 8.945 0 0 1 6.375-2.703 8.946 8.946 0 0 1 6.375 2.703 9.327 9.327 0 0 1 2.648 6.508 9.343 9.343 0 0 1-2.652 6.504 8.964 8.964 0 0 1-6.37 2.707zM175.708 17.89c-3.675.009-7.195 1.503-9.794 4.155a14.337 14.337 0 0 0-4.07 10 14.318 14.318 0 0 0 4.066 10.003c2.599 2.653 6.122 4.146 9.798 4.15a13.736 13.736 0 0 0 9.798-4.15 14.318 14.318 0 0 0 4.066-10.004 14.318 14.318 0 0 0-4.066-10.004 13.736 13.736 0 0 0-9.798-4.15zm0 23.365a8.945 8.945 0 0 1-6.374-2.703 9.321 9.321 0 0 1 0-13.016 8.944 8.944 0 0 1 6.374-2.703c1.185 0 2.358.238 3.453.701a9.023 9.023 0 0 1 2.927 1.997 9.252 9.252 0 0 1 1.956 2.988 9.387 9.387 0 0 1 0 7.05 9.249 9.249 0 0 1-1.956 2.988 9.022 9.022 0 0 1-2.927 1.997 8.866 8.866 0 0 1-3.453.701zM100.708 17.825a13.738 13.738 0 0 0-9.799 4.15 14.322 14.322 0 0 0-4.065 10.004 14.322 14.322 0 0 0 4.065 10.003 13.738 13.738 0 0 0 9.799 4.15 13.675 13.675 0 0 0 6.785-1.826 14.035 14.035 0 0 0 5.046-4.978l-4.713-1.648a9.082 9.082 0 0 1-3.156 2.618 8.889 8.889 0 0 1-3.962.941 8.94 8.94 0 0 1-5.599-2.005 9.268 9.268 0 0 1-3.18-5.113h22.595a30.701 30.701 0 0 0 0-3.296 14.255 14.255 0 0 0-4.418-9.258 13.692 13.692 0 0 0-9.398-3.742zm-8.586 11.352a9.222 9.222 0 0 1 3.272-4.63 8.913 8.913 0 0 1 5.314-1.78 8.98 8.98 0 0 1 5.278 1.763 9.285 9.285 0 0 1 3.293 4.565l-17.157.082zM45.939 17.825a13.739 13.739 0 0 0-9.8 4.15c-2.599 2.653-4.06 6.251-4.065 10.004.005 3.752 1.467 7.35 4.066 10.003a13.739 13.739 0 0 0 9.799 4.15 13.676 13.676 0 0 0 6.785-1.826 14.031 14.031 0 0 0 5.045-4.978l-4.713-1.648a9.076 9.076 0 0 1-3.156 2.618 8.886 8.886 0 0 1-3.961.941 8.908 8.908 0 0 1-5.595-2.003 9.236 9.236 0 0 1-3.17-5.115H59.77a26.762 26.762 0 0 0 0-3.296 14.269 14.269 0 0 0-4.427-9.26 13.708 13.708 0 0 0-9.404-3.74zm-8.57 11.303a9.221 9.221 0 0 1 3.27-4.63 8.916 8.916 0 0 1 5.316-1.78 8.914 8.914 0 0 1 5.286 1.75 9.218 9.218 0 0 1 3.284 4.578l-17.157.082zM157.058 18.302l-9.104 19.493-9.345-19.493h-5.39l12.072 25.194-9.313 19.905h5.359l21.078-45.099h-5.357z"/></g></svg>
    <h1>maksv</h1>
    <h2>становимся лучше для вас</h2>
    <p>Ведутся технические работы. Сайт будет временно недоступен.<br>
    Приносим извинения за предоставленные неудобства.</p>
    <p>Напишите нам, мы на связи! <a href="https://t.me/belleyoubot" target="_blank">https://t.me/belleyoubot</a></p>
  </div>
</div>

</body>
</html>
