<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable // Note: This already extends Model through Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * We add 'role' here since we added it to the migration.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Added for WeLancer RBAC
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    // --- WE-LANCER RELATIONSHIPS ---

    /**
     * A User can create many Projects (e.g., Administrator/CEO role).
     */
    public function projectsCreated(): HasMany
    {
        return $this->hasMany(Project::class, 'creator_id');
    }

    /**
     * A User can assign many Tasks (e.g., Manager/Team Leader role).
     */
    public function tasksAssigned(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_id');
    }

    /**
     * A User can be assigned many Tasks (e.g., Team Member/Employee role).
     */
    public function tasksAssignedTo(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to_id');
    }

    // --- HELPER METHODS FOR RBAC ---
    
    /**
     * Helper to check if the user has an 'admin' (CEO/Administrator) role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}