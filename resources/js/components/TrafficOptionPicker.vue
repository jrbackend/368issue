<script setup lang="ts">
import { Check, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';

export type PickerOption = {
    value: string;
    label: string;
    prefix?: string;
};

const props = defineProps<{
    options: PickerOption[];
    searchPlaceholder: string;
    emptySelectionLabel: string;
}>();

const selected = defineModel<string[]>({ required: true });

const searchTerm = ref('');

const optionsByValue = computed(
    () => new Map(props.options.map((option) => [option.value, option])),
);

const selectedOptions = computed(() =>
    selected.value
        .map((value) => optionsByValue.value.get(value))
        .filter((option): option is PickerOption => option !== undefined),
);

const filteredOptions = computed(() => {
    const normalizedTerm = normalize(searchTerm.value.trim());

    if (normalizedTerm === '') {
        return props.options;
    }

    return props.options.filter(
        (option) =>
            normalize(option.label).includes(normalizedTerm) ||
            option.value.toLowerCase().includes(normalizedTerm),
    );
});

function normalize(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

function isSelected(value: string): boolean {
    return selected.value.includes(value);
}

function toggle(value: string): void {
    selected.value = isSelected(value)
        ? selected.value.filter((selectedValue) => selectedValue !== value)
        : [...selected.value, value];
}

function clear(): void {
    selected.value = [];
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex min-h-9 flex-wrap items-center gap-1.5">
            <span
                v-if="selectedOptions.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{ emptySelectionLabel }}
            </span>
            <template v-else>
                <button
                    v-for="option in selectedOptions"
                    :key="option.value"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full border border-primary/30 bg-primary/10 py-1 pr-1.5 pl-2.5 text-xs font-medium transition hover:bg-primary/20"
                    :aria-label="`Remover ${option.label}`"
                    @click="toggle(option.value)"
                >
                    <span v-if="option.prefix">{{ option.prefix }}</span>
                    {{ option.label }}
                    <X class="size-3 opacity-70" />
                </button>
                <button
                    type="button"
                    class="ml-1 text-xs text-muted-foreground underline-offset-4 hover:underline"
                    @click="clear"
                >
                    Limpar
                </button>
            </template>
        </div>

        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="searchTerm"
                type="search"
                class="pl-9"
                :placeholder="searchPlaceholder"
            />
        </div>

        <div
            class="grid max-h-64 gap-1 overflow-y-auto rounded-md border p-1 sm:grid-cols-2"
        >
            <button
                v-for="option in filteredOptions"
                :key="option.value"
                type="button"
                class="flex items-center gap-2 rounded px-2.5 py-1.5 text-left text-sm transition hover:bg-accent"
                :class="{ 'bg-accent font-medium': isSelected(option.value) }"
                :aria-pressed="isSelected(option.value)"
                @click="toggle(option.value)"
            >
                <span
                    class="flex size-4 shrink-0 items-center justify-center rounded-[4px] border"
                    :class="
                        isSelected(option.value)
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input'
                    "
                >
                    <Check v-if="isSelected(option.value)" class="size-3" />
                </span>
                <span v-if="option.prefix">{{ option.prefix }}</span>
                <span class="truncate">{{ option.label }}</span>
                <span class="ml-auto text-xs text-muted-foreground uppercase">
                    {{ option.value }}
                </span>
            </button>
            <p
                v-if="filteredOptions.length === 0"
                class="col-span-full px-2.5 py-4 text-center text-sm text-muted-foreground"
            >
                Nenhum resultado para "{{ searchTerm }}".
            </p>
        </div>
    </div>
</template>
