<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Generic admin index.
 *
 * Several catalogue tables (departments, categories, brands, tags, attributes,
 * advertisers, placements) are edited rarely and share the same shape: a
 * searchable, sortable, paginated list with a create/edit form and a delete.
 * Rather than duplicating that eight times, the shared behaviour lives here and
 * each subclass declares its model and its columns.
 *
 * This is deliberately an abstract base and not a config-driven mega-component:
 * a subclass that needs genuinely different behaviour overrides a method.
 */
abstract class ResourceIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 20;

    /** Whether the inline create/edit panel is open. */
    public bool $showForm = false;

    public ?int $editingId = null;

    /** Field values for the form, keyed by column. */
    public array $form = [];

    /** @var array<string,string> */
    protected array $validationRules = [];

    /** The Eloquent model class this screen manages. */
    abstract protected function model(): string;

    /** Columns shown in the table: key => label. */
    abstract protected function columns(): array;

    /** Fields in the create/edit form: key => [label, type]. */
    abstract protected function fields(): array;

    /** Which columns the search box looks at. */
    protected function searchable(): array
    {
        return ['name'];
    }

    /** Columns the sort control may use. */
    protected function sortable(): array
    {
        return ['name', 'created_at'];
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->sortable(), true)) {
            return;
        }

        // Toggle direction when the same column is clicked again.
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sort = $column;
        $this->direction = 'asc';
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $record = $this->model()::find($id);

        if (! $record) {
            return;
        }

        $this->editingId = $record->getKey();
        $this->form = collect(array_keys($this->fields()))
            ->mapWithKeys(fn (string $key) => [$key => $record->getAttribute($key)])
            ->all();

        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $rules = $this->rulesForSave();

        if ($rules !== []) {
            $this->validate($rules);
        }

        $data = $this->formData();

        if ($this->editingId) {
            $record = $this->model()::find($this->editingId);

            if (! $record) {
                return;
            }

            $record->update($data);
            $this->toastSuccess(__('hanbell.admin.saved'));
        } else {
            $this->model()::create($data);
            $this->toastSuccess(__('hanbell.admin.created'));
        }

        $this->showForm = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $record = $this->model()::find($id);

        if (! $record) {
            return;
        }

        $record->delete();
        $this->toastSuccess(__('hanbell.admin.deleted'));
    }

    public function toggleActive(int $id): void
    {
        $record = $this->model()::find($id);

        if (! $record || ! $this->hasColumn($record, 'is_active')) {
            return;
        }

        $record->forceFill(['is_active' => ! $record->is_active])->save();
        $this->toastSuccess(__('hanbell.admin.saved'));
    }

    protected function hasColumn(Model $model, string $column): bool
    {
        return array_key_exists($column, $model->getAttributes())
            || in_array($column, $model->getFillable(), true);
    }

    /** @return array<string,string> */
    protected function rulesForSave(): array
    {
        return $this->validationRules;
    }

    /** Only the declared form fields may ever be written. */
    protected function formData(): array
    {
        return collect(array_keys($this->fields()))
            ->mapWithKeys(fn (string $key) => [$key => $this->form[$key] ?? null])
            ->all();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = collect(array_keys($this->fields()))
            ->mapWithKeys(fn (string $key) => [$key => null])
            ->all();
    }

    protected function query(): Builder
    {
        $query = $this->model()::query();

        if (filled($this->search)) {
            $query->where(function (Builder $q): void {
                foreach ($this->searchable() as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'like', '%'.$this->search.'%');
                }
            });
        }

        return $query->orderBy($this->sort, $this->direction);
    }

    public function render(): View
    {
        return view('livewire.admin.catalogue.resource-table', [
            'records' => $this->query()->paginate($this->perPage),
            'heading' => $this->heading(),
            'seo' => app(Seo::class)->title($this->heading())->noindex(),
        ]);
    }

    abstract protected function heading(): string;
}
