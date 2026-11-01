<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Forms\Settings\Tests\Support\Doubles;

use Statamic\Fields\Fieldtype;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\Traits\RandomizesOptions;

/**
 * A fieldtype using the RandomizesOptions trait whose preset options are the
 * letters a to j in order.
 */
final class RandomizesOptionsHarness extends Fieldtype
{
    use RandomizesOptions;

    /** @return array<array-key, mixed> */
    private function getOptionsWithPreset(): array
    {
        return range('a', 'j');
    }

    /** @return array<array-key, mixed> */
    public function options(): array
    {
        return $this->getOptions();
    }
}
