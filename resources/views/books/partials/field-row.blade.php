<div class="rounded border bg-white p-3">
    <div class="grid gap-2 md:grid-cols-12 md:items-start">
        <label class="text-sm md:col-span-4">
            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-stone-500">Champ</span>
            <input name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="w-full rounded border p-2">
        </label>

        <label class="text-sm md:col-span-8">
            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-stone-500">Valeur</span>
            <textarea name="fields[{{ $field->id }}][value]" rows="2" class="w-full rounded border p-2">{{ $field->value }}</textarea>
        </label>
    </div>
</div>
