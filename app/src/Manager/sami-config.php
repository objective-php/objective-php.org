<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$tmpDir = __DIR__ . '/../../tmp/';

$infos = json_decode(file_get_contents($tmpDir . 'infos.json'));

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir = $tmpDir . $infos->repoPath . '/src');

$sami = new Sami($iterator, [
    'theme'                => 'objectivephp-sami',
    'versions'             => $infos->version,
    'title'                => ucfirst(str_replace('-', ' ', $infos->compoName)),
    'build_dir'            => __DIR__ . '/../../../public/doc/' . $infos->compoName . '/%version%/api',
    'cache_dir'            => $tmpDir . '/cache/' . $infos->compoName . '/%version%',
    'template_dirs'        => [__DIR__ . '/../../../app/layouts'],
    'default_opened_level' => 1
]);

$sami->extend('twig', function ($twig) use ($infos) {
    $asset = json_decode(file_get_contents(__DIR__ . '/../../../public/dist/manifest.json'), true);
    $twig->addGlobal('style', $asset['theme.css']);
    $twig->addGlobal('app', $asset['app.js']);
    $twig->addGlobal('componentrawname', $infos->compoName);
    $twig->addGlobal('githublinktext', 'This package on Github');

    return $twig;
});

//$sami->offsetGet('project')->update();

return $sami;

