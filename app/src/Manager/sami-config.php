<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$tmpDir = __DIR__ . '/../../tmp/';

$infos = json_decode(file_get_contents($tmpDir . 'infos.json'));

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir = $tmpDir . $infos->repoPath . '/src');

return new Sami($iterator, [
    'theme'                => 'objectivephp-sami',
    'versions'             => $infos->version,
    'title'                => 'API_test',
    'build_dir'            =>  __DIR__ . '/../../../public/docapi/' .$infos->compoName. '/%version%',
    'cache_dir'            => $tmpDir . '/cache/' . $infos->compoName . '/%version%',
    'template_dirs'        => [__DIR__ . '/' . 'objectivephp-sami'],
    'default_opened_level' => 1
]);
