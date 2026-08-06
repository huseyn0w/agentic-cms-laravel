<?php

namespace App\Http\Requests\Auth;

use App\Http\Models\User;
use App\Rules\PasswordNotReused;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', password_policy_rule(), new PasswordNotReused($this->resolveUser()), 'confirmed'],
        ];
    }

    /**
     * The account being reset, resolved from the submitted email so the reuse
     * policy can read its history. Null (rule passes) when the email is missing
     * or unknown — the reset itself then fails on the invalid token/email.
     */
    private function resolveUser(): ?User
    {
        $email = $this->input('email');

        return is_string($email) && $email !== ''
            ? User::where('email', $email)->first()
            : null;
    }
}
