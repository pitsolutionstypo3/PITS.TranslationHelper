# PITS.TranslationHelper
This backend plugin is used for managing translations in the translation files of NEOS CMS.
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

## Plugin Details
-   You can see list of translation files contained packages.
![Image](translationHelperScreenshoot/packageList.png)
