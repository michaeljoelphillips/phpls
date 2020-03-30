<?php

declare(strict_types=1);

namespace Fixtures;

use OtherFixtures\TypeResolverFixture as AliasedTypeResolverFixture;
use Bar\Bar;
use Bar\Baz;
use Bar\Bag;
use Fixtures\UnqualifiedClassName;
use stdClass;

class TypeResolverFixture extends ParentFixture
{
    private stdClass $nativelyTypedProperty;

    /** @var stdClass */
    private $docBlockTypedProperty;

    public function __construct(stdClass $nativelyTypedProperty, stdClass $propertyAssignedInConstructor)
    {
        $this->nativelyTypedProperty = $nativelyTypedProperty;
        $this->propertyAssignedInConstructor = $propertyAssignedInConstructor;
    }

    public function getTypeForLocalVariableFixture()
    {
        $localVariable = new LocalVariable();
    }

    public function getTypeForPropertyFetchOnMethodParameterFixture(stdClass $nativelyTypedParameter)
    {
        return $nativelyTypedParameter->publicProperty;
    }

    public function getTypeForPropertyFetchOnMethodCallReturnTypeFixture(): stdClass
    {
        return new stdClass();
    }

    public function getTypeForMethodCallOnMethodCallReturnTypeFixture(): TypeResolverFixture
    {
        return new TypeResolverFixture();
    }

    public function getTypeForMethodCallWithNoReturnTypeFixture()
    {
        return new TypeResolverFixture();
    }

    public function getTypeForMethodCallReturningSelfFixture(): self
    {
        return new self();
    }

    public function getTypeForMethodCallReturningParentFixture(): parent
    {
        return new parent();
    }

    /**
     * @return stdClass
     */
    public function getTypeForMethodCallWithDocBlockReturnTypeFixture()
    {
        return new stdClass();
    }

    /**
     * @param stdClass $paramWithDocBlockType
     */
    public function getTypeForArgumentWithDocBlockParamTypeFixture($paramWithDocBlockType)
    {
        return $paramWithDocBlockType->publicProperty;
    }

    public function getTypeForPropertyOnVariableFixture()
    {
        $object = new TypeResolverFixture();

        $object->nativelyTypedProperty;
    }
}

abstract class ParentFixture
{
}
