# Yii2 WebClient with pahntomJS
This is component for Yii2. 
 
Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

add

```
"repositories": 
[
        { "type": "vcs", "url": "https://github.com/vistarsvo/webclient" }
]
```
and
```
"vistarsvo/webclient": "*"
```

to the require section of your `composer.json` file.

After in config file add
```
'components' => [
    'webclient' => [
        'class' => 'vistarsvo\webclient\WebClient'
    ],
...
```
 

