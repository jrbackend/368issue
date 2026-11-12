<?php

namespace App\Concerns;

trait PresentsProviderCredentials
{
    /**
     * Describe a messaging provider for the dashboard, without exposing stored secrets.
     *
     * @param  array<string, array{label: string, type: 'text'|'email'|'password', secret: bool, placeholder: string, help: string|null, rules: list<string>}>  $fields
     * @param  array<string, string>  $storedCredentials
     * @return array{key: string, label: string, configured: bool, fields: list<array{name: string, label: string, type: string, secret: bool, placeholder: string, help: string|null, value: string}>}
     */
    protected function presentProvider(string $key, string $label, array $fields, array $storedCredentials): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'configured' => $storedCredentials !== [],
            'fields' => array_map(fn (string $name, array $field) => [
                'name' => $name,
                'label' => $field['label'],
                'type' => $field['type'],
                'secret' => $field['secret'],
                'placeholder' => $field['placeholder'],
                'help' => $field['help'],
                'value' => $field['secret'] ? '' : ($storedCredentials[$name] ?? ''),
            ], array_keys($fields), $fields),
        ];
    }
}
