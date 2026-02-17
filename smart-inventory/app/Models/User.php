// app/Models/User.php
public function role()
{
    return $this->belongsTo(Role::class);
}