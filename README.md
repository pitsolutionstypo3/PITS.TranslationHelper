# PITS.TranslationHelper
This is a backend Neos cms plugin for using translation file management in NEOS CMS.
## Installation steps

-   First Install this NEOS CMS plugin using composer.
```
composer require pits/translationhelper
```
-   Add following in top level Configuration/Routes.yaml just after the TYPO3 Neos route.
```
-
  name: 'pitsTranslationHelper'
  uriPattern: '<pitsTranslationHelperSubroutes>'
  defaults:
    '@package': 'PITS.TranslationHelper'

  subRoutes:
    'pitsTranslationHelperSubroutes':
      package: 'PITS.TranslationHelper'
```
-   Flush all caches using the below command
```
php ./flow typo3.flow:cache:flush
```
-   Warm up caches using the below command
```
php ./flow typo3.flow:cache:warmup
```
-   Open your NEOS CMS site backend, then you can see **Translation Module** under **Management** main module.
