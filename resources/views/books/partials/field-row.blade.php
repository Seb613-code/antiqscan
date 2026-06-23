<div class="rounded border bg-white p-3">
    <div class="grid gap-2 md:grid-cols-12 md:items-start">
        <label class="text-sm md:col-span-3">
            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-stone-500">Champ</span>
            <input name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="w-full rounded border p-2">
        </label>

        <label class="text-sm md:col-span-6">
            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-stone-500">Valeur</span>
            <textarea name="fields[{{ $field->id }}][value]" rows="2" class="w-full rounded border p-2">{{ $field->value }}</textarea>
        </label>

        <label class="flex items-center gap-2 text-sm md:col-span-2 md:mt-6">
            <input type="checkbox" name="fields[{{ $field->id }}][is_validated]" value="1" @checked($field->is_validated)>
            validé
        </label>

        <p class="text-xs text-stone-500 md:col-span-1 md:mt-6">{{ $field->origin }}</p>
    </div>
</div>
