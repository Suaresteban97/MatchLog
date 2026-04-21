<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContributionService
{
    /**
     * Submit a new community contribution (proposed change to a resource field).
     *
     * Resolves the polymorphic class from the short type string ('game', 'genre',
     * 'platform'), snapshots the current field value, and stores the record.
     *
     * @param  User   $user           The authenticated user submitting the proposal.
     * @param  string $type           Short type key: 'game' | 'genre' | 'platform'.
     * @param  int    $resourceId     ID of the target resource.
     * @param  string $field          Field name being proposed for change.
     * @param  string $proposedValue  The new value proposed by the user.
     * @return Contribution
     *
     * @throws \InvalidArgumentException If $type is not a recognised resource type.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If resource not found.
     */
    public function submit(
        User   $user,
        string $type,
        int    $resourceId,
        string $field,
        string $proposedValue
    ): Contribution {
        $modelClass = Contribution::resolveContributableClass($type);

        // Fetch the target model to snapshot the current value
        /** @var \Illuminate\Database\Eloquent\Model $resource */
        $resource = $modelClass::findOrFail($resourceId);

        // Snapshot the current value (may be null if field is empty/new)
        $currentValue = $resource->getAttribute($field);

        return Contribution::create([
            'user_id'            => $user->id,
            'contributable_type' => $modelClass,
            'contributable_id'   => $resourceId,
            'field'              => $field,
            'current_value'      => $currentValue !== null ? (string) $currentValue : null,
            'proposed_value'     => $proposedValue,
            'status'             => 'pending',
        ]);
    }

    /**
     * List contributions targeting a specific resource, optionally filtered by status.
     *
     * @param  string $type       Short type key: 'game' | 'genre' | 'platform'.
     * @param  int    $resourceId ID of the target resource.
     * @param  string $status     'pending' | 'approved' | 'rejected' | 'all'
     * @param  int    $perPage
     */
    public function listForResource(
        string $type,
        int    $resourceId,
        string $status  = 'pending',
        int    $perPage = 15
    ): LengthAwarePaginator {
        $modelClass = Contribution::resolveContributableClass($type);

        $query = Contribution::with(['user:id,name', 'reviewer:id,name'])
            ->where('contributable_type', $modelClass)
            ->where('contributable_id', $resourceId);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * List all contributions submitted by the given user.
     *
     * @param  User $user
     * @param  int  $perPage
     */
    public function listByUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Contribution::with(['reviewer:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get pending contributions for the moderation panel.
     */
    public function getPendingContributions(int $perPage = 15): LengthAwarePaginator
    {
        return Contribution::with(['user:id,name', 'contributable'])
            ->where('status', 'pending')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Resolve a pending contribution.
     * If approved, dynamically updates the target model.
     *
     * @param int $id The contribution ID
     * @param string $status 'approved' | 'rejected'
     * @param string|null $reviewerNotes
     * @param User $reviewer
     */
    public function resolve(int $id, string $status, ?string $reviewerNotes, User $reviewer): Contribution
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Status must be approved or rejected.');
        }

        $contribution = Contribution::findOrFail($id);

        if ($contribution->status !== 'pending') {
            throw new \InvalidArgumentException('This contribution has already been resolved.');
        }

        if ($status === 'approved') {
            $modelClass = $contribution->contributable_type;
            $resource = $modelClass::findOrFail($contribution->contributable_id);
            
            // Reflexively update the field
            $resource->setAttribute($contribution->field, $contribution->proposed_value);
            $resource->save();
        }

        $contribution->status = $status;
        $contribution->reviewer_id = $reviewer->id;
        $contribution->rejection_reason = $reviewerNotes;
        $contribution->reviewed_at = now();
        $contribution->save();

        return $contribution;
    }
}
