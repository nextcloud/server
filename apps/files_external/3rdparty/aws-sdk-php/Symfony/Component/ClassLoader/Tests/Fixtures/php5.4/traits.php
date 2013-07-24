<?php
namespace {
    trait TFoo
    {
    }

    class CFoo
    {
        use TFoo;
    }
}

namespace Foo {
    trait TBar
    {
    }

    interface IBar
    {
    }

    trait TFooBar
    {
    }

    class CBar implements IBar
    {
        use TBar, TFooBar;
    }
}
