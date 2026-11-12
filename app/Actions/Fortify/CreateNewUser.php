<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Auth\RegistrationKeywordController;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * Only guests that passed the registration keyword check may register.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        abort_unless(session(RegistrationKeywordController::VERIFIED_SESSION_KEY) === true, 403);

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        session()->forget(RegistrationKeywordController::VERIFIED_SESSION_KEY);

        return $user;
    }
}
