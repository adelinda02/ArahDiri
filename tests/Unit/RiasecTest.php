<?php

namespace Tests\Unit;

use App\Support\AccreditationRating;
use App\Support\Riasec;
use PHPUnit\Framework\TestCase;

class RiasecTest extends TestCase
{
    public function test_holland_code_breaks_ties_in_riasec_order(): void
    {
        $v = ['r' => .5, 'i' => .9, 'a' => .5, 's' => .5, 'e' => .1, 'c' => .1];
        $this->assertSame('IRA', Riasec::hollandCode($v));
    }

    public function test_identical_profiles_match_fully(): void
    {
        $v = ['r' => .1, 'i' => .9, 'a' => .7, 's' => .5, 'e' => .2, 'c' => .3];
        $this->assertEqualsWithDelta(1.0, Riasec::match($v, $v), 1e-9);
    }

    public function test_onet_scale_is_normalized(): void
    {
        $this->assertSame(0.0, Riasec::normalizeOnet(1.0));
        $this->assertSame(1.0, Riasec::normalizeOnet(7.0));
    }

    public function test_accreditation_order(): void
    {
        $this->assertSame(6, AccreditationRating::score('Terakreditasi Unggul'));
        $this->assertSame(5, AccreditationRating::score('Baik Sekali'));
        $this->assertSame(2, AccreditationRating::score('terakreditasi  pertama'));
        $this->assertSame(0, AccreditationRating::score('Tidak Terakreditasi'));
    }
}
