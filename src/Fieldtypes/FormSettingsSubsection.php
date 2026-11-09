<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Forms\Settings\Fieldtypes;

use Statamic\Fields\Fieldtype;

/**
 * A heading that separates the blocks of the form settings screen.
 *
 * Statamic 6 deprecated its Section fieldtype and removes it in Statamic 7, so
 * the add-on carries this one for its own screen rather than relying on a
 * fieldtype the host may not register. It is not offered in any blueprint's
 * fieldtype selector.
 */
class FormSettingsSubsection extends Fieldtype
{
    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: RemoveArrayItem
    /**
     * The categories this fieldtype belongs to in the Statamic fieldtype selector.
     *
     * @var array<string> Categories this fieldtype belongs to in the Statamic fieldtype selector.
     */
    protected $categories = [
        'special',
    ];

    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: FalseToTrue
    /**
     * Indicates whether the fieldtype is selectable in collection blueprints.
     *
     * @var bool Indicates whether the fieldtype is selectable in collection blueprints
     */
    protected $selectable = false;

    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: FalseToTrue
    /**
     * Indicates whether the fieldtype is selectable in form blueprints.
     *
     * @var bool Indicates whether the fieldtype is selectable in form blueprints
     */
    protected $selectableInForms = false;

    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: FalseToTrue
    /**
     * Indicates whether the fieldtype's value can be localized.
     *
     * @var bool Indicates whether the fieldtype's value can be localized
     */
    protected $localizable = false;

    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: FalseToTrue
    /**
     * Indicates whether the fieldtype's value can be validated.
     *
     * @var bool Indicates whether the fieldtype's value can be validated
     */
    protected $validatable = false;

    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: FalseToTrue
    /**
     * Indicates whether the fieldtype can have a default value.
     *
     * @var bool Indicates whether the fieldtype can have a default value
     */
    protected $defaultable = false;

    /**
     * The icon for this fieldtype.
     *
     * @return string The icon name
     */
    #[\Override]
    public function icon(): string
    {
        return 'fieldtype-section';
    }
}
