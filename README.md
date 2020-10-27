в проекте модульная система, каждый сайт саттелит имеет свой фронтенд модуль, но движок у всех один
- конфиги модуля подтягиваются в /frontend/web/index.php в зависимости от входящего servername
- конфиги модулей лежат в /frontend/config/%name%
- при подключении модуля первым делом стартует /frontent/modules/%name%/Module.php. В нем производятся предварительные глобальные настройки, в случае сайта ДР это поддоменная логика. Потом уже контроллеры

Насолько я понял данные по ресторанам асинхронно опрашиваются по апи gorko. Какая там примерно приходит структура можно посмотреть по урлу /test/test/. Данные пишутся в базу.
БД на локалке pmn_pmnbd. На сайте основная сущность ресторан(бд restaraunts, модель Restaraunts). У реста несколько залов rooms. Один рест может быть нескольких типов: поле `restaraunts`.`type` = через запятую несколько `restaurants_types`.`value`.  Почему value а не id, потому что value это соответствие idшнику прилетающему от api. Также например и с полем city_id, это не id из таблички subdomen, а idшник города согласно api. 

Картинки(images) рестов тоже обновляются по апи из общего гугл-каталога но для каждого сайта свои с проставлением ватермарок. мне не нужно было, не лазил

сущности фильтра:

- бд filter, модель Filter. это 4 селекта на странице листинга
alias = название параметра в querystring ?mesto=1,2
type = на сайте ДР в макетах только селекты, поэтому пока только select, другие типы есть на сайтах соседях

- бд filter_items, модель FilterItems, собственно варианты конкретного типа фильтра
filter_id = идшник filter
value = значение в querystring ?mesto=1,2
api_arr = json запроса для эластика, если что они разбираются в \common\models\elastic\FilterQueryConstructorElastic.php
если будешь добавлять свои, то логика очевидна, и можно подсмотреть если что у других сайтов разновидности этих запросов

-бд slices, модель Slices, сеошная сущность, срез
alias название среза, /kafe
сначала договорились что этих частей в урле будет до 4ёх (/kafe/check-100/15-chel/svoy-alco), но потом отменилось, остается один как и на других сайтах движка. В ПФ можешь НЕ читать наш с Женей диалог
params = json c названием фильтра filter и значение равное соотв-щему id filter_items

я добавил НЕ все возможные filter_items и slices

бд pages сеошка с плейсхолдерами для нефильтровых страниц

у каждого сайта своя эластик модель рестов и залов /frontend/modules/pmnbd/models/ElasticItems.php
cоотв-но все имеющиеся поля рестов можно подсмотреть там
я добавлял например поле restaurant_slug, для представления ресторанов в урле не idшниками а транслитерированными названиями 
обновление индекса после добавления поля /test/renewelastic/

еще модельки по эластику лежат в /сommon/models/elastic/. И модельки парсинга урлов /common/components/ParamsFromQuery.php, QueryFromSlice. Все это в контроллерах используется, по выходящим данным можно понять че делают, но это не точно 


================================
накатка картиночносеошной системы
================================


1 меняем в console конфиг на нужную бд, накатываем миграции
2 composer install
3 в конфиг модуля main.php и на фронте и на бэке добавляем строчку

$params = array_merge(
    require __DIR__ . '/../../../common/config/params.php',
    require __DIR__ . '/../../../common/config/params-local.php',
    require __DIR__ . '/../params.php',
    require __DIR__ . '/../params-local.php',
    \common\utility\SiteParamsHelper::getParamsForModule(%название папки модуля%)
);

глобально в \Yii::$app->params становятся доступны 'siteAddress'  = %название_хоста_без_поддомена%.ru:880 и 'siteProtocol'

поэтому switch($host) в index.php можно заменить на switch($config['params']['siteAddress'])

4 если нужно добавить к сущности картинки и сео
- наследуемся ей от common\models\siteobject\BaseSiteObject (по аналогии с Pages)
- создаем(или расширяем) frontend/modules/%модуль%/models файл класса MediaEnum с примерно следующим содержимым

<?php
namespace frontend\modules\pmnbd\models;

use common\models\Pages;
use common\models\siteobject\BaseMediaEnum;
use common\models\SubdomenPages;

class MediaEnum extends BaseMediaEnum
{
    const HEADER_IMAGE = 'header-image'; //alias по которому будут доступны картинки
    const ADVANTAGES = 'advantages'; //alias по которому будут доступны картинки

    const LABEL_MAP = [
        self::HEADER_IMAGE => 'Изображения шапки', //подпись инпута в админке
        self::ADVANTAGES => 'Изображения преимуществ', //подпись инпута в админке
    ];

    public static function getMediaTypes() //обязательный метод в котором для названия класса накидываем какие нужны типы картинок
    {
        return [
            SubdomenPages::class => [self::HEADER_IMAGE, self::ADVANTAGES],
            Pages::class => [self::HEADER_IMAGE, self::ADVANTAGES],
        ];
    }
}

- добавить в соответствующую вьюху админки(_form) рендер инпутов и сео по аналогии backend/views/blog-tag/_form.php
там есть уже отдельные вьюхи для отображения всей этой херовины. В инпутах у каждой картинки есть свой инпут в который можно вбить альт, и не забыть нажать дискетку. сортировка также в комплекте
- аккуратно скопировать содержимое backend/_site.js в backend/web/js/site.js. Чтобы убрать инстазагрузку файлов убираем блок из site.js под комментом //insta upload
 
- на фронте в контроллере вызываем на сущности например Pages::getFilesData(MediaEnum::HEADER_IMAGE) и получаем объекты картинок. сигнатуры и выхлоп этого метода смотреть в common\models\siteobject\BaseSiteObject, вкратце на выхлопе объекты у которых два поля src и alt. Есть метод получения только объекта самой первой картинки getFileData(MediaEnum::HEADER_IMAGE)

5 в backend/config/%name%/main.php в 'urlManager' суем 'suffix' => '/' (тут скорее всего в некоторых местах по коду придется добавлять этот суффикс к вручную вписанным урлам админки, но у меня слишком уж много где в этой системе вручную навписано урлов с этим суффиксом поэтому вот, да). и в 'rules' суем

'rules' => [
                ['pattern' => '/update', 'route' => 'update/update'],
                'media/<id:\d+>/resort/<sort:\d+>' => 'media/resort',
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'blog-blocks',
                    'except' => ['delete', 'create', 'update'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'blog-post-blocks',
                    'extraPatterns' => [
                        'POST sort' => 'sort',
                    ]
                ],
                //нащет того что ниже не уверен, вроде по дефолту также работает, хз
                '<controller>' => '<controller>/index',
                '<controller>/<id:\d+>/<action>' => '<controller>/<action>',
                '<controller>/<action>' => '<controller>/<action>',
            ],

6 накатить систему на существующие обьъекты Pages::createSiteObjects() и т.п
7 недоделано
- backend/modules/%name%/controlles/SubdomenPagesController если надо чтоб сеошка по новой системе была, то в actionCreate надо добавить 
if($model->save()) {
            return $this->redirect(['update', 'id' => $model->id]);
}
вместо
return $this->render('create', [
            'model' => $model,
        ]);
- моделька Seo костыльно конфигурируется чтобы подсасывать файлы для Pages и SubdomenPages через withMedia
пример в контроллере $seo = (new Seo($type, $page, $count))->withMedia([MediaEnum::HEADER_IMAGE, MediaEnum::ADVANTAGES]);
в twig <img src="{{seo.media["header-image"]|first.src}}" alt="{{seo.media['header-image']|first.alt}}">

===============================================
для блога
===============================================

1 в backend/blog-constructor npm install && npm run build. в backend/web/js появляется constructor.js. Он сам во вьюхе подключается через backend\assets\Constructor
2 в backend/modules/%name%/views/layouts/left.php добавляем

['label' => 'Блог', 'options' => ['class' => 'header']],
['label' => 'Посты', 'icon' => 'pencil-square-o', 'url' => ['/blog-post']],
['label' => 'Тэги', 'icon' => 'tags', 'url' => ['/blog-tag']],
['label' => 'Блоки', 'icon' => 'indent', 'url' => ['/blog-block']]

3 по функционалу конструктора https://liderpoiska.ru/upload/howto1.png и https://liderpoiska.ru/upload/howto2.png

4 по написанию блоков

- в Inputs пишем json вида
{
    "text": [{"title":"заголовок","slug":"h","heading":1}],
    "html": [{"title":"текст","slug":"t"},{"title":"ищо текст","slug":"t2"}]
    "image":[{"title":"Изображения","slug":"pic"}], 
    "settings": [
        {
            "title":"Цветовая тема",
            "slug":"theme",
            "type":"select",
            "variants":[
                { "value": "default", "label": "Обычная" },
                { "value": "white", "label": "Светлая" }
            ]
            
        },
    ]
}
где "text" это массив с объектами, описывающими ПРОСТЫЕ текстовые инпуты, которые на выходе дают чистый текст.
"html" это массив с объектами, описывающими инпуты с полноценными html редакторами, которые на выходе дают html.
"image" это массив с объектами, описывающими файловые инпуты.
в которых
"title" - название инпута
"slug" - уникальный для инпута постфикс, который будет нужен в шаблоне
"heading"(не обяз.) - уровень заголовка, это если надо будет вывести оглавление, отдельно обьясню

также свои настройки блока "settings" - массив объектов , описывающих инпут настройки блока
"type" - пока только "select" вроде бы
"variants" - "value" = че будет на выходе при выбранном селекте, "label" название опции

- в Template пишем темплейт
<div id="{{paragraph.alias}}" class="post-block post-block_color_{{setting_color}} post-block_margin_{{setting_margin}}">
  <div class="wrapper wrapper_size_{{setting_size}}">
    <h2 class="post-block__h2">{{text_h}}</h2>
    <div class="post-block__text">{{html_t}}</div>
    <div class="post-block__text">{{html_t2}}</div>
    {{# image_pic }}
                    <div class="swiper-slide">
                        <img class="" src="{{src}}" alt="{{alt}}" />
                        <div class="swiper-slide__caption txt-format_color_{{setting_theme}}">
                            {{alt}}
                        </div>
                    </div>
    {{/ image_pic }}
  </div>
</div>
-для рендера используется https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
сам рендер если интересно в BlogBlock render()
-в {{}} выводится контент из инпутов
например {{html_t2}} выводит html из инпута описанного вторым объектом(со slug t2) определнного ранее массива "html": [{"title":"текст","slug":"t"},{"title":"ищо текст","slug":"t2"}]
в конструкции
    {{# image_pic }}
        <img src="{{src}}" alt="{{alt}}"/>
    {{/ image_pic }}
доступны поля src и alt для вывода ссылок и альтов изображений из инпута, image_pic это массив
-setting_color, setting_margin, setting_size дефолтные настройки, описанные сейчас вначале в constructor.jsx. 
Поидее они вообще не нужны, но были запилены в самом начале еще до внедрения кастомных настроек
соотв-но setting_theme это значение из выбранного инпута, описанного в поле settings в json

========================================
правки по блогу 13.10.20

-добавлена возможность назначить файловый шаблон, для этого выбираем тип блока Вставка, и пишем в
поле Template путь до php или twig файла например @backend/views/_template.php.
Движок прокинет в переменные шаблона контент из инпутов, названия переменных будут такими же text_h, image_pic и т.д

-добавлена возможность создать отдельные ссылки для уменьшенных изображений.
Для этого в json инпута добавляем ключ 'src' и значение = объект описывающий конфиги

 "image":[{"title":"Изображения","slug":"pic","src":{"s":{"w":200},"m":{"w":300,'h':100,'q':75}}}]

 где 's' и 'm' это постфиксы, которые будут доступны в шаблоне в переменных src_s и src_m соотв-но. можно добавлять сколько угодно.

 где 'w' это ширина, 'h' высота и 'q' коэф шакалов. как минимум один параметр должен быть заполнен.

в итоге в шаблоне:
{{# image_pic }}
        <img src="{{src}}" alt="{{alt}}"/>
        <img src="{{src_s}}" alt="{{alt}}"/>
        <img src="{{src_m}}" alt="{{alt}}"/>
{{/ image_pic }}

========================================
правки по блогу 27.10.20

-из корня делаем
./yii migrate накатываем миграцию добавляющую поле html к blogpost (только внимательнее с тем какой используется конфиг db для консоли)
cd backend/blog-constructor
cp settings_.js settings.js
npm install
npm run build
-настройки вынесены в отедельный файл settings.js(добавлен в .gitignore). settings_.js это файл пример. 
можно назначить настройки блока общие для всех блоков(не забыть пересобирать бандл после изменения settings.js)
-в этих общих, а также в кастомных настройках блока к опции можно добавить поле default:1, эта опция будет проставлена по умолчанию в селекте
-теперь файл стилей конструктора подсасыавется в бандл
-по кнопке Применить происходит рендер блоков и запись html в новое поле у BlogPost. Если оно не пустое то оно отдается в методе getHtml()
-у инпутов типа text и html, можно добавить поле titlePreview:1
{
    "text": [{"title":"заголовок","slug":"h","heading":1, "titlePreview":1}],
    "html": [{"title":"текст","slug":"t"}]
}
тогда текст из этого инпута будет появляться в шапке блока при схлопнутом виде. Сделано для более удобной навигации. Добавить хотябы к заголовочным инпутам




