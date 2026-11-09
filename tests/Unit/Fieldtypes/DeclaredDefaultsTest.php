<?php

declare(strict_types=1);

use UAMSWeb\Aether\Forms\Settings\Fieldtypes\FormConditionalLogic;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\FormEmailFieldSelect;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\FormEmailRouting;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\FormSettingsSubsection;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\LikertScale;
use UAMSWeb\Aether\Forms\Settings\Fieldtypes\Quiz;
use UAMSWeb\Aether\Forms\Settings\Mail\FormNotificationEmail;
use UAMSWeb\Aether\Forms\Settings\Rules\QuizAnswerValidation;
use UAMSWeb\Aether\Forms\Settings\Rules\SubfieldsAny;
use UAMSWeb\Aether\Forms\Settings\ServiceProvider;

/**
 * Pins the declared defaults of the fieldtypes and rules.
 *
 * A property default has no executable line, so the coverage driver never reports its
 * mutants as covered and each one carries a `@pest-mutate-ignore` in the source. These
 * cases are what keep the declared values verified: a changed default fails here.
 */
dataset('declared defaults', [
    'FormSettingsSubsection' => [FormSettingsSubsection::class, [
        'categories' => ['special'],
        'selectable' => false,
        'selectableInForms' => false,
        'localizable' => false,
        'validatable' => false,
        'defaultable' => false,
    ]],
    'FormConditionalLogic' => [FormConditionalLogic::class, [
        'categories' => ['forms', 'special'],
        'selectable' => false,
        'selectableInForms' => false,
        'localizable' => false,
        'component' => 'form_conditional_logic',
    ]],
    'FormEmailFieldSelect' => [FormEmailFieldSelect::class, [
        'categories' => ['forms', 'special'],
        'selectable' => false,
        'selectableInForms' => false,
        'localizable' => false,
        'component' => 'form_email_field_select',
    ]],
    'FormEmailRouting' => [FormEmailRouting::class, [
        'categories' => ['forms', 'special'],
        'selectable' => false,
        'selectableInForms' => false,
        'localizable' => false,
        'component' => 'form_email_routing',
    ]],
    'Quiz' => [Quiz::class, [
        'categories' => ['controls'],
        'selectable' => true,
        'selectableInForms' => true,
        'defaultable' => false,
    ]],
    'LikertScale' => [LikertScale::class, [
        'categories' => [],
        'defaultable' => false,
        'component' => 'likert_scale',
    ]],
    'QuizAnswerValidation' => [QuizAnswerValidation::class, ['implicit' => true]],
    'SubfieldsAny' => [SubfieldsAny::class, ['implicit' => true]],
]);

it('declares exactly the expected property defaults', function (string $class, array $expected): void {
    $defaults = (new ReflectionClass($class))->getDefaultProperties();

    foreach ($expected as $property => $value) {
        expect($defaults)->toHaveKey($property)
            ->and($defaults[$property])->toBe($value, $class.'::$'.$property);
    }
})->with('declared defaults');

it('declares the notification email defaults', function (): void {
    $class = new ReflectionClass(FormNotificationEmail::class);

    expect($class->getReflectionConstant('UNPARSED_CONFIG_KEYS')->getValue())->toBe(['message'])
        ->and($class->getDefaultProperties()['messageText'])->toBe('');
});

it('declares the Vite build the Control Panel assets come from', function (): void {
    expect((new ReflectionClass(ServiceProvider::class))->getDefaultProperties()['vite'])->toBe([
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ]);
});
