<?php

declare(strict_types=1);

function para_goster($tutar): string
{
    return number_format((float) $tutar, 2, ',', '.') . ' TL';
}
