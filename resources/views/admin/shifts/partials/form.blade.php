@php
    $shiftFormKey = $shiftFormKey ?? ($shift ? 'edit-'.$shift->id : 'create');
    $crossesMidnightChecked = session()->hasOldInput()
        ? old('crosses_midnight')
        : (bool) $shift?->crosses_midnight;
@endphp

<div>
    <label class="label" for="shift-name-{{ $shiftFormKey }}">Nama shift</label>
    <input class="input" id="shift-name-{{ $shiftFormKey }}" name="name" value="{{ old('name', $shift?->name) }}" maxlength="100" required>
</div>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="label" for="shift-start-time-{{ $shiftFormKey }}">Jam masuk</label>
        <input class="input" id="shift-start-time-{{ $shiftFormKey }}" type="time" name="start_time" value="{{ old('start_time', $shift ? substr($shift->start_time, 0, 5) : '07:30') }}" required>
    </div>
    <div>
        <label class="label" for="shift-end-time-{{ $shiftFormKey }}">Jam pulang</label>
        <input class="input" id="shift-end-time-{{ $shiftFormKey }}" type="time" name="end_time" value="{{ old('end_time', $shift ? substr($shift->end_time, 0, 5) : '15:30') }}" required>
    </div>
</div>

<div class="grid gap-3 sm:grid-cols-3">
    <div>
        <label class="label" for="shift-check-in-open-{{ $shiftFormKey }}">Buka masuk</label>
        <input class="input" id="shift-check-in-open-{{ $shiftFormKey }}" type="number" name="check_in_open_minutes" value="{{ old('check_in_open_minutes', $shift?->check_in_open_minutes ?? 90) }}" min="0" max="720" required aria-describedby="shift-check-in-open-help-{{ $shiftFormKey }}">
        <p class="help" id="shift-check-in-open-help-{{ $shiftFormKey }}">Menit sebelum jam masuk.</p>
    </div>
    <div>
        <label class="label" for="shift-tolerance-{{ $shiftFormKey }}">Toleransi</label>
        <input class="input" id="shift-tolerance-{{ $shiftFormKey }}" type="number" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', $shift?->late_tolerance_minutes ?? 10) }}" min="0" max="180" required aria-describedby="shift-tolerance-help-{{ $shiftFormKey }}">
        <p class="help" id="shift-tolerance-help-{{ $shiftFormKey }}">Menit setelah jam masuk.</p>
    </div>
    <div>
        <label class="label" for="shift-check-out-open-{{ $shiftFormKey }}">Buka pulang</label>
        <input class="input" id="shift-check-out-open-{{ $shiftFormKey }}" type="number" name="check_out_open_minutes" value="{{ old('check_out_open_minutes', $shift?->check_out_open_minutes ?? 30) }}" min="0" max="720" required aria-describedby="shift-check-out-open-help-{{ $shiftFormKey }}">
        <p class="help" id="shift-check-out-open-help-{{ $shiftFormKey }}">Menit sebelum jam pulang.</p>
    </div>
</div>

<label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold" for="shift-crosses-midnight-{{ $shiftFormKey }}">
    <input id="shift-crosses-midnight-{{ $shiftFormKey }}" type="checkbox" name="crosses_midnight" value="1" @checked($crossesMidnightChecked)>
    Shift melewati tengah malam
</label>
