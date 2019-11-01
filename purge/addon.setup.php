<?php

use KevinCupp\Addons\Purge\Service;

return array(
    'author'         => 'Kevin Cupp',
    'author_url'     => 'https://kevincupp.com/',
    'name'           => 'Purge',
    'description'    => '',
    'version'        => '2.1.0',
    'namespace'      => 'KevinCupp\Addons\Purge',
    'settings_exist' => true,
    'models'         => [
        'Rule' => 'Model\Rule',
    ],
    'services' => [
        'Varnish' => function ($addon) {
            return new Service\Varnish();
        }
    ]
);

// EOF
