<?php

namespace App\Livewire\Admin\Content;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Review;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ReviewIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(except: 'pending')]
    public string $filter = 'pending';

    public ?int $rejectingId = null;

    public string $note = '';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id, AuditLogger $audit): void
    {
        $review = Review::find($id);

        if (! $review) {
            return;
        }

        $review->approve();

        $audit->moderation('review.approved', $review, 'Review approved', ['product' => $review->product?->name]);
        $this->toastSuccess('Review published.');
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->note = '';
    }

    public function reject(AuditLogger $audit): void
    {
        $this->validate(['note' => 'required|string|max:500']);

        $review = Review::find($this->rejectingId);

        if (! $review) {
            return;
        }

        $review->reject($this->note);

        $audit->moderation('review.rejected', $review, 'Review rejected', ['note' => $this->note]);

        $this->rejectingId = null;
        $this->note = '';
        $this->toastSuccess('Review rejected.');
    }

    public function delete(int $id, AuditLogger $audit): void
    {
        $review = Review::find($id);

        if (! $review) {
            return;
        }

        $product = $review->product;
        $review->delete();

        // The aggregate is denormalised, so it must be recomputed by hand.
        $product?->refreshRating();

        $audit->moderation('review.deleted', $product ?? $review, 'Review deleted', []);
        $this->toastSuccess(__('hanbell.admin.deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.content.review-index', [
            'reviews' => Review::query()
                ->with(['product:id,name', 'user:id,name,email'])
                ->when($this->filter === 'pending', fn ($q) => $q->pending())
                ->when($this->filter === 'approved', fn ($q) => $q->approved())
                ->latest()
                ->paginate(20),
            'pendingCount' => Review::pending()->count(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.reviews'))->noindex(),
        ]);
    }
}