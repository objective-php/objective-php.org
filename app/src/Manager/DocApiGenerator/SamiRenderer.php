<?php

namespace App\Manager\DocApiGenerator;

use Sami\Project;
use Sami\Renderer\Renderer;

require_once 'phar://' . __DIR__ . '/../../../../sami.phar/Sami/Renderer/Renderer.php';
require_once 'phar://' . __DIR__ . '/../../../../sami.phar/Sami/Project.php';

/**
 * Class SamiRenderer
 *
 * This class is meant to decorate the items from getIndex().
 * We dont want the properties
 *
 * @package App\Manager\DocApiGenerator
 */
class SamiRenderer extends Renderer
{
    /**
     * @param Project $project
     *
     * @return array
     */
    protected function getIndex(Project $project): array
    {
        $items = parent::getIndex($project);
        foreach ($items as $k => $elements) {
            foreach ($elements as $key => $element) {
                if ($element[0] === 'property') {
                    if (\count($items[$k]) === 1) {
                        unset($items[$k]);
                    } else {
                        unset($items[$k][$key]);
                    }
                }
            }
        }

        return $items;
    }
}
