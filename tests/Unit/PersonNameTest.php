<?php

namespace Tests\Unit;

use App\Support\PersonName;
use Tests\TestCase;

class PersonNameTest extends TestCase
{
    public function test_split_handles_space_separated_names(): void
    {
        $this->assertSame(['ゲスト', '太郎'], PersonName::split('ゲスト 太郎'));
        $this->assertSame(['ゲスト', '太郎'], PersonName::split('ゲスト　太郎'));
    }

    public function test_split_keeps_single_token_as_last_name(): void
    {
        $this->assertSame(['山田太郎', ''], PersonName::split('山田太郎'));
        $this->assertSame(['', ''], PersonName::split(''));
    }
}
