<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Like;
use PHPUnit\Framework\TestCase;

class LikeTest extends TestCase
{
    public function test_it_wraps_the_term_in_wildcards(): void
    {
        $this->assertSame('%persiana%', Like::contains('persiana'));
    }

    public function test_it_escapes_wildcards_and_the_escape_character(): void
    {
        $this->assertSame('%100\\%%', Like::contains('100%'));
        $this->assertSame('%a\\_b%', Like::contains('a_b'));
        $this->assertSame('%C:\\\\tienda%', Like::contains('C:\\tienda'));
    }
}
