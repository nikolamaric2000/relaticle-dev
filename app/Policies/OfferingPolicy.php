<?php
declare(strict_types=1);
namespace App\Policies;
use App\Models\Offering;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;
final readonly class OfferingPolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }
    public function view(User $user, Offering $offering): bool
    {
        return $user->belongsToTeam($offering->team);
    }
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }
    public function update(User $user, Offering $offering): bool
    {
        return $user->belongsToTeam($offering->team);
    }
    public function delete(User $user, Offering $offering): bool
    {
        return $user->belongsToTeam($offering->team);
    }
    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }
    public function restore(User $user, Offering $offering): bool
    {
        return $user->belongsToTeam($offering->team);
    }
    public function restoreAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }
    public function forceDelete(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}