<?php

return [
    'db' => config('database.connections.' . (empty(env('LOCALIZABLE_DB')) ? config('database.default') : env('LOCALIZABLE_DB'))),

    'table' => !empty(env('LOCALIZABLE_TABLE')) ?: 'localizable',

    'model' => Wakjoko\Localizable\Model::class,
];