<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\Generator;

use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use JMS\DiExtraBundle\Metadata\ClassMetadata;

class LookupMethodClassGenerator
{
    public function generate(\ReflectionClass $class, array $lookupMethods, $className)
    {
        $code = <<<'EOF'
<?php

namespace JMS\DiExtraBundle\DependencyInjection\LookupMethodClass;

use JMS\DiExtraBundle\DependencyInjection\LookupMethodClassInterface;

/**
 * This class has been auto-generated by the JMSDiExtraBundle.
 *
 * Manual changes to it will be lost.
 *
 * You can modify this class by changing your "@LookupMethod" configuration.
 */
class %s extends \%s implements LookupMethodClassInterface
{
    private $__symfonyDependencyInjectionContainer;

    public final function __jmsDiExtra_getOriginalClassName()
    {
        return %s;
    }%s
}
EOF;

        $lookupMethod = <<<'EOF'


    %s function %s()
    {
        return %s;
    }
EOF;

        $lookupCode = '';
        foreach ($lookupMethods as $name => $value) {
            $lookupCode .= sprintf($lookupMethod,
                $class->getMethod($name)->isPublic() ? 'public' : 'protected',
                $name,
                $this->dumpValue($value)
            );
        }

        return sprintf($code, $className, $class->getName(), var_export($class->getName(), true), $lookupCode);
    }

    private function dumpValue($value)
    {
        if ($value instanceof Parameter) {
            return '$this->__symfonyDependencyInjectionContainer->getParameter('.var_export((string) $value, true).')';
        } else if ($value instanceof Reference) {
            return '$this->__symfonyDependencyInjectionContainer->get('.var_export((string) $value, true).', '.var_export($value->getInvalidBehavior(), true).')';
        } else if (is_string($value) && '%' === $value[0]) {
            return '$this->__symfonyDependencyInjectionContainer->getParameter('.var_export(substr($value, 1, -1), true).')';
        } else if (is_array($value) || is_scalar($value) || null === $value) {
            return var_export($value, true);
        }

        throw new \RuntimeException(sprintf('Invalid value for lookup method: %s', json_encode($value)));
    }
}