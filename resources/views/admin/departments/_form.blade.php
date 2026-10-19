@csrf

@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>Please fix {{ $errors->count() === 1 ? 'the error' : 'the '.$errors->count().' errors' }} below.</div>
    </div>
@endif

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Department</h2>
    </div>
    <div class="panel-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $department->name) }}" maxlength="100" required autofocus
                       class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Engineering">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" id="code" name="code" value="{{ old('code', $department->code) }}" maxlength="20" required
                       class="form-control text-uppercase font-monospace @error('code') is-invalid @enderror" placeholder="e.g. ENG-1">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Short unique code: letters, numbers, dashes and underscores.</div>
            </div>

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
            </div>

            <div class="col-12">
                <label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="description" name="description" rows="3" maxlength="1000"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="What does this department do?">{{ old('description', $department->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="panel-body border-top d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.departments.index') }}" class="btn btn-clear">Cancel</a>
        <button type="submit" class="btn btn-generate">{{ $submitLabel }}</button>
    </div>
</div>
