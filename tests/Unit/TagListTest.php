<?php

namespace Tests\Unit;

use App\Support\TagList;
use PHPUnit\Framework\TestCase;

class TagListTest extends TestCase
{
    public function test_it_trims_deduplicates_and_drops_a_leading_hash(): void
    {
        $this->assertSame(
            ['React', 'documentatie', 'php'],
            TagList::parse(' React, documentatie, react, #php, , '),
        );
    }

    public function test_it_rejects_too_many_or_too_long_tags(): void
    {
        $this->assertSame(
            'Je kunt hoogstens 8 tags toevoegen.',
            TagList::problem('een, twee, drie, vier, vijf, zes, zeven, acht, negen'),
        );
        $this->assertSame(
            'Een tag mag hoogstens 30 tekens zijn.',
            TagList::problem(str_repeat('a', 31)),
        );
        $this->assertNull(TagList::problem('react, documentatie'));
    }
}
