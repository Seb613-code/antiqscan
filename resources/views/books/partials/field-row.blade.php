<div class="rounded-[1rem] border border-stone-200 bg-stone-50 p-4">
    <div class="grid gap-3 lg:grid-cols-[minmax(0,14rem)_minmax(0,1fr)] lg:items-start">
        <label class="text-sm text-stone-700">
            <span class="mb-2 block font-medium text-stone-800">Libellé</span>
            <input name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="form-input">
        </label>

        <label class="text-sm text-stone-700">
            <span class="mb-2 block font-medium text-stone-800">Valeur</span>
            <textarea name="fields[{{ $field->id }}][value]" rows="2" class="form-textarea">{{ $field->value }}</textarea>
        </label>
    </div>
</div>
