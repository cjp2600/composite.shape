#Composite shape#

Битрикс компонет для отложенной загрузки нагруженных елементов сайта.


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

       }
   ),                                    # The anonymous function (body -shape)
   null,
   array(
       'HIDE_ICONS' => 'Y'
   )
);
```
 