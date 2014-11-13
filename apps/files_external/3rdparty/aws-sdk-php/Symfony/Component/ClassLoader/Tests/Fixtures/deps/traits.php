<?php

trait TD
{
}

trait TZ
{
    use TD;
}

trait TC
{
    use TD;
}

trait TB
{
    use TC;
}

trait TA
{
    use TB;
}

class CTFoo
{
    use TA;
    use TZ;
}

class CTBar
{
    use TZ;
    use TA;
}
