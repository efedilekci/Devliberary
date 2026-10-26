<?php

namespace App\Http\Responses;

use App\Enums\ApprovalStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user !== null && $user->approval_status !== ApprovalStatus::Approved) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with('status', __('Je account wacht op goedkeuring door een beheerder.'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
