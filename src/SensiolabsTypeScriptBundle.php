<?php

namespace Sensiolabs\TypeScriptBundle;

use Sensiolabs\TypeScriptBundle\DependencyInjection\SensiolabsTypeScriptExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SensiolabsTypeScriptBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        if (null === $this->extension || false === $this->extension) {
            $this->extension = new SensiolabsTypeScriptExtension();
        }

        return $this->extension;
    }
}
