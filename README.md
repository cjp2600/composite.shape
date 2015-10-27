#Composite shape#

Компонент - оболочка для элементов сайта с высокой нагрузкой для CMS Bitrix.

#Установка через Composer##

В своем Bitrix проекте в файле composer.json необходимо прописать:

```json
{
  "extra": {
    "installer-paths": {
      "local/components/{$vendor}/{$name}/": [
        "type:bitrix-component"
      ]
    }
  },
  "require": {
    "cjp2600/composite.shape": ">=0.0.1"
  }
}
```
Указать путь к папке компонентов, и ссылку на репозиторий 

##Example:##

```php
$APPLICATION->IncludeComponent(
  '8il:composite.shape',
  '',
   array(
       "ID" => "UniqueShapeComponentID", # ID -shape (Required, unique parameter)
       "CACHE_TIME"    => 604800,        # Time caching (not required)
       "USE_PRELOADER" => false,         # Use preloader (true / false) (Optional - default false)
       "PRELOADER_IMG" => " ... ",       # The path of a custom preloader for (the default one that is in the images)
       "CACHE_TAG"     => array("Pro")   # The tag for the cache.
       "CALL_FUNCTION" => function() {

           echo time();   
           sleep(3);

       }
   ),                                    # The anonymous function (body -shape)
   null,
   array(
       'HIDE_ICONS' => 'Y'
   )
);
```


##Clear cache example:##

 ```php
\CBitrixComponent::includeComponentClass("8il:composite.shape");
if (class_exists('CCompositeShapeClass')) {
   \CCompositeShapeClass::clearCacheById($cacheId);
}
  
 ```