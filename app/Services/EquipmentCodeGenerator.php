<?php

namespace App\Services;

use App\Models\Equipment;

class EquipmentCodeGenerator
{
    public function next(): string
    {
        $lastCode = Equipment::query()
            ->whereNotNull('code')
            ->where('code', 'like', 'EQ-%')
            ->orderByDesc('id')
            ->value('code');

        $nextNumber = $lastCode ? ((int) preg_replace('/\D/', '', $lastCode)) + 1 : 1;

        return sprintf('EQ-%06d', $nextNumber);
    }
}
