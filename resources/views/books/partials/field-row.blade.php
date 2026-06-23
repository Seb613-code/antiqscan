<div class="grid gap-2 rounded border p-3 md:grid-cols-12">
    <input name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="rounded border p-2 md:col-span-3">
    <textarea name="fields[{{ $field->id }}][value]" class="rounded border p-2 md:col-span-6">{{ $field->value }}</textarea>
    <label class="text-sm md:col-span-2">
        <input type="checkbox" name="fields[{{ $field->id }}][is_validated]" value="1" @checked($field->is_validated)>
        validé
    </label>
    <p class="text-xs text-stone-500 md:col-span-1">{{ $field->origin }} {{ $field->confidence }}</p>
</div>
