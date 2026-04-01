<?php

namespace App\Support;

/**
 * Bảng tham chiếu size móng (preset XS–L) — dùng chung cho trang Sizing Kit và admin product.
 */
final class ReferenceNailSizeChart
{
    /**
     * @return array<int, array{preset: string, thumb: array{mm: int, num: int}, index: array{mm: int, num: int}, middle: array{mm: int, num: int}, ring: array{mm: int, num: int}, pinky: array{mm: int, num: int}}>
     */
    public static function table(): array
    {
        return [
            ['preset' => 'XS', 'thumb' => ['mm' => 14, 'num' => 3], 'index' => ['mm' => 11, 'num' => 6], 'middle' => ['mm' => 12, 'num' => 5], 'ring' => ['mm' => 10, 'num' => 7], 'pinky' => ['mm' => 8, 'num' => 9]],
            ['preset' => 'S', 'thumb' => ['mm' => 15, 'num' => 2], 'index' => ['mm' => 12, 'num' => 5], 'middle' => ['mm' => 13, 'num' => 4], 'ring' => ['mm' => 11, 'num' => 6], 'pinky' => ['mm' => 9, 'num' => 8]],
            ['preset' => 'M', 'thumb' => ['mm' => 16, 'num' => 1], 'index' => ['mm' => 12, 'num' => 5], 'middle' => ['mm' => 13, 'num' => 4], 'ring' => ['mm' => 11, 'num' => 6], 'pinky' => ['mm' => 10, 'num' => 7]],
            ['preset' => 'L', 'thumb' => ['mm' => 18, 'num' => 0], 'index' => ['mm' => 14, 'num' => 3], 'middle' => ['mm' => 15, 'num' => 2], 'ring' => ['mm' => 13, 'num' => 4], 'pinky' => ['mm' => 11, 'num' => 6]],
        ];
    }
}
