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
    'title'                => ucfirst($infos->compoName),
    'build_dir'            =>  __DIR__ . '/../../../public/doc/' .$infos->compoName. '/%version%/api',
    'cache_dir'            => $tmpDir . '/cache/' . $infos->compoName . '/%version%',
    'template_dirs'        => [__DIR__ . '/../../../sami/' . 'objectivephp-sami'],
    'default_opened_level' => 1
]);
