<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\Request;

/**
 * A business rule was broken, e.g. "this request is already approved".
 *
 * Different from validation: the form can be filled in perfectly and the action is still
 * not allowed because of the CURRENT state of the data. Services throw it; Laravel calls
 * render() and the user goes back to the page with the message in red.
 */
class BusinessRuleException extends DomainException
{
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->withErrors(['business' => $this->getMessage()]);
    }
}
