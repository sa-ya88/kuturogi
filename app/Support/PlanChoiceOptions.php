<?php

namespace App\Support;

class PlanChoiceOptions
{
    /**
     * @param  array<int, array<string, mixed>>|null  $options
     * @return array<int, array{prompt: string, choices: array<int, array{label: string}>}>|null
     */
    public static function normalize(?array $options): ?array
    {
        if (empty($options)) {
            return null;
        }

        $normalized = collect($options)
            ->map(function (array $item): ?array {
                $prompt = trim((string) ($item['prompt'] ?? ''));

                $choices = collect($item['choices'] ?? [])
                    ->map(fn ($choice): ?string => filled($choice['label'] ?? null) ? trim((string) $choice['label']) : null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(fn (string $label): array => ['label' => $label])
                    ->all();

                if ($prompt === '' || $choices === []) {
                    return null;
                }

                return [
                    'prompt' => $prompt,
                    'choices' => $choices,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $choiceOptions
     * @param  array<int, string|null>|null  $selectedByIndex
     * @return array<int, array{prompt: string, choice: string}>
     */
    public static function validateSelections(?array $choiceOptions, ?array $selectedByIndex): array
    {
        if (empty($choiceOptions)) {
            return [];
        }

        $result = [];

        foreach ($choiceOptions as $index => $option) {
            $selected = trim((string) ($selectedByIndex[$index] ?? ''));

            if ($selected === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "selected_choices.{$index}" => '「'.$option['prompt'].'」を選択してください。',
                ]);
            }

            $allowed = collect($option['choices'] ?? [])
                ->pluck('label')
                ->all();

            if (! in_array($selected, $allowed, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "selected_choices.{$index}" => '「'.$option['prompt'].'」の選択内容が不正です。',
                ]);
            }

            $result[] = [
                'prompt' => $option['prompt'],
                'choice' => $selected,
            ];
        }

        return $result;
    }
}
