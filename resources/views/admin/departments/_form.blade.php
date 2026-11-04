@csrf

{{-- Shared by Add department and Edit department: the form on the left, a summary + Save on the right (full width).
     Every field marked * is also required by the server (App\Http\Requests\Admin\DepartmentRequest). --}}
@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>Please fix {{ $errors->count() === 1 ? 'the error' : 'the '.$errors->count().' errors' }} below.</div>
    </div>
@endif

<div class="row g-4">
    <div class="col-xl-8 d-flex flex-column gap-4">
        {{-- Identity --}}
        <div class="panel">
            <div class="panel-header justify-content-start">
                <span class="section-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <h2 class="panel-title">Department</h2>
                    <div class="small text-muted">Its name and the short code used in reports.</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                            <input type="text" id="name" name="name" value="{{ old('name', $department->name) }}" maxlength="100" required autofocus
                                   class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Engineering">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Unique inside its branch.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                            <input type="text" id="code" name="code" value="{{ old('code', $department->code) }}" maxlength="20" required
                                   class="form-control text-uppercase font-monospace @error('code') is-invalid @enderror" placeholder="e.g. ENG-1">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Short unique code: letters, numbers, dashes and underscores.</div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea id="description" name="description" rows="4" maxlength="1000"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What does this department do?">{{ old('description', $department->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Where it is --}}
        <div class="panel">
            <div class="panel-header justify-content-start">
                <span class="section-icon"><i class="bi bi-building"></i></span>
                <div>
                    <h2 class="panel-title">Location</h2>
                    <div class="small text-muted">The branch (and so the company) this department belongs to.</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                        <select id="branch_id" name="branch_id" required class="form-select select-divider @error('branch_id') is-invalid @enderror">
                            <option value="">Choose a branch</option>
                            @foreach ($branches->groupBy(fn ($branch) => $branch->company?->name ?? 'No company') as $company => $group)
                                <optgroup label="{{ $company }}">
                                    @foreach ($group as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $department->branch_id) === (string) $branch->id)>
                                            {{ $branch->name }}{{ $branch->city ? ' ('.$branch->city.')' : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Grouped by company.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary + Save (stays in view while scrolling on wide screens) --}}
    <div class="col-xl-4">
        <div class="panel summary-panel">
            <div class="panel-header">
                <h2 class="panel-title">Summary</h2>
            </div>
            <div class="panel-body">
                @if ($department->exists)
                    <dl class="summary-list mb-0">
                        <div><dt>Code</dt><dd><span class="badge text-bg-light border font-monospace">{{ $department->code ?? '—' }}</span></dd></div>
                        <div><dt>Branch</dt><dd>{{ $department->branch?->name ?? '—' }}</dd></div>
                        <div><dt>Company</dt><dd>{{ $department->branch?->company?->name ?? '—' }}</dd></div>
                        <div><dt>Employees</dt><dd>
                            <a href="{{ route('employees.index', ['filters' => ['department_id' => $department->id]]) }}">{{ $department->employees()->count() }}</a>
                        </dd></div>
                        <div><dt>Created</dt><dd>{{ $department->created_at?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt>Last updated</dt><dd title="{{ $department->updated_at?->format('d/m/Y H:i') }}">{{ $department->updated_at?->diffForHumans() ?? '—' }}</dd></div>
                    </dl>
                @else
                    <ul class="summary-steps mb-0">
                        <li><i class="bi bi-1-circle"></i> Give the department a name and a short code.</li>
                        <li><i class="bi bi-2-circle"></i> Choose the branch it belongs to.</li>
                        <li><i class="bi bi-3-circle"></i> Then add employees to it from Manage Employees.</li>
                    </ul>
                @endif
            </div>
            <div class="panel-body border-top d-grid gap-2">
                <x-button type="submit" icon="bi-check2">{{ $submitLabel }}</x-button>
                <x-button variant="secondary" href="{{ route('admin.departments.index') }}">Cancel</x-button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .section-icon { width: 2.25rem; height: 2.25rem; border-radius: .55rem; display: inline-flex; align-items: center; justify-content: center; background: var(--brand-light); color: var(--brand); font-size: 1.05rem; flex-shrink: 0; }
    .panel-header.justify-content-start { gap: .8rem; }
    @media (min-width: 1200px) { .summary-panel { position: sticky; top: 5.5rem; } }
    .summary-list > div { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed var(--line); }
    .summary-list > div:last-child { border-bottom: 0; }
    .summary-list dt { font-weight: 500; color: var(--muted); font-size: .8rem; }
    .summary-list dd { margin: 0; font-weight: 600; font-size: .82rem; text-align: right; }
    .summary-steps { list-style: none; padding: 0; display: grid; gap: .7rem; font-size: .82rem; color: var(--ink-soft); }
    .summary-steps i { color: var(--brand); margin-right: .4rem; }
</style>
@endpush
